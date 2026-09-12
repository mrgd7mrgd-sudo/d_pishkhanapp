<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Timeline Actor Type Enum (Architecture §6.1, §6.2, TASK-050).
 */
enum TimelineActorType: string
{
    case CITIZEN = 'citizen';
    case OPERATOR = 'operator';
    case SYSTEM = 'system';
    case GOVERNMENT = 'government';
    case COURIER = 'courier';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'شهروند (متقاضی)',
            self::OPERATOR => 'کارشناس دفتر',
            self::SYSTEM => 'سامانه هوشمند',
            self::GOVERNMENT => 'دستگاه دولتی',
            self::COURIER => 'سفیر تحویل (پیک/پست)',
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
