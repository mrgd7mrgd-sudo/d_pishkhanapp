<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'خدمات هویتی و سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->citizenA = new Citizen;
    $this->citizenA->national_id = '0010350802';
    $this->citizenA->mobile = '09121112233';
    $this->citizenA->full_name = 'شهروند الف';
    $this->citizenA->tier = CitizenTier::BRONZE;
    $this->citizenA->province_code = 'THR';
    $this->citizenA->save();

    $this->citizenB = new Citizen;
    $this->citizenB->national_id = '0020450903';
    $this->citizenB->mobile = '09129998877';
    $this->citizenB->full_name = 'شهروند ب';
    $this->citizenB->tier = CitizenTier::SILVER;
    $this->citizenB->province_code = 'THR';
    $this->citizenB->save();

    $this->service = Service::query()->create([
        'category_id' => 'identity',
        'slug' => 'svc_identity_smart_card',
        'title' => 'صدور و تعویض کارت هوشمند ملی (بیومتریک)',
        'description' => 'درخواست کارت ملی هوشمند',
        'tags' => ['in-person'],
        'estimated_days_min' => 7,
        'estimated_days_max' => 14,
        'fee_rials' => 3400000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->office = Office::query()->create([
        'code' => 'off_thr_0142',
        'name' => 'دفتر پیشخوان دولت ولیعصر',
        'phone' => '۰۲۱۸۸۹۰۱۲۳۴',
        'rating' => 4.9,
    ]);

    $this->ledgerService = app(LedgerService::class);
});

function createSampleActionRequiredCase(Citizen $citizen, Service $service, Office $office): CaseRequest
{
    $case = new CaseRequest([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CR-1405-99841',
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::ACTION_REQUIRED,
        'turn_owner' => TurnOwner::CITIZEN,
        'current_step' => 3,
        'total_steps' => 6,
        'fee_paid_rials' => 3400000,
        'office_share_rials' => 1700000,
        'platform_share_rials' => 1700000,
        'delivery_preference' => DeliveryPreference::COURIER,
        'sla_deadline_at' => Carbon::now()->addHours(72),
    ]);
    $case->save();

    // 2 documents
    CaseDocument::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'document_type_code' => 'DOC_BIRTH_CERT',
        'version' => 1,
        'status' => CaseDocumentStatus::REJECTED,
        'reason_code' => 'DOC_BLUR',
        'storage_key' => 'uploads/nid_v1.enc',
        'encrypted_data_key' => 'enc-key-1',
        'content_sha256' => hash('sha256', 'doc1'),
        'mime_type' => 'image/jpeg',
        'quality_warnings' => ['blur'],
        'uploaded_at' => Carbon::now()->subHours(20),
    ]);

    CaseDocument::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'document_type_code' => 'DOC_POSTAL_CODE',
        'version' => 1,
        'status' => CaseDocumentStatus::VERIFIED,
        'reason_code' => null,
        'storage_key' => 'uploads/post_v1.enc',
        'encrypted_data_key' => 'enc-key-2',
        'content_sha256' => hash('sha256', 'doc2'),
        'mime_type' => 'image/jpeg',
        'quality_warnings' => [],
        'uploaded_at' => Carbon::now()->subHours(19),
    ]);

    // Timeline steps
    CaseTimelineStep::query()->create([
        'id' => 'tl_1',
        'case_id' => $case->id,
        'sequence' => 1,
        'title' => 'ثبت درخواست',
        'description' => 'درخواست ثبت و هزینه کسر شد.',
        'status' => TimelineStepStatus::DONE,
        'turn_owner' => TurnOwner::CITIZEN,
        'turn_owner_label' => 'شهروند',
        'actor_type' => TimelineActorType::CITIZEN,
        'occurred_at' => Carbon::now()->subHours(20),
        'duration_actual_minutes' => 0,
        'duration_typical_minutes' => 1,
    ]);

    CaseTimelineStep::query()->create([
        'id' => 'tl_2',
        'case_id' => $case->id,
        'sequence' => 2,
        'title' => 'اختصاص دفتر',
        'description' => 'دفتر ولیعصر پرونده را پذیرفت.',
        'status' => TimelineStepStatus::DONE,
        'turn_owner' => TurnOwner::OFFICE,
        'turn_owner_label' => 'دفتر پیشخوان',
        'actor_type' => TimelineActorType::OPERATOR,
        'occurred_at' => Carbon::now()->subHours(19),
        'duration_actual_minutes' => 1,
        'duration_typical_minutes' => 5,
    ]);

    CaseTimelineStep::query()->create([
        'id' => 'tl_3',
        'case_id' => $case->id,
        'sequence' => 3,
        'title' => 'بررسی کارشناس',
        'description' => 'مدرک شناسنامه به دلیل تاری بازگردانده شد.',
        'status' => TimelineStepStatus::WARNING,
        'turn_owner' => TurnOwner::CITIZEN,
        'turn_owner_label' => 'شهروند',
        'actor_type' => TimelineActorType::CITIZEN,
        'office_note' => 'لطفاً در نور کافی عکس بگیرید.',
        'occurred_at' => Carbon::now()->subHours(10),
        'duration_typical_minutes' => 180,
    ]);

    // Case Return record
    $returnReason = ReturnReason::query()->where('code', ReturnReasonCode::DOC_BLUR->value)->firstOrFail();
    CaseReturn::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'return_reason_id' => $returnReason->id,
        'reason_code' => ReturnReasonCode::DOC_BLUR->value,
        'target_document_type_code' => 'DOC_BIRTH_CERT',
        'operator_note' => 'لطفاً صفحه اول شناسنامه، کامل و بدون انعکاس نور.',
        'deadline_hours' => 72,
    ]);

    return $case;
}

