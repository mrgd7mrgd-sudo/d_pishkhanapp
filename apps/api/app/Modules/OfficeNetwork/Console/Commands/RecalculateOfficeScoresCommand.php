<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Console\Commands;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\SlaScoreCalculator;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * RecalculateOfficeScoresCommand (Architecture §5.9, §9.6, TASK-102).
 * Runs daily at 03:00 to recalculate SLA quality scores for offices.
 */
final class RecalculateOfficeScoresCommand extends Command
{
    protected $signature = 'pishkhan:recalculate-office-scores {--office-id= : Recalculate for a specific office}';

    protected $description = 'Recalculate SLA scores for offices based on rolling performance events';

    public function handle(SlaScoreCalculator $calculator): int
    {
        $lock = Cache::lock('lock:sched:recalculate_office_scores', 300);

        if (! $lock->get()) {
            $this->warn('RecalculateOfficeScoresCommand is already running on another instance.');

            return self::SUCCESS;
        }

        try {
            $specificOfficeId = $this->option('office-id');

            if (is_string($specificOfficeId) && ! empty($specificOfficeId)) {
                $office = Office::query()->find($specificOfficeId);
                if ($office === null) {
                    $this->error("Office [{$specificOfficeId}] not found.");

                    return self::FAILURE;
                }

                $newScore = $calculator->recalculateOffice($office);
                $this->info("Office [{$office->id}] SLA score recalculated to: {$newScore}");

                return self::SUCCESS;
            }

            $offices = Office::query()->whereNull('deleted_at')->get();
            $count = 0;

            foreach ($offices as $office) {
                $calculator->recalculateOffice($office);
                $count++;
            }

            AuditLogger::record(
                action: AuditableAction::SLA_SCORES_RECALCULATED,
                subject: null,
                changes: ['offices_count' => $count],
                context: ['scheduled' => true],
                actorType: 'system',
                actorId: null,
            );

            $this->info("Recalculated SLA scores for {$count} offices.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
