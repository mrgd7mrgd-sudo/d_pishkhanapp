<?php

declare(strict_types=1);

use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('province seeder populates exactly 31 provinces with valid codes', function () {
    $this->seed(ProvinceSeeder::class);

    $provincesCount = DB::table('provinces')->count();
    expect($provincesCount)->toBe(31);

    // Verify key provinces exist with expected 3-letter codes
    $tehran = DB::table('provinces')->where('province_code', 'THR')->first();
    expect($tehran)->not->toBeNull();
    expect($tehran->name)->toBe('تهران');

    $isfahan = DB::table('provinces')->where('province_code', 'ESF')->first();
    expect($isfahan)->not->toBeNull();
    expect($isfahan->name)->toBe('اصفهان');

    // Verify cities populated
    $citiesCount = DB::table('cities')->count();
    expect($citiesCount)->toBeGreaterThanOrEqual(31);

    $tehranCity = DB::table('cities')->where('name', 'تهران')->where('province_code', 'THR')->first();
    expect($tehranCity)->not->toBeNull();
});

test('province seeder is idempotent and produces zero duplicates on re-run', function () {
    // Run seeder first time
    $this->seed(ProvinceSeeder::class);
    $initialProvincesCount = DB::table('provinces')->count();
    $initialCitiesCount = DB::table('cities')->count();

    expect($initialProvincesCount)->toBe(31);

    // Run seeder second time
    $this->seed(ProvinceSeeder::class);
    $reRunProvincesCount = DB::table('provinces')->count();
    $reRunCitiesCount = DB::table('cities')->count();

    expect($reRunProvincesCount)->toBe($initialProvincesCount);
    expect($reRunCitiesCount)->toBe($initialCitiesCount);
});

test('spatial coordinates for Tehran center are correctly recorded and queryable', function () {
    $this->seed(ProvinceSeeder::class);

    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver === 'pgsql') {
        $result = DB::select("
            SELECT name, ST_Y(center::geometry) as lat, ST_X(center::geometry) as lng,
                   ST_Distance(center, ST_SetSRID(ST_MakePoint(51.3890, 35.6892), 4326)::geography) as dist_meters
            FROM cities
            WHERE province_code = 'THR' AND name = 'تهران'
        ");

        expect($result)->toHaveCount(1);
        expect((float) $result[0]->lat)->toEqualWithDelta(35.6892, 0.001);
        expect((float) $result[0]->lng)->toEqualWithDelta(51.3890, 0.001);
        expect((float) $result[0]->dist_meters)->toEqualWithDelta(0, 1.0);
    } else {
        $tehran = DB::table('cities')
            ->where('province_code', 'THR')
            ->where('name', 'تهران')
            ->first();

        expect($tehran)->not->toBeNull();
        expect((float) $tehran->latitude)->toEqualWithDelta(35.6892, 0.001);
        expect((float) $tehran->longitude)->toEqualWithDelta(51.3890, 0.001);
    }
});
