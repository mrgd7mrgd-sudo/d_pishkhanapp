<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Resources;

use App\Modules\Consultation\Domain\Models\ConsultationSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsultationSession
 */
final class ConsultationSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'advisor_id' => $this->advisor_id,
            'citizen_id' => $this->citizen_id,
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'duration_seconds' => (int) $this->duration_seconds,
            'total_fee_rials' => (int) $this->total_fee_rials,
            'tracking_code' => $this->tracking_code,
            'uploaded_docs_count' => (int) $this->uploaded_docs_count,
            'advisor_verdict' => $this->advisor_verdict,
            'linked_service' => $this->relationLoaded('linkedService') && $this->linkedService !== null
                ? [
                    'id' => $this->linkedService->id,
                    'title' => $this->linkedService->title,
                    'slug' => $this->linkedService->slug,
                ]
                : null,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'advisor' => $this->relationLoaded('advisor') && $this->advisor !== null
                ? [
                    'id' => $this->advisor->id,
                    'display_name' => $this->advisor->display_name,
                    'title' => $this->advisor->title,
                    'category' => $this->advisor->category->value,
                    'avatar_key' => $this->advisor->avatar_key,
                ]
                : null,
        ];
    }
}
