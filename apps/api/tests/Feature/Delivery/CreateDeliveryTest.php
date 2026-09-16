<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

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
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->officeA = Office::query()->create([
        'code' => '8001',
        'name' => 'دفتر پیشخوان مرکزی تهران',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->officeB = Office::query()->create([
        'code' => '8002',
        'name' => 'دفتر پیشخوان نیاوران',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_central';
    $this->operatorA->password_hash = Hash::make('Password123!');
    $this->operatorA->full_name = 'سهراب احمدی';
    $this->operatorA->national_id = '0089876541';
    $this->operatorA->mobile = '09121234567';
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->counter_number = 1;
    $this->operatorA->is_active = true;
    $this->operatorA->save();

    $this->operatorB = new Operator;
    $this->operatorB->office_id = $this->officeB->id;
    $this->operatorB->username = 'op_niavaran';
    $this->operatorB->password_hash = Hash::make('Password123!');
    $this->operatorB->full_name = 'مریم کریمی';
    $this->operatorB->national_id = '0089876542';
    $this->operatorB->mobile = '09127654321';
    $this->operatorB->role = OperatorRole::OPERATOR;
    $this->operatorB->counter_number = 1;
    $this->operatorB->is_active = true;
    $this->operatorB->save();

    $this->citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09129998877'),
        'mobile_encrypted' => 'enc:09129998877',
        'national_id_hash' => hash('sha256', '0010350801'),
        'national_id_encrypted' => 'enc:0010350801',
        'full_name' => 'داوود مرادی',
        'tier' => CitizenTier::SILVER->value,
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-del-test',
        'title' => 'خدمات هویتی و ثبت',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'صدور المثنی کارت هوشمند ملی',
        'slug' => 'del-national-id-card',
        'description' => 'سرویس صدور و تحویل کارت هوشمند ملی با پیک اختصاصی',
        'tags' => ['identity', 'delivery'],
        'fee_rials' => 600000,
        'office_share_percent' => 80,
        'is_active' => true,
    ]);
});

function createDeliveryTestCase(Citizen $citizen, Service $service, Office $office, CaseStatus $status): CaseRequest
{
    CaseRequest::$allowDirectStatusAssignment = true;

    $case = CaseRequest::query()->create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-DEL-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'dispatch_mode' => 'manual',
        'office_id' => $office->id,
        'status' => $status,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::COURIER,
        'fee_paid_rials' => 600000,
    ]);

    CaseRequest::$allowDirectStatusAssignment = false;

    return $case;
}

