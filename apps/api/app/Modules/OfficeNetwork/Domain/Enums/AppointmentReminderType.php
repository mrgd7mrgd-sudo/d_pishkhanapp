<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Enums;

enum AppointmentReminderType: string
{
    case Sms = 'sms';
    case Push = 'push';
    case All = 'all';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'فقط پیامک',
            self::Push => 'فقط اعلان سیستمی (Push)',
            self::All => 'پیامک و اعلان سیستمی',
        };
    }
}
