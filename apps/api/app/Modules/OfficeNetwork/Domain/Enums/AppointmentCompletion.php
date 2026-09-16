<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Enums;

enum AppointmentCompletion: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NotCompleted = 'not_completed';

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
            self::Pending => 'در انتظار بررسی',
            self::InProgress => 'در حال انجام خدمت',
            self::Completed => 'خدمت با موفقیت انجام شد',
            self::NotCompleted => 'ناتمام / نیازمند پیگیری',
        };
    }
}
