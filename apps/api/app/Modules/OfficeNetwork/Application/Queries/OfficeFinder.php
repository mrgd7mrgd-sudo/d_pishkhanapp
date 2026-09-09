<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Queries;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Infrastructure\Cache\GeohashCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OfficeFinder
{
    public function __construct(
        private readonly GeohashCache $cache
    ) {}

    /**
     * Find nearby offices with PostGIS Smart Score calculation and Geohash caching (§5.6 #4, TASK-042).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findNearby(
        float $lat,
        float $lng,
        float $radiusKm = 10.0,
        ?string $categoryId = null,
        int $limit = 20,
        bool $bypassCache = false
    ): array {
        $radiusKm = max(0.1, min($radiusKm, 100.0));
        $limit = max(1, min($limit, 50));

        if ($bypassCache) {
            return $this->executeNearbyQuery($lat, $lng, $radiusKm, $categoryId, $limit);
        }

        return $this->cache->remember(
            $lat,
            $lng,
            $categoryId,
            $radiusKm,
            $limit,
            fn (): array => $this->executeNearbyQuery($lat, $lng, $radiusKm, $categoryId, $limit)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function executeNearbyQuery(float $lat, float $lng, float $radiusKm, ?string $categoryId, int $limit): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return $this->executePostGisQuery($lat, $lng, $radiusKm, $categoryId, $limit);
        }

        return $this->executeFallbackQuery($lat, $lng, $radiusKm, $categoryId, $limit);
    }

    /**
     * Execute native PostGIS query using exact formula from Architecture §5.6 #4.
     *
     * @return array<int, array<string, mixed>>
     */
    private function executePostGisQuery(float $lat, float $lng, float $radiusKm, ?string $categoryId, int $limit): array
    {
        $radiusMeters = $radiusKm * 1000;

        $categoryJoin = '';
        $categoryParam = [];
        if (! empty($categoryId)) {
            $categoryJoin = 'JOIN office_service_coverage c ON c.office_id = o.id AND c.category_id = ? AND c.is_active = true';
            $categoryParam[] = $categoryId;
        }

        $sql = "
            SELECT o.*,
                   ST_Distance(o.location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000.0 AS distance_km,
                   (  0.45 * (1.0 - LEAST(ST_Distance(o.location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / ?, 1.0))
                    + 0.30 * (o.rating / 5.0)
                    + 0.15 * (1.0 - LEAST(o.current_waiting_queue::float / 20.0, 1.0))
                    + 0.10 * (o.sla_score / 100.0)
                   ) AS smart_score
            FROM offices o
            {$categoryJoin}
            WHERE o.is_online = true
              AND o.membership_status = 'registered_online'
              AND o.deleted_at IS NULL
              AND ST_DWithin(o.location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)
            ORDER BY smart_score DESC
            LIMIT ?
        ";

        $bindings = array_merge(
            [$lng, $lat],
            [$lng, $lat, $radiusMeters],
            $categoryParam,
            [$lng, $lat, $radiusMeters],
            [$limit]
        );

        /** @var list<object{id: string, distance_km: float|int|string, smart_score: float|int|string, location?: mixed}> $rows */
        $rows = DB::select($sql, $bindings);

        return $this->hydrateOfficesWithRelations($rows);
    }

    /**
     * Fallback Haversine calculation for non-Postgres environments (e.g. SQLite tests).
     *
     * @return array<int, array<string, mixed>>
     */
    private function executeFallbackQuery(float $lat, float $lng, float $radiusKm, ?string $categoryId, int $limit): array
    {
        $query = Office::query()
            ->where('is_online', true)
            ->where('membership_status', 'registered_online');

        if (! empty($categoryId)) {
            $query->whereHas('serviceCoverages', function ($q) use ($categoryId): void {
                $q->where('category_id', $categoryId)->where('is_active', true);
            });
        }

        $offices = $query->with(['specialties', 'medals', 'serviceCoverages'])->get();
        $results = [];

        foreach ($offices as $office) {
            if (empty($office->location)) {
                continue;
            }

            $parts = explode(',', (string) $office->location);
            if (count($parts) !== 2) {
                continue;
            }

            $offLat = (float) $parts[0];
            $offLng = (float) $parts[1];

            $distanceKm = $this->calculateHaversineDistance($lat, $lng, $offLat, $offLng);
            if ($distanceKm > $radiusKm) {
                continue;
            }

            $smartScore = $this->computeSmartScore(
                $distanceKm,
                $radiusKm,
                (float) $office->rating,
                (int) $office->current_waiting_queue,
                (float) $office->sla_score
            );

            $results[] = [
                'office' => $office,
                'distance_km' => round($distanceKm, 2),
                'smart_score' => round($smartScore, 4),
                'coords' => ['lat' => $offLat, 'lng' => $offLng],
            ];
        }

        usort($results, fn ($a, $b) => $b['smart_score'] <=> $a['smart_score']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Compute smart score according to Architecture §5.6 #4.
     * Weights: 0.45 distance + 0.30 rating + 0.15 queue + 0.10 SLA
     */
    public function computeSmartScore(float $distanceKm, float $radiusKm, float $rating, int $queue, float $slaScore): float
    {
        $distFactor = 1.0 - min($distanceKm / max(0.001, $radiusKm), 1.0);
        $ratingFactor = $rating / 5.0;
        $queueFactor = 1.0 - min(((float) $queue) / 20.0, 1.0);
        $slaFactor = $slaScore / 100.0;

        return (0.45 * $distFactor) + (0.30 * $ratingFactor) + (0.15 * $queueFactor) + (0.10 * $slaFactor);
    }

    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * @param  list<object{id: string, distance_km: float|int|string, smart_score: float|int|string, location?: mixed}>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function hydrateOfficesWithRelations(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        /** @var list<string> $ids */
        $ids = array_map(fn ($r) => (string) $r->id, $rows);

        /** @var Collection<string, Office> $officeModels */
        $officeModels = Office::query()
            ->with(['specialties', 'medals', 'serviceCoverages'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $office = $officeModels->get((string) $row->id);
            if ($office === null) {
                continue;
            }

            $coords = $this->extractCoordsFromRow($row);

            $result[] = [
                'office' => $office,
                'distance_km' => round((float) $row->distance_km, 2),
                'smart_score' => round((float) $row->smart_score, 4),
                'coords' => $coords,
            ];
        }

        return $result;
    }

    /**
     * @param  object{id: string, location?: mixed}  $row
     * @return array{lat: float, lng: float}
     */
    private function extractCoordsFromRow(object $row): array
    {
        // Try reading ST_X / ST_Y if extracted, or fallback
        if (isset($row->location)) {
            $point = DB::selectOne(
                'SELECT ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat FROM offices WHERE id = ?',
                [$row->id]
            );

            if ($point !== null) {
                return [
                    'lat' => round((float) $point->lat, 4),
                    'lng' => round((float) $point->lng, 4),
                ];
            }
        }

        return ['lat' => 35.7000, 'lng' => 51.4000];
    }
}
