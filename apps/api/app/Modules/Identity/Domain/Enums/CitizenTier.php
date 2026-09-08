<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum CitizenTier: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';

    public function label(): string
    {
        return match ($this) {
            self::BRONZE => 'شهروند برنزی (پایه)',
            self::SILVER => 'شهروند نقره‌ای (ویژه)',
            self::GOLD => 'شهروند طلایی (VIP)',
        };
    }
}
