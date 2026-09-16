<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryPaymentMethod;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryEvent;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\Carbon;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(ProvinceSeeder::class);

    $this->office = Office::query()->create([
        'code' => '1001',
        'name' => 'دفتر پیشخوان انقلاب',
        'is_online' => true,
    ]);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند نمونه تحویل';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->service = Service::query()->create([
        'id' => 'svc_delivery_test',
        'category_id' => 'identity',
        'slug' => 'svc-delivery-test',
        'title' => 'خدمت کارت هوشمند تحویلی',
        'description' => 'تست تحویل',
        'tags' => ['in-person'],
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
        'fee_rials' => 2_000_000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->case = CaseRequest::query()->create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::READY_FOR_ISSUE,
        'tracking_code' => 'CR-1405-DELIV01',
        'fee_paid_rials' => 2_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);
});

test('delivery_requests table stores delivery request and raw OTP is never stored in any column (TASK-094-T, §7.4)', function (): void {
    $rawOtp = '482910';
    $delivery = DeliveryRequest::query()->create([
        'case_id' => $this->case->id,
        'office_id' => $this->office->id,
        'doc_type' => DeliveryDocType::SMART_CARD,
        'doc_type_name' => 'کارت هوشمند ملی',
        'doc_serial_number' => 'NID-99881122',
        'destination_address' => 'تهران، خیابان انقلاب، پلاک ۱۲',
        'destination_postal_code' => '1311122233',
        'destination_zone' => 'منطقه ۶',
        'courier_type' => CourierType::EXPRESS_COURIER,
        'delivery_status' => DeliveryStatus::READY_FOR_DISPATCH,
        'shipping_fee_rials' => 450_000,
        'payment_method' => DeliveryPaymentMethod::PREPAID,
        'require_old_doc_return' => true,
        'is_sealed_pack' => true,
        'security_note' => 'محرمانه — فقط به صاحب شناسنامه تحویل گردد',
        'tracking_barcode' => 'BARCODE-1405-99881',
    ]);

    $delivery->setOtp($rawOtp, Carbon::now()->addHours(24));
    $delivery->save();

    // Verify raw OTP is not stored
    $fresh = DeliveryRequest::query()->findOrFail($delivery->id);
    expect($fresh->otp_hash)->not->toBeNull()
        ->and($fresh->otp_hash)->not->toBe($rawOtp)
        ->and(Hash::check($rawOtp, $fresh->otp_hash))->toBeTrue()
        ->and($fresh->verifyOtp($rawOtp))->toBeTrue()
        ->and($fresh->verifyOtp('000000'))->toBeFalse();

    // Verify hidden from array/JSON serialization
    $array = $fresh->toArray();
    expect(array_key_exists('otp_hash', $array))->toBeFalse();
});

test('all 6 delivery document types and 5 delivery statuses are supported in schema and enums', function (): void {
    expect(DeliveryDocType::values())->toBe([
        'smart_card',
        'identity_booklet',
        'official_certificate',
        'sealed_dossier',
        'business_license',
        'postal_packet',
    ])->and(count(DeliveryDocType::cases()))->toBe(6);

    expect(DeliveryStatus::values())->toBe([
        'ready_for_dispatch',
        'courier_assigned',
        'in_transit',
        'delivered',
        'failed',
    ])->and(count(DeliveryStatus::cases()))->toBe(5);

    expect(CourierType::values())->toBe([
        'express_courier',
        'special_post',
        'registered_post',
    ])->and(count(CourierType::cases()))->toBe(3);

    expect(DeliveryPaymentMethod::values())->toBe([
        'cod',
        'prepaid',
        'office_wallet',
    ])->and(count(DeliveryPaymentMethod::cases()))->toBe(3);
});

