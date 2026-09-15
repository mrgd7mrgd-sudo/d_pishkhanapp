<?php

declare(strict_types=1);

namespace App\Modules\Payments\Console\Commands;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Application\Queries\OfficeSettlementReport;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use App\Modules\Payments\Domain\Models\Payout;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GenerateOfficePayoutsCommand (Architecture §5.9, §8.2, TASK-090).
 * Runs daily at 02:00 to generate daily office settlement payouts matching double-entry ledger entries.
 */
final class GenerateOfficePayoutsCommand extends Command
{
    protected $signature = 'payments:generate-office-payouts
                            {--date= : The target date for settlement (Y-m-d), defaults to yesterday}
                            {--office= : Generate payout for a specific office ID only}
                            {--force : Re-generate payout even if one already exists for the period}';

    protected $description = 'Generate daily office payouts and settlement reports based on double-entry ledger';

    public function __construct(
        private readonly OfficeSettlementReport $reportQuery
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting daily office settlement payout generation...');

        // 1. Verify and assert global double-entry ledger integrity
        $this->reportQuery->assertGlobalLedgerBalanced();

        // 2. Determine target period (start and end of day)
        [$periodStart, $periodEnd] = $this->resolvePeriod();
        $this->info("Settlement period: {$periodStart->toDateTimeString()} to {$periodEnd->toDateTimeString()}");

        // 3. Resolve target offices
        $offices = $this->resolveOffices();
        $this->info("Processing {$offices->count()} offices for settlement...");

        $payoutsGenerated = 0;
        foreach ($offices as $office) {
            $payout = $this->processOfficePayout($office, $periodStart, $periodEnd);
            if ($payout !== null) {
                $payoutsGenerated++;
            }
        }

        $this->info("Completed daily settlement. {$payoutsGenerated} payouts generated.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolvePeriod(): array
    {
        /** @var string|null $dateOption */
        $dateOption = $this->option('date');

        if ($dateOption !== null && $dateOption !== '') {
            $targetDate = Carbon::parse($dateOption);
        } else {
            $targetDate = Carbon::yesterday();
        }

        return [
            $targetDate->copy()->startOfDay(),
            $targetDate->copy()->endOfDay(),
        ];
    }

    /**
     * @return Collection<int, Office>
     */
    private function resolveOffices(): Collection
    {
        /** @var string|null $officeId */
        $officeId = $this->option('office');

        if ($officeId !== null && $officeId !== '') {
            /** @var Collection<int, Office> $offices */
            $offices = Office::query()->where('id', $officeId)->get();

            return $offices;
        }

        /** @var Collection<int, Office> $offices */
        $offices = Office::query()->get();

        return $offices;
    }

    private function processOfficePayout(
        Office $office,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd
    ): ?Payout {
        // Idempotency: skip if payout already generated for this office & period
        $existingPayout = Payout::query()
            ->where('office_id', $office->id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->where('status', '!=', PayoutStatus::FAILED->value)
            ->first();

        if ($existingPayout !== null && ! (bool) $this->option('force')) {
            $this->line("Payout already exists for office {$office->code} ({$periodStart->toDateString()}), skipping.");

            return null;
        }

        $report = $this->reportQuery->generate($office->id, $periodStart, $periodEnd);

        if ($report->periodCreditsRials <= 0) {
            return null;
        }

        return DB::transaction(function () use ($office, $periodStart, $periodEnd, $report): Payout {
            $referenceNumber = 'PAYOUT-'.$office->code.'-'.$periodStart->format('Ymd');

            /** @var Payout $payout */
            $payout = Payout::query()->create([
                'office_id' => $office->id,
                'amount_rials' => $report->periodCreditsRials,
                'status' => PayoutStatus::PENDING,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_cases_count' => $report->totalCasesCount,
                'reference_number' => $referenceNumber,
                'generated_at' => now(),
            ]);

            AuditLogger::record(
                action: AuditableAction::PAYOUT_GENERATED,
                subject: $payout,
                changes: [
                    'payout_id' => $payout->id,
                    'office_id' => $office->id,
                    'office_code' => $office->code,
                    'amount_rials' => $payout->amount_rials,
                    'period_start' => $periodStart->toIso8601String(),
                    'period_end' => $periodEnd->toIso8601String(),
                    'total_cases_count' => $payout->total_cases_count,
                    'reference_number' => $referenceNumber,
                ],
                actorType: 'system',
                actorId: null
            );

            Log::info("Generated daily office payout {$payout->id} for office {$office->code}.", [
                'payout_id' => $payout->id,
                'office_id' => $office->id,
                'amount_rials' => $payout->amount_rials,
                'total_cases' => $payout->total_cases_count,
            ]);

            $this->info("✓ Office {$office->code}: Payout {$payout->amount_rials} Rials ({$payout->total_cases_count} cases)");

            return $payout;
        });
    }
}
