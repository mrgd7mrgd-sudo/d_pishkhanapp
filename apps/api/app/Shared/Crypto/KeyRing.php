<?php

declare(strict_types=1);

namespace App\Shared\Crypto;

use InvalidArgumentException;
use RuntimeException;

final class KeyRing
{
    /** @var array<string, string> */
    private array $keys = [];

    private string $currentVersion;

    private string $pepper;

    /**
     * @param  array<string, string>  $keys  Map of version (e.g. 'kek_v1') => binary 32-byte key
     */
    public function __construct(array $keys, string $currentVersion, string $pepper)
    {
        if (empty($keys)) {
            throw new InvalidArgumentException('KeyRing requires at least one master key.');
        }

        if (! isset($keys[$currentVersion])) {
            throw new InvalidArgumentException("Current KEK version '{$currentVersion}' not found in KeyRing.");
        }

        foreach ($keys as $version => $key) {
            if (strlen($key) !== 32) {
                throw new InvalidArgumentException("KEK '{$version}' must be exactly 32 bytes (256 bits).");
            }
            $this->keys[$version] = $key;
        }

        if (strlen($pepper) < 16) {
            throw new InvalidArgumentException('Pepper must be at least 16 bytes.');
        }

        $this->currentVersion = $currentVersion;
        $this->pepper = $pepper;
    }

    public static function fromEnv(): self
    {
        /** @var string $currentVersion */
        $currentVersion = config('pishkhan.crypto.current_kek_version', 'kek_v1');

        /** @var string $pepperRaw */
        $pepperRaw = config('pishkhan.crypto.pepper', '');
        $pepper = self::decodeKey($pepperRaw);

        $keys = [];
        // Scan environment for KEK_V* keys (e.g. KEK_V1, KEK_V2)
        foreach ($_ENV as $key => $val) {
            if (is_string($val) && preg_match('/^KEK_V([0-9]+)$/', $key, $matches)) {
                $versionName = 'kek_v'.$matches[1];
                $keys[$versionName] = self::decodeKey($val);
            }
        }

        if (empty($keys)) {
            // Check fallback from config
            $kek1 = config('pishkhan.crypto.kek_v1');
            if (is_string($kek1) && $kek1 !== '') {
                $keys['kek_v1'] = self::decodeKey($kek1);
            }
        }

        return new self($keys, $currentVersion, $pepper);
    }

    private static function decodeKey(string $val): string
    {
        if (str_starts_with($val, 'base64:')) {
            $decoded = base64_decode(substr($val, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('Failed to base64 decode key.');
            }

            return $decoded;
        }

        return $val;
    }

    public function getKey(string $version): string
    {
        if (! isset($this->keys[$version])) {
            throw new RuntimeException("Unknown KEK version: '{$version}'.");
        }

        return $this->keys[$version];
    }

    public function getCurrentKey(): string
    {
        return $this->keys[$this->currentVersion];
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function getPepper(): string
    {
        return $this->pepper;
    }

    public function hasVersion(string $version): bool
    {
        return isset($this->keys[$version]);
    }
}
