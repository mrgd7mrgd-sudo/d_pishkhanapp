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
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class RequestInquiryAction
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

        if ($case->status !== CaseStatus::EXPERT_REVIEW) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 409,
                'code' => 'INVALID_STATUS_FOR_INQUIRY',
                'detail' => "Cannot request government inquiry for case in status [{$case->status->value}].",
            ], 409));
        }

        $context = new TransitionContext(
            title: 'استعلام از مراجع دولتی',
            description: 'مدارک تأیید شد و استعلام برخط به سازمان مربوطه ارسال گردید.',
            stepStatus: TimelineStepStatus::CURRENT,
            actorType: TimelineActorType::OPERATOR,
            actorId: $operator->id
        );

        $updatedCase = $this->stateMachine->transition($case, CaseStatus::GOVERNMENT_INQUIRY, $context);

        AuditLogger::record(
            AuditableAction::CASE_TRANSITION,
            $updatedCase,
            ['status' => CaseStatus::GOVERNMENT_INQUIRY->value],
            ['office_id' => $operator->office_id],
            'operator',
            $operator->id
        );

        return $updatedCase;
    }
}
