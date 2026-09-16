<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain\Enums;

enum AiMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
