<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyAuditChainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:verify-chain {--from= : Start date (YYYY-MM-DD)} {--to= : End date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the cryptographic hash chain integrity of the immutable audit_logs table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting audit log cryptographic hash chain verification...');

        $from = $this->option('from');
        $to = $this->option('to');

        $query = DB::table('audit_logs')
            ->orderBy('occurred_at', 'asc')
            ->orderBy('created_at', 'asc');

        if (is_string($from) && $from !== '') {
            $query->where('occurred_at', '>=', CarbonImmutable::parse($from)->startOfDay());
        }

        if (is_string($to) && $to !== '') {
            $query->where('occurred_at', '<=', CarbonImmutable::parse($to)->endOfDay());
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info('No audit log entries found in the specified window.');

            return self::SUCCESS;
        }

        $expectedPrevHash = null;
        $verifiedCount = 0;

        foreach ($records as $record) {
            $subjectString = ($record->subject_type !== null && $record->subject_id !== null)
                ? "{$record->subject_type}:{$record->subject_id}"
                : null;

            $changes = json_decode($record->changes, true);
            if (! is_array($changes)) {
                $changes = [];
            }

            $occurredAtIso = CarbonImmutable::parse($record->occurred_at)->toIso8601String();

            // 1. Verify prev_hash matches prior entry if not at beginning of queried partition
            if ($expectedPrevHash !== null && $record->prev_hash !== $expectedPrevHash) {
                $this->error("CHAIN BROKEN: Audit log ID [{$record->id}] has prev_hash [{$record->prev_hash}], but expected [{$expectedPrevHash}].");

                return self::FAILURE;
            }

            // 2. Recompute entry_hash and compare
            $computedHash = AuditLogger::computeEntryHash(
                $record->prev_hash,
                $record->action,
                $record->actor_type,
                $record->actor_id,
                $subjectString,
                $changes,
                $occurredAtIso
            );

            if ($computedHash !== $record->entry_hash) {
                $this->error("TAMPER DETECTED: Audit log ID [{$record->id}] has invalid entry_hash. Computed: [{$computedHash}], Recorded: [{$record->entry_hash}].");

                return self::FAILURE;
            }

            $expectedPrevHash = $record->entry_hash;
            $verifiedCount++;
        }

        $this->info("SUCCESS: Cryptographic hash chain verified cleanly across {$verifiedCount} records. Zero tamper detected.");

        return self::SUCCESS;
    }
}
