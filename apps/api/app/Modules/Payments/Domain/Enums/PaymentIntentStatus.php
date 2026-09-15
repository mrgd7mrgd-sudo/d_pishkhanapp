<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * PaymentIntentStatus Enum (Architecture §6.1, §8.2, TASK-084).
 */
enum PaymentIntentStatus: string
{
    case CREATED = 'created';
    case REDIRECTED = 'redirected';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case RECONCILED = 'reconciled';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'ایجاد شده',
            self::REDIRECTED => 'هدایت به درگاه',
            self::PAID => 'پرداخت موفق',
            self::FAILED => 'پرداخت ناموفق',
            self::EXPIRED => 'منقضی شده',
            self::RECONCILED => 'مغایرت‌گیری شده',
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
