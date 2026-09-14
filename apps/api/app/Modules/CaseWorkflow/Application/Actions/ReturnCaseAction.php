<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ReturnCaseAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    /**
     * @return array{data: array<string, mixed>}
     */
    public function execute(
        Operator $operator,
        string $caseId,
        string $reasonCode,
        ?string $operatorNote = null,
        ?string $targetDocCode = null,
        int $deadlineHours = 72
    ): array {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->where('id', $caseId)->first();

        // Horizontal Isolation §7.3: Always 404, never 403
        if (! $case || $case->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Case not found.',
            ], 404));
        }

        // Validate 10 standardized codes
        $enumCode = ReturnReasonCode::tryFrom($reasonCode);
        if (! $enumCode) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'INVALID_RETURN_REASON',
                'detail' => "Invalid return reason code [{$reasonCode}].",
                'allowed_codes' => ReturnReasonCode::values(),
            ], 422));
        }

        $now = Carbon::now();
        $deadlineAt = (clone $now)->addHours($deadlineHours);

        // Update target document to rejected if present
        $this->rejectTargetDocument($case->id, $targetDocCode, $reasonCode, $operator->id);

        // Create CaseReturn record
        CaseReturn::create([
            'id' => (string) Str::uuid(),
            'case_id' => $case->id,
            'reason_code' => $enumCode,
            'target_document_type_code' => $targetDocCode,
            'operator_note' => $operatorNote,
            'operator_id' => $operator->id,
            'deadline_at' => $deadlineAt,
        ]);

        // Transition case status to action_required
        $this->transitionCase($case, $enumCode, $operator, $operatorNote);

        // Side effect: Dispatch SendCaseNotificationJob
        SendCaseNotificationJob::dispatch(
            citizenId: $case->citizen_id,
            type: 'case-returned',
            title: 'نقص مدرک و بازگشت پرونده',
            body: "پرونده {$case->tracking_code} نیاز به اصلاح مدرک دارد. مهلت: {$deadlineHours} ساعت",
            smsTemplate: 'case-returned',
            smsParams: ['tracking' => $case->tracking_code, 'hours' => $deadlineHours],
            payload: ['tracking_code' => $case->tracking_code, 'case_id' => $case->id]
        );

        // Side effect: Audit Log
        AuditLogger::record(
            AuditableAction::CASE_RETURNED,
            $case,
            [
                'reason_code' => $reasonCode,
                'deadline_at' => $deadlineAt->toISOString(),
                'target_document_type_code' => $targetDocCode,
            ],
            ['operator_note' => $operatorNote],
            'operator',
            $operator->id
        );

        // Response strictly matching Architecture §5.6 sample 7
        return [
            'data' => [
                'id' => $case->id,
                'status' => CaseStatus::ACTION_REQUIRED->value,
                'turn_owner' => 'citizen',
                'returned_at' => $now->toISOString(),
                'deadline_at' => $deadlineAt->toISOString(),
                'notifications_sent' => ['push', 'sms'],
            ],
        ];
    }

    private function rejectTargetDocument(string $caseId, ?string $targetDocCode, string $reasonCode, string $operatorId): void
    {
        if (! $targetDocCode) {
            return;
        }

        /** @var CaseDocument|null $targetDoc */
        $targetDoc = CaseDocument::query()
            ->where('case_id', $caseId)
            ->where('document_type_code', $targetDocCode)
            ->orderBy('version', 'desc')
            ->first();

        if ($targetDoc) {
            $targetDoc->status = CaseDocumentStatus::REJECTED;
            $targetDoc->reason_code = $reasonCode;
            $targetDoc->reviewed_by = $operatorId;
            $targetDoc->save();
        }
    }

    private function transitionCase(CaseRequest $case, ReturnReasonCode $enumCode, Operator $operator, ?string $operatorNote): void
    {
        /** @var ReturnReason|null $reasonRecord */
        $reasonRecord = ReturnReason::query()->where('code', $enumCode->value)->first();
        $description = $reasonRecord?->default_message ?? $enumCode->defaultMessage();

        $context = new TransitionContext(
            title: 'بازگشت پرونده جهت اصلاح مدارک',
            description: $description,
            stepStatus: TimelineStepStatus::WARNING,
            actorType: TimelineActorType::OPERATOR,
            actorId: $operator->id,
            reasonCode: $enumCode->value,
            metadata: ['office_note' => $operatorNote]
        );

        $this->stateMachine->transition($case, CaseStatus::ACTION_REQUIRED, $context);
    }
}
