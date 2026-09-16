<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitOfficeReviewAction
{
    /**
     * Submit a verified review for a completed case and recalculate office rating.
     *
     * @param  list<string>|null  $tags
     *
     * @throws ValidationException
     */
    public function execute(
        Citizen $citizen,
        string $caseId,
        int $rating,
        string $comment,
        ?array $tags = null
    ): OfficeReview {
        $case = CaseRequest::with('office')->findOrFail($caseId);
        $this->validateReviewEligibility($citizen, $case);

        return DB::transaction(fn (): OfficeReview => $this->persistReviewAndAudit(
            $citizen,
            $case,
            $rating,
            $comment,
            $tags
        ));
    }

    private function validateReviewEligibility(Citizen $citizen, CaseRequest $case): void
    {
        if ($case->citizen_id !== $citizen->id) {
            throw ValidationException::withMessages([
                'case_id' => ['امکان ثبت نظر تنها برای پرونده‌های متعلق به خود کاربر مجاز است.'],
            ]);
        }

        if ($case->status !== CaseStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'case_id' => ['تنها پرونده‌های تکمیل‌شده امکان ثبت نظر دارند.'],
            ]);
        }

        if (OfficeReview::query()->where('case_id', $case->id)->exists()) {
            throw ValidationException::withMessages([
                'case_id' => ['برای این پرونده قبلاً نظر ثبت شده است.'],
            ]);
        }
    }

    /**
     * @param  list<string>|null  $tags
     */
    private function persistReviewAndAudit(
        Citizen $citizen,
        CaseRequest $case,
        int $rating,
        string $comment,
        ?array $tags
    ): OfficeReview {
        $review = OfficeReview::query()->create([
            'office_id' => $case->office_id,
            'citizen_id' => $citizen->id,
            'case_id' => $case->id,
            'rating' => $rating,
            'comment' => $comment,
            'tags' => $tags,
            'likes' => 0,
            'is_verified' => true,
        ]);

        $this->recalculateOfficeRating($case->office);

        AuditLogger::record(
            action: AuditableAction::REVIEW_SUBMITTED,
            subject: $review,
            changes: [
                'rating' => $rating,
                'office_id' => $case->office_id,
                'case_id' => $case->id,
                'new_office_rating' => $case->office?->rating,
                'new_office_review_count' => $case->office?->review_count,
            ],
            context: ['citizen_id' => $citizen->id, 'office_id' => $case->office_id],
            actorType: get_class($citizen),
            actorId: (string) $citizen->id,
        );

        return $review;
    }

    private function recalculateOfficeRating(?Office $office): void
    {
        if ($office === null) {
            return;
        }

        $stats = OfficeReview::query()
            ->where('office_id', $office->id)
            ->where('is_verified', true)
            ->selectRaw('ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as review_count')
            ->first();

        if ($stats !== null) {
            $office->rating = (float) ($stats->avg_rating ?? 0.0);
            $office->review_count = (int) ($stats->review_count ?? 0);
            $office->save();
        }
    }
}
