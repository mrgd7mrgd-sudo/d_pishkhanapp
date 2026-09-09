<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Domain\Enums;

/**
 * Service Tag Enum (§6.3, D-06, TASK-036).
 * Values match packages/domain/src/service-tags.ts.
 */
enum ServiceTag: string
{
    case ONLINE = 'online';
    case SEMI_ONLINE = 'semi-online';
    case IN_PERSON = 'in-person';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'تماماً آنلاین (غیرحضوری)',
            self::SEMI_ONLINE => 'نیمه‌حضوری (پیش‌ثبت‌نام آنلاین)',
            self::IN_PERSON => 'حضوری (نیازمند باجه)',
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
