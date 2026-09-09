<?php

declare(strict_types=1);

namespace App\Integration\Geo\Drivers;

use App\Integration\Geo\AddressResult;
use App\Integration\Geo\DistanceMatrixItem;
use App\Integration\Geo\Enums\MapTheme;
use App\Integration\Geo\GeoPoint;
use App\Integration\Geo\GeoProvider;
use App\Integration\Geo\RouteResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Neshan Platform Driver (§8.4)
 * Secret API key is held exclusively on server and never leaked to client.
 */
final class NeshanDriver implements GeoProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.neshan.org',
        private readonly string $tileProxyUrlPrefix = '/tiles',
    ) {}

    public function reverseGeocode(float $lat, float $lng): AddressResult
    {
        $response = Http::withHeaders(['Api-Key' => $this->apiKey])
            ->timeout(5)
            ->retry(2)
            ->get("{$this->baseUrl}/v5/reverse", [
                'lat' => $lat,
                'lng' => $lng,
            ]);

        if (! $response->successful()) {
            Log::warning('Neshan reverse geocoding request failed', [
                'status' => $response->status(),
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return new AddressResult(
                formattedAddress: '',
                latitude: $lat,
                longitude: $lng,
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $this->mapAddressResult($data, $lat, $lng);
    }

    public function searchAddress(string $query, ?float $lat = null, ?float $lng = null): Collection
    {
        $params = ['term' => $query];
        if ($lat !== null && $lng !== null) {
            $params['lat'] = (string) $lat;
            $params['lng'] = (string) $lng;
        }

        $response = Http::withHeaders(['Api-Key' => $this->apiKey])
            ->timeout(5)
            ->retry(2)
            ->get("{$this->baseUrl}/v1/search", $params);

        if (! $response->successful()) {
            Log::warning('Neshan address search request failed', [
                'status' => $response->status(),
                'query' => $query,
            ]);

            return collect();
        }

        /** @var array{items?: list<array<string, mixed>>} $data */
        $data = $response->json() ?? [];
        $items = $data['items'] ?? [];

        return collect($items)->map(function (array $item): AddressResult {
            /** @var array{x?: float|int, y?: float|int} $loc */
            $loc = $item['location'] ?? [];
            $itemLng = isset($loc['x']) ? (float) $loc['x'] : null;
            $itemLat = isset($loc['y']) ? (float) $loc['y'] : null;

            return new AddressResult(
                formattedAddress: (string) ($item['address'] ?? $item['title'] ?? ''),
                neighbourhood: isset($item['neighbourhood']) ? (string) $item['neighbourhood'] : null,
                city: isset($item['region']) ? (string) $item['region'] : null,
                place: isset($item['title']) ? (string) $item['title'] : null,
                latitude: $itemLat,
                longitude: $itemLng,
            );
        });
    }

    public function geocode(string $address, ?string $city = null): Collection
    {
        $term = $city !== null && $city !== '' ? "{$city} {$address}" : $address;

        return $this->searchAddress($term);
    }

    public function calculateRoute(float $originLat, float $originLng, float $destLat, float $destLng): RouteResult
    {
        $response = Http::withHeaders(['Api-Key' => $this->apiKey])
            ->timeout(5)
            ->retry(2)
            ->get("{$this->baseUrl}/v4/direction", [
                'type' => 'car',
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
            ]);

        if (! $response->successful()) {
            Log::warning('Neshan routing request failed', [
                'status' => $response->status(),
                'origin' => "{$originLat},{$originLng}",
                'dest' => "{$destLat},{$destLng}",
            ]);

            return new RouteResult(distanceMeters: 0, durationSeconds: 0);
        }

        /** @var array{routes?: list<array<string, mixed>>} $data */
        $data = $response->json() ?? [];
        $route = $data['routes'][0] ?? [];
        /** @var list<array<string, mixed>> $legs */
        $legs = $route['legs'] ?? [];
        $leg = $legs[0] ?? [];

        /** @var array{value?: int, text?: string} $distance */
        $distance = $leg['distance'] ?? [];
        /** @var array{value?: int, text?: string} $duration */
        $duration = $leg['duration'] ?? [];
        /** @var array{points?: string} $overviewPolyline */
        $overviewPolyline = $route['overview_polyline'] ?? [];

        return new RouteResult(
            distanceMeters: (int) ($distance['value'] ?? 0),
            durationSeconds: (int) ($duration['value'] ?? 0),
            distanceText: isset($distance['text']) ? (string) $distance['text'] : null,
            durationText: isset($duration['text']) ? (string) $duration['text'] : null,
            summaryRoute: isset($leg['summary']) ? (string) $leg['summary'] : null,
            encodedPolyline: isset($overviewPolyline['points']) ? (string) $overviewPolyline['points'] : null,
        );
    }

    public function distanceMatrix(GeoPoint $origin, array $destinations): Collection
    {
        return collect($destinations)->map(function (GeoPoint $dest) use ($origin): DistanceMatrixItem {
            $route = $this->calculateRoute($origin->latitude, $origin->longitude, $dest->latitude, $dest->longitude);

            return new DistanceMatrixItem(
                destination: $dest,
                distanceMeters: $route->distanceMeters,
                durationSeconds: $route->durationSeconds,
            );
        });
    }

    public function tileUrlTemplate(MapTheme|string $theme = MapTheme::STANDARD_DAY): string
    {
        $themeName = $this->resolveThemeName($theme);

        return "{$this->tileProxyUrlPrefix}/{$themeName}/{z}/{x}/{y}.png";
    }

    public function getTileProxyUrl(int $z, int $x, int $y, MapTheme|string $theme = MapTheme::STANDARD_DAY): string
    {
        $themeName = $this->resolveThemeName($theme);

        return "{$this->tileProxyUrlPrefix}/{$themeName}/{$z}/{$x}/{$y}.png";
    }

    public function fetchTile(int $z, int $x, int $y, MapTheme|string $theme = MapTheme::STANDARD_DAY): ?string
    {
        $themeName = $this->resolveThemeName($theme);
        $url = "{$this->baseUrl}/v4/tiles/raster/{$themeName}/{$z}/{$x}/{$y}.png";

        $response = Http::withHeaders(['Api-Key' => $this->apiKey])
            ->timeout(5)
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapAddressResult(array $data, float $lat, float $lng): AddressResult
    {
        return new AddressResult(
            formattedAddress: (string) ($data['formatted_address'] ?? ''),
            routeName: isset($data['route_name']) ? (string) $data['route_name'] : null,
            neighbourhood: isset($data['neighbourhood']) ? (string) $data['neighbourhood'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            state: isset($data['state']) ? (string) $data['state'] : null,
            place: isset($data['place']) ? (string) $data['place'] : null,
            district: isset($data['district']) ? (string) $data['district'] : null,
            latitude: $lat,
            longitude: $lng,
            inTrafficZone: (bool) ($data['in_traffic_zone'] ?? false),
            inOddEvenZone: (bool) ($data['in_odd_even_zone'] ?? false),
        );
    }

    private function resolveThemeName(MapTheme|string $theme): string
    {
        if ($theme instanceof MapTheme) {
            return $theme->value;
        }

        return MapTheme::fromPrototype($theme)->value;
    }
}
