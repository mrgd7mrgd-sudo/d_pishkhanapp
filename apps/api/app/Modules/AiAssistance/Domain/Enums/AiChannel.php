<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain\Enums;

enum AiChannel: string
{
    case Chat = 'chat';
    case Voice = 'voice';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
