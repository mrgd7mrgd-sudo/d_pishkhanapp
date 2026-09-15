<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * RefundReason Enum (Architecture §3.5, §8.2, TASK-088).
 */
enum RefundReason: string
{
    case DISPATCH_EXHAUSTED = 'dispatch.exhausted';
    case DEADLINE_EXPIRED = 'deadline.expired';
    case CASE_REJECTED = 'case.rejected';
    case MANUAL = 'manual';

    public function isFullRefund(): bool
    {
        return $this === self::DISPATCH_EXHAUSTED;
    }

    public function refundRatio(): float
    {
        return match ($this) {
            self::DISPATCH_EXHAUSTED => 1.0,
            self::DEADLINE_EXPIRED, self::CASE_REJECTED => 0.7,
            self::MANUAL => 1.0,
        };
    }
}
