<?php

declare(strict_types=1);

namespace App\Integration\Sms\Enums;

enum SmsDeliveryStatus: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'ارسال شده',
            self::DELIVERED => 'تحویل داده شده',
            self::FAILED => 'ناموفق',
            self::UNKNOWN => 'نامشخص',
        };
    }
}
