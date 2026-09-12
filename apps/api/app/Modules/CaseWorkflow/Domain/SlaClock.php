<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use Carbon\CarbonImmutable;

/**
 * SlaClock (Architecture §3.5, §5.9, TASK-061).
 * Computes remaining SLA duration and expiration dynamically from server timestamp.
 */
final class SlaClock
{
    public const ACTION_REQUIRED_TIMEOUT_HOURS = 72;

    /**
     * Compute remaining seconds until SLA deadline.
     */
    public function getRemainingSeconds(CaseRequest $case): int
    {
        if ($this->isTerminalStatus($case->status)) {
            return 0;
        }

        $deadline = $this->getDeadline($case);
        $now = CarbonImmutable::now();

        return max(0, $deadline->getTimestamp() - $now->getTimestamp());
    }

    /**
     * Determine if case deadline has been breached.
     */
    public function isBreached(CaseRequest $case): bool
    {
        if ($this->isTerminalStatus($case->status)) {
            return false;
        }

        $deadline = $this->getDeadline($case);
        $now = CarbonImmutable::now();

        return $now->greaterThan($deadline);
    }

    /**
     * Determine if an action_required case has exceeded its 72-hour citizen response deadline.
     */
    public function isActionRequiredExpired(CaseRequest $case): bool
    {
        if ($case->status !== CaseStatus::ACTION_REQUIRED) {
            return false;
        }

        $deadline = $this->getActionRequiredDeadline($case);
        $now = CarbonImmutable::now();

        return $now->greaterThan($deadline);
    }

    /**
     * Get exact SLA deadline as CarbonImmutable.
     */
    public function getDeadline(CaseRequest $case): CarbonImmutable
    {
        $status = $case->status;
        if ($status === CaseStatus::ACTION_REQUIRED) {
            return $this->getActionRequiredDeadline($case);
        }

        if ($case->sla_deadline_at !== null) {
            return CarbonImmutable::instance($case->sla_deadline_at);
        }

        $service = $case->service;
        $slaHours = ($service !== null && $service->estimated_days_max > 0)
            ? $service->estimated_days_max * 24
            : 48;
        $base = CarbonImmutable::instance($case->created_at);

        return $base->addHours($slaHours);
    }

    /**
     * Calculate 72-hour deadline for citizen correction in action_required state.
     */
    public function getActionRequiredDeadline(CaseRequest $case): CarbonImmutable
    {
        if ($case->sla_deadline_at !== null) {
            return CarbonImmutable::instance($case->sla_deadline_at);
        }

        /** @var CaseReturn|null $latestReturn */
        $latestReturn = $case->returns()->latest('created_at')->first();
        if ($latestReturn?->deadline_at !== null) {
            return CarbonImmutable::instance($latestReturn->deadline_at);
        }

        $base = CarbonImmutable::instance($case->updated_at);

        return $base->addHours(self::ACTION_REQUIRED_TIMEOUT_HOURS);
    }

    private function isTerminalStatus(CaseStatus $status): bool
    {
        return in_array($status, [
            CaseStatus::COMPLETED,
            CaseStatus::REJECTED,
            CaseStatus::CANCELLED,
        ], true);
    }
}
