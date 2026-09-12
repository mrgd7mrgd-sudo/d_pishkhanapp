<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\CaseStatusChanged;
use App\Modules\CaseWorkflow\Domain\Exceptions\InvalidCaseTransitionException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Errors\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestContextCase(CaseStatus $initialStatus): CaseRequest
{
    $citizen = Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', Str::random(12)),
        'mobile_encrypted' => 'enc:'.Str::random(11),
        'national_id_hash' => hash('sha256', Str::random(10)),
        'national_id_encrypted' => 'enc:0010350802',
        'full_name' => 'کاربر تست ماشین وضعیت',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-sm-'.Str::random(5),
        'title' => 'دسته ماشین وضعیت',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'خدمت تست ماشین وضعیت',
        'slug' => 'sm-svc-'.Str::random(6),
        'description' => 'توضیحات تست ماشین وضعیت',
        'tags' => ['online'],
        'fee_rials' => 450000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    return CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-SM-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => $initialStatus,
        'turn_owner' => TurnOwner::from(CaseStateMachine::TURN_OWNER[$initialStatus->value]),
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 1,
        'total_steps' => 5,
        'fee_paid_rials' => 450000,
        'office_share_rials' => 315000,
        'platform_share_rials' => 135000,
    ]);
}

test('state machine reports correct allowed transitions and target turn owners', function (): void {
    $sm = new CaseStateMachine;

    expect($sm->getAllowedTransitions(CaseStatus::DRAFT))->toEqual([CaseStatus::SEARCHING_OFFICE])
        ->and($sm->getAllowedTransitions(CaseStatus::COMPLETED))->toBeEmpty()
        ->and($sm->getAllowedTransitions(CaseStatus::REJECTED))->toBeEmpty()
        ->and($sm->getAllowedTransitions(CaseStatus::CANCELLED))->toBeEmpty();

    expect($sm->getTargetTurnOwner(CaseStatus::DRAFT))->toBe(TurnOwner::CITIZEN)
        ->and($sm->getTargetTurnOwner(CaseStatus::SEARCHING_OFFICE))->toBe(TurnOwner::SYSTEM)
        ->and($sm->getTargetTurnOwner(CaseStatus::ASSIGNED_TO_OFFICE))->toBe(TurnOwner::OFFICE)
        ->and($sm->getTargetTurnOwner(CaseStatus::EXPERT_REVIEW))->toBe(TurnOwner::OFFICE)
        ->and($sm->getTargetTurnOwner(CaseStatus::ACTION_REQUIRED))->toBe(TurnOwner::CITIZEN)
        ->and($sm->getTargetTurnOwner(CaseStatus::GOVERNMENT_INQUIRY))->toBe(TurnOwner::GOVERNMENT)
        ->and($sm->getTargetTurnOwner(CaseStatus::READY_FOR_ISSUE))->toBe(TurnOwner::OFFICE)
        ->and($sm->getTargetTurnOwner(CaseStatus::DELIVERING))->toBe(TurnOwner::POSTAL)
        ->and($sm->getTargetTurnOwner(CaseStatus::COMPLETED))->toBe(TurnOwner::SYSTEM)
        ->and($sm->getTargetTurnOwner(CaseStatus::REJECTED))->toBe(TurnOwner::SYSTEM)
        ->and($sm->getTargetTurnOwner(CaseStatus::CANCELLED))->toBe(TurnOwner::SYSTEM);
});

dataset('allowed_transitions_matrix', [
    '1: draft -> searching_office' => [CaseStatus::DRAFT, CaseStatus::SEARCHING_OFFICE, TurnOwner::SYSTEM, false],
    '2: searching_office -> assigned_to_office' => [CaseStatus::SEARCHING_OFFICE, CaseStatus::ASSIGNED_TO_OFFICE, TurnOwner::OFFICE, false],
    '3: searching_office -> cancelled' => [CaseStatus::SEARCHING_OFFICE, CaseStatus::CANCELLED, TurnOwner::SYSTEM, true],
    '4: assigned_to_office -> expert_review' => [CaseStatus::ASSIGNED_TO_OFFICE, CaseStatus::EXPERT_REVIEW, TurnOwner::OFFICE, false],
    '5: assigned_to_office -> searching_office' => [CaseStatus::ASSIGNED_TO_OFFICE, CaseStatus::SEARCHING_OFFICE, TurnOwner::SYSTEM, false],
    '6: expert_review -> action_required' => [CaseStatus::EXPERT_REVIEW, CaseStatus::ACTION_REQUIRED, TurnOwner::CITIZEN, false],
    '7: expert_review -> government_inquiry' => [CaseStatus::EXPERT_REVIEW, CaseStatus::GOVERNMENT_INQUIRY, TurnOwner::GOVERNMENT, false],
    '8: expert_review -> rejected' => [CaseStatus::EXPERT_REVIEW, CaseStatus::REJECTED, TurnOwner::SYSTEM, true],
    '9: action_required -> expert_review' => [CaseStatus::ACTION_REQUIRED, CaseStatus::EXPERT_REVIEW, TurnOwner::OFFICE, false],
    '10: action_required -> cancelled' => [CaseStatus::ACTION_REQUIRED, CaseStatus::CANCELLED, TurnOwner::SYSTEM, true],
    '11: government_inquiry -> ready_for_issue' => [CaseStatus::GOVERNMENT_INQUIRY, CaseStatus::READY_FOR_ISSUE, TurnOwner::OFFICE, false],
    '12: government_inquiry -> action_required' => [CaseStatus::GOVERNMENT_INQUIRY, CaseStatus::ACTION_REQUIRED, TurnOwner::CITIZEN, false],
    '13: government_inquiry -> rejected' => [CaseStatus::GOVERNMENT_INQUIRY, CaseStatus::REJECTED, TurnOwner::SYSTEM, true],
    '14: ready_for_issue -> completed' => [CaseStatus::READY_FOR_ISSUE, CaseStatus::COMPLETED, TurnOwner::SYSTEM, true],
    '15: ready_for_issue -> delivering' => [CaseStatus::READY_FOR_ISSUE, CaseStatus::DELIVERING, TurnOwner::POSTAL, false],
    '16: delivering -> completed' => [CaseStatus::DELIVERING, CaseStatus::COMPLETED, TurnOwner::SYSTEM, true],
    '17: delivering -> ready_for_issue' => [CaseStatus::DELIVERING, CaseStatus::READY_FOR_ISSUE, TurnOwner::OFFICE, false],
]);

