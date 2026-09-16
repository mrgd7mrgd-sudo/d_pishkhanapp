<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use Carbon\CarbonImmutable;

/**
 * SlaScoreCalculator (Architecture §5.9, §9.6, TASK-102).
 *
 * Deterministic calculation of office SLA quality score:
 * Base score: 100.00
 * Penalty deduction: Sum of penalty points from breach events in rolling window (default 30 days).
 * Score range: [0.00, 100.00], rounded to 2 decimal places.
 */
final class SlaScoreCalculator
{
    public const BASE_SCORE = 100.00;

    public const DEFAULT_WINDOW_DAYS = 30;

    /**
     * Calculate score given base score and total penalties.
     */
    public function calculate(float $baseScore, float $penalties): float
    {
        $computed = $baseScore - $penalties;

        return max(0.00, min(100.00, round($computed, 2)));
    }

    /**
     * Calculate current SLA score for an office over rolling window.
     */
    public function calculateForOffice(Office|string $office, ?int $windowDays = self::DEFAULT_WINDOW_DAYS): float
    {
        $officeId = $office instanceof Office ? $office->id : $office;

        $query = OfficeSlaEvent::query()
            ->where('office_id', $officeId)
            ->where('is_breach', true);

        if ($windowDays !== null && $windowDays > 0) {
            $since = CarbonImmutable::now()->subDays($windowDays);
            $query->where('occurred_at', '>=', $since);
        }

        $totalPenalties = (float) $query->sum('penalty_points');

        return $this->calculate(self::BASE_SCORE, $totalPenalties);
    }

    /**
     * Recalculate and persist SLA score for an office.
     */
    public function recalculateOffice(Office|string $office, ?int $windowDays = self::DEFAULT_WINDOW_DAYS): float
    {
        $officeModel = $office instanceof Office ? $office : Office::query()->findOrFail($office);
        $newScore = $this->calculateForOffice($officeModel, $windowDays);

        $officeModel->update(['sla_score' => $newScore]);

        return $newScore;
    }
}
