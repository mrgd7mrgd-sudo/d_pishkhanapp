<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\AppointmentSlotManager;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentReminderType;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Exceptions\OfficeClosedException;
use App\Modules\OfficeNetwork\Domain\Exceptions\SlotCapacityExceededException;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Shared\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BookAppointmentAction
{
    public function execute(
        Citizen $citizen,
        string $officeId,
        string $serviceId,
        string $appointmentDate,
        string $timeSlot,
        AppointmentReminderType $reminderType = AppointmentReminderType::All,
        bool $reminderEnabled = true
    ): Appointment {
        $office = Office::findOrFail($officeId);
        $service = Service::findOrFail($serviceId);

        $this->validatePreconditions($office, $citizen, $appointmentDate, $timeSlot);

        return $this->bookInTransaction(
            $citizen,
            $office,
            $service,
            $appointmentDate,
            $timeSlot,
            $reminderType,
            $reminderEnabled
        );
    }

    private function validatePreconditions(Office $office, Citizen $citizen, string $date, string $slot): void
    {
        if (! AppointmentSlotManager::isOfficeOpenOnDate($office, $date)) {
            throw new OfficeClosedException('دفتر در تاریخ انتخابی تعطیل است.');
        }

        if (! AppointmentSlotManager::isValidSlotForDate($office, $date, $slot)) {
            throw ValidationException::withMessages([
                'time_slot' => ['بازه زمانی انتخابی برای این روز معتبر نیست.'],
            ]);
        }

        $duplicate = Appointment::where('citizen_id', $citizen->id)
            ->where('office_id', $office->id)
            ->where('appointment_date', $date)
            ->where('time_slot', $slot)
            ->where('status', AppointmentStatus::Active)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'time_slot' => ['شما قبلاً در این بازه زمانی نوبت فعال دارید.'],
            ]);
        }
    }

    private function bookInTransaction(
        Citizen $citizen,
        Office $office,
        Service $service,
        string $date,
        string $slot,
        AppointmentReminderType $reminderType,
        bool $reminderEnabled
    ): Appointment {
        return DB::transaction(function () use ($citizen, $office, $service, $date, $slot, $reminderType, $reminderEnabled): Appointment {
            Office::where('id', $office->id)->lockForUpdate()->first();

            $capacity = AppointmentSlotManager::getSlotCapacity($office);
            $bookedCount = Appointment::where('office_id', $office->id)
                ->where('appointment_date', $date)
                ->where('time_slot', $slot)
                ->where('status', '!=', AppointmentStatus::Cancelled->value)
                ->lockForUpdate()
                ->count();

            if ($bookedCount >= $capacity) {
                throw new SlotCapacityExceededException('ظرفیت این بازه زمانی تکمیل شده است.');
            }

            $queueNumber = $this->nextQueueNumber($office->id, $date);
            $trackingCode = 'APT-'.str_replace('-', '', $date).'-'.strtoupper(Str::random(6));
            $reminderAt = $this->calculateReminderTime($date, $slot);

            $appointment = Appointment::create([
                'citizen_id' => $citizen->id,
                'office_id' => $office->id,
                'service_id' => $service->id,
                'appointment_date' => $date,
                'time_slot' => $slot,
                'tracking_code' => $trackingCode,
                'status' => AppointmentStatus::Active,
                'attendance' => AppointmentAttendance::Pending,
                'completion' => AppointmentCompletion::Pending,
                'queue_number' => $queueNumber,
                'counter_number' => 1,
                'reminder_enabled' => $reminderEnabled,
                'reminder_type' => $reminderType,
                'reminder_at' => $reminderAt,
            ]);

            $this->recordAudit($citizen, $appointment, $trackingCode, $queueNumber);

            return $appointment;
        });
    }

    private function nextQueueNumber(string $officeId, string $date): string
    {
        $maxQueue = Appointment::where('office_id', $officeId)
            ->where('appointment_date', $date)
            ->lockForUpdate()
            ->max(DB::raw('CAST(queue_number AS INTEGER)'));

        return (string) (($maxQueue !== null ? (int) $maxQueue : 0) + 1);
    }

    private function calculateReminderTime(string $date, string $slot): Carbon
    {
        $slotParts = explode('-', $slot);
        $startTime = trim($slotParts[0] ?? '08:00');

        return Carbon::parse("{$date} {$startTime}", 'Asia/Tehran')->subHour()->setTimezone('UTC');
    }

    private function recordAudit(Citizen $citizen, Appointment $appointment, string $code, string $queue): void
    {
        AuditLogger::record(
            action: 'appointment.booked',
            subject: $appointment,
            changes: [
                'tracking_code' => $code,
                'office_id' => $appointment->office_id,
                'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                'time_slot' => $appointment->time_slot,
                'queue_number' => $queue,
            ],
            context: ['office_id' => $appointment->office_id],
            actorType: 'citizen',
            actorId: $citizen->id
        );
    }
}
