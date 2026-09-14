<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Events\DispatchOfferTaken;
use App\Modules\CaseWorkflow\Domain\Exceptions\OfferAlreadyTakenException;
use App\Modules\CaseWorkflow\Domain\Exceptions\OfferExpiredException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Models\Operator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * AcceptOfferAction (Architecture §5.8, TASK-069).
 * Thread-safe competitive offer acceptance using SELECT FOR UPDATE on CaseRequest.
 * Enforces horizontal isolation (404 on cross-office access) and prevents race conditions (409 OFFER_ALREADY_TAKEN).
 */
final class AcceptOfferAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(DispatchOffer $offer, Operator $operator): array
    {
        // Horizontal isolation: operator can only access offers for their assigned office (§7.3, TASK-031)
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

        // If offer is already taken/expired due to another office accepting
        if ($offer->status !== DispatchOfferStatus::PENDING) {
            throw new OfferAlreadyTakenException;
        }

        return DB::transaction(function () use ($offer, $operator, $now): array {
            /** @var CaseRequest|null $case */
            $case = CaseRequest::query()
                ->where('id', $offer->case_id)
                ->lockForUpdate()
                ->first();

            if ($case === null) {
                throw new HttpResponseException(new JsonResponse([
                    'status' => 404,
                    'detail' => 'پرونده یافت نشد.',
                ], 404));
            }

            if ($case->status->value !== CaseStatus::SEARCHING_OFFICE->value) {
                $offer->update([
                    'status' => DispatchOfferStatus::EXPIRED,
                    'responded_at' => $now,
                ]);

                throw new OfferAlreadyTakenException;
            }

            // Mark this offer as accepted
            $offer->update([
                'status' => DispatchOfferStatus::ACCEPTED,
                'responded_at' => $now,
                'responded_by' => $operator->id,
            ]);

            // Assign case to the accepting office
            $case->office_id = $operator->office_id;

            // Transition case state machine to ASSIGNED_TO_OFFICE
            $updatedCase = $this->stateMachine->transition(
                $case,
                CaseStatus::ASSIGNED_TO_OFFICE,
                TransitionContext::offerAccepted($operator->id)
            );

            // Invalidate all other pending offers for this case across all offices and notify via WebSocket
            $otherOffers = DispatchOffer::query()
                ->where('case_id', $case->id)
                ->where('id', '!=', $offer->id)
                ->where('status', DispatchOfferStatus::PENDING->value)
                ->get();

            foreach ($otherOffers as $otherOffer) {
                $otherOffer->update([
                    'status' => DispatchOfferStatus::EXPIRED,
                    'responded_at' => $now,
                ]);

                Event::dispatch(new DispatchOfferTaken(
                    $otherOffer->id,
                    $case->id,
                    $otherOffer->office_id,
                    'taken'
                ));
            }

            return [
                'offer_id' => $offer->id,
                'case_id' => $updatedCase->id,
                'tracking_code' => $updatedCase->tracking_code,
                'status' => $updatedCase->status->value,
                'turn_owner' => $updatedCase->turn_owner->value,
                'assigned_office_id' => $updatedCase->office_id,
                'accepted_at' => $now->toIso8601String(),
            ];
        });
    }
}
