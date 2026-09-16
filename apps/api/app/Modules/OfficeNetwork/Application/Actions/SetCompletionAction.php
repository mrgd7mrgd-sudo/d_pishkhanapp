<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SetCompletionAction
{
    /**
     * Update appointment completion status, completion reason, status, and record audit log.
     *
     * @throws ValidationException
     */
    public function execute(
        Operator $operator,
        Appointment $appointment,
        AppointmentCompletion $completion,
        ?string $completionReason = null
    ): Appointment {
        if ($completion === AppointmentCompletion::NotCompleted && ($completionReason === null || trim($completionReason) === '')) {
            throw ValidationException::withMessages([
                'completion_reason' => ['در صورت ناتمام ماندن نوبت، ثبت علت الزامی است.'],
            ]);
        }

        return DB::transaction(function () use ($operator, $appointment, $completion, $completionReason): Appointment {
            $appointment->completion = $completion;
            $appointment->completion_reason = $completionReason;

            $appointment->status = match ($completion) {
                AppointmentCompletion::Completed,
                AppointmentCompletion::NotCompleted => AppointmentStatus::Completed,
                AppointmentCompletion::InProgress,
                AppointmentCompletion::Pending => AppointmentStatus::Active,
            };

            $appointment->save();

            AuditLogger::record(
                action: AuditableAction::APPOINTMENT_COMPLETION_UPDATED,
                subject: $appointment,
                changes: [
                    'completion' => $completion->value,
                    'completion_reason' => $completionReason,
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
