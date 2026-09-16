<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Enums;

enum AppointmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

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
            self::Active => 'فعال / در انتظار مراجعه',
            self::Completed => 'انجام‌شده',
            self::Cancelled => 'لغو‌شده',
        };
    }
}
