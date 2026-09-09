<?php

declare(strict_types=1);

namespace App\Integration\Geo;

/**
 * Value object representing routing direction result (§8.4)
 */
final readonly class RouteResult
{
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
        public ?string $distanceText = null,
        public ?string $durationText = null,
        public ?string $summaryRoute = null,
        public ?string $encodedPolyline = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'distance_meters' => $this->distanceMeters,
            'duration_seconds' => $this->durationSeconds,
            'distance_text' => $this->distanceText,
            'duration_text' => $this->durationText,
            'summary_route' => $this->summaryRoute,
            'encoded_polyline' => $this->encodedPolyline,
        ];
    }
}
