<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Resources;

use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OfficeReview
 */
final class OfficeReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $serviceTitle = null;
        if ($this->relationLoaded('caseRequest') && $this->caseRequest !== null) {
            $serviceTitle = $this->caseRequest->relationLoaded('service') && $this->caseRequest->service !== null
                ? $this->caseRequest->service->title
                : null;
        }

        return [
            'id' => $this->id,
            'office_id' => $this->office_id,
            'office_name' => $this->relationLoaded('office') ? $this->office->name : null,
            'citizen_id' => $this->citizen_id,
            'citizen_name' => $this->relationLoaded('citizen') ? $this->citizen->full_name : null,
            'case_id' => $this->case_id,
            'service_title' => $serviceTitle,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'tags' => $this->tags ?? [],
            'likes' => $this->likes,
            'is_verified' => $this->is_verified,
            'is_verified_citizen' => $this->is_verified,
            'manager_reply' => $this->manager_reply,
            'manager_replied_at' => $this->manager_replied_at?->toIso8601String(),
            'manager_reply_info' => $this->hasReply() ? [
                'text' => $this->manager_reply,
                'date' => $this->manager_replied_at?->toIso8601String(),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
