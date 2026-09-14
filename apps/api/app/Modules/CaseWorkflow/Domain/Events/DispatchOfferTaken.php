<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * DispatchOfferTaken Broadcast Event (Architecture §5.7, TASK-071).
 * Broadcasts offer.taken on private-office.{officeId}.
 */
final class DispatchOfferTaken extends DomainEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly string $offerId,
        public readonly string $caseId,
        public readonly string $officeId,
        public readonly string $status = 'taken',
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'offer.taken';
    }

    public function broadcastAs(): string
    {
        return 'offer.taken';
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("office.{$this->officeId}"),
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
        return [
            'offer_id' => $this->offerId,
            'case_id' => $this->caseId,
            'office_id' => $this->officeId,
            'status' => $this->status,
            'at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
