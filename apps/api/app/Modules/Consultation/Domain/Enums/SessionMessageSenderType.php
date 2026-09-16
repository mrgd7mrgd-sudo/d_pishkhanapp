<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Enums;

enum SessionMessageSenderType: string
{
    case Citizen = 'citizen';
    case Advisor = 'advisor';
    case System = 'system';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
