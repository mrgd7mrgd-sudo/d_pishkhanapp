<?php

declare(strict_types=1);

namespace App\Integration\Geo;

/**
 * Value object representing geocoded or reverse-geocoded address result (§8.4)
 */
final readonly class AddressResult
{
    public function __construct(
        public string $formattedAddress,
        public ?string $routeName = null,
        public ?string $neighbourhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $place = null,
        public ?string $district = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $inTrafficZone = false,
        public bool $inOddEvenZone = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'formatted_address' => $this->formattedAddress,
            'route_name' => $this->routeName,
            'neighbourhood' => $this->neighbourhood,
            'city' => $this->city,
            'state' => $this->state,
            'place' => $this->place,
            'district' => $this->district,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'in_traffic_zone' => $this->inTrafficZone,
            'in_odd_even_zone' => $this->inOddEvenZone,
        ];
    }
}
