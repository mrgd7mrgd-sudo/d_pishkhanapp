<?php

declare(strict_types=1);

namespace App\Shared\Crypto;

use RuntimeException;

final class EnvelopeEncryptor
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12; // 96 bits for GCM

    private const TAG_LENGTH = 16; // 128 bits auth tag

    public function __construct(private readonly KeyRing $keyRing) {}

    /**
     * Encrypts plaintext using a freshly generated DEK.
     * DEK is encrypted with current KEK from KeyRing.
     *
     * @return array{
     *     ciphertext: string,
     *     iv: string,
     *     auth_tag: string,
     *     encrypted_dek: string,
     *     dek_iv: string,
     *     dek_tag: string,
     *     kek_version: string
     * }
     */
    public function encrypt(string $plaintext): array
    {
        // 1. Generate unique 32-byte DEK (AES-256)
        $dek = random_bytes(32);

        // 2. Encrypt plaintext with DEK using AES-256-GCM
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
            throw new RuntimeException('Encryption of plaintext failed: '.openssl_error_string());
        }

        // 3. Encrypt DEK with current KEK using AES-256-GCM
        $currentKekVersion = $this->keyRing->getCurrentVersion();
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

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'auth_tag' => base64_encode($tag),
            'encrypted_dek' => base64_encode($encryptedDek),
            'dek_iv' => base64_encode($dekIv),
            'dek_tag' => base64_encode($dekTag),
            'kek_version' => $currentKekVersion,
        ];
    }

    /**
     * Decrypts envelope using the specified KEK version.
     *
     * @param array{
     *     ciphertext: string,
     *     iv: string,
     *     auth_tag: string,
     *     encrypted_dek: string,
     *     dek_iv: string,
     *     dek_tag: string,
     *     kek_version: string
     * } $envelope
     */
    public function decrypt(array $envelope): string
    {
        $kek = $this->keyRing->getKey($envelope['kek_version']);

        // 1. Decrypt DEK with KEK
        $encryptedDek = base64_decode($envelope['encrypted_dek'], true);
        $dekIv = base64_decode($envelope['dek_iv'], true);
        $dekTag = base64_decode($envelope['dek_tag'], true);

        if ($encryptedDek === false || $dekIv === false || $dekTag === false) {
            throw new RuntimeException('Corrupted base64 envelope parameters for DEK.');
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
            throw new RuntimeException('Decryption of DEK failed or authentication tag mismatch.');
        }

        // 2. Decrypt ciphertext with DEK
        $ciphertext = base64_decode($envelope['ciphertext'], true);
        $iv = base64_decode($envelope['iv'], true);
        $tag = base64_decode($envelope['auth_tag'], true);

        if ($ciphertext === false || $iv === false || $tag === false) {
            throw new RuntimeException('Corrupted base64 envelope parameters for ciphertext.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption of ciphertext failed or authentication tag mismatch.');
        }

        return $plaintext;
    }

    /**
     * Rotates KEK on envelope without re-encrypting the payload ciphertext.
     *
     * @param array{
     *     ciphertext: string,
     *     iv: string,
     *     auth_tag: string,
     *     encrypted_dek: string,
     *     dek_iv: string,
     *     dek_tag: string,
     *     kek_version: string
     * } $envelope
     * @return array{
     *     ciphertext: string,
     *     iv: string,
     *     auth_tag: string,
     *     encrypted_dek: string,
     *     dek_iv: string,
     *     dek_tag: string,
     *     kek_version: string
     * }
     */
    public function rotateKek(array $envelope, string $newKekVersion): array
    {
        $oldKek = $this->keyRing->getKey($envelope['kek_version']);
        $newKek = $this->keyRing->getKey($newKekVersion);

        // Decrypt DEK using old KEK
        $encryptedDek = base64_decode($envelope['encrypted_dek'], true);
        $dekIv = base64_decode($envelope['dek_iv'], true);
        $dekTag = base64_decode($envelope['dek_tag'], true);

        if ($encryptedDek === false || $dekIv === false || $dekTag === false) {
            throw new RuntimeException('Corrupted base64 envelope parameters.');
        }

        $dek = openssl_decrypt(
            $encryptedDek,
            self::CIPHER,
            $oldKek,
            OPENSSL_RAW_DATA,
            $dekIv,
            $dekTag
        );

        if ($dek === false) {
            throw new RuntimeException('Failed to decrypt DEK during KEK rotation.');
        }

        // Re-encrypt DEK with new KEK
        $newDekIv = random_bytes(self::IV_LENGTH);
        $newDekTag = '';
        $newEncryptedDek = openssl_encrypt(
            $dek,
            self::CIPHER,
            $newKek,
            OPENSSL_RAW_DATA,
            $newDekIv,
            $newDekTag,
            '',
            self::TAG_LENGTH
        );

        if ($newEncryptedDek === false) {
            throw new RuntimeException('Failed to encrypt DEK with new KEK.');
        }

        $newEnvelope = $envelope;
        $newEnvelope['encrypted_dek'] = base64_encode($newEncryptedDek);
        $newEnvelope['dek_iv'] = base64_encode($newDekIv);
        $newEnvelope['dek_tag'] = base64_encode($newDekTag);
        $newEnvelope['kek_version'] = $newKekVersion;

        return $newEnvelope;
    }

    /**
     * Compute blind index hash for search (SHA-256 + Pepper)
     */
    public function hashIndex(string $value): string
    {
        return hash('sha256', $value.$this->keyRing->getPepper());
    }
}
