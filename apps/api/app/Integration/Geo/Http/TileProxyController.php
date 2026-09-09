<?php

declare(strict_types=1);

namespace App\Integration\Geo\Http;

use App\Integration\Geo\Enums\MapTheme;
use App\Integration\Geo\GeoProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * TileProxyController (§8.4)
 * Provides cached raster map tiles proxying Neshan upstream with 30-day TTL.
 */
final class TileProxyController
{
    public function __construct(
        private readonly GeoProvider $geoProvider,
    ) {}

    public function getTile(Request $request, string $theme, int $z, int $x, int $y): Response|JsonResponse
    {
        $mapTheme = MapTheme::tryFrom($theme);
        if ($mapTheme === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/not-found',
                'title' => 'تم نقشه یافت نشد',
                'status' => 404,
                'detail' => "تم نقشه '{$theme}' نامعتبر است.",
            ], 404);
        }

        if ($z < 0 || $z > 22 || $x < 0 || $y < 0) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/validation-error',
                'title' => 'مختصات تایل نامعتبر است',
                'status' => 422,
                'detail' => 'مقادیر z، x یا y خارج از محدوده مجاز هستند.',
            ], 422);
        }

        $cacheKey = "geo:tile:{$mapTheme->value}:{$z}:{$x}:{$y}";

        /** @var string|null $cachedBinary */
        $cachedBinary = Cache::get($cacheKey);
        if ($cachedBinary !== null) {
            return new Response($cachedBinary, 200, [
                'Content-Type' => 'image/png',
                'X-Cache' => 'HIT',
                'Cache-Control' => 'public, max-age=2592000, immutable',
            ]);
        }

        $binary = $this->geoProvider->fetchTile($z, $x, $y, $mapTheme);
        if ($binary === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/not-found',
                'title' => 'تایل یافت نشد',
                'status' => 404,
                'detail' => 'تایل درخواستی در سرور نقشه یافت نشد.',
            ], 404);
        }

        // Cache for 30 days (§8.4, §4.6)
        Cache::put($cacheKey, $binary, now()->addDays(30));

        return new Response($binary, 200, [
            'Content-Type' => 'image/png',
            'X-Cache' => 'MISS',
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }
}
