<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class AiBudgetGuard
{
    private const WARNING_THRESHOLD_PERCENT = 0.80;

    public function checkRateLimit(string $citizenId): bool
    {
        $hourlyLimit = (int) config('pishkhan.ai.citizen_hourly_limit', 30);
        $dailyLimit = (int) config('pishkhan.ai.citizen_daily_limit', 200);

        $hourlyCount = (int) Cache::get("ai:rate:citizen:{$citizenId}:hour", 0);
        if ($hourlyCount >= $hourlyLimit) {
            return false;
        }

        $dailyCount = (int) Cache::get("ai:rate:citizen:{$citizenId}:day", 0);
        if ($dailyCount >= $dailyLimit) {
            return false;
        }

        return true;
    }

    public function incrementCitizenUsage(string $citizenId): void
    {
        $hourlyKey = "ai:rate:citizen:{$citizenId}:hour";
        $hourlyCount = (int) Cache::get($hourlyKey, 0) + 1;
        Cache::put($hourlyKey, $hourlyCount, 3600);

        $dailyKey = "ai:rate:citizen:{$citizenId}:day";
        $dailyCount = (int) Cache::get($dailyKey, 0) + 1;
        Cache::put($dailyKey, $dailyCount, 86400);
    }

    public function isBudgetAvailable(): bool
    {
        $monthlyLimit = $this->getMonthlyBudget();
        $consumed = $this->getMonthlyUsage();

        return $consumed < $monthlyLimit;
    }

    public function recordUsage(int $costRials): void
    {
        if ($costRials <= 0) {
            return;
        }

        $key = $this->getBudgetCacheKey();
        $current = (int) Cache::get($key, 0) + $costRials;
        Cache::put($key, $current, 86400 * 35);

        if ($this->isWarningThresholdReached()) {
            Log::warning('AI monthly budget reached 80% threshold (§9.6)', [
                'consumed_rials' => $current,
                'budget_limit_rials' => $this->getMonthlyBudget(),
            ]);
        }
    }

    public function getMonthlyUsage(): int
    {
        return (int) Cache::get($this->getBudgetCacheKey(), 0);
    }

    public function getMonthlyBudget(): int
    {
        return (int) config('pishkhan.ai.monthly_budget_rials', 50000000);
    }

    public function isWarningThresholdReached(): bool
    {
        $budget = $this->getMonthlyBudget();
        if ($budget <= 0) {
            return false;
        }

        return ($this->getMonthlyUsage() / $budget) >= self::WARNING_THRESHOLD_PERCENT;
    }

    public function resetMonthlyUsage(): void
    {
        Cache::forget($this->getBudgetCacheKey());
    }

    private function getBudgetCacheKey(): string
    {
        $ym = date('Ym');

        return "ai:budget:month:{$ym}";
    }
}
