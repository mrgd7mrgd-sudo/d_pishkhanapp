<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * DispatchOfferCreated Broadcast Event (Architecture §5.7, TASK-071).
 * Broadcasts offer.new on private-office.{officeId}.
 */
final class DispatchOfferCreated extends DomainEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly DispatchOffer $offer,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'offer.new';
    }

    public function broadcastAs(): string
    {
        return 'offer.new';
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("office.{$this->offer->office_id}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->toPayload();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $case = $this->offer->case;
        $now = CarbonImmutable::now();
        $remainingSeconds = max(0, $now->diffInSeconds($this->offer->expires_at, false));

        return [
            'offer_id' => $this->offer->id,
            'case_id' => $this->offer->case_id,
            'office_id' => $this->offer->office_id,
            'tracking_code' => $case?->tracking_code,
            'service_id' => $case?->service_id,
            'province_code' => $case?->province_code,
            'city' => $case?->city,
            'round' => $this->offer->round,
            'status' => $this->offer->status->value,
            'expires_at' => $this->offer->expires_at->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
