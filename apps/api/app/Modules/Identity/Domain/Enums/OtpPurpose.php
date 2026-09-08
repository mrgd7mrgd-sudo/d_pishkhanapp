<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum OtpPurpose: string
{
    case LOGIN = 'login';
    case REGISTER = 'register';
    case DELEGATION = 'delegation';
    case SENSITIVE_ACTION = 'sensitive_action';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN => 'ورود به سامانه',
            self::REGISTER => 'ثبت نام در سامانه',
            self::DELEGATION => 'تأیید نمایندگی',
            self::SENSITIVE_ACTION => 'اقدام حساس',
        };
    }
}
