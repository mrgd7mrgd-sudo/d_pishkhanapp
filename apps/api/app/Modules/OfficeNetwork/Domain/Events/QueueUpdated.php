<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Events;

use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * QueueUpdated Broadcast Event (Architecture §5.7, §9.2, TASK-071).
 * Broadcasts queue.updated on private-office.{officeId} with 2-second server debounce.
 */
final class QueueUpdated extends DomainEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly string $officeId,
        public readonly int $waitingQueue,
        public readonly int $activeCounters,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'queue.updated';
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
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
            'office_id' => $this->officeId,
            'waiting_queue' => $this->waitingQueue,
            'active_counters' => $this->activeCounters,
            'at' => $this->occurredAt->toIso8601String(),
        ];
    }

    /**
     * Dispatch debounced event: suppresses event storm if dispatched within 2 seconds for the same office.
     */
    public static function dispatchDebounced(string $officeId, int $waitingQueue, int $activeCounters, int $debounceSeconds = 2): bool
    {
        $cacheKey = "debounce:queue_updated:{$officeId}";
        $acquired = Cache::add($cacheKey, true, $debounceSeconds);

        if (! $acquired) {
            return false;
        }

        Event::dispatch(new self($officeId, $waitingQueue, $activeCounters));

        return true;
    }
}
