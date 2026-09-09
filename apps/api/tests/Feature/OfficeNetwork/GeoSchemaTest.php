<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeAnnouncement;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('ensures offices table has all required columns and strictly excludes distance_km and coords residues', function (): void {
    expect(Schema::hasTable('offices'))->toBeTrue();

    // Mandatory columns (§6.1, §6.4)
    expect(Schema::hasColumn('offices', 'id'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'code'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'name'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'manager_name'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'membership_status'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'is_online'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'rating'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'review_count'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'address'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'city_id'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'province_code'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'location'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'phone'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'working_hours'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'active_counters'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'current_waiting_queue'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'sla_score'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('offices', 'deleted_at'))->toBeTrue();

    // ARCHITECTURE §6.2, §6.4 STRICT RULE:
    // distance_km and coords.mapX/mapY are derived/prototype residues and MUST NOT exist in DB!
    expect(Schema::hasColumn('offices', 'distance_km'))->toBeFalse()
        ->and(Schema::hasColumn('offices', 'coords'))->toBeFalse()
        ->and(Schema::hasColumn('offices', 'mapX'))->toBeFalse()
        ->and(Schema::hasColumn('offices', 'mapY'))->toBeFalse()
        ->and(Schema::hasColumn('offices', 'map_x'))->toBeFalse()
        ->and(Schema::hasColumn('offices', 'map_y'))->toBeFalse();
});

it('verifies office_service_coverage table schema, foreign keys, and relationships', function (): void {
    expect(Schema::hasTable('office_service_coverage'))->toBeTrue();

    expect(Schema::hasColumn('office_service_coverage', 'id'))->toBeTrue()
        ->and(Schema::hasColumn('office_service_coverage', 'office_id'))->toBeTrue()
        ->and(Schema::hasColumn('office_service_coverage', 'category_id'))->toBeTrue()
        ->and(Schema::hasColumn('office_service_coverage', 'service_id'))->toBeTrue()
        ->and(Schema::hasColumn('office_service_coverage', 'is_active'))->toBeTrue()
        ->and(Schema::hasColumn('office_service_coverage', 'daily_capacity'))->toBeTrue();

    $category = ServiceCategory::query()->create([
        'id' => 'civil-test-cat',
        'title' => 'ثبت احوال آزمایشی',
    ]);

    $office = Office::query()->create([
        'code' => '9001',
        'name' => 'دفتر پیشخوان ۹۰۰۱',
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
    ]);

    $coverage = OfficeServiceCoverage::query()->create([
        'office_id' => $office->id,
        'category_id' => $category->id,
        'is_active' => true,
        'daily_capacity' => 75,
    ]);

    expect($office->serviceCoverages)->toHaveCount(1)
        ->and($office->serviceCoverages->first()?->id)->toBe($coverage->id)
        ->and($coverage->office->id)->toBe($office->id)
        ->and($coverage->category->id)->toBe($category->id);

    // Cascading delete
    $office->forceDelete();
    expect(OfficeServiceCoverage::query()->where('id', $coverage->id)->exists())->toBeFalse();
});

it('verifies office_specialties is an independent table and properly relates to office', function (): void {
    expect(Schema::hasTable('office_specialties'))->toBeTrue();

    $office = Office::query()->create([
        'code' => '9002',
        'name' => 'دفتر پیشخوان ۹۰۰۲',
    ]);

    $specialty = OfficeSpecialty::query()->create([
        'office_id' => $office->id,
        'title' => 'امور گذرنامه و مهاجرت',
        'is_active' => true,
    ]);

    expect($office->specialties)->toHaveCount(1)
        ->and($office->specialties->first()?->title)->toBe('امور گذرنامه و مهاجرت')
        ->and($specialty->office->id)->toBe($office->id);
});

it('verifies office_medals is an independent table and properly relates to office', function (): void {
    expect(Schema::hasTable('office_medals'))->toBeTrue();

    $office = Office::query()->create([
        'code' => '9003',
        'name' => 'دفتر پیشخوان ۹۰۰۳',
    ]);

    $medal = OfficeMedal::query()->create([
        'office_id' => $office->id,
        'title' => 'رتبه اول رضایت مشتریان استان',
        'icon' => 'medal-gold',
        'earned_at' => Carbon::now(),
    ]);

    expect($office->medals)->toHaveCount(1)
        ->and($office->medals->first()?->title)->toBe('رتبه اول رضایت مشتریان استان')
        ->and($medal->office->id)->toBe($office->id);
});

it('verifies office_announcements table and soft deletes', function (): void {
    expect(Schema::hasTable('office_announcements'))->toBeTrue();

    $office = Office::query()->create([
        'code' => '9004',
        'name' => 'دفتر پیشخوان ۹۰۰۴',
    ]);

    $announcement = OfficeAnnouncement::query()->create([
        'office_id' => $office->id,
        'title' => 'تعطیلی موقت باجه ثبت احوال',
        'content' => 'به دلیل ارتقای سرورهای ثبت احوال، این باجه فردا تعطیل است.',
        'priority' => 'urgent',
        'is_active' => true,
    ]);

    expect($office->announcements)->toHaveCount(1)
        ->and($office->announcements->first()?->priority)->toBe('urgent');

    $announcement->delete();
    expect(OfficeAnnouncement::query()->where('id', $announcement->id)->exists())->toBeFalse()
        ->and(OfficeAnnouncement::withTrashed()->where('id', $announcement->id)->exists())->toBeTrue();
});

it('verifies membership_status enum casting and valid domain values', function (): void {
    $office = Office::query()->create([
        'code' => '9005',
        'name' => 'دفتر ۹۰۰۵',
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
    ]);

    $fresh = Office::query()->findOrFail($office->id);
    expect($fresh->membership_status)->toBe(OfficeMembershipStatus::REGISTERED_ONLINE)
        ->and($fresh->membership_status->value)->toBe('registered_online')
        ->and($fresh->membership_status->label())->toBe('عضویت آنلاین تأییدشده');
});

it('verifies PostGIS geospatial capabilities when running on PostgreSQL or validates location schema', function (): void {
    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver === 'pgsql') {
        // 1. Verify GIST index on location exists
        $indexes = DB::select("
            SELECT indexname, indexdef
            FROM pg_indexes
            WHERE tablename = 'offices' AND indexname = 'idx_offices_location';
        ");
        expect($indexes)->not->toBeEmpty();
        expect($indexes[0]->indexdef)->toContain('USING gist (location)');

        // 2. Insert test office with geography point (Tehran: lng 51.4000, lat 35.7000)
        $officeId = Str::uuid()->toString();
        DB::statement("
            INSERT INTO offices (id, code, name, location, membership_status, is_online, created_at, updated_at)
            VALUES (
                '{$officeId}',
                '9006',
                'دفتر پستی تهران مرکز',
                ST_SetSRID(ST_MakePoint(51.4000, 35.7000), 4326)::geography,
                'registered_online',
                true,
                NOW(),
                NOW()
            )
        ");

        // 3. Test ST_DWithin spatial query
        // Query within 2000m of (51.4010, 35.7010)
        $nearby = DB::select("
            SELECT id, name
            FROM offices
            WHERE ST_DWithin(
                location,
                ST_SetSRID(ST_MakePoint(51.4010, 35.7010), 4326)::geography,
                2000
            ) AND id = '{$officeId}'
        ");
        expect($nearby)->toHaveCount(1);

        // 4. Test query outside 100m
        $far = DB::select("
            SELECT id, name
            FROM offices
            WHERE ST_DWithin(
                location,
                ST_SetSRID(ST_MakePoint(51.5000, 35.8000), 4326)::geography,
                100
            ) AND id = '{$officeId}'
        ");
        expect($far)->toBeEmpty();

        // 5. Test EXPLAIN to verify execution plan
        $explain = DB::select('
            EXPLAIN
            SELECT id, name
            FROM offices
            WHERE ST_DWithin(
                location,
                ST_SetSRID(ST_MakePoint(51.4000, 35.7000), 4326)::geography,
                5000
            )
        ');
        expect($explain)->not->toBeEmpty();
    } else {
        // SQLite fallback
        expect(Schema::hasColumn('offices', 'location'))->toBeTrue();

        $office = Office::query()->create([
            'code' => '9007',
            'name' => 'دفتر نمونه آزمایشی',
            'location' => '35.7000,51.4000',
        ]);
        expect($office->location)->toBe('35.7000,51.4000');
    }
});
