<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum OperatorRole: string
{
    case OPERATOR = 'operator';
    case MANAGER = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::OPERATOR => 'اپراتور پیشخوان',
            self::MANAGER => 'مدیر دفتر',
        };
    }
}
