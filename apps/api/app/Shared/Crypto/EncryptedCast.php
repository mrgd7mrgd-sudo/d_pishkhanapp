<?php

declare(strict_types=1);

namespace App\Shared\Crypto;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonException;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
final class EncryptedCast implements CastsAttributes
{
    /**
     * Cast the given value from database storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            /** @var array{ciphertext: string, iv: string, auth_tag: string, encrypted_dek: string, dek_iv: string, dek_tag: string, kek_version: string} $envelope */
            $envelope = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
            $encryptor = app(EnvelopeEncryptor::class);

            return $encryptor->decrypt($envelope);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $encryptor = app(EnvelopeEncryptor::class);
        $envelope = $encryptor->encrypt((string) $value);

        return json_encode($envelope, JSON_THROW_ON_ERROR);
    }
}
