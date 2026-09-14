<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Enums;

/**
 * Message Sender Type Enum (Architecture §6.1, TASK-072).
 */
enum MessageSenderType: string
{
    case CITIZEN = 'citizen';
    case OPERATOR = 'operator';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'شهروند',
            self::OPERATOR => 'کارشناس دفتر',
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
