<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class RejectCaseAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    public function execute(Operator $operator, string $caseId, ?string $reason = null): CaseRequest
    {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->where('id', $caseId)->first();

        // Horizontal Isolation §7.3: Always 404, never 403
        if (! $case || $case->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Case not found.',
            ], 404));
        }

        // Only office manager can perform final rejection (TASK-074, TASK-074-T)
        $isManager = $operator->hasRole('office_manager') || $operator->role === OperatorRole::MANAGER;
        if (! $isManager) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'code' => 'FORBIDDEN_NOT_OFFICE_MANAGER',
                'detail' => 'فقط مدیر دفتر مجاز به رد نهایی پرونده است.',
            ], 403));
        }

        if (! in_array($case->status, [CaseStatus::EXPERT_REVIEW, CaseStatus::GOVERNMENT_INQUIRY], true)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 409,
                'code' => 'INVALID_STATUS_FOR_REJECTION',
                'detail' => "Cannot reject case from status [{$case->status->value}].",
            ], 409));
        }

        $context = new TransitionContext(
            title: 'رد نهایی پرونده',
            description: $reason ?? 'پرونده توسط مدیر دفتر رد شد.',
            stepStatus: TimelineStepStatus::FAILED,
            actorType: TimelineActorType::OPERATOR,
            actorId: $operator->id,
            reasonCode: 'REJECTED_BY_MANAGER',
            metadata: ['office_note' => $reason]
        );

        $updatedCase = $this->stateMachine->transition($case, CaseStatus::REJECTED, $context);

        // Dispatch notification
        SendCaseNotificationJob::dispatch(
            citizenId: $updatedCase->citizen_id,
            type: 'case_rejected',
            title: 'رد پرونده',
            body: "پرونده {$updatedCase->tracking_code} رد گردید: ".($reason ?? 'عدم احراز شرایط'),
            payload: ['case_id' => $updatedCase->id, 'tracking_code' => $updatedCase->tracking_code]
        );

        // Audit log with real operator id
        AuditLogger::record(
            AuditableAction::CASE_TRANSITION,
            $updatedCase,
            [
                'status' => CaseStatus::REJECTED->value,
                'reason' => $reason,
            ],
            ['office_id' => $operator->office_id],
            'operator',
            $operator->id
        );

        return $updatedCase;
    }
}
