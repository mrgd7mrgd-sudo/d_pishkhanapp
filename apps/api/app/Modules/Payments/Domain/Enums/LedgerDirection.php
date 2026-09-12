<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

enum LedgerDirection: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'بدهکار (برداشت)',
            self::CREDIT => 'بستانکار (واریز)',
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