test('GET /cases/{trackingCode} strictly matches Architecture sample 6 structure and data', function (): void {
    $case = createSampleActionRequiredCase($this->citizenA, $this->service, $this->office);

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/cases/{$case->tracking_code}");

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and($data['id'])->toBe($case->id)
        ->and($data['tracking_code'])->toBe('CR-1405-99841')
        ->and($data['status'])->toBe('action_required')
        ->and($data['turn_owner'])->toBe('citizen')
        ->and($data['turn_owner_label'])->toBe('نوبت شماست')
        ->and($data['last_change_text'])->toContain('دفتر پیشخوان دولت ولیعصر مدرک شما را برای اصلاح بازگرداند')
        ->and($data['service']['id'])->toBe($this->service->id)
        ->and($data['service']['title'])->toBe('صدور و تعویض کارت هوشمند ملی (بیومتریک)')
        ->and($data['assigned_office']['name'])->toBe('دفتر پیشخوان دولت ولیعصر')
        ->and($data['current_step'])->toBe(3)
        ->and($data['total_steps'])->toBe(6)
        ->and($data['return_reason']['code'])->toBe('DOC_BLUR')
        ->and($data['return_reason']['operator_note'])->toBe('لطفاً صفحه اول شناسنامه، کامل و بدون انعکاس نور.')
        ->and($data['sla']['phase'])->toBe('citizen_fix')
        ->and($data['sla']['remaining_seconds'])->toBeGreaterThan(0)
        ->and($data['documents'])->toHaveCount(2)
        ->and($data['timeline'])->toHaveCount(3)
        ->and($data['available_actions'])->toBe(['upload_fix_document', 'open_chat', 'cancel_case'])
        ->and($data['realtime_channel'])->toBe('private-case.'.$case->id);
});

test('citizen A cannot view citizen B case and receives 404', function (): void {
    $caseB = createSampleActionRequiredCase($this->citizenB, $this->service, $this->office);

    // Citizen A tries to access Citizen B's tracking code
    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/cases/{$caseB->tracking_code}");

    $response->assertStatus(404);
});

