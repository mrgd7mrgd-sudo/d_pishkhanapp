<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function labelFarsi(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Expired => 'منقضی شده',
            self::Cancelled => 'لغو شده',
        };
    }
}
