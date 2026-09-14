<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Enums;

/**
 * Notification Type Enum (Architecture §6.1, §8.3, TASK-072).
 */
enum NotificationType: string
{
    case CASE_STATUS = 'case_status';
    case CASE_RETURNED = 'case_returned';
    case CASE_MESSAGE = 'case_message';
    case SLA_WARNING = 'sla_warning';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CASE_STATUS => 'تغییر وضعیت پرونده',
            self::CASE_RETURNED => 'نقص مدارک و بازگشت پرونده',
            self::CASE_MESSAGE => 'پیام جدید در پرونده',
            self::SLA_WARNING => 'هشدار انقضای مهلت (SLA)',
            self::SYSTEM => 'اطلاعیه سامانه',
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
