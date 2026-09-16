<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Console\Commands;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\OfficeNetwork\Application\Actions\RecordOfficeSlaEventAction;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * CheckSlaBreachesCommand (Architecture §5.9, §9.6, TASK-102).
 * Runs every 15 minutes to detect active office cases exceeding their SLA deadline.
 */
final class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'pishkhan:check-sla-breaches';

    protected $description = 'Detect cases exceeding SLA deadlines and record office SLA breach events';

    public function handle(RecordOfficeSlaEventAction $recordAction): int
    {
        $lock = Cache::lock('lock:sched:check_sla_breaches', 120);

        if (! $lock->get()) {
            $this->warn('CheckSlaBreachesCommand is already running on another instance.');

            return self::SUCCESS;
        }

        try {
            $now = CarbonImmutable::now();

            $overdueCases = CaseRequest::query()
                ->whereIn('status', [
                    CaseStatus::ASSIGNED_TO_OFFICE->value,
                    CaseStatus::EXPERT_REVIEW->value,
                    CaseStatus::READY_FOR_ISSUE->value,
                ])
                ->whereNotNull('office_id')
                ->whereNotNull('sla_deadline_at')
                ->where('sla_deadline_at', '<', $now)
                ->get();

            $breachesCount = 0;

            foreach ($overdueCases as $case) {
                if ($case->office_id === null || $case->sla_deadline_at === null) {
                    continue;
                }

                $alreadyRecorded = OfficeSlaEvent::query()
                    ->where('office_id', $case->office_id)
                    ->where('case_id', $case->id)
                    ->where('event_type', 'review_delayed')
                    ->exists();

                if ($alreadyRecorded) {
                    continue;
                }

                $delayMinutes = max(1, $now->diffInMinutes($case->sla_deadline_at));

                $recordAction->execute(
                    officeId: $case->office_id,
                    eventType: 'review_delayed',
                    caseId: $case->id,
                    penaltyPoints: 3,
                    durationMinutes: (int) $delayMinutes,
                    isBreach: true,
                    occurredAt: $now,
                    metadata: [
                        'status' => $case->status->value,
                        'deadline_at' => $case->sla_deadline_at->toIso8601String(),
                    ]
                );

                $breachesCount++;
            }

            $this->info("Detected and recorded {$breachesCount} SLA breaches.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
