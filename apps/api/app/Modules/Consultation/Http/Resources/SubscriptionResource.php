<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Resources;

use App\Modules\Consultation\Domain\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
final class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'citizen_id' => $this->citizen_id,
            'status' => $this->status->value,
            'started_on' => $this->started_on->toDateString(),
            'expires_on' => $this->expires_on->toDateString(),
            'plan' => $this->relationLoaded('plan') && $this->plan !== null
                ? (new SubscriptionPlanResource($this->plan))->resolve()
                : null,
            'quotas' => $this->relationLoaded('quotaUsages')
                ? $this->quotaUsages->map(fn ($q) => [
                    'quota_key' => $q->quota_key,
                    'used' => (int) $q->used,
                    'limit' => (int) $q->limit,
                    'remaining' => max(0, (int) ($q->limit - $q->used)),
                ])->values()->all()
                : [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
