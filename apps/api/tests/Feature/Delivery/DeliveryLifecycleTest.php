<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Application\Actions\CreateDeliveryRequestAction;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
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

    $this->office = Office::query()->create([
        'code' => '8010',
        'name' => 'دفتر پیشخوان فاطمی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->operator = new Operator;
    $this->operator->office_id = $this->office->id;
    $this->operator->username = 'op_fatemi';
    $this->operator->password_hash = Hash::make('Password123!');
    $this->operator->full_name = 'رضا کریمی';
    $this->operator->national_id = '0089876599';
    $this->operator->mobile = '09123334455';
    $this->operator->role = OperatorRole::OPERATOR;
    $this->operator->counter_number = 2;
    $this->operator->is_active = true;
    $this->operator->save();

    $this->citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09125556677'),
        'mobile_encrypted' => 'enc:09125556677',
        'national_id_hash' => hash('sha256', '0010350899'),
        'national_id_encrypted' => 'enc:0010350899',
        'full_name' => 'ناهید صادقی',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-lifecycle',
        'title' => 'خدمات ثبتی',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'صدور شناسنامه المثنی',
        'slug' => 'birth-cert-replacement',
        'description' => 'صدور شناسنامه المثنی و ارسال با پیک',
        'tags' => ['identity', 'delivery'],
        'fee_rials' => 500000,
        'office_share_percent' => 75,
        'is_active' => true,
    ]);
});

function createReadyCase(Citizen $citizen, Service $service, Office $office): CaseRequest
{
    CaseRequest::$allowDirectStatusAssignment = true;

    $case = CaseRequest::query()->create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-LFC-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'dispatch_mode' => 'manual',
        'office_id' => $office->id,
        'status' => CaseStatus::READY_FOR_ISSUE,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::COURIER,
        'fee_paid_rials' => 500000,
    ]);

    CaseRequest::$allowDirectStatusAssignment = false;

    return $case;
}

test('E2E scenario E11: create -> assign courier -> in transit -> wrong OTP rejected -> correct OTP -> delivered -> case completed', function (): void {
    Queue::fake();

    $case = createReadyCase($this->citizen, $this->service, $this->office);

    // 1. Create Delivery Request
    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::IDENTITY_BOOKLET->value,
        'destination_address' => 'تهران، خیابان فاطمی، کوچه هشتم، پلاک ۴',
        'destination_postal_code' => '1415678901',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    expect($delivery->delivery_status)->toBe(DeliveryStatus::READY_FOR_DISPATCH);
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::DELIVERING);

    // Extract OTP dispatched via notification
    $dispatchedOtp = null;
    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use (&$dispatchedOtp): bool {
        if ($job->smsTemplate === 'delivery-otp') {
            $dispatchedOtp = (string) $job->smsParams['code'];

            return true;
        }

        return false;
    });
    expect($dispatchedOtp)->not->toBeNull();

    // 2. Operator assigns courier
    Sanctum::actingAs($this->operator, ['*']);
    $assignResponse = $this->postJson("/api/v1/deliveries/{$delivery->id}/assign-courier", [
        'courier_name' => 'سعید اسماعیلی',
        'courier_phone' => '09124445566',
        'courier_plate' => '۱۲ ایران ۳۴۵ ب ۶۷',
    ]);
    $assignResponse->assertOk();
    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::COURIER_ASSIGNED)
        ->and($delivery->courier_name)->toBe('سعید اسماعیلی');

    // 3. Operator marks in-transit
    $transitResponse = $this->postJson("/api/v1/deliveries/{$delivery->id}/in-transit", [
        'location' => 'میدان فاطمی به سمت مقصد',
    ]);
    $transitResponse->assertOk();
    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::IN_TRANSIT)
        ->and($delivery->dispatched_at)->not->toBeNull();

    // 4. Semi-public Courier confirms with wrong OTP -> 422 rejected
    $wrongResponse = $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", [
        'otp' => '000000',
    ]);
    $wrongResponse->assertStatus(422);
    expect($wrongResponse->json('code'))->toBe('DELIVERY_OTP_INVALID');
    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::IN_TRANSIT);

    // 5. Semi-public Courier confirms with correct OTP -> 200 OK delivered
    $confirmResponse = $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", [
        'otp' => $dispatchedOtp,
    ]);
    $confirmResponse->assertOk();

    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::DELIVERED)
        ->and($delivery->delivered_at)->not->toBeNull()
        ->and($delivery->otp_hash)->toBeNull(); // OTP invalidated

    // Verify Case transitioned to completed
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::COMPLETED)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM)
        ->and($case->closed_at)->not->toBeNull();
});

test('failed delivery returns package to office and reverts case to ready_for_issue', function (): void {
    Queue::fake();

    $case = createReadyCase($this->citizen, $this->service, $this->office);

    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SMART_CARD->value,
        'destination_address' => 'تهران، خیابان ولیعصر، پلاک ۵۰',
        'destination_postal_code' => '1515678901',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    Sanctum::actingAs($this->operator, ['*']);

    // Mark failed
    $failResponse = $this->postJson("/api/v1/deliveries/{$delivery->id}/fail", [
        'reason' => 'گیرنده در آدرس حضور نداشت و تلفن را پاسخ نداد',
        'location' => 'دفتر فاطمی',
    ]);
    $failResponse->assertOk();

    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::FAILED)
        ->and($delivery->otp_hash)->toBeNull();

    // Invariant test: Case reverted to ready_for_issue
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::READY_FOR_ISSUE)
        ->and($case->turn_owner)->toBe(TurnOwner::OFFICE);
});

test('3 incorrect OTP attempts regenerates OTP and sends new SMS', function (): void {
    Queue::fake();

    $case = createReadyCase($this->citizen, $this->service, $this->office);

    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::OFFICIAL_CERTIFICATE->value,
        'destination_address' => 'تهران، بلوار کشاورز، پلاک ۸',
        'destination_postal_code' => '1415678999',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    $firstOtp = null;
    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use (&$firstOtp): bool {
        if ($job->smsTemplate === 'delivery-otp') {
            $firstOtp = (string) $job->smsParams['code'];

            return true;
        }

        return false;
    });

    // Attempt 1: Wrong
    $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", ['otp' => '111111'])->assertStatus(422);

    // Attempt 2: Wrong
    $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", ['otp' => '222222'])->assertStatus(422);

    // Attempt 3: Wrong -> triggers regeneration
    $thirdAttempt = $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", ['otp' => '333333']);
    $thirdAttempt->assertStatus(422);
    expect($thirdAttempt->json('detail'))->toContain('به دلیل ۳ تلاش ناموفق، کد جدید برای گیرنده پیامک شد');

    $delivery->refresh();
    // Old OTP must no longer verify
    expect($delivery->verifyOtp((string) $firstOtp))->toBeFalse();

    // Second OTP dispatched via notification
    $secondOtp = null;
    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use (&$secondOtp, $firstOtp): bool {
        if ($job->smsTemplate === 'delivery-otp' && (string) $job->smsParams['code'] !== $firstOtp) {
            $secondOtp = (string) $job->smsParams['code'];

            return true;
        }

        return false;
    });

    expect($secondOtp)->not->toBeNull();
    expect($delivery->verifyOtp((string) $secondOtp))->toBeTrue();

    // Confirming with the new OTP succeeds
    $confirmWithNew = $this->postJson("/api/v1/deliveries/{$delivery->id}/confirm", ['otp' => $secondOtp]);
    $confirmWithNew->assertOk();

    $delivery->refresh();
    expect($delivery->delivery_status)->toBe(DeliveryStatus::DELIVERED);
});
