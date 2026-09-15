<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * PayoutStatus Enum (Architecture §6.1, §8.2, TASK-084, TASK-090).
 */
enum PayoutStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار تسویه',
            self::PROCESSING => 'در حال پردازش',
            self::COMPLETED => 'تکمیل شده',
            self::FAILED => 'ناموفق',
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
