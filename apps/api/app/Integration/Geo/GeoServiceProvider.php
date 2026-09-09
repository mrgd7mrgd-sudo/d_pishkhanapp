<?php

declare(strict_types=1);

namespace App\Integration\Geo;

use App\Integration\Geo\Drivers\FakeDriver;
use App\Integration\Geo\Drivers\NeshanDriver;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Geo integration (§8.0, §8.4)
 */
final class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeoProvider::class, function (): GeoProvider {
            $driver = (string) config('pishkhan.geo.driver', 'fake');

            if ($driver === 'neshan') {
                $apiKey = (string) config('pishkhan.geo.neshan.api_key', '');
                $baseUrl = (string) config('pishkhan.geo.neshan.base_url', 'https://api.neshan.org');
                $tilePrefix = (string) config('pishkhan.geo.tile_proxy.url_prefix', '/tiles');

                return new NeshanDriver($apiKey, $baseUrl, $tilePrefix);
            }

            $tilePrefix = (string) config('pishkhan.geo.tile_proxy.url_prefix', '/tiles');

            return new FakeDriver($tilePrefix);
        });
    }
}
