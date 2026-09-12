<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Timeline Step Status Enum (Architecture §6.1, §6.2, TASK-050).
 */
enum TimelineStepStatus: string
{
    case DONE = 'done';
    case CURRENT = 'current';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case WARNING = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::DONE => 'انجام‌شده و تایید',
            self::CURRENT => 'در دست اقدام جاری',
            self::PENDING => 'در انتظار نوبت',
            self::FAILED => 'رد شده یا ناموفق',
            self::WARNING => 'نقص مدرک یا هشدار',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
