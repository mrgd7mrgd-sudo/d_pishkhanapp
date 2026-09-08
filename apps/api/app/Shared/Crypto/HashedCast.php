<?php

declare(strict_types=1);

namespace App\Shared\Crypto;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
final class HashedCast implements CastsAttributes
{
    /**
     * Cast the given value from database storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * Prepare the given value for storage by computing blind index hash.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $encryptor = app(EnvelopeEncryptor::class);

        return $encryptor->hashIndex((string) $value);
    }
}
