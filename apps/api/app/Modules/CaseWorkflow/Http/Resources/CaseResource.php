<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Resources;

use App\Modules\CaseWorkflow\Application\Queries\AvailableActionsResolver;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseRequest
 */
final class CaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = $this->service;
        $serviceId = $this->service_id;
        $serviceTitle = '';
        $serviceTag = 'in-person';

        if ($service !== null) {
            $serviceId = $service->id;
            $serviceTitle = $service->title;
            $tags = is_array($service->tags) ? $service->tags : [];
            $serviceTag = count($tags) > 0 ? (string) $tags[0] : 'in-person';
        }

        $resolver = app(AvailableActionsResolver::class);
        $actions = $resolver->resolve($this->resource, $request->user());

        $assignedOffice = null;
        if ($this->office !== null) {
            $assignedOffice = [
                'id' => $this->office->id,
                'name' => $this->office->name,
            ];
        }

        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status->value,
            'turn_owner' => $this->turn_owner->value,
            'turn_owner_label' => $this->status->value === CaseStatus::SEARCHING_OFFICE->value
                ? 'در حال یافتن دفتر مناسب'
                : $this->turn_owner->label(),
            'service' => [
                'id' => $serviceId,
                'title' => $serviceTitle,
                'tag' => $serviceTag,
            ],
            'assigned_office' => $assignedOffice,
            'current_step' => $this->current_step,
            'total_steps' => $this->total_steps,
            'fee_paid_rials' => $this->fee_paid_rials,
            'created_at' => $this->created_at->toISOString(),
            'sla_deadline_at' => $this->sla_deadline_at?->toISOString(),
            'available_actions' => $actions,
        ];
    }
}
