<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('seeds exactly 10 return reasons with non-empty Persian default messages (DoD: TASK-051-T)', function (): void {
    $this->seed(ReturnReasonSeeder::class);

    $reasons = ReturnReason::query()->get();

    // 1. Exactly 10 records (count assertion)
    expect($reasons)->toHaveCount(10);

    $expectedCodes = [
        'DOC_BLUR', 'DOC_CROP', 'DOC_EXPIRED', 'DOC_MISMATCH', 'DOC_MISSING',
        'DOC_WRONG_TYPE', 'FORM_INVALID', 'INQUIRY_MISMATCH', 'ELIGIBILITY_FAIL',
        'PRESENCE_REQUIRED',
    ];

    $actualCodes = $reasons->pluck('code')->map(fn ($c) => $c instanceof ReturnReasonCode ? $c->value : (string) $c)->all();
    expect($actualCodes)->toBe($expectedCodes);

    // 2. Each code has non-empty Persian default_message
    foreach ($reasons as $reason) {
        expect($reason->title)->not->toBeEmpty()
            ->and($reason->default_message)->not->toBeEmpty()
            ->and($reason->is_active)->toBeTrue();
    }
});

it('verifies ReturnReasonSeeder is idempotent and safe to re-run in production', function (): void {
    $this->seed(ReturnReasonSeeder::class);
    expect(ReturnReason::query()->count())->toBe(10);

    // Re-run seeder
    $this->seed(ReturnReasonSeeder::class);
    expect(ReturnReason::query()->count())->toBe(10);
});

it('creates case return record with relationships to case, return reason, and operator', function (): void {
    $this->seed(ReturnReasonSeeder::class);

    $citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc-nid',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_encrypted' => 'enc-mob',
        'mobile_hash' => hash('sha256', Str::random(11)),
        'full_name' => 'سهراب سپهری',
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-return-test',
        'title' => 'خدمات تستی',
    ]);

    $service = Service::query()->create([
        'slug' => 'return-test-service',
        'title' => 'خدمت تست بازگشت',
        'description' => 'توضیحات تست بازگشت',
        'tags' => ['in-person'],
        'category_id' => $category->id,
        'fee_rials' => 300_000,
    ]);

    $case = CaseRequest::query()->create([
        'tracking_code' => 'CR-RET-001',
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::ACTION_REQUIRED,
        'turn_owner' => TurnOwner::CITIZEN,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    $operator = Operator::query()->create([
        'username' => 'op_test_'.Str::random(5),
        'password_hash' => 'hash',
        'full_name' => 'کارشناس باجه یک',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_hash' => hash('sha256', Str::random(11)),
        'role' => 'operator',
        'counter_number' => 1,
        'is_active' => true,
    ]);

    $caseReturn = CaseReturn::query()->create([
        'case_id' => $case->id,
        'reason_code' => ReturnReasonCode::DOC_BLUR,
        'target_document_type_code' => 'national_card_front',
        'operator_note' => 'لطفاً تصویر واضح‌تر ارسال نمایید.',
        'operator_id' => $operator->id,
        'deadline_at' => Carbon::now()->addHours(72),
    ]);

    expect($caseReturn->reason_code)->toBe(ReturnReasonCode::DOC_BLUR)
        ->and($caseReturn->case?->id)->toBe($case->id)
        ->and($caseReturn->reason?->code)->toBe(ReturnReasonCode::DOC_BLUR)
        ->and($caseReturn->operator?->id)->toBe($operator->id)
        ->and($caseReturn->target_document_type_code)->toBe('national_card_front');
});
