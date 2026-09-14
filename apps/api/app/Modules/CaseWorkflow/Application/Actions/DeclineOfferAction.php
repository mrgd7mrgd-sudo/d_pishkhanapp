<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Exceptions\OfferAlreadyTakenException;
use App\Modules\CaseWorkflow\Domain\Exceptions\OfferExpiredException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Jobs\DispatchCaseJob;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * DeclineOfferAction (Architecture §5.8, TASK-069).
 * Thread-safe offer decline with horizontal isolation, SLA penalty, and automatic next-round advance.
 */
final class DeclineOfferAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(DispatchOffer $offer, Operator $operator): array
    {
        // Horizontal isolation: operator can only decline offers for their assigned office (§7.3, TASK-031)
        if ($operator->office_id === null || $operator->office_id !== $offer->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'پیشنهاد یافت نشد.',
            ], 404));
        }

        $now = CarbonImmutable::now();

        // Natural TTL expiration check
        if ($offer->expires_at <= $now) {
            throw new OfferExpiredException;
        }

        if ($offer->status !== DispatchOfferStatus::PENDING) {
            throw new OfferAlreadyTakenException;
        }

        return DB::transaction(function () use ($offer, $operator, $now): array {
            /** @var DispatchOffer|null $lockedOffer */
            $lockedOffer = DispatchOffer::query()
                ->where('id', $offer->id)
                ->lockForUpdate()
                ->first();

            if ($lockedOffer === null) {
                throw new HttpResponseException(new JsonResponse([
                    'status' => 404,
                    'detail' => 'پیشنهاد یافت نشد.',
                ], 404));
            }

            if ($lockedOffer->status !== DispatchOfferStatus::PENDING) {
                throw new OfferAlreadyTakenException;
            }

            $lockedOffer->update([
                'status' => DispatchOfferStatus::DECLINED,
                'responded_at' => $now,
                'responded_by' => $operator->id,
            ]);

            // SLA penalty on office for declining a dispatched offer (§5.8, TASK-069)
            $office = Office::query()->where('id', $lockedOffer->office_id)->first();
            if ($office !== null) {
                $newSlaScore = max(0.00, round((float) $office->sla_score - 1.00, 2));
                $office->update(['sla_score' => $newSlaScore]);
            }

            // If this was the last pending offer of the round, advance to next round
            $remainingPending = DispatchOffer::query()
                ->where('case_id', $lockedOffer->case_id)
                ->where('round', $lockedOffer->round)
                ->where('status', DispatchOfferStatus::PENDING->value)
                ->count();

            if ($remainingPending === 0) {
                $nextRoundExists = DispatchOffer::query()
                    ->where('case_id', $lockedOffer->case_id)
                    ->where('round', '>=', $lockedOffer->round + 1)
                    ->exists();

                $case = CaseRequest::query()->find($lockedOffer->case_id);
                if ($case !== null && $case->status->value === CaseStatus::SEARCHING_OFFICE->value && ! $nextRoundExists) {
                    DispatchCaseJob::dispatch($case->id, $lockedOffer->round + 1);
                }
            }

            return [
                'offer_id' => $lockedOffer->id,
                'case_id' => $lockedOffer->case_id,
                'status' => DispatchOfferStatus::DECLINED->value,
                'declined_at' => $now->toIso8601String(),
            ];
        });
    }
}
