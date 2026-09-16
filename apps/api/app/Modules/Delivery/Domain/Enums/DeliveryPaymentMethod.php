<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Enums;

/**
 * DeliveryPaymentMethod Enum (Architecture §6.1, §6.3, TASK-094).
 */
enum DeliveryPaymentMethod: string
{
    case COD = 'cod';
    case PREPAID = 'prepaid';
    case OFFICE_WALLET = 'office_wallet';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'پرداخت در محل (POS پیک)',
            self::PREPAID => 'پرداخت آنلاین پیش‌کرایه',
            self::OFFICE_WALLET => 'کسر از کیف پول دفتر',
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
