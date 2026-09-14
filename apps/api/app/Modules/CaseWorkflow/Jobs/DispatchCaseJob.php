<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Jobs;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Events\DispatchOfferCreated;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

/**
 * DispatchCaseJob (Architecture §5.8, TASK-067).
 * Snapp-style dispatch engine: 5 rounds, radii [5,10,15,25,40] km, batch size 3, TTL 90s.
 */
final class DispatchCaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 45];

    public function __construct(
        public readonly string $caseId,
        public readonly int $round = 1
    ) {
        $this->onQueue('dispatch');
    }

    public function handle(OfficeFinder $finder, CaseStateMachine $sm): void
    {
        $case = CaseRequest::query()->with('service')->find($this->caseId);
        if ($case === null) {
            return;
        }

        // If case was already accepted or moved out of searching_office, stop dispatching (§5.8)
        if ($case->status !== CaseStatus::SEARCHING_OFFICE) {
            return;
        }

        $maxRounds = (int) Config::get('pishkhan.dispatch.max_rounds', 5);

        if ($this->round > $maxRounds) {
            $sm->transition($case, CaseStatus::CANCELLED, TransitionContext::dispatchExhausted());
            RefundCaseFeeJob::dispatch($case->id);

            return;
        }

        /** @var array<int, float> $radiusMap */
        $radiusMap = Config::get('pishkhan.dispatch.radius_km', [
            1 => 5.0,
            2 => 10.0,
            3 => 15.0,
            4 => 25.0,
            5 => 40.0,
        ]);

        $radiusKm = (float) ($radiusMap[$this->round] ?? 40.0);
        $batchSize = (int) Config::get('pishkhan.dispatch.batch_size', 3);
        $offerTtl = (int) Config::get('pishkhan.dispatch.offer_ttl_seconds', 90);

        $categoryId = $case->service?->category_id ?? '';

        $candidates = $finder->findCandidates(
            location: $case->citizen_location,
            categoryId: $categoryId,
            radiusKm: $radiusKm,
            excludeOfficeIds: $case->declinedOfficeIds(),
            limit: $batchSize
        );

        if ($candidates->isEmpty()) {
            self::dispatch($this->caseId, $this->round + 1)->delay(CarbonImmutable::now()->addSeconds(10));

            return;
        }

        $now = CarbonImmutable::now();
        $expiresAt = $now->addSeconds($offerTtl);

        foreach ($candidates as $office) {
            /** @var DispatchOffer $offer */
            $offer = DispatchOffer::query()->create([
                'case_id' => $case->id,
                'office_id' => $office->id,
                'round' => $this->round,
                'status' => DispatchOfferStatus::PENDING,
                'expires_at' => $expiresAt,
            ]);

            Event::dispatch(new DispatchOfferCreated($offer));
            ExpireDispatchOfferJob::dispatch($offer->id)->delay($offer->expires_at);
        }

        self::dispatch($this->caseId, $this->round + 1)->delay($now->addSeconds($offerTtl + 5));
    }
}
