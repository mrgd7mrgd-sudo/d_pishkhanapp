<?php

declare(strict_types=1);

use App\Shared\Crypto\KeyRing;

it('creates KeyRing with valid keys and returns current key', function (): void {
    $k1 = random_bytes(32);
    $k2 = random_bytes(32);
    $pepper = random_bytes(16);

    $ring = new KeyRing([
        'kek_v1' => $k1,
        'kek_v2' => $k2,
    ], 'kek_v2', $pepper);

    expect($ring->getCurrentVersion())->toBe('kek_v2')
        ->and($ring->getCurrentKey())->toBe($k2)
        ->and($ring->getKey('kek_v1'))->toBe($k1)
        ->and($ring->hasVersion('kek_v1'))->toBeTrue()
        ->and($ring->hasVersion('kek_v3'))->toBeFalse()
        ->and($ring->getPepper())->toBe($pepper);
});

it('throws exception if key map is empty', function (): void {
    expect(fn () => new KeyRing([], 'kek_v1', random_bytes(16)))
        ->toThrow(InvalidArgumentException::class, 'KeyRing requires at least one master key.');
});

it('throws exception if current version is not in keys', function (): void {
    expect(fn () => new KeyRing(['kek_v1' => random_bytes(32)], 'kek_v2', random_bytes(16)))
        ->toThrow(InvalidArgumentException::class, "Current KEK version 'kek_v2' not found in KeyRing.");
});

it('throws exception if key length is not exactly 32 bytes', function (): void {
    expect(fn () => new KeyRing(['kek_v1' => random_bytes(16)], 'kek_v1', random_bytes(16)))
        ->toThrow(InvalidArgumentException::class, "KEK 'kek_v1' must be exactly 32 bytes (256 bits).");
});

it('throws exception if pepper is less than 16 bytes', function (): void {
    expect(fn () => new KeyRing(['kek_v1' => random_bytes(32)], 'kek_v1', 'short'))
        ->toThrow(InvalidArgumentException::class, 'Pepper must be at least 16 bytes.');
});

it('throws exception when requesting unknown key version', function (): void {
    $ring = new KeyRing(['kek_v1' => random_bytes(32)], 'kek_v1', random_bytes(16));

    expect(fn () => $ring->getKey('non_existent'))
        ->toThrow(RuntimeException::class, "Unknown KEK version: 'non_existent'.");
});

it('initializes KeyRing from environment variables', function (): void {
    $ring = KeyRing::fromEnv();

    expect($ring->getCurrentVersion())->toBe('kek_v1')
        ->and($ring->hasVersion('kek_v1'))->toBeTrue()
        ->and(strlen($ring->getCurrentKey()))->toBe(32)
        ->and(strlen($ring->getPepper()))->toBeGreaterThanOrEqual(16);
});
