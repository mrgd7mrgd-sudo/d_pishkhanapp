<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Resources;

use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseTimelineStep
 */
final class TimelineStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'turn_owner' => $this->turn_owner->value,
            'turn_owner_label' => $this->turn_owner_label,
            'occurred_at' => $this->occurred_at->toISOString(),
            'office_note' => $this->office_note,
            'duration_actual_minutes' => $this->duration_actual_minutes,
            'duration_typical_minutes' => $this->duration_typical_minutes,
        ];
    }
}
