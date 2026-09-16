<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ReplyOfficeReviewAction
{
    /**
     * Reply to a customer review by the office manager.
     *
     * @throws AuthorizationException
     */
    public function execute(
        Operator $operator,
        OfficeReview $review,
        string $replyText
    ): OfficeReview {
        // Horizontal isolation: cross-office must return 404
        if ($operator->office_id !== $review->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نظر یافت نشد.',
            ], 404));
        }

        // Role check: reply allowed ONLY for office_manager
        $isManager = $operator->hasRole('office_manager') || $operator->role === OperatorRole::MANAGER;
        if (! $isManager) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'code' => 'FORBIDDEN_NOT_OFFICE_MANAGER',
                'detail' => 'تنها مدیر دفتر مجاز به پاسخگویی به نظرات است.',
            ], 403));
        }

        return DB::transaction(function () use ($operator, $review, $replyText): OfficeReview {
            $review->manager_reply = $replyText;
            $review->manager_replied_at = Carbon::now();
            $review->manager_operator_id = $operator->id;
            $review->save();

            AuditLogger::record(
                action: AuditableAction::REVIEW_REPLIED,
                subject: $review,
                changes: [
                    'manager_reply' => $replyText,
                    'manager_operator_id' => $operator->id,
                ],
                context: [
                    'office_id' => $operator->office_id,
                    'review_id' => $review->id,
                ],
                actorType: get_class($operator),
                actorId: (string) $operator->id,
            );

            return $review;
        });
    }
}
