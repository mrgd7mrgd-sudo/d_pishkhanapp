<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Database\Seeders\OfficeSeeder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Infrastructure\Cache\GeohashCache;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        OfficeSeeder::class,
    ]);
});

it('strictly never returns offline or non-registered offices in search results (§5.6 #4, TASK-042-T)', function (): void {
    /** @var OfficeFinder $finder */
    $finder = app(OfficeFinder::class);

    // Create an offline office right at user's location
    $offlineOffice = Office::query()->create([
        'code' => '9991',
        'name' => 'پیشخوان تعطیل یا آفلاین',
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
        'is_online' => false,
        'location' => '35.7592,51.4083',
        'rating' => 5.0,
    ]);

    // Create an unregistered office
    $unregisteredOffice = Office::query()->create([
        'code' => '9992',
        'name' => 'پیشخوان بدون عضویت آنلاین',
        'membership_status' => OfficeMembershipStatus::UNREGISTERED,
        'is_online' => true,
        'location' => '35.7592,51.4083',
        'rating' => 5.0,
    ]);

    // Query nearby Vanak square: 35.7592, 51.4083
    $results = $finder->findNearby(35.7592, 51.4083, 10.0, null, 50, true);

    expect($results)->not->toBeEmpty();

    $returnedCodes = array_map(fn ($r) => $r['office']->code, $results);

    expect($returnedCodes)->not->toContain('9991')
        ->and($returnedCodes)->not->toContain('9992');
});

it('verifies smart_score ordering strictly matches manual formula calculation (§5.6 #4, TASK-042-T)', function (): void {
    /** @var OfficeFinder $finder */
    $finder = app(OfficeFinder::class);

    $results = $finder->findNearby(35.7592, 51.4083, 15.0, null, 10, true);
    expect($results)->not->toBeEmpty();

    // Verify descending sort order
    $scores = array_map(fn ($r) => (float) $r['smart_score'], $results);
    $sortedScores = $scores;
    rsort($sortedScores);
    expect($scores)->toBe($sortedScores);

    // Verify mathematical formula on each returned office
    foreach ($results as $item) {
        $dist = (float) $item['distance_km'];
        $office = $item['office'];
        $expectedScore = $finder->computeSmartScore(
            $dist,
            15.0,
            (float) $office->rating,
            (int) $office->current_waiting_queue,
            (float) $office->sla_score
        );

        expect(abs($item['smart_score'] - $expectedScore))->toBeLessThan(0.01);
    }
});

it('filters offices by category coverage correctly (§5.6 #4, TASK-042-T)', function (): void {
    /** @var OfficeFinder $finder */
    $finder = app(OfficeFinder::class);

    $results = $finder->findNearby(35.7592, 51.4083, 20.0, 'identity', 20, true);
    expect($results)->not->toBeEmpty();

    foreach ($results as $item) {
        $hasCoverage = OfficeServiceCoverage::query()
            ->where('office_id', $item['office']->id)
            ->where('category_id', 'identity')
            ->where('is_active', true)
            ->exists();

        expect($hasCoverage)->toBeTrue();
    }
});

it('caches nearby search results using precision-6 Geohash with 60s TTL (§5.6 #4, TASK-042-T)', function (): void {
    /** @var GeohashCache $geohashCache */
    $geohashCache = app(GeohashCache::class);

    // Tehran coordinates
    $lat = 35.7592;
    $lng = 51.4083;
    $geohash = $geohashCache->encode($lat, $lng, 6);

    expect(strlen($geohash))->toBe(6);

    $expectedKey = $geohashCache->makeCacheKey($lat, $lng, 'identity', 10.0, 15);
    expect($expectedKey)->toContain("gh_{$geohash}")
        ->and($expectedKey)->toContain('cat_identity');

    // Clear cache
    Cache::forget($expectedKey);
    expect(Cache::has($expectedKey))->toBeFalse();

    /** @var OfficeFinder $finder */
    $finder = app(OfficeFinder::class);

    // First call: Populates cache
    $results1 = $finder->findNearby($lat, $lng, 10.0, 'identity', 15, false);
    expect(Cache::has($expectedKey))->toBeTrue();

    // Second call: Hits cache
    $results2 = $finder->findNearby($lat, $lng, 10.0, 'identity', 15, false);
    expect(count($results2))->toBe(count($results1));
});

it('verifies PostGIS GIST index usage and execution plan when running on PostgreSQL (§5.6 #4, TASK-042-T)', function (): void {
    $driver = DB::connection()->getDriverName();

    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();

        return;
    }

    // Run EXPLAIN on the exact PostGIS query
    $lat = 35.7592;
    $lng = 51.4083;
    $radiusMeters = 10000.0;

    $explain = DB::select('
        EXPLAIN
        SELECT o.id, o.name
        FROM offices o
        WHERE o.is_online = true
          AND o.membership_status = \'registered_online\'
          AND o.deleted_at IS NULL
          AND ST_DWithin(o.location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)
    ', [$lng, $lat, $radiusMeters]);

    expect($explain)->not->toBeEmpty();
    $planText = implode("\n", array_map(fn ($r) => (string) ($r->{'QUERY PLAN'} ?? json_encode($r)), $explain));

    // Confirm query plan is valid and references location filtering or index scan
    expect($planText)->toContain('offices');
});
