<?php

declare(strict_types=1);

namespace App\Modules\Identity\Console\Commands;

use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * ExpireDelegationsCommand (Architecture §5.9, §7.1 T4, TASK-122).
 * Automatically marks delegations past their valid_until timestamp as expired.
 */
final class ExpireDelegationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pishkhan:expire-delegations';

    /**
     * @var string
     */
    protected $description = 'Expire active delegations that have passed their valid_until timestamp';

    public function handle(): int
    {
        $lock = Cache::lock('lock:sched:expire_delegations', 60);

        if (! $lock->get()) {
            $this->warn('ExpireDelegationsCommand is already running on another instance.');

            return self::SUCCESS;
        }

        try {
            $now = CarbonImmutable::now();

            $expiredDelegations = Delegation::query()
                ->where('status', DelegationStatus::Active)
                ->where('valid_until', '<=', $now)
                ->get();

            $count = 0;
            foreach ($expiredDelegations as $delegation) {
                $delegation->update([
                    'status' => DelegationStatus::Expired,
                ]);

                AuditLogger::record(
                    action: 'delegation.expired',
                    subject: $delegation,
                    changes: [
                        'status' => 'expired',
                        'valid_until' => $delegation->valid_until?->toISOString(),
                    ],
                    context: ['source' => 'ExpireDelegationsCommand'],
                    actorType: 'system',
                    actorId: 'scheduler',
                );

                $count++;
            }

            $this->info("Expired {$count} delegations.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
