<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class TrackingResult
{
    /**
     * @param  list<array<string, mixed>>  $history
     */
    public function __construct(
        public readonly string $barcode,
        public readonly string $status,
        public readonly ?string $lastLocation = null,
        public readonly ?string $lastEventTime = null,
        public readonly array $history = []
    ) {}
}
