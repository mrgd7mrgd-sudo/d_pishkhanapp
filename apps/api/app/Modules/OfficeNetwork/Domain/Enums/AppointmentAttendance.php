<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Enums;

enum AppointmentAttendance: string
{
    case Pending = 'pending';
    case Attended = 'attended';
    case Absent = 'absent';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار حضور',
            self::Attended => 'حاضر در دفتر',
            self::Absent => 'عدم مراجعه (غایب)',
        };
    }
}
