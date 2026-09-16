<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Shared\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

final class CancelAppointmentAction
{
    public function execute(Appointment $appointment, Authenticatable $actor, ?string $reason = null): Appointment
    {
        if (! $appointment->isActive()) {
            throw ValidationException::withMessages([
                'appointment' => ['تنها نوبت‌های در وضعیت فعال قابل لغو هستند.'],
            ]);
        }

        $appointment->status = AppointmentStatus::Cancelled;
        $appointment->cancelled_at = Carbon::now();
        $appointment->cancellation_reason = $reason ?? 'لغو توسط متقاضی';
        $appointment->save();

        AuditLogger::record(
            action: 'appointment.cancelled',
            subject: $appointment,
            changes: [
                'status' => AppointmentStatus::Cancelled->value,
                'cancelled_at' => $appointment->cancelled_at->toIso8601String(),
                'cancellation_reason' => $appointment->cancellation_reason,
            ],
            context: ['office_id' => $appointment->office_id],
            actorType: $actor instanceof Operator ? 'operator' : 'citizen',
            actorId: (string) $actor->getAuthIdentifier()
        );

        return $appointment;
    }
}
