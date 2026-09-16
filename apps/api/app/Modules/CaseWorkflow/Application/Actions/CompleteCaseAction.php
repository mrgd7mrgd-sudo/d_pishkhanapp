<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\Payments\Jobs\SettleCaseFeeJob;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class CompleteCaseAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    public function execute(Operator $operator, string $caseId): CaseRequest
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

        if ($case->status !== CaseStatus::READY_FOR_ISSUE) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 409,
                'code' => 'INVALID_STATUS_FOR_COMPLETION',
                'detail' => "Cannot complete case from status [{$case->status->value}]. Must be ready_for_issue.",
            ], 409));
        }

        $context = new TransitionContext(
            title: 'تکمیل و تحویل پرونده',
            description: 'خدمت با موفقیت انجام و مدارک به متقاضی تحویل گردید.',
            stepStatus: TimelineStepStatus::DONE,
            actorType: TimelineActorType::OPERATOR,
            actorId: $operator->id
        );

        $updatedCase = $this->stateMachine->transition($case, CaseStatus::COMPLETED, $context);

        // Side effect: Dispatch notification
        SendCaseNotificationJob::dispatch(
            citizenId: $updatedCase->citizen_id,
            type: 'case_completed',
            title: 'پرونده شما تکمیل شد',
            body: "پرونده {$updatedCase->tracking_code} با موفقیت تکمیل و نهایی گردید.",
            smsTemplate: 'case-ready',
            smsParams: ['tracking' => $updatedCase->tracking_code],
            payload: ['case_id' => $updatedCase->id, 'tracking_code' => $updatedCase->tracking_code]
        );

        // Side effect: Audit Log
        AuditLogger::record(
            AuditableAction::CASE_COMPLETED,
            $updatedCase,
            ['status' => CaseStatus::COMPLETED->value],
            ['office_id' => $operator->office_id],
            'operator',
            $operator->id
        );

        // Side effect: Fee settlement between office and platform (§8.2)
        if ($updatedCase->fee_paid_rials > 0) {
            SettleCaseFeeJob::dispatch($updatedCase->id);
        }

        return $updatedCase;
    }
}
