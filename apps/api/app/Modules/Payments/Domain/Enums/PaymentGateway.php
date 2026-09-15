<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * PaymentGateway Enum (Architecture §8.0, §8.2, TASK-084, TASK-085).
 */
enum PaymentGateway: string
{
    case ZARINPAL = 'zarinpal';
    case ZIBAL = 'zibal';
    case FAKE = 'fake';

    public function label(): string
    {
        return match ($this) {
            self::ZARINPAL => 'زرین‌پال',
            self::ZIBAL => 'زیبال',
            self::FAKE => 'درگاه آزمایشی شبیه‌ساز',
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
