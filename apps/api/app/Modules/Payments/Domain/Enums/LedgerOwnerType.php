<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

enum LedgerOwnerType: string
{
    case CITIZEN = 'citizen';
    case OFFICE = 'office';
    case ADVISOR = 'advisor';
    case PLATFORM = 'platform';
    case GATEWAY = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'شهروند',
            self::OFFICE => 'دفتر پیشخوان',
            self::ADVISOR => 'مشاور تخصصی',
            self::PLATFORM => 'سامانه و پلتفرم مرکزی',
            self::GATEWAY => 'درگاه پرداخت بانکی',
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
