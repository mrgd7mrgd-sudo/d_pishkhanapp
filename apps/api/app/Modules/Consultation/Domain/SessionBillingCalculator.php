<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain;

use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Models\Advisor;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * SessionBillingCalculator (§5.3, §8.2, TASK-119).
 *
 * Invariant §5.3: Duration and fee are computed STRICTLY from server timestamps, NEVER from client input.
 * Revenue split: 80% Advisor payable / 20% Platform revenue (§8.2).
 */
final class SessionBillingCalculator
{
    public const ADVISOR_SHARE_PERCENT = 80;

    public const PLATFORM_SHARE_PERCENT = 20;

    /**
     * Calculate session duration in seconds and fee in Rials strictly from server timestamps.
     *
     * @return array{
     *     duration_seconds: int,
     *     total_fee_rials: int,
     *     advisor_fee_rials: int,
     *     platform_fee_rials: int
     * }
     */
    public static function calculate(
        Advisor $advisor,
        ConsultationMode $mode,
        CarbonInterface $startedAt,
        CarbonInterface $endedAt
    ): array {
        if ($endedAt->lessThan($startedAt)) {
            throw new InvalidArgumentException('زمان پایان جلسه نمی‌تواند قبل از زمان شروع باشد.');
        }

        $durationSeconds = (int) $startedAt->diffInSeconds($endedAt);

        $totalFeeRials = match ($mode) {
            ConsultationMode::Call => (int) (
                max(1, (int) ceil($durationSeconds / 60)) * $advisor->price_phone_per_minute_rials
            ),
            ConsultationMode::CaseReview => (int) $advisor->price_deep_review_rials,
            ConsultationMode::Text => (int) $advisor->price_text_chat_rials,
        };

        $advisorFeeRials = (int) round(($totalFeeRials * self::ADVISOR_SHARE_PERCENT) / 100);
        $platformFeeRials = $totalFeeRials - $advisorFeeRials;

        return [
            'duration_seconds' => $durationSeconds,
            'total_fee_rials' => $totalFeeRials,
            'advisor_fee_rials' => $advisorFeeRials,
            'platform_fee_rials' => $platformFeeRials,
        ];
    }
}
