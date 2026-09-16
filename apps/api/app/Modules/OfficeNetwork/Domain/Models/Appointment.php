<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentReminderType;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Appointment Domain Model (§6.1, §5.3, TASK-099).
 *
 * Invariants:
 * - Non-overlapping slot capacity
 * - Unique queue_number per office and appointment_date
 *
 * @property string $id
 * @property string $citizen_id
 * @property string $office_id
 * @property string $service_id
 * @property Carbon $appointment_date
 * @property string $time_slot
 * @property string $tracking_code
 * @property AppointmentStatus $status
 * @property AppointmentAttendance $attendance
 * @property AppointmentCompletion $completion
 * @property string|null $completion_reason
 * @property string $queue_number
 * @property int $counter_number
 * @property bool $reminder_enabled
 * @property AppointmentReminderType $reminder_type
 * @property Carbon|null $reminder_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read Citizen $citizen
 * @property-read Service $service
 */
final class Appointment extends Model
{
    use HasUuids;

    protected $table = 'appointments';

    protected $fillable = [
        'citizen_id',
        'office_id',
        'service_id',
        'appointment_date',
        'time_slot',
        'tracking_code',
        'status',
        'attendance',
        'completion',
        'completion_reason',
        'queue_number',
        'counter_number',
        'reminder_enabled',
        'reminder_type',
        'reminder_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date:Y-m-d',
            'status' => AppointmentStatus::class,
            'attendance' => AppointmentAttendance::class,
            'completion' => AppointmentCompletion::class,
            'reminder_type' => AppointmentReminderType::class,
            'reminder_enabled' => 'boolean',
            'counter_number' => 'integer',
            'reminder_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function isActive(): bool
    {
        return $this->status === AppointmentStatus::Active;
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::Cancelled;
    }

    public function isCompleted(): bool
    {
        return $this->status === AppointmentStatus::Completed;
    }
}
