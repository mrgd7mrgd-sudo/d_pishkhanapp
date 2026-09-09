<?php

declare(strict_types=1);

namespace App\Integration\Geo;

use App\Integration\Geo\Enums\MapTheme;
use Illuminate\Support\Collection;

/**
 * GeoProvider Port (§8.0, §8.4)
 */
interface GeoProvider
{
    /**
     * Reverse geocoding from coordinates to human-readable address (§8.4).
     */
    public function reverseGeocode(float $lat, float $lng): AddressResult;

    /**
     * Search address or geocode query (§8.4).
     *
     * @return Collection<int, AddressResult>
     */
    public function searchAddress(string $query, ?float $lat = null, ?float $lng = null): Collection;

    /**
     * Geocode an address to geographic points/results (§8.4).
     *
     * @return Collection<int, AddressResult>
     */
    public function geocode(string $address, ?string $city = null): Collection;

    /**
     * Calculate route between two points (§8.4).
     */
    public function calculateRoute(float $originLat, float $originLng, float $destLat, float $destLng): RouteResult;

    /**
     * Calculate distance matrix from origin to multiple destinations (§8.4).
     *
     * @param  list<GeoPoint>  $destinations
     * @return Collection<int, DistanceMatrixItem>
     */
    public function distanceMatrix(GeoPoint $origin, array $destinations): Collection;

    /**
     * Get tile URL template for map layers (Leaflet) (§8.4).
     */
    public function tileUrlTemplate(MapTheme|string $theme = MapTheme::STANDARD_DAY): string;

    /**
     * Get tile proxy URL for a specific tile coordinate (z, x, y).
     */
    public function getTileProxyUrl(int $z, int $x, int $y, MapTheme|string $theme = MapTheme::STANDARD_DAY): string;

    /**
     * Fetch raw tile bytes from provider.
     */
    public function fetchTile(int $z, int $x, int $y, MapTheme|string $theme = MapTheme::STANDARD_DAY): ?string;
}