test('duplicate tracking_barcode violates unique constraint and throws QueryException', function (): void {
    $barcode = 'BARCODE-UNIQUE-12345';

    DeliveryRequest::query()->create([
        'case_id' => $this->case->id,
        'office_id' => $this->office->id,
        'doc_type' => DeliveryDocType::IDENTITY_BOOKLET,
        'doc_type_name' => 'شناسنامه',
        'destination_address' => 'تهران، میدان آزادی',
        'destination_postal_code' => '1411122233',
        'courier_type' => CourierType::SPECIAL_POST,
        'delivery_status' => DeliveryStatus::READY_FOR_DISPATCH,
        'shipping_fee_rials' => 300_000,
        'payment_method' => DeliveryPaymentMethod::PREPAID,
        'tracking_barcode' => $barcode,
    ]);

    expect(function () use ($barcode): void {
        DeliveryRequest::query()->create([
            'case_id' => $this->case->id,
            'office_id' => $this->office->id,
            'doc_type' => DeliveryDocType::IDENTITY_BOOKLET,
            'doc_type_name' => 'شناسنامه',
            'destination_address' => 'تهران، میدان ونک',
            'destination_postal_code' => '1911122233',
            'courier_type' => CourierType::SPECIAL_POST,
            'delivery_status' => DeliveryStatus::READY_FOR_DISPATCH,
            'shipping_fee_rials' => 300_000,
            'payment_method' => DeliveryPaymentMethod::PREPAID,
            'tracking_barcode' => $barcode,
        ]);
    })->toThrow(QueryException::class);
});

test('delivery_events logs milestones and maintains proper relationships with delivery request', function (): void {
    $delivery = DeliveryRequest::query()->create([
        'case_id' => $this->case->id,
        'office_id' => $this->office->id,
        'doc_type' => DeliveryDocType::OFFICIAL_CERTIFICATE,
        'doc_type_name' => 'دانشنامه رسمی',
        'destination_address' => 'تهران، بلوار کشاورز',
        'destination_postal_code' => '1411133344',
        'courier_type' => CourierType::EXPRESS_COURIER,
        'delivery_status' => DeliveryStatus::COURIER_ASSIGNED,
        'tracking_barcode' => 'BARCODE-EVENT-TEST-1',
    ]);

    $event1 = DeliveryEvent::query()->create([
        'delivery_request_id' => $delivery->id,
        'event' => 'courier_assigned',
        'location' => 'دفتر پیشخوان انقلاب',
        'note' => 'بسته به پیک شماره ۱۲ تحویل شد',
        'occurred_at' => Carbon::now()->subMinutes(30),
    ]);

    $event2 = DeliveryEvent::query()->create([
        'delivery_request_id' => $delivery->id,
        'event' => 'in_transit',
        'location' => 'میدان ولیعصر',
        'note' => 'پیک در مسیر مقصد است',
        'occurred_at' => Carbon::now()->subMinutes(10),
    ]);

    expect($delivery->events)->toHaveCount(2)
        ->and($delivery->case->id)->toBe($this->case->id)
        ->and($delivery->office->id)->toBe($this->office->id)
        ->and($event1->deliveryRequest->id)->toBe($delivery->id);
});

test('exact parity between PHP delivery enums and packages/domain TypeScript delivery enums (§6.3, DoD: EnumParity)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../../packages/domain/src/delivery.ts');
    expect($tsPath)->not->toBeFalse();

    $content = File::get($tsPath);

    // Extract TypeScript string arrays
    $extractArray = function (string $name) use ($content): array {
        $pattern = '/export\s+const\s+'.preg_quote($name, '/').'\s*=\s*\[(.*?)\]\s*as\s*const;/s';
        if (! preg_match($pattern, $content, $matches)) {
            return [];
        }
        preg_match_all("/['\"]([^'\"]+)['\"]/", $matches[1] ?? '', $valMatches);

        return $valMatches[1] ?? [];
    };

    expect(DeliveryDocType::values())->toBe($extractArray('DELIVERY_DOC_TYPES'));
    expect(DeliveryStatus::values())->toBe($extractArray('DELIVERY_STATUSES'));
    expect(CourierType::values())->toBe($extractArray('COURIER_TYPES'));
    expect(DeliveryPaymentMethod::values())->toBe($extractArray('DELIVERY_PAYMENT_METHODS'));
});
