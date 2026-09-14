<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Jobs\GovernmentInquiryJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);

    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);

    $this->officeA = Office::query()->create([
        'code' => '7001',
        'name' => 'دفتر پیشخوان بهشتی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->officeB = Office::query()->create([
        'code' => '7002',
        'name' => 'دفتر پیشخوان مطهری',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_beheshti';
    $this->operatorA->password_hash = Hash::make('Secret123!');
    $this->operatorA->full_name = 'علی رضایی';
    $this->operatorA->national_id = '0081234561';
    $this->operatorA->mobile = '09121110001';
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->counter_number = 1;
    $this->operatorA->is_active = true;
    $this->operatorA->save();
    $this->operatorA->assignRole('office_operator');

    $this->managerA = new Operator;
    $this->managerA->office_id = $this->officeA->id;
    $this->managerA->username = 'mgr_beheshti';
    $this->managerA->password_hash = Hash::make('Secret123!');
    $this->managerA->full_name = 'حسین مدیر';
    $this->managerA->national_id = '0081234569';
    $this->managerA->mobile = '09121110009';
    $this->managerA->role = OperatorRole::MANAGER;
    $this->managerA->counter_number = 9;
    $this->managerA->is_active = true;
    $this->managerA->save();
    $this->managerA->assignRole('office_manager');

    $this->operatorB = new Operator;
    $this->operatorB->office_id = $this->officeB->id;
    $this->operatorB->username = 'op_motahari';
    $this->operatorB->password_hash = Hash::make('Secret123!');
    $this->operatorB->full_name = 'زهرا حسینی';
    $this->operatorB->national_id = '0081234562';
    $this->operatorB->mobile = '09121110002';
    $this->operatorB->role = OperatorRole::OPERATOR;
    $this->operatorB->counter_number = 2;
    $this->operatorB->is_active = true;
    $this->operatorB->save();
    $this->operatorB->assignRole('office_operator');

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'احمد محمدی';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->category = ServiceCategory::query()->create([
        'id' => 'cat-desk-test',
        'title' => 'خدمات پیشخوان',
    ]);

    $this->service = Service::query()->create([
        'slug' => 'desk-test-service',
        'title' => 'خدمت تست میز کار',
        'description' => 'توضیحات تست میز کار',
        'tags' => ['in-person'],
        'category_id' => $this->category->id,
        'fee_rials' => 250_000,
    ]);
});

function createTestDeskCase(
    Citizen $citizen,
    Service $service,
    Office $office,
    CaseStatus $status = CaseStatus::ASSIGNED_TO_OFFICE,
    TurnOwner $turnOwner = TurnOwner::OFFICE
): CaseRequest {
    return CaseRequest::query()->create([
        'tracking_code' => 'CR-DSK-'.Str::upper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => $status,
        'turn_owner' => $turnOwner,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'fee_paid_rials' => 250_000,
    ]);
}

it('lists cases for the operator office with status and search filtering', function (): void {
    $caseA1 = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);
    $caseA2 = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::ACTION_REQUIRED);
    $caseB = createTestDeskCase($this->citizen, $this->service, $this->officeB, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    // List all cases for Office A
    $response = $this->getJson('/api/v1/desk/cases');
    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($caseA1->id, $caseA2->id)
        ->and($ids)->not->toContain($caseB->id);

    // Filter by status
    $filtered = $this->getJson('/api/v1/desk/cases?status=expert_review');
    $filtered->assertOk()
        ->assertJsonCount(1, 'data');
    expect($filtered->json('data.0.id'))->toBe($caseA1->id);

    // Search by tracking code
    $searched = $this->getJson('/api/v1/desk/cases?q='.$caseA2->tracking_code);
    $searched->assertOk()
        ->assertJsonCount(1, 'data');
    expect($searched->json('data.0.id'))->toBe($caseA2->id);
});

it('shows desk case details for operator office and hides cross-office cases with 404', function (): void {
    $caseA = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->getJson('/api/v1/desk/cases/'.$caseA->id);
    $response->assertOk()
        ->assertJsonPath('data.id', $caseA->id)
        ->assertJsonPath('data.tracking_code', $caseA->tracking_code)
        ->assertJsonPath('data.status', 'expert_review');

    // Cross-office access by operatorB returns 404
    Sanctum::actingAs($this->operatorB, ['*']);
    $crossResponse = $this->getJson('/api/v1/desk/cases/'.$caseA->id);
    $crossResponse->assertNotFound();
});

it('starts review of an assigned case and logs audit', function (): void {
    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::ASSIGNED_TO_OFFICE);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/review");
    $response->assertOk();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::EXPERT_REVIEW)
        ->and($case->turn_owner)->toBe(TurnOwner::OFFICE);

    $audit = DB::table('audit_logs')
        ->where('subject_type', CaseRequest::class)
        ->where('subject_id', $case->id)
        ->where('action', 'case.transition')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->operatorA->id);
});

