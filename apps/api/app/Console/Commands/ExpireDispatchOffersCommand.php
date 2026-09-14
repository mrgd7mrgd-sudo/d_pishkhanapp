<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Jobs\ExpireDispatchOfferJob;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * ExpireDispatchOffersCommand (Architecture §5.8, §5.9, TASK-068).
 * Scheduled every minute with a Redis lock to catch any unresponded pending dispatch
 * offers that were not expired by the delayed queue job (two-layer defense).
 */
final class ExpireDispatchOffersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pishkhan:expire-dispatch-offers';

    /**
     * @var string
     */
    protected $description = 'Expire unresponded pending dispatch offers past their TTL and advance dispatch round';

    public function handle(): int
    {
        $lock = Cache::lock('lock:sched:expire_dispatch_offers', 60);

        if (! $lock->get()) {
            $this->warn('ExpireDispatchOffersCommand is already running on another instance.');

            return self::SUCCESS;
        }

        try {
            $now = CarbonImmutable::now();

            $overdueOffers = DispatchOffer::query()
                ->where('status', DispatchOfferStatus::PENDING->value)
                ->where('expires_at', '<=', $now)
                ->get();

            $expiredCount = 0;

            foreach ($overdueOffers as $offer) {
                (new ExpireDispatchOfferJob($offer->id))->handle();
                $expiredCount++;
            }

            $this->info("Expired {$expiredCount} overdue dispatch offers.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
