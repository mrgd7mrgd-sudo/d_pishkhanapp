<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Enums;

/**
 * DeliveryStatus Enum (Architecture §6.1, §6.3, TASK-094).
 */
enum DeliveryStatus: string
{
    case READY_FOR_DISPATCH = 'ready_for_dispatch';
    case COURIER_ASSIGNED = 'courier_assigned';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::READY_FOR_DISPATCH => 'آماده تحویل به پیک',
            self::COURIER_ASSIGNED => 'سفیر اختصاص یافت',
            self::IN_TRANSIT => 'در مسیر ارسال',
            self::DELIVERED => 'تحویل داده شد',
            self::FAILED => 'تحویل ناموفق (برگشتی)',
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
