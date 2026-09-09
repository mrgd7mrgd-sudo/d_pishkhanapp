<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Enums;

/**
 * Office Membership Status Enum (Architecture §6.1, §6.4, TASK-037).
 */
enum OfficeMembershipStatus: string
{
    case REGISTERED_ONLINE = 'registered_online';
    case REGISTERED_OFFLINE = 'registered_offline';
    case UNREGISTERED = 'unregistered';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED_ONLINE => 'عضویت آنلاین تأییدشده',
            self::REGISTERED_OFFLINE => 'عضویت سنتی (فقط حضوری)',
            self::UNREGISTERED => 'ثبت‌نام‌نشده در سامانه',
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
