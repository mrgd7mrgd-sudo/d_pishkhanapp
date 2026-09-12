<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Dispatch Offer Status Enum (Architecture §5.8, §6.1, TASK-066).
 * Single source of truth for the 4 dispatch offer statuses.
 */
enum DispatchOfferStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پاسخ دفتر',
            self::ACCEPTED => 'پذیرفته شده توسط دفتر',
            self::DECLINED => 'رد شده توسط دفتر',
            self::EXPIRED => 'منقضی شده',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::ACCEPTED, self::DECLINED, self::EXPIRED => true,
            self::PENDING => false,
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