test('rejects delivery creation if case is in a status other than ready_for_issue with CASE_INVALID_TRANSITION', function (): void {
    Queue::fake();

    $case = createDeliveryTestCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson('/api/v1/deliveries', [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SMART_CARD->value,
        'destination_address' => 'تهران، خیابان شریعتی، کوچه بهار، پلاک ۱۰',
        'destination_postal_code' => '1234567890',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    $response->assertStatus(422);
    $json = $response->json();
    expect($json['code'])->toBe('CASE_INVALID_TRANSITION');

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::EXPERT_REVIEW);
    expect(DeliveryRequest::query()->count())->toBe(0);
});

test('enforces horizontal isolation returning 404 if operator belongs to a different office', function (): void {
    Queue::fake();

    $case = createDeliveryTestCase($this->citizen, $this->service, $this->officeA, CaseStatus::READY_FOR_ISSUE);

    Sanctum::actingAs($this->operatorB, ['*']);

    $response = $this->postJson('/api/v1/deliveries', [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SMART_CARD->value,
        'destination_address' => 'تهران، خیابان آزادی، پلاک ۲۰',
        'destination_postal_code' => '1234567890',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    $response->assertNotFound();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::READY_FOR_ISSUE);
    expect(DeliveryRequest::query()->count())->toBe(0);
});

test('creates delivery request, transitions case to delivering, and generates tracking barcode', function (): void {
    Queue::fake();

    $case = createDeliveryTestCase($this->citizen, $this->service, $this->officeA, CaseStatus::READY_FOR_ISSUE);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson('/api/v1/deliveries', [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SMART_CARD->value,
        'doc_type_name' => 'کارت ملی هوشمند متقاضی',
        'doc_serial_number' => 'SN-987654321',
        'destination_address' => 'تهران، بلوار کشاورز، خیابان ۱۶ آذر، پلاک ۵',
        'destination_postal_code' => '1417633445',
        'destination_zone' => 'منطقه ۶',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
        'shipping_fee_rials' => 450000,
        'payment_method' => DeliveryPaymentMethod::COD->value,
        'require_old_doc_return' => true,
        'is_sealed_pack' => true,
        'security_note' => 'تحویل صرفاً به شخص متقاضی پس از دریافت کارت قدیمی',
    ]);

    $response->assertCreated();
    $data = $response->json('data');

    expect($data['case_id'])->toBe($case->id)
        ->and($data['office_id'])->toBe($this->officeA->id)
        ->and($data['doc_type'])->toBe('smart_card')
        ->and($data['delivery_status'])->toBe('ready_for_dispatch')
        ->and($data['shipping_fee_rials'])->toBe(450000)
        ->and($data['payment_method'])->toBe('cod')
        ->and($data['require_old_doc_return'])->toBeTrue()
        ->and($data['is_sealed_pack'])->toBeTrue()
        ->and($data['tracking_barcode'])->toStartWith('DEL-');

    // Verify Case status changed to delivering with TurnOwner postal
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::DELIVERING)
        ->and($case->turn_owner)->toBe(TurnOwner::POSTAL);

    // Verify DeliveryEvent created
    $delivery = DeliveryRequest::query()->where('id', $data['id'])->firstOrFail();
    expect($delivery->events)->toHaveCount(1)
        ->and($delivery->events->first()->event)->toBe(DeliveryStatus::READY_FOR_DISPATCH->value);
});

test('dispatches delivery-otp SMS with 6-digit OTP and strictly omits OTP and otp_hash from API response', function (): void {
    Queue::fake();

    $case = createDeliveryTestCase($this->citizen, $this->service, $this->officeA, CaseStatus::READY_FOR_ISSUE);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson('/api/v1/deliveries', [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SEALED_DOSSIER->value,
        'destination_address' => 'تهران، میدان تجریش، خیابان دربند، پلاک ۴',
        'destination_postal_code' => '1987654321',
        'courier_type' => CourierType::SPECIAL_POST->value,
    ]);

    $response->assertCreated();
    $responseData = $response->json('data');

    // Strict Security Test (§7.4, §7.7): Neither raw OTP nor hash in API response
    expect($responseData)->not->toHaveKey('otp')
        ->and($responseData)->not->toHaveKey('raw_otp')
        ->and($responseData)->not->toHaveKey('otp_hash');

    $responseRawContent = (string) $response->getContent();
    expect($responseRawContent)->not->toContain('otp_hash');

    // Verify notification was dispatched
    $delivery = DeliveryRequest::query()->where('id', $responseData['id'])->firstOrFail();

    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use ($case, $delivery): bool {
        if ($job->citizenId !== $case->citizen_id) {
            return false;
        }

        if ($job->smsTemplate !== 'delivery-otp') {
            return false;
        }

        $code = (string) ($job->smsParams['code'] ?? '');
        $tracking = (string) ($job->smsParams['tracking'] ?? '');

        // 6-digit numeric OTP requirement
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        // Must match tracking barcode
        if ($tracking !== $delivery->tracking_barcode) {
            return false;
        }

        // Verification: The generated OTP must verify against the stored bcrypt hash
        return $delivery->verifyOtp($code);
    });
});

test('operator can view delivery request under their office via GET /deliveries/{id}', function (): void {
    Queue::fake();

    $case = createDeliveryTestCase($this->citizen, $this->service, $this->officeA, CaseStatus::READY_FOR_ISSUE);

    Sanctum::actingAs($this->operatorA, ['*']);

    $createResponse = $this->postJson('/api/v1/deliveries', [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::IDENTITY_BOOKLET->value,
        'destination_address' => 'تهران، میدان ونک، خیابان ملاصدرا',
        'destination_postal_code' => '1435678901',
        'courier_type' => CourierType::REGISTERED_POST->value,
    ]);

    $createResponse->assertCreated();
    $deliveryId = $createResponse->json('data.id');

    // 1. Same office operator can view
    $showResponse = $this->getJson("/api/v1/deliveries/{$deliveryId}");
    $showResponse->assertOk();
    expect($showResponse->json('data.id'))->toBe($deliveryId)
        ->and($showResponse->json('data'))->not->toHaveKey('otp_hash');

    // 2. Different office operator gets 404 (Horizontal Isolation)
    Sanctum::actingAs($this->operatorB, ['*']);
    $forbiddenShow = $this->getJson("/api/v1/deliveries/{$deliveryId}");
    $forbiddenShow->assertNotFound();
});
