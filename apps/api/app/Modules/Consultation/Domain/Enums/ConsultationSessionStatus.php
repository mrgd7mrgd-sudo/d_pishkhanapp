<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Enums;

enum ConsultationSessionStatus: string
{
    case Scheduled = 'scheduled';
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

    public function labelFarsi(): string
    {
        return match ($this) {
            self::Scheduled => 'زمان‌بندی شده',
            self::Active => 'در حال برگزاری',
            self::Completed => 'پایان یافته',
            self::Cancelled => 'لغو شده',
        };
    }
}
