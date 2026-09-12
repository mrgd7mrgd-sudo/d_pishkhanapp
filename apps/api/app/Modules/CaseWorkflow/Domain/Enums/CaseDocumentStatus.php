<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Case Document Status Enum (Architecture §6.1, §6.2, TASK-050).
 */
enum CaseDocumentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار بررسی',
            self::PROCESSING => 'در حال پردازش امنیتی و کیفیت',
            self::VERIFIED => 'تأییدشده',
            self::REJECTED => 'رد شده (دارای نقص)',
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
