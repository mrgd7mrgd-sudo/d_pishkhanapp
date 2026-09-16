<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Resources;

use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubscriptionPlan
 */
final class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_key' => $this->plan_key,
            'title' => $this->title,
            'badge' => $this->badge,
            'is_popular' => (bool) $this->is_popular,
            'price_monthly_rials' => (int) $this->price_monthly_rials,
            'target_audience' => $this->target_audience,
            'features' => (array) ($this->features ?? []),
            'quota' => (array) ($this->quota ?? []),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
