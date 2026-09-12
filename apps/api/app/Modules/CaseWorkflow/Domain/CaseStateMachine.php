<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\CaseStatusChanged;
use App\Modules\CaseWorkflow\Domain\Exceptions\InvalidCaseTransitionException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for case status transitions (Architecture §3.5, §5.4).
 */
final class CaseStateMachine
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'draft' => ['searching_office'],
        'searching_office' => ['assigned_to_office', 'cancelled'],
        'assigned_to_office' => ['expert_review', 'searching_office'],
        'expert_review' => ['action_required', 'government_inquiry', 'rejected'],
        'action_required' => ['expert_review', 'cancelled'],
        'government_inquiry' => ['ready_for_issue', 'action_required', 'rejected'],
        'ready_for_issue' => ['completed', 'delivering'],
        'delivering' => ['completed', 'ready_for_issue'],
        'completed' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    /** @var array<string, string> */
    public const TURN_OWNER = [
        'draft' => 'citizen',
        'searching_office' => 'system',
        'assigned_to_office' => 'office',
        'expert_review' => 'office',
        'action_required' => 'citizen',
        'government_inquiry' => 'government',
        'ready_for_issue' => 'office',
        'delivering' => 'postal',
        'completed' => 'system',
        'rejected' => 'system',
        'cancelled' => 'system',
    ];

    public function canTransition(CaseStatus $from, CaseStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    /**
     * @return list<CaseStatus>
     */
    public function getAllowedTransitions(CaseStatus $from): array
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];
        $result = [];

        foreach ($allowed as $statusValue) {
            $result[] = CaseStatus::from($statusValue);
        }

        return $result;
    }

    public function getTargetTurnOwner(CaseStatus $to): TurnOwner
    {
        $ownerValue = self::TURN_OWNER[$to->value] ?? 'system';

        return TurnOwner::from($ownerValue);
    }

    public function transition(CaseRequest $case, CaseStatus $to, TransitionContext $ctx): CaseRequest
    {
        $from = $case->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidCaseTransitionException($from, $to);
        }

        return DB::transaction(function () use ($case, $from, $to, $ctx): CaseRequest {
            $targetOwner = $this->getTargetTurnOwner($to);

            $this->updateCaseStatusAndOwner($case, $to, $targetOwner);
            $this->appendTimelineStep($case, $targetOwner, $ctx);
            $this->recordAuditLog($case, $from, $to, $ctx);
            $this->fireDomainEvent($case, $from, $to, $ctx);

            return $case;
        });
    }

    private function updateCaseStatusAndOwner(CaseRequest $case, CaseStatus $to, TurnOwner $targetOwner): void
    {
        $case->status = $to;
        $case->turn_owner = $targetOwner;

        if (in_array($to, [CaseStatus::COMPLETED, CaseStatus::REJECTED, CaseStatus::CANCELLED], true)) {
            $case->closed_at = now();
        }

        $case->save();
    }

    private function appendTimelineStep(CaseRequest $case, TurnOwner $targetOwner, TransitionContext $ctx): void
    {
        $nextSequence = ((int) $case->timelineSteps()->max('sequence')) + 1;

        $case->timelineSteps()->create([
            'sequence' => $nextSequence,
            'title' => $ctx->title,
            'description' => $ctx->description,
            'status' => $ctx->stepStatus,
            'turn_owner' => $targetOwner,
            'turn_owner_label' => $targetOwner->label(),
            'actor_type' => $ctx->actorType,
            'actor_id' => $ctx->actorId,
            'occurred_at' => now(),
        ]);
    }

    private function recordAuditLog(CaseRequest $case, CaseStatus $from, CaseStatus $to, TransitionContext $ctx): void
    {
        AuditLogger::record(
            action: 'case.transition',
            subject: $case,
            changes: [
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $ctx->reasonCode,
            ],
            actorType: $ctx->actorType->value,
            actorId: $ctx->actorId,
        );
    }

    private function fireDomainEvent(CaseRequest $case, CaseStatus $from, CaseStatus $to, TransitionContext $ctx): void
    {
        event(new CaseStatusChanged($case, $from, $to, $ctx));
    }
}
