<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Queries;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Contracts\Auth\Authenticatable;

final class AvailableActionsResolver
{
    /**
     * Resolve permitted client actions for a given case and actor (Architecture §5.6, D-19).
     *
     * @return list<string>
     */
    public function resolve(CaseRequest $case, ?Authenticatable $actor): array
    {
        if ($actor instanceof Citizen && $actor->id === $case->citizen_id) {
            return $this->resolveForCitizen($case);
        }

        if ($actor instanceof Operator) {
            return $this->resolveForOperator($case, $actor);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function resolveForCitizen(CaseRequest $case): array
    {
        return match ($case->status) {
            CaseStatus::SEARCHING_OFFICE => ['cancel_case'],
            CaseStatus::ASSIGNED_TO_OFFICE => ['open_chat', 'cancel_case'],
            CaseStatus::EXPERT_REVIEW => ['open_chat'],
            CaseStatus::ACTION_REQUIRED => ['upload_fix_document', 'open_chat', 'cancel_case'],
            CaseStatus::GOVERNMENT_INQUIRY => ['open_chat'],
            CaseStatus::READY_FOR_ISSUE => ['view_receipt', 'open_chat'],
            CaseStatus::DELIVERING => ['track_courier', 'open_chat'],
            CaseStatus::COMPLETED => ['download_result', 'rate_service'],
            CaseStatus::REJECTED => ['view_rejection_reason', 'submit_objection'],
            CaseStatus::CANCELLED => ['view_cancellation_details'],
            CaseStatus::DRAFT => ['edit_case', 'submit_case', 'discard_case'],
        };
    }

    /**
     * @return list<string>
     */
    private function resolveForOperator(CaseRequest $case, Operator $operator): array
    {
        $belongsToAssignedOffice = $case->office_id !== null && $operator->office_id === $case->office_id;
        if (! $belongsToAssignedOffice && ! $operator->hasRole('admin')) {
            return [];
        }

        return match ($case->status) {
            CaseStatus::ASSIGNED_TO_OFFICE => ['accept_case', 'reject_offer'],
            CaseStatus::EXPERT_REVIEW => ['return_case', 'request_inquiry', 'mark_ready_for_issue', 'reject_case', 'open_chat'],
            CaseStatus::ACTION_REQUIRED => ['open_chat'],
            CaseStatus::GOVERNMENT_INQUIRY => ['check_inquiry_status', 'mark_ready_for_issue', 'reject_case'],
            CaseStatus::READY_FOR_ISSUE => ['issue_document', 'handover_to_courier', 'deliver_in_person'],
            CaseStatus::DELIVERING => ['track_delivery'],
            default => [],
        };
    }
}