test('each of the 17 allowed transitions in architecture section 3.5 executes properly', function (
    CaseStatus $from,
    CaseStatus $to,
    TurnOwner $expectedOwner,
    bool $isTerminal
): void {
    Event::fake([CaseStatusChanged::class]);

    $sm = new CaseStateMachine;
    expect($sm->canTransition($from, $to))->toBeTrue();

    $case = createTestContextCase($from);
    $ctx = new TransitionContext(
        title: "گذار از {$from->value} به {$to->value}",
        description: 'توضیحات تست گذر',
        stepStatus: TimelineStepStatus::DONE,
        actorType: TimelineActorType::SYSTEM,
        actorId: null,
        reasonCode: 'TEST_TRANSITION',
    );

    $updatedCase = $sm->transition($case, $to, $ctx);

    expect($updatedCase->status)->toBe($to)
        ->and($updatedCase->turn_owner)->toBe($expectedOwner);

    if ($isTerminal) {
        expect($updatedCase->closed_at)->not->toBeNull();
    } else {
        expect($updatedCase->closed_at)->toBeNull();
    }

    // Verify timeline step appended
    $latestStep = $updatedCase->timelineSteps()->orderByDesc('sequence')->first();
    expect($latestStep)->not->toBeNull()
        ->and($latestStep->title)->toBe("گذار از {$from->value} به {$to->value}")
        ->and($latestStep->turn_owner)->toBe($expectedOwner)
        ->and($latestStep->turn_owner_label)->toBe($expectedOwner->label());

    // Verify DomainEvent fired
    Event::assertDispatched(CaseStatusChanged::class, function (CaseStatusChanged $event) use ($case, $from, $to): bool {
        return $event->case->id === $case->id
            && $event->from === $from
            && $event->to === $to
            && $event->eventName() === 'case.status_changed'
            && $event->toPayload()['case_id'] === $case->id;
    });
})->with('allowed_transitions_matrix');

test('all 104 disallowed transitions throw InvalidCaseTransitionException', function (): void {
    $sm = new CaseStateMachine;
    $allStatuses = CaseStatus::cases();
    $disallowedCount = 0;
    $case = createTestContextCase(CaseStatus::DRAFT);
    $ctx = new TransitionContext(title: 'تست گذار غیرمجاز');

    foreach ($allStatuses as $from) {
        CaseRequest::$allowDirectStatusAssignment = true;
        $case->status = $from;
        CaseRequest::$allowDirectStatusAssignment = false;

        foreach ($allStatuses as $to) {
            if ($sm->canTransition($from, $to)) {
                continue;
            }

            $disallowedCount++;

            try {
                $sm->transition($case, $to, $ctx);
                $this->fail("Expected transition from {$from->value} to {$to->value} to throw InvalidCaseTransitionException");
            } catch (InvalidCaseTransitionException $e) {
                expect($e->from)->toBe($from)
                    ->and($e->to)->toBe($to)
                    ->and($e->getStatusCode())->toBe(422)
                    ->and($e->getErrorCode())->toBe(ErrorCode::CASE_INVALID_TRANSITION)
                    ->and($e->getHeaders())->toBeArray()
                    ->and($e->getMessage())->toContain($from->value)
                    ->and($e->getMessage())->toContain($to->value);
            }
        }
    }

    // 11 statuses * 11 statuses = 121 possible pairs. 17 allowed => exactly 104 disallowed.
    expect($disallowedCount)->toBe(104);
});
