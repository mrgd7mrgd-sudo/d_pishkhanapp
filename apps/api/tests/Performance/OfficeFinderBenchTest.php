<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Database\Seeders\OfficeSeeder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('benchmarks OfficeFinder query latency achieving p95 under 200ms (§9.2, §5.6 #4, TASK-042-T)', function (): void {
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        OfficeSeeder::class,
    ]);

    /** @var OfficeFinder $finder */
    $finder = app(OfficeFinder::class);

    // Seed batch of simulated offices to test query volume
    $driver = DB::connection()->getDriverName();
    $additionalOffices = [];
    $baseLat = 35.7000;
    $baseLng = 51.4000;

    for ($i = 1; $i <= 100; $i++) {
        $lat = $baseLat + (mt_rand(-100, 100) / 1000.0);
        $lng = $baseLng + (mt_rand(-100, 100) / 1000.0);
        $loc = $driver === 'pgsql'
            ? DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography")
            : "{$lat},{$lng}";

        $additionalOffices[] = [
            'id' => (string) Str::uuid(),
            'code' => (string) (7000 + $i),
            'name' => "دفتر شبیه‌سازی بنچمارک {$i}",
            'manager_name' => "مدیر آزمایشی {$i}",
            'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE->value,
            'is_online' => true,
            'rating' => 4.5,
            'review_count' => 150,
            'location' => $loc,
            'sla_score' => 95.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    Office::query()->insert($additionalOffices);

    // Warm-up query
    $finder->findNearby(35.7592, 51.4083, 10.0, null, 20, true);

    // Execute 30 benchmark runs
    $timingsMs = [];
    for ($run = 1; $run <= 30; $run++) {
        $sampleLat = 35.7000 + (mt_rand(-30, 30) / 1000.0);
        $sampleLng = 51.4000 + (mt_rand(-30, 30) / 1000.0);

        $start = hrtime(true);
        $results = $finder->findNearby($sampleLat, $sampleLng, 10.0, null, 20, true);
        $durationMs = (hrtime(true) - $start) / 1_000_000.0;

        $timingsMs[] = $durationMs;
        expect($results)->toBeArray();
    }

    sort($timingsMs);
    $count = count($timingsMs);
    $p50 = $timingsMs[(int) floor($count * 0.50)];
    $p90 = $timingsMs[(int) floor($count * 0.90)];
    $p95 = $timingsMs[(int) floor($count * 0.95)];

    // Output real benchmark measurement
    fwrite(STDERR, sprintf(
        "\n[OfficeFinder Benchmark] Runs: %d | p50: %.2f ms | p90: %.2f ms | p95: %.2f ms\n",
        $count,
        $p50,
        $p90,
        $p95
    ));

    // SLO: p95 < 200ms
    expect($p95)->toBeLessThan(200.0);
});
