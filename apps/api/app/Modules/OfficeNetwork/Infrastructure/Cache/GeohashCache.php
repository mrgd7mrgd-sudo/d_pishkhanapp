<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;

final class GeohashCache
{
    private const BASE32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    private const DEFAULT_TTL_SECONDS = 60;

    /**
     * Encode coordinates to Geohash string with specified precision (default 6 ~ 1.2km x 0.6km) (§5.6 #4, TASK-042).
     */
    public function encode(float $latitude, float $longitude, int $precision = 6): string
    {
        $latMin = -90.0;
        $latMax = 90.0;
        $lonMin = -180.0;
        $lonMax = 180.0;

        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;

        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lonMin + $lonMax) / 2;
                if ($longitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lonMin = $mid;
                } else {
                    $lonMax = $mid;
                }
            } else {
                $mid = ($latMin + $latMax) / 2;
                if ($latitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latMin = $mid;
                } else {
                    $latMax = $mid;
                }
            }

            $isEven = ! $isEven;

            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= self::BASE32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }

    /**
     * Build cache key using geohash precision 6, category, and radius (§5.6 #4, TASK-042).
     */
    public function makeCacheKey(float $lat, float $lng, ?string $categoryId, float $radiusKm, int $limit): string
    {
        $geohash = $this->encode($lat, $lng, 6);
        $cat = $categoryId ?? 'all';

        return "offices:nearby:gh_{$geohash}:cat_{$cat}:r_{$radiusKm}:l_{$limit}";
    }

    /**
     * Remember nearby offices result with 60 seconds TTL (§5.6 #4, TASK-042).
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function remember(float $lat, float $lng, ?string $categoryId, float $radiusKm, int $limit, \Closure $callback): mixed
    {
        $key = $this->makeCacheKey($lat, $lng, $categoryId, $radiusKm, $limit);

        return Cache::remember($key, self::DEFAULT_TTL_SECONDS, $callback);
    }
}
