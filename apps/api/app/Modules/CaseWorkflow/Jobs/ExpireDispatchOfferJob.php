<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Jobs;

use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * ExpireDispatchOfferJob (Architecture §5.8, TASK-067, TASK-068).
 * Runs with delay on offer expires_at to mark pending offer expired.
 */
final class ExpireDispatchOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15];

    public function __construct(
        public readonly string $offerId
    ) {
        $this->onQueue('dispatch');
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $offer = DispatchOffer::query()->where('id', $this->offerId)->lockForUpdate()->first();
            if ($offer === null) {
                return;
            }

            if ($offer->status === DispatchOfferStatus::PENDING) {
                $offer->update([
                    'status' => DispatchOfferStatus::EXPIRED,
                    'responded_at' => CarbonImmutable::now(),
                ]);
            }
        });
    }
}
