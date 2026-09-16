<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain;

use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class AppointmentSlotManager
{
    public const DEFAULT_SLOT_DURATION_MINUTES = 30;

    public static function isOfficeOpenOnDate(Office $office, CarbonInterface|string $date): bool
    {
        $carbonDate = $date instanceof CarbonInterface
            ? $date->copy()->setTimezone('Asia/Tehran')
            : Carbon::parse($date, 'Asia/Tehran');

        if ($carbonDate->dayOfWeek === Carbon::FRIDAY) {
            return false;
        }

        $workingHours = is_array($office->working_hours) ? $office->working_hours : [];

        if (! empty($workingHours['is_closed'])) {
            return false;
        }

        $dateKey = $carbonDate->format('Y-m-d');
        if (! empty($workingHours['holidays']) && is_array($workingHours['holidays'])) {
            if (in_array($dateKey, $workingHours['holidays'], true)) {
                return false;
            }
        }

        return true;
    }

    public static function getSlotCapacity(Office $office): int
    {
        $workingHours = is_array($office->working_hours) ? $office->working_hours : [];
        if (isset($workingHours['slot_capacity']) && is_numeric($workingHours['slot_capacity'])) {
            return max(1, (int) $workingHours['slot_capacity']);
        }

        $counters = max(1, (int) $office->active_counters);

        return max(2, $counters * 2);
    }

    /**
     * @return list<array{time_slot: string, start_time: string, end_time: string, capacity: int, booked_count: int, available_capacity: int, is_available: bool}>
     */
    public static function generateSlots(Office $office, string $dateStr): array
    {
        if (! self::isOfficeOpenOnDate($office, $dateStr)) {
            return [];
        }

        $bounds = self::resolveTimeBounds($office, $dateStr);
        $capacity = self::getSlotCapacity($office);
        $bookedCounts = self::queryBookedCounts($office->id, $dateStr);

        return self::buildSlotList($bounds['start'], $bounds['end'], $capacity, $bookedCounts);
    }

    public static function isValidSlotForDate(Office $office, string $dateStr, string $timeSlot): bool
    {
        $slots = self::generateSlots($office, $dateStr);
        foreach ($slots as $slot) {
            if ($slot['time_slot'] === $timeSlot) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolveTimeBounds(Office $office, string $dateStr): array
    {
        $carbonDate = Carbon::parse($dateStr, 'Asia/Tehran');
        $isThursday = $carbonDate->dayOfWeek === Carbon::THURSDAY;
        $workingHours = is_array($office->working_hours) ? $office->working_hours : [];

        $startTimeStr = '08:00';
        $endTimeStr = $isThursday ? '13:30' : (string) ($workingHours['end'] ?? '18:00');

        return [
            'start' => Carbon::parse("{$dateStr} {$startTimeStr}", 'Asia/Tehran'),
            'end' => Carbon::parse("{$dateStr} {$endTimeStr}", 'Asia/Tehran'),
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function queryBookedCounts(string $officeId, string $dateStr): array
    {
        /** @var array<string, int> $counts */
        $counts = Appointment::where('office_id', $officeId)
            ->where('appointment_date', $dateStr)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->groupBy('time_slot')
            ->selectRaw('time_slot, count(*) as total')
            ->pluck('total', 'time_slot')
            ->all();

        return $counts;
    }

    /**
     * @param  array<string, int>  $bookedCounts
     * @return list<array{time_slot: string, start_time: string, end_time: string, capacity: int, booked_count: int, available_capacity: int, is_available: bool}>
     */
    private static function buildSlotList(Carbon $start, Carbon $end, int $capacity, array $bookedCounts): array
    {
        $slots = [];
        $current = $start->copy();

        while ($current->lt($end)) {
            $slotStart = $current->format('H:i');
            $next = $current->copy()->addMinutes(self::DEFAULT_SLOT_DURATION_MINUTES);

            if ($next->gt($end)) {
                break;
            }

            $slotEnd = $next->format('H:i');
            $timeSlot = "{$slotStart}-{$slotEnd}";
            $booked = (int) ($bookedCounts[$timeSlot] ?? 0);
            $available = max(0, $capacity - $booked);

            $slots[] = [
                'time_slot' => $timeSlot,
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'capacity' => $capacity,
                'booked_count' => $booked,
                'available_capacity' => $available,
                'is_available' => $available > 0,
            ];

            $current = $next;
        }

        return $slots;
    }
}
