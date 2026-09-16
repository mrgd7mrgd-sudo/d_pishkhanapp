<?php

declare(strict_types=1);

namespace Tests\Contract\Government;

use App\Integration\Government\Drivers\HttpDriver;
use App\Integration\Government\Drivers\Simulator\SimulatorPostalClient;
use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;
use App\Integration\Government\PostalClient;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryPaymentMethod;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    putenv('SIMULATOR_FAST_TEST=true');
    $this->seed(ProvinceSeeder::class);
});

test('PostalClient simulator responses strictly match PostalAddress, ShipmentResult, and TrackingResult DTO shapes (TASK-098-T)', function (): void {
    $client = new SimulatorPostalClient;
    expect($client)->toBeInstanceOf(PostalClient::class);

    // 1. validatePostalCode -> PostalAddress DTO
    $addr = $client->validatePostalCode('1234567890');
    expect($addr)->toBeInstanceOf(PostalAddress::class)
        ->and($addr->isValid)->toBeTrue()
        ->and($addr->postalCode)->toBe('1234567890')
        ->and($addr->province)->not->toBeEmpty()
        ->and($addr->city)->not->toBeEmpty()
        ->and($addr->address)->not->toBeEmpty();

    // 2. createShipment -> ShipmentResult DTO
    $shipment = $client->createShipment(new ShipmentRequest(
        caseId: (string) Str::uuid(),
        originOfficeId: (string) Str::uuid(),
        destinationPostalCode: '1234567890',
        destinationAddress: 'تهران، خیابان ولیعصر',
        recipientName: 'علی رضایی',
        recipientMobile: '09121112233',
        packageType: 'smart_card'
    ));
    expect($shipment)->toBeInstanceOf(ShipmentResult::class)
        ->and($shipment->isSuccess)->toBeTrue()
        ->and($shipment->barcode)->not->toBeNull()
        ->and($shipment->trackingUrl)->toContain('https://tracking.post.ir');

    // 3. trackShipment -> TrackingResult DTO
    $tracking = $client->trackShipment('1098123456789012');
    expect($tracking)->toBeInstanceOf(TrackingResult::class)
        ->and($tracking->barcode)->toBe('1098123456789012')
        ->and($tracking->status)->not->toBeEmpty()
        ->and($tracking->history)->toBeArray();
});

test('invalid postal codes are correctly rejected', function (): void {
    $client = new SimulatorPostalClient;

    // Simulator deterministic rule: postal code ending in '1' or '4' is invalid
    $invalid1 = $client->validatePostalCode('1234567891');
    expect($invalid1->isValid)->toBeFalse();

    $invalid4 = $client->validatePostalCode('1234567894');
    expect($invalid4->isValid)->toBeFalse();

    // HttpDriver validates 10-digit format
    $httpDriver = new HttpDriver;
    $badLength = $httpDriver->validatePostalCode('123');
    expect($badLength->isValid)->toBeFalse();

    $badChars = $httpDriver->validatePostalCode('12345abcde');
    expect($badChars->isValid)->toBeFalse();
});

test('SyncPostTrackingCommand synchronizes in-transit post shipments and creates delivery_events', function (): void {
    $office = Office::query()->create([
        'code' => '8040',
        'name' => 'دفتر پیشخوان انقلاب',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09123331122'),
        'mobile_encrypted' => 'enc:09123331122',
        'national_id_hash' => hash('sha256', '0010350866'),
        'national_id_encrypted' => 'enc:0010350866',
        'full_name' => 'سعید قاسمی',
        'tier' => CitizenTier::SILVER->value,
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-post-test',
        'title' => 'خدمات پستی',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'خدمت پستی هوشمند',
        'slug' => 'post-smart-svc',
        'description' => 'تست خدمت پستی',
        'tags' => ['post'],
        'fee_rials' => 200000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    CaseRequest::$allowDirectStatusAssignment = true;
    $case = CaseRequest::query()->create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-PST-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'dispatch_mode' => 'manual',
        'office_id' => $office->id,
        'status' => CaseStatus::DELIVERING,
        'turn_owner' => TurnOwner::POSTAL,
        'delivery_preference' => DeliveryPreference::POST,
        'fee_paid_rials' => 200000,
    ]);
    CaseRequest::$allowDirectStatusAssignment = false;

    // Create an in_transit postal delivery
    $delivery = DeliveryRequest::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'office_id' => $office->id,
        'doc_type' => DeliveryDocType::POSTAL_PACKET,
        'doc_type_name' => 'بسته پستی مدارک',
        'destination_address' => 'تهران، خیابان آزادی، پلاک ۱۰',
        'destination_postal_code' => '1345678901',
        'courier_type' => CourierType::SPECIAL_POST,
        'delivery_status' => DeliveryStatus::IN_TRANSIT,
        'payment_method' => DeliveryPaymentMethod::PREPAID,
        'tracking_barcode' => '1098765432101234',
        'dispatched_at' => now(),
    ]);

    expect($delivery->events)->toHaveCount(0);

    // Run sync command
    $exitCode = Artisan::call('delivery:sync-post-tracking');
    expect($exitCode)->toBe(0);

    // Verify delivery_events were created
    $delivery->refresh();
    expect($delivery->events)->not->toBeEmpty();

    $latestEvent = $delivery->events->first();
    expect($latestEvent->event)->toBe('in_transit')
        ->and($latestEvent->location)->toBe('مرکز تجزیه و مبادلات پستی تهران')
        ->and($latestEvent->note)->toContain('همگام‌سازی خودکار پستی');
});

test('swapping POST_DRIVER=http resolves HttpDriver without code change', function (): void {
    // 1. Simulator driver by default
    putenv('POST_DRIVER=simulator');
    app()->forgetInstance(PostalClient::class);
    $defaultClient = app(PostalClient::class);
    expect($defaultClient)->toBeInstanceOf(SimulatorPostalClient::class);

    // 2. Swapping to http
    putenv('POST_DRIVER=http');
    app()->forgetInstance(PostalClient::class);
    $httpClient = app(PostalClient::class);
    expect($httpClient)->toBeInstanceOf(HttpDriver::class)
        ->and($httpClient)->toBeInstanceOf(PostalClient::class);

    // Cleanup env
    putenv('POST_DRIVER=simulator');
});
