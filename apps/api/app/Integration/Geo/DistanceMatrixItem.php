<?php

declare(strict_types=1);

namespace App\Integration\Geo;

/**
 * Value object representing distance matrix entry (§8.4)
 */
final readonly class DistanceMatrixItem
{
    public function __construct(
        public GeoPoint $destination,
        public int $distanceMeters,
        public int $durationSeconds,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'destination' => $this->destination->toArray(),
            'distance_meters' => $this->distanceMeters,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
