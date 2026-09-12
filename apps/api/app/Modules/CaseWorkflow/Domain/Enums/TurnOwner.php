<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Turn Owner Enum (Architecture §4.7, §5.4, §6.1, TASK-049).
 * Single source of truth for who holds the action turn.
 */
enum TurnOwner: string
{
    case CITIZEN = 'citizen';
    case OFFICE = 'office';
    case GOVERNMENT = 'government';
    case POSTAL = 'postal';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'شهروند (متقاضی)',
            self::OFFICE => 'دفتر پیشخوان',
            self::GOVERNMENT => 'دستگاه دولتی / سامانه مرجع',
            self::POSTAL => 'پست / پیک تحویل',
            self::SYSTEM => 'سامانه هوشمند',
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
