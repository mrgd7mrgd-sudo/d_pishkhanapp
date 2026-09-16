<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum DelegationStatus: string
{
    case PendingOtp = 'pending_otp';
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';

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
            self::PendingOtp => 'در انتظار تأیید پیامکی موکل و وکیل',
            self::Active => 'فعال و معتبر',
            self::Revoked => 'عزل / ابطال شده توسط موکل',
            self::Expired => 'منقضی‌شده',
        };
    }
}
