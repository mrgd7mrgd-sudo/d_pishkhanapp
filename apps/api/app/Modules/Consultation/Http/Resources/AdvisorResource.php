<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Resources;

use App\Modules\Consultation\Domain\Models\Advisor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Advisor
 */
final class AdvisorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citizen_id' => $this->citizen_id,
            'display_name' => $this->display_name,
            'avatar_key' => $this->avatar_key,
            'title' => $this->title,
            'category' => $this->category->value,
            'credentials_badge' => $this->credentials_badge,
            'license_number' => $this->license_number,
            'experience_years' => $this->experience_years,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'rating_breakdown' => [
                'accuracy' => (float) $this->rating_accuracy,
                'eloquence' => (float) $this->rating_eloquence,
                'patience' => (float) $this->rating_patience,
            ],
            'is_online' => (bool) $this->is_online,
            'is_verified' => (bool) $this->is_verified,
            'bio' => $this->bio,
            'consultation_count' => (int) $this->consultation_count,
            'pricing' => [
                'text_chat_rials' => (int) $this->price_text_chat_rials,
                'phone_per_minute_rials' => (int) $this->price_phone_per_minute_rials,
                'deep_review_rials' => (int) $this->price_deep_review_rials,
            ],
            'specialties' => $this->relationLoaded('specialties')
                ? $this->specialties->pluck('specialty_name')->values()->all()
                : [],
            'application_status' => $this->application_status->value,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
