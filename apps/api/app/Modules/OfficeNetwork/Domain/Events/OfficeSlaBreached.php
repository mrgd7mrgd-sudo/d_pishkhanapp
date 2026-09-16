<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Events;

use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * OfficeSlaBreached Broadcast Event (Architecture §5.7, §9.6, TASK-102).
 * Broadcasts on private-office.{officeId} (sla.breached) and private-admin.system (sla.breach).
 */
final class OfficeSlaBreached extends DomainEvent implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public readonly string $officeId,
        public readonly string $eventType,
        public readonly ?string $caseId,
        public readonly int $penaltyPoints,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'sla.breached';
    }

    public function broadcastAs(): string
    {
        return 'sla.breached';
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("office.{$this->officeId}"),
            new PrivateChannel('admin.system'),
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
            'event_id' => $this->eventId,
            'office_id' => $this->officeId,
            'event_type' => $this->eventType,
            'case_id' => $this->caseId,
            'penalty_points' => $this->penaltyPoints,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
