<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Resources;

use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
final class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citizen_id' => $this->citizen_id,
            'office_id' => $this->office_id,
            'office_name' => $this->relationLoaded('office') ? $this->office->name : null,
            'office_code' => $this->relationLoaded('office') ? trim($this->office->code) : null,
            'office_address' => $this->relationLoaded('office') ? $this->office->address : null,
            'service_id' => $this->service_id,
            'service_title' => $this->relationLoaded('service') ? $this->service->title : null,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'time_slot' => $this->time_slot,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'attendance' => $this->attendance->value,
            'attendance_label' => $this->attendance->label(),
            'completion' => $this->completion->value,
            'completion_label' => $this->completion->label(),
            'completion_reason' => $this->completion_reason,
            'queue_number' => $this->queue_number,
            'counter_number' => $this->counter_number,
            'reminder_enabled' => $this->reminder_enabled,
            'reminder_type' => $this->reminder_type->value,
            'reminder_at' => $this->reminder_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
