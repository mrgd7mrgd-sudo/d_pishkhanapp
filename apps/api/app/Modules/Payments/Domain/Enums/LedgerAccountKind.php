<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

enum LedgerAccountKind: string
{
    case WALLET = 'wallet';
    case PAYABLE = 'payable';
    case REVENUE = 'revenue';
    case CLEARING = 'clearing';
    case ESCROW = 'escrow';

    public function label(): string
    {
        return match ($this) {
            self::WALLET => 'کیف پول نقدی',
            self::PAYABLE => 'حساب پرداختنی (بستانکار دفتر/مشاور)',
            self::REVENUE => 'درآمد پلتفرم',
            self::CLEARING => 'حساب واسط تسویه درگاه',
            self::ESCROW => 'حساب امانی پرونده‌ها',
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
