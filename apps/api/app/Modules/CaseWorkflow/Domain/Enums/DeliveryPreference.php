<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Delivery Preference Enum (Architecture §6.1, §6.2, TASK-049).
 */
enum DeliveryPreference: string
{
    case IN_PERSON = 'in_person';
    case COURIER = 'courier';
    case POST = 'post';

    public function label(): string
    {
        return match ($this) {
            self::IN_PERSON => 'تحویل حضوری در دفتر',
            self::COURIER => 'ارسال با پیک شهری',
            self::POST => 'ارسال پستی به نشانی',
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