it('returns case with each of the 10 standardized codes individually', function (ReturnReasonCode $reasonCode): void {
    Queue::fake();

    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    $doc = CaseDocument::query()->create([
        'case_id' => $case->id,
        'document_type_code' => 'national_card_front',
        'status' => CaseDocumentStatus::PENDING,
        'storage_key' => 'documents/test-front.jpg',
        'encrypted_data_key' => 'dummy-key',
        'content_sha256' => hash('sha256', 'dummy'),
        'size_bytes' => 1024,
        'mime_type' => 'image/jpeg',
        'quality_warnings' => [],
        'version' => 1,
        'uploaded_at' => Carbon::now(),
    ]);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/return", [
        'reason_code' => $reasonCode->value,
        'operator_note' => 'تست بازگشت با کد '.$reasonCode->value,
        'target_document_type_code' => 'national_card_front',
        'deadline_hours' => 48,
    ]);

    $response->assertOk();

    // 1. Verify case status & turn owner
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::ACTION_REQUIRED)
        ->and($case->turn_owner)->toBe(TurnOwner::CITIZEN);

    // 2. Verify CaseReturn record created with exact reason_code and operator ID
    $caseReturn = CaseReturn::query()
        ->where('case_id', $case->id)
        ->where('reason_code', $reasonCode->value)
        ->first();

    expect($caseReturn)->not->toBeNull()
        ->and($caseReturn->operator_id)->toBe($this->operatorA->id)
        ->and($caseReturn->target_document_type_code)->toBe('national_card_front');

    // 3. Verify target document rejected
    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::REJECTED)
        ->and($doc->reason_code)->toBe($reasonCode->value)
        ->and($doc->reviewed_by)->toBe($this->operatorA->id);

    // 4. Verify SendCaseNotificationJob dispatched
    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use ($case): bool {
        return $job->citizenId === $case->citizen_id && $job->type === 'case-returned';
    });

    // 5. Verify Audit Log has real operator ID
    $audit = DB::table('audit_logs')
        ->where('subject_type', CaseRequest::class)
        ->where('subject_id', $case->id)
        ->where('action', 'case.returned')
        ->latest('id')
        ->first();

    $changes = json_decode((string) $audit->changes, true);

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->operatorA->id)
        ->and($changes['reason_code'])->toBe($reasonCode->value);
})->with(ReturnReasonCode::cases());

it('rejects return with invalid reason code (422)', function (): void {
    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/return", [
        'reason_code' => 'INVALID_UNKNOWN_REASON',
        'operator_note' => 'خطای نامعتبر',
    ]);

    $response->assertStatus(422);
});

it('strictly conforms return response snapshot to Architecture §5.6 sample 7', function (): void {
    Queue::fake();

    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/return", [
        'reason_code' => ReturnReasonCode::DOC_BLUR->value,
        'operator_note' => 'تصویر تار است.',
        'deadline_hours' => 72,
    ]);

    $response->assertOk();

    $json = $response->json();

    // Must match Architecture §5.6 sample 7:
    // { "data": { "id": "...", "status": "action_required", "turn_owner": "citizen", "returned_at": "...", "deadline_at": "...", "notifications_sent": ["push", "sms"] } }
    expect($json)->toHaveKey('data')
        ->and($json['data'])->toHaveKeys([
            'id',
            'status',
            'turn_owner',
            'returned_at',
            'deadline_at',
            'notifications_sent',
        ])
        ->and($json['data']['id'])->toBe($case->id)
        ->and($json['data']['status'])->toBe('action_required')
        ->and($json['data']['turn_owner'])->toBe('citizen')
        ->and($json['data']['notifications_sent'])->toBe(['push', 'sms']);
});

it('requests government inquiry from expert review', function (): void {
    Queue::fake();

    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/inquiry");
    $response->assertOk();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::GOVERNMENT_INQUIRY)
        ->and($case->turn_owner)->toBe(TurnOwner::GOVERNMENT);

    Queue::assertPushed(GovernmentInquiryJob::class, function (GovernmentInquiryJob $job) use ($case): bool {
        return $job->caseId === $case->id;
    });
});

it('completes case from ready_for_issue', function (): void {
    Queue::fake();

    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::READY_FOR_ISSUE);

    Sanctum::actingAs($this->operatorA, ['*']);

    $response = $this->postJson("/api/v1/cases/{$case->id}/complete");
    $response->assertOk();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::COMPLETED)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM)
        ->and($case->closed_at)->not->toBeNull();

    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $job) use ($case): bool {
        return $job->citizenId === $case->citizen_id && $job->type === 'case_completed';
    });
});

it('allows office_manager to perform final rejection, but forbids office_operator with 403', function (): void {
    Queue::fake();

    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    // 1. Regular office operator attempts rejection -> 403 Forbidden
    Sanctum::actingAs($this->operatorA, ['*']);
    $forbiddenResponse = $this->postJson("/api/v1/cases/{$case->id}/reject", [
        'reason' => 'عدم احراز شرایط قانونی',
    ]);
    $forbiddenResponse->assertStatus(403);

    // Verify case unchanged
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::EXPERT_REVIEW);

    // 2. Office manager performs rejection -> 200 OK
    Sanctum::actingAs($this->managerA, ['*']);
    $successResponse = $this->postJson("/api/v1/cases/{$case->id}/reject", [
        'reason' => 'عدم احراز شرایط قانونی توسط مدیر',
    ]);
    $successResponse->assertOk();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::REJECTED)
        ->and($case->closed_at)->not->toBeNull();

    // Verify audit recorded with manager ID
    $audit = DB::table('audit_logs')
        ->where('subject_type', CaseRequest::class)
        ->where('subject_id', $case->id)
        ->where('action', 'case.transition')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->managerA->id);
});

it('enforces horizontal isolation returning 404 for cross-office action requests', function (): void {
    $case = createTestDeskCase($this->citizen, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operatorB, ['*']);

    $this->postJson("/api/v1/cases/{$case->id}/review")->assertNotFound();
    $this->postJson("/api/v1/cases/{$case->id}/return", ['reason_code' => 'DOC_BLUR'])->assertNotFound();
    $this->postJson("/api/v1/cases/{$case->id}/inquiry")->assertNotFound();
    $this->postJson("/api/v1/cases/{$case->id}/complete")->assertNotFound();
    $this->postJson("/api/v1/cases/{$case->id}/reject")->assertNotFound();
});
