<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Cache;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\OfficeNetwork\Domain\Events\QueueUpdated;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * OfficeQueueCache (Architecture §5.7, §6.7, TASK-076).
 * Manages Redis caching for office live queue with 30-second TTL and DB fallback.
 */
final class OfficeQueueCache
{
    public const TTL_SECONDS = 30;

    public function key(string $officeId): string
    {
        return "office:{$officeId}:queue";
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $officeId): ?array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($this->key($officeId));

        return is_array($cached) ? $cached : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function put(string $officeId, array $data, int $ttl = self::TTL_SECONDS): void
    {
        Cache::put($this->key($officeId), $data, $ttl);
    }

    public function forget(string $officeId): void
    {
        Cache::forget($this->key($officeId));
    }

    /**
     * Recalculates queue state directly from database (source of truth), updates cache, and broadcasts debounced event.
     *
     * @return array<string, mixed>
     */
    public function calculate(string $officeId): array
    {
        /** @var Office|null $office */
        $office = Office::query()->find($officeId);

        $activeCounters = 1;
        if ($office !== null) {
            $activeCounters = max(1, (int) ($office->active_counters > 0 ? $office->active_counters : 1));
        }

        // Active waiting cases in the office (Architecture §6.1, §6.7)
        $waitingCount = CaseRequest::query()
            ->where('office_id', $officeId)
            ->whereIn('status', [
                CaseStatus::ASSIGNED_TO_OFFICE->value,
                CaseStatus::EXPERT_REVIEW->value,
                CaseStatus::ACTION_REQUIRED->value,
                CaseStatus::GOVERNMENT_INQUIRY->value,
                CaseStatus::READY_FOR_ISSUE->value,
            ])
            ->count();

        // Average 15 minutes per case
        $avgMinutesPerCase = 15;
        $estimatedWaitMinutes = (int) ceil(($waitingCount * $avgMinutesPerCase) / $activeCounters);

        $data = [
            'office_id' => $officeId,
            'waiting_queue' => $waitingCount,
            'active_counters' => $activeCounters,
            'estimated_wait_minutes' => $estimatedWaitMinutes,
            'updated_at' => CarbonImmutable::now()->toISOString(),
        ];

        $this->put($officeId, $data, self::TTL_SECONDS);

        // Update database column as well
        if ($office !== null) {
            $office->current_waiting_queue = $waitingCount;
            $office->save();
        }

        // Broadcast QueueUpdated debounced (2s)
        QueueUpdated::dispatchDebounced($officeId, $waitingCount, $activeCounters, 2);

        return $data;
    }

    /**
     * Cache-aside retrieval: returns cached value or recalculates from database (resilience §6.7).
     *
     * @return array<string, mixed>
     */
    public function getOrCalculate(string $officeId): array
    {
        $cached = $this->get($officeId);
        if ($cached !== null) {
            return $cached;
        }

        return $this->calculate($officeId);
    }
}
