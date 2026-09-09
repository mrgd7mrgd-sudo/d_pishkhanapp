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

/**
 * Fake Driver for Geo integration (§8.0, §8.4)
 * Deterministic offline test double with realistic Tehran geographic data.
 */
final class FakeDriver implements GeoProvider
{
    /**
     * Standard transparent 1x1 PNG image binary.
     */
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function __construct(
        private readonly string $tileProxyUrlPrefix = '/tiles',
    ) {}

    public function reverseGeocode(float $lat, float $lng): AddressResult
    {
        if (abs($lat - 35.7118) < 0.03 && abs($lng - 51.4055) < 0.03) {
            return new AddressResult(
                formattedAddress: 'تهران، میدان ولیعصر، بلوار کشاورز',
                routeName: 'بلوار کشاورز',
                neighbourhood: 'ولیعصر',
                city: 'تهران',
                state: 'استان تهران',
                place: 'میدان ولیعصر',
                district: 'منطقه ۶',
                latitude: $lat,
                longitude: $lng,
                inTrafficZone: true,
                inOddEvenZone: false,
            );
        }

        if (abs($lat - 35.7820) < 0.03 && abs($lng - 51.3730) < 0.03) {
            return new AddressResult(
                formattedAddress: 'تهران، سعادت‌آباد، میدان سرو',
                routeName: 'بلوار پاک‌نژاد',
                neighbourhood: 'سعادت‌آباد',
                city: 'تهران',
                state: 'استان تهران',
                place: 'میدان سرو',
                district: 'منطقه ۲',
                latitude: $lat,
                longitude: $lng,
                inTrafficZone: false,
                inOddEvenZone: false,
            );
        }

        return new AddressResult(
            formattedAddress: 'استان تهران، تهران، خیابان آزادی، پلاک ۱۰',
            routeName: 'خیابان آزادی',
            neighbourhood: 'آزادی',
            city: 'تهران',
            state: 'استان تهران',
            latitude: $lat,
            longitude: $lng,
        );
    }

    public function searchAddress(string $query, ?float $lat = null, ?float $lng = null): Collection
    {
        $landmarks = [
            [
                'title' => 'میدان ولیعصر',
                'address' => 'تهران، میدان ولیعصر',
                'neighbourhood' => 'منطقه ۶، تهران',
                'city' => 'تهران',
                'lat' => 35.7118,
                'lng' => 51.4055,
            ],
            [
                'title' => 'میدان سرو سعادت‌آباد',
                'address' => 'تهران، سعادت‌آباد، میدان سرو',
                'neighbourhood' => 'منطقه ۲، تهران',
                'city' => 'تهران',
                'lat' => 35.7820,
                'lng' => 51.3730,
            ],
            [
                'title' => 'میدان انقلاب',
                'address' => 'تهران، میدان انقلاب اسلامی',
                'neighbourhood' => 'دانشگاه تهران',
                'city' => 'تهران',
                'lat' => 35.7008,
                'lng' => 51.3912,
            ],
        ];

        $matched = array_filter($landmarks, function (array $l) use ($query): bool {
            return str_contains($l['title'], $query) || str_contains($l['address'], $query);
        });

        if (empty($matched)) {
            $matched = [[
                'title' => $query,
                'address' => "تهران، خیابان {$query}",
                'neighbourhood' => 'مرکز تهران',
                'city' => 'تهران',
                'lat' => $lat ?? 35.7118,
                'lng' => $lng ?? 51.4055,
            ]];
        }

        return collect($matched)->map(function (array $item): AddressResult {
            return new AddressResult(
                formattedAddress: (string) $item['address'],
                neighbourhood: (string) $item['neighbourhood'],
                city: (string) $item['city'],
                place: (string) $item['title'],
                latitude: (float) $item['lat'],
                longitude: (float) $item['lng'],
            );
        });
    }

    public function geocode(string $address, ?string $city = null): Collection
    {
        return $this->searchAddress($address);
    }

    public function calculateRoute(float $originLat, float $originLng, float $destLat, float $destLng): RouteResult
    {
        $distanceMeters = (int) round($this->haversine($originLat, $originLng, $destLat, $destLng) * 1.3);
        $durationSeconds = (int) max(60, round($distanceMeters / 8.33)); // ~30 km/h in city

        $km = round($distanceMeters / 1000, 1);
        $minutes = (int) ceil($durationSeconds / 60);

        return new RouteResult(
            distanceMeters: $distanceMeters,
            durationSeconds: $durationSeconds,
            distanceText: "{$km} کیلومتر",
            durationText: "{$minutes} دقیقه",
            summaryRoute: 'مسیر شهری نمونه',
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
        $binary = base64_decode(self::PNG_1X1, true);

        return $binary !== false ? $binary : null;
    }

    private function resolveThemeName(MapTheme|string $theme): string
    {
        if ($theme instanceof MapTheme) {
            return $theme->value;
        }

        return MapTheme::fromPrototype($theme)->value;
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
