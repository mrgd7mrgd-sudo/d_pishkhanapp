<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\OfficeNetwork\Database\Seeders\OfficeSeeder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Http\Resources\OfficeResource;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        OfficeSeeder::class,
    ]);
});

it('matches Architecture §5.6 Example 4 response contract for nearby offices endpoint (TASK-043-T)', function (): void {
    $response = $this->getJson('/api/v1/offices/nearby?lat=35.7592&lng=51.4083&radius_km=15&limit=5');

    $response->assertStatus(200)
        ->assertHeader('ETag')
        ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=86400');

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();

    $item = $data[0];

    // Verify all contract fields from Architecture §5.6 Example 4
    expect($item)->toHaveKeys([
        'id',
        'code',
        'name',
        'manager_name',
        'membership_status',
        'is_online',
        'rating',
        'review_count',
        'medals',
        'specialties',
        'address',
        'province_code',
        'city',
        'region',
        'coords',
        'distance_km',
        'phone',
        'working_hours',
        'active_counters',
        'current_waiting_queue',
        'estimated_wait_minutes',
        'supported_category_ids',
        'smart_score',
    ]);

    expect($item['coords'])->toHaveKeys(['lat', 'lng'])
        ->and($item['working_hours'])->toHaveKeys(['label', 'is_open_now'])
        ->and($item['rating'])->toBeFloat()
        ->and($item['review_count'])->toBeInt()
        ->and($item['medals'])->toBeArray()
        ->and($item['specialties'])->toBeArray()
        ->and($item['supported_category_ids'])->toBeArray()
        ->and(is_numeric($item['distance_km']))->toBeTrue()
        ->and(is_numeric($item['smart_score']))->toBeTrue()
        ->and($item['estimated_wait_minutes'])->toBeInt();

    $meta = $response->json('meta');
    expect($meta)->toHaveKeys(['center', 'radius_km', 'count'])
        ->and($meta['center'])->toHaveKeys(['lat', 'lng'])
        ->and((float) $meta['radius_km'])->toBe(15.0)
        ->and($meta['count'])->toBeGreaterThan(0);
});

it('filters offices with only_online=true returning strictly registered_online status (TASK-043-T)', function (): void {
    // Create an offline and an unregistered office
    Office::query()->create([
        'code' => '9981',
        'name' => 'دفتر غیر آنلاین',
        'membership_status' => OfficeMembershipStatus::REGISTERED_OFFLINE,
        'is_online' => false,
    ]);

    $response = $this->getJson('/api/v1/offices?only_online=true');
    $response->assertStatus(200);

    $items = $response->json('data');
    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect($item['is_online'])->toBeTrue()
            ->and($item['membership_status'])->toBe('registered_online');
    }
});

it('calculates is_open_now correctly according to Iran timezone and working hours (TASK-043-T)', function (): void {
    // 1. Saturday at 10:00 AM (Open)
    Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00', 'Asia/Tehran')); // Saturday
    expect(OfficeResource::calculateIsOpenNow([]))->toBeTrue();

    // 2. Saturday at 21:00 PM (Closed)
    Carbon::setTestNow(Carbon::parse('2026-09-12 21:00:00', 'Asia/Tehran')); // Saturday night
    expect(OfficeResource::calculateIsOpenNow([]))->toBeFalse();

    // 3. Thursday at 11:00 AM (Open half day)
    Carbon::setTestNow(Carbon::parse('2026-09-10 11:00:00', 'Asia/Tehran')); // Thursday
    expect(OfficeResource::calculateIsOpenNow([]))->toBeTrue();

    // 4. Thursday at 15:00 PM (Closed half day)
    Carbon::setTestNow(Carbon::parse('2026-09-10 15:00:00', 'Asia/Tehran')); // Thursday afternoon
    expect(OfficeResource::calculateIsOpenNow([]))->toBeFalse();

    // 5. Friday (Closed - weekend)
    Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', 'Asia/Tehran')); // Friday
    expect(OfficeResource::calculateIsOpenNow([]))->toBeFalse();

    // Reset clock
    Carbon::setTestNow(null);
});

it('validates nearby coordinates rejecting invalid or missing parameters with RFC 7807 422 error (TASK-043-T)', function (): void {
    // Missing lat/lng
    $responseMissing = $this->getJson('/api/v1/offices/nearby');
    $responseMissing->assertStatus(422)
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);

    // Out of range latitude (> 90)
    $responseInvalid = $this->getJson('/api/v1/offices/nearby?lat=95.0&lng=51.0');
    $responseInvalid->assertStatus(422)
        ->assertJsonValidationErrors(['lat']);

    // Non-numeric longitude
    $responseString = $this->getJson('/api/v1/offices/nearby?lat=35.0&lng=invalid_coord');
    $responseString->assertStatus(422)
        ->assertJsonValidationErrors(['lng']);
});

it('retrieves single office by ID or code with 200 or 404 RFC 7807 when not found (TASK-043-T)', function (): void {
    /** @var Office $firstOffice */
    $firstOffice = Office::query()->where('code', '1402')->firstOrFail();

    // Find by UUID
    $resId = $this->getJson("/api/v1/offices/{$firstOffice->id}");
    $resId->assertStatus(200)
        ->assertHeader('ETag');
    expect($resId->json('data.code'))->toBe('1402');

    // Find by 4-digit code
    $resCode = $this->getJson('/api/v1/offices/1402');
    $resCode->assertStatus(200);
    expect($resCode->json('data.id'))->toBe($firstOffice->id);

    // 404 Not Found
    $notFound = $this->getJson('/api/v1/offices/non-existent-office-code');
    $notFound->assertStatus(404)
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code'])
        ->assertJson([
            'status' => 404,
            'code' => 'OFFICE_NOT_FOUND',
        ]);
});

it('supports ETag caching returning 304 Not Modified for office endpoints (TASK-043-T)', function (): void {
    $res = $this->getJson('/api/v1/offices?limit=5');
    $res->assertStatus(200);

    $etag = $res->headers->get('ETag');
    expect($etag)->not->toBeEmpty();

    $conditional = $this->withHeaders(['If-None-Match' => $etag])->getJson('/api/v1/offices?limit=5');
    $conditional->assertStatus(304);
    expect($conditional->getContent())->toBeEmpty();
});
