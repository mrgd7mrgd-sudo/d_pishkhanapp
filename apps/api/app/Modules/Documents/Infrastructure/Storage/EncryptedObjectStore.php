<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Storage;

use App\Shared\Crypto\KeyRing;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * EncryptedObjectStore (Architecture §6.8, §7.4).
 *
 * Implements envelope encryption for MinIO object storage:
 * - Unique AES-256 DEK generated per document.
 * - Plaintext encrypted with DEK using AES-256-GCM (12-byte IV + 16-byte tag).
 * - MinIO stores ONLY the raw ciphertext payload ($iv . $tag . $ciphertext).
 * - DEK is encrypted with versioned KEK from KeyRing and stored in database.
 * - Direct download from MinIO yields unreadable ciphertext.
 */
final class EncryptedObjectStore
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12;

    private const TAG_LENGTH = 16;

    private readonly Filesystem $disk;

    public function __construct(
        private readonly KeyRing $keyRing,
        ?Filesystem $disk = null
    ) {
        $this->disk = $disk ?? Storage::disk('documents');
    }

    /**
     * Store plaintext content encrypted with an envelope DEK into object storage.
     *
     * @return array{
     *     storage_key: string,
     *     encrypted_data_key: string,
     *     content_sha256: string,
     *     size_bytes: int,
     *     encrypted_size_bytes: int
     * }
     */
    public function store(string $storageKey, string $plaintext): array
    {
        $dek = random_bytes(32);
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption of document plaintext failed: '.openssl_error_string());
        }

        $storedPayload = $iv.$tag.$ciphertext;
        $this->disk->put($storageKey, $storedPayload);

        $encryptedDekEnvelope = $this->encryptDek($dek);

        return [
            'storage_key' => $storageKey,
            'encrypted_data_key' => $encryptedDekEnvelope,
            'content_sha256' => hash('sha256', $plaintext),
            'size_bytes' => strlen($plaintext),
            'encrypted_size_bytes' => strlen($storedPayload),
        ];
    }

    /**
     * Retrieve and decrypt an encrypted document from object storage.
     */
    public function retrieve(string $storageKey, string $encryptedDataKey): string
    {
        $payload = $this->disk->get($storageKey);
        if ($payload === null || strlen($payload) < (self::IV_LENGTH + self::TAG_LENGTH)) {
            throw new RuntimeException("Invalid or missing encrypted object at storage key [{$storageKey}].");
        }

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

        $dek = $this->decryptDek($encryptedDataKey);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption of document failed or authentication tag mismatch.');
        }

        return $plaintext;
    }

    /**
     * Get raw stored content directly from storage without decrypting.
     * Direct download yields unreadable binary ciphertext.
     */
    public function getRaw(string $storageKey): string
    {
        $raw = $this->disk->get($storageKey);
        if ($raw === null) {
            throw new RuntimeException("Object not found at storage key [{$storageKey}].");
        }

        return $raw;
    }

    /**
     * Delete an object from storage.
     */
    public function delete(string $storageKey): bool
    {
        return $this->disk->delete($storageKey);
    }

    /**
     * Check if object exists in storage.
     */
    public function exists(string $storageKey): bool
    {
        return $this->disk->exists($storageKey);
    }

    /**
     * Generate presigned PUT URL for direct client upload (5 min expiry).
     */
    public function generatePresignedUploadUrl(
        string $storageKey,
        CarbonInterface $expiresAt,
        string $contentType = 'application/octet-stream'
    ): string {
        $adapter = $this->disk;

        if (method_exists($adapter, 'temporaryUploadUrl')) {
            try {
                /** @var string $url */
                $url = $adapter->temporaryUploadUrl($storageKey, $expiresAt, [
                    'ContentType' => $contentType,
                ]);

                return $url;
            } catch (\Throwable) {
                // Fallback below if adapter doesn't support S3 presigned PUT directly
            }
        }

        return URL::temporarySignedRoute(
            'documents.upload.direct',
            $expiresAt,
            ['key' => $storageKey]
        );
    }

    /**
     * Build standard storage key for case document according to Architecture §6.8.
     * Layout: case-documents/{province}/{yyyy}/{mm}/{caseId}/{documentId}/v{n}.enc
     */
    public function buildCaseDocumentKey(
        string $province,
        int|string $year,
        int|string $month,
        string $caseId,
        string $documentId,
        int $version
    ): string {
        $cleanProvince = strtoupper(trim($province));
        $cleanMonth = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        return "case-documents/{$cleanProvince}/{$year}/{$cleanMonth}/{$caseId}/{$documentId}/v{$version}.enc";
    }

    /**
     * Build standard storage key for vault document according to Architecture §6.8.
     * Layout: vault/{province}/{citizenIdHash8}/{documentId}/v{n}.enc
     */
    public function buildVaultDocumentKey(
        string $province,
        string $citizenIdHash8,
        string $documentId,
        int $version
    ): string {
        $cleanProvince = strtoupper(trim($province));
        $hash8 = substr($citizenIdHash8, 0, 8);

        return "vault/{$cleanProvince}/{$hash8}/{$documentId}/v{$version}.enc";
    }

    /**
     * Encrypt DEK with KEK using AES-256-GCM.
     */
    private function encryptDek(string $dek): string
    {
        $kekVersion = $this->keyRing->getCurrentVersion();
        $kek = $this->keyRing->getCurrentKey();
        $dekIv = random_bytes(self::IV_LENGTH);
        $dekTag = '';

        $encryptedDek = openssl_encrypt(
            $dek,
            self::CIPHER,
            $kek,
            OPENSSL_RAW_DATA,
            $dekIv,
            $dekTag,
            '',
            self::TAG_LENGTH
        );

        if ($encryptedDek === false) {
            throw new RuntimeException('Encryption of DEK failed: '.openssl_error_string());
        }

        $json = json_encode([
            'v' => $kekVersion,
            'dek' => base64_encode($encryptedDek),
            'iv' => base64_encode($dekIv),
            'tag' => base64_encode($dekTag),
        ], JSON_THROW_ON_ERROR);

        return base64_encode($json);
    }

    /**
     * Decrypt DEK with versioned KEK.
     */
    private function decryptDek(string $encryptedDataKey): string
    {
        $decodedJson = base64_decode($encryptedDataKey, true);
        if ($decodedJson === false) {
            throw new RuntimeException('Corrupted encrypted data key base64.');
        }

        /** @var array{v: string, dek: string, iv: string, tag: string} $envelope */
        $envelope = json_decode($decodedJson, true, 512, JSON_THROW_ON_ERROR);

        $kek = $this->keyRing->getKey($envelope['v']);
        $encryptedDek = base64_decode($envelope['dek'], true);
        $dekIv = base64_decode($envelope['iv'], true);
        $dekTag = base64_decode($envelope['tag'], true);

        if ($encryptedDek === false || $dekIv === false || $dekTag === false) {
            throw new RuntimeException('Corrupted DEK envelope parameters.');
        }

        $dek = openssl_decrypt(
            $encryptedDek,
            self::CIPHER,
            $kek,
            OPENSSL_RAW_DATA,
            $dekIv,
            $dekTag
        );

        if ($dek === false) {
            throw new RuntimeException('Decryption of DEK failed.');
        }

        return $dek;
    }
}
