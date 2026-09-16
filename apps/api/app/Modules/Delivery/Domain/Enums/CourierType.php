<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Enums;

/**
 * CourierType Enum (Architecture §6.1, §6.3, TASK-094).
 */
enum CourierType: string
{
    case EXPRESS_COURIER = 'express_courier';
    case SPECIAL_POST = 'special_post';
    case REGISTERED_POST = 'registered_post';

    public function label(): string
    {
        return match ($this) {
            self::EXPRESS_COURIER => 'پیک اختصاصی شهری (اکسپرس)',
            self::SPECIAL_POST => 'پست ویژه (پیشتاز ۲۴ ساعته)',
            self::REGISTERED_POST => 'پست سفارشی سراسری',
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
