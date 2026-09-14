<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Jobs;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
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

            if ($offer->status !== DispatchOfferStatus::PENDING) {
                return;
            }

            $offer->update([
                'status' => DispatchOfferStatus::EXPIRED,
                'responded_at' => CarbonImmutable::now(),
            ]);

            $remainingPending = DispatchOffer::query()
                ->where('case_id', $offer->case_id)
                ->where('round', $offer->round)
                ->where('status', DispatchOfferStatus::PENDING->value)
                ->count();

            if ($remainingPending === 0) {
                $nextRoundExists = DispatchOffer::query()
                    ->where('case_id', $offer->case_id)
                    ->where('round', '>=', $offer->round + 1)
                    ->exists();

                $case = CaseRequest::query()->find($offer->case_id);
                if ($case !== null && $case->status->value === CaseStatus::SEARCHING_OFFICE->value && ! $nextRoundExists) {
                    DispatchCaseJob::dispatch($case->id, $offer->round + 1);
                }
            }
        });
    }
}