test('available_actions returns correct options across all 11 lifecycle statuses', function (): void {
    $statusesExpected = [
        CaseStatus::SEARCHING_OFFICE->value => ['cancel_case'],
        CaseStatus::ASSIGNED_TO_OFFICE->value => ['open_chat', 'cancel_case'],
        CaseStatus::EXPERT_REVIEW->value => ['open_chat'],
        CaseStatus::ACTION_REQUIRED->value => ['upload_fix_document', 'open_chat', 'cancel_case'],
        CaseStatus::GOVERNMENT_INQUIRY->value => ['open_chat'],
        CaseStatus::READY_FOR_ISSUE->value => ['view_receipt', 'open_chat'],
        CaseStatus::DELIVERING->value => ['track_courier', 'open_chat'],
        CaseStatus::COMPLETED->value => ['download_result', 'rate_service'],
        CaseStatus::REJECTED->value => ['view_rejection_reason', 'submit_objection'],
        CaseStatus::CANCELLED->value => ['view_cancellation_details'],
        CaseStatus::DRAFT->value => ['edit_case', 'submit_case', 'discard_case'],
    ];

    foreach ($statusesExpected as $status => $expectedActions) {
        $case = new CaseRequest([
            'id' => (string) Str::uuid(),
            'tracking_code' => 'CR-1405-'.Str::random(5),
            'citizen_id' => $this->citizenA->id,
            'service_id' => $this->service->id,
            'province_code' => 'THR',
            'status' => CaseStatus::from($status),
            'turn_owner' => TurnOwner::SYSTEM,
            'delivery_preference' => DeliveryPreference::IN_PERSON,
        ]);
        $case->save();

        $response = $this->actingAs($this->citizenA, 'sanctum')
            ->getJson("/api/v1/cases/{$case->tracking_code}");

        $response->assertStatus(200);
        expect($response->json('data.available_actions'))->toBe($expectedActions);
    }
});

test('GET /cases list never leaks national ID or sensitive PII', function (): void {
    createSampleActionRequiredCase($this->citizenA, $this->service, $this->office);

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/cases');

    $response->assertStatus(200);
    $content = (string) $response->getContent();

    // PII Minimization (§7.7): No raw national ID or hashes in listing
    expect($content)->not->toContain('0010350802')
        ->and($content)->not->toContain('national_id')
        ->and($content)->not->toContain('mobile_hash');
});

test('GET /cases filters by status and supports cursor pagination', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $case = new CaseRequest([
            'id' => (string) Str::uuid(),
            'tracking_code' => 'CR-1405-F'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'citizen_id' => $this->citizenA->id,
            'service_id' => $this->service->id,
            'province_code' => 'THR',
            'status' => $i < 2 ? CaseStatus::COMPLETED : CaseStatus::SEARCHING_OFFICE,
            'turn_owner' => TurnOwner::SYSTEM,
            'delivery_preference' => DeliveryPreference::IN_PERSON,
        ]);
        $case->save();
    }

    // Filter by searching_office
    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/cases?status=searching_office&per_page=2');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');

    expect($response->json('meta.next_cursor'))->not->toBeNull();
});

test('POST /cases/{id}/cancel transitions case to cancelled and performs reverse ledger refund', function (): void {
    // 1. Setup citizen wallet with balance
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizenA->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    // Initial deduction happened during creation: 3,400,000 in escrow
    $this->ledgerService->recordTransaction(
        reference: 'SETUP-ESCROW',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 3400000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 3400000),
        ]
    );

    // Citizen wallet is 0, escrow is 3,400,000
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(-3400000);
    expect($this->ledgerService->getBalanceRials($escrow))->toBe(3400000);

    // Create case in searching_office
    $case = new CaseRequest([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CR-1405-CANCEL1',
        'citizen_id' => $this->citizenA->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'fee_paid_rials' => 3400000,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);
    $case->save();

    // Cancel case
    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->withHeader('Idempotency-Key', 'IDEM-CANCEL-'.Str::random(6))
        ->postJson("/api/v1/cases/{$case->id}/cancel");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    // Verify refund in ledger: Escrow debited 3,400,000 -> 0; Wallet credited 3,400,000 -> 0
    expect($this->ledgerService->getBalanceRials($escrow))->toBe(0);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(0);

    // Timeline step added for cancellation
    expect($case->timelineSteps()->where('title', 'انصراف از درخواست')->exists())->toBeTrue();
});

test('GET /cases has constant query count preventing N+1 problems', function (): void {
    for ($i = 0; $i < 6; $i++) {
        $case = new CaseRequest([
            'id' => (string) Str::uuid(),
            'tracking_code' => 'CR-1405-N'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'citizen_id' => $this->citizenA->id,
            'service_id' => $this->service->id,
            'office_id' => $this->office->id,
            'province_code' => 'THR',
            'status' => CaseStatus::SEARCHING_OFFICE,
            'turn_owner' => TurnOwner::SYSTEM,
            'delivery_preference' => DeliveryPreference::IN_PERSON,
        ]);
        $case->save();
    }

    DB::enableQueryLog();

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/cases?per_page=5');

    $response->assertStatus(200);

    $queries = DB::getQueryLog();
    // Strictly <= 6 queries regardless of number of cases
    expect(count($queries))->toBeLessThanOrEqual(6);
});
