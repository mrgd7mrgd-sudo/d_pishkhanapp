<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final class SetAttendanceAction
{
    /**
     * Update appointment attendance, assign counter, update status if absent, and record audit log.
     */
    public function execute(
        Operator $operator,
        Appointment $appointment,
        AppointmentAttendance $attendance,
        ?int $counterNumber = null
    ): Appointment {
        return DB::transaction(function () use ($operator, $appointment, $attendance, $counterNumber): Appointment {
            $appointment->attendance = $attendance;

            if ($counterNumber !== null) {
                $appointment->counter_number = $counterNumber;
            } elseif ($operator->counter_number !== null && $appointment->counter_number === 0) {
                $appointment->counter_number = $operator->counter_number;
            }

            if ($attendance === AppointmentAttendance::Absent) {
                $appointment->status = AppointmentStatus::Completed;
            }

            $appointment->save();

            AuditLogger::record(
                action: AuditableAction::APPOINTMENT_ATTENDANCE_UPDATED,
                subject: $appointment,
                changes: [
                    'attendance' => $attendance->value,
                    'counter_number' => $appointment->counter_number,
                    'status' => $appointment->status->value,
                ],
                context: [
                    'operator_id' => $operator->id,
                    'office_id' => $operator->office_id,
                ],
                actorType: get_class($operator),
                actorId: (string) $operator->id,
            );

            return $appointment;
        });
    }
}
