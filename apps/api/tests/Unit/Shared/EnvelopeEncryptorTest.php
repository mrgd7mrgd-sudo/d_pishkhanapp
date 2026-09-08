<?php

declare(strict_types=1);

use App\Shared\Crypto\EnvelopeEncryptor;
use App\Shared\Crypto\KeyRing;

beforeEach(function (): void {
    $this->k1 = random_bytes(32);
    $this->k2 = random_bytes(32);
    $this->pepper = random_bytes(16);

    $this->keyRing = new KeyRing([
        'kek_v1' => $this->k1,
        'kek_v2' => $this->k2,
    ], 'kek_v1', $this->pepper);

    $this->encryptor = new EnvelopeEncryptor($this->keyRing);
});

it('encrypts and decrypts sensitive plaintext successfully', function (): void {
    $plaintext = '0012345678 - کد ملی محرمانه شهروند';

    $envelope = $this->encryptor->encrypt($plaintext);

    expect($envelope)->toHaveKeys([
        'ciphertext', 'iv', 'auth_tag', 'encrypted_dek', 'dek_iv', 'dek_tag', 'kek_version',
    ])
        ->and($envelope['kek_version'])->toBe('kek_v1')
        ->and($envelope['ciphertext'])->not->toBe($plaintext);

    $decrypted = $this->encryptor->decrypt($envelope);

    expect($decrypted)->toBe($plaintext);
});

it('fails decryption when ciphertext is tampered with', function (): void {
    $envelope = $this->encryptor->encrypt('Sensitive Data');

    // Tamper with ciphertext
    $rawCiphertext = base64_decode($envelope['ciphertext']);
    $rawCiphertext[0] = chr(ord($rawCiphertext[0]) ^ 0xFF);
    $envelope['ciphertext'] = base64_encode($rawCiphertext);

    expect(fn () => $this->encryptor->decrypt($envelope))
        ->toThrow(RuntimeException::class);
});

it('fails decryption when auth tag is tampered with', function (): void {
    $envelope = $this->encryptor->encrypt('Sensitive Data');

    // Tamper with auth tag
    $rawTag = base64_decode($envelope['auth_tag']);
    $rawTag[0] = chr(ord($rawTag[0]) ^ 0xFF);
    $envelope['auth_tag'] = base64_encode($rawTag);

    expect(fn () => $this->encryptor->decrypt($envelope))
        ->toThrow(RuntimeException::class);
});

it('rotates KEK on envelope without re-encrypting ciphertext', function (): void {
    $plaintext = 'اسناد محرمانه پرونده پیشخوان';
    $envelope = $this->encryptor->encrypt($plaintext);

    $originalCiphertext = $envelope['ciphertext'];
    $originalIv = $envelope['iv'];
    $originalTag = $envelope['auth_tag'];

    // Rotate from kek_v1 to kek_v2
    $rotatedEnvelope = $this->encryptor->rotateKek($envelope, 'kek_v2');

    expect($rotatedEnvelope['kek_version'])->toBe('kek_v2')
        ->and($rotatedEnvelope['ciphertext'])->toBe($originalCiphertext)
        ->and($rotatedEnvelope['iv'])->toBe($originalIv)
        ->and($rotatedEnvelope['auth_tag'])->toBe($originalTag)
        ->and($rotatedEnvelope['encrypted_dek'])->not->toBe($envelope['encrypted_dek']);

    // Verify decryption using rotated envelope
    $decrypted = $this->encryptor->decrypt($rotatedEnvelope);
    expect($decrypted)->toBe($plaintext);
});

it('computes deterministic blind index hash using pepper', function (): void {
    $val = '0012345678';

    $h1 = $this->encryptor->hashIndex($val);
    $h2 = $this->encryptor->hashIndex($val);
    $h3 = $this->encryptor->hashIndex('0012345679');

    expect($h1)->toBe($h2)
        ->and($h1)->not->toBe($h3)
        ->and(strlen($h1))->toBe(64); // SHA-256 hex
});
