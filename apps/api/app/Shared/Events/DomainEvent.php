<?php

declare(strict_types=1);

namespace App\Shared\Events;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

abstract class DomainEvent
{
    public readonly string $eventId;

    public readonly CarbonImmutable $occurredAt;

    public function __construct(?string $eventId = null, ?CarbonImmutable $occurredAt = null)
    {
        $this->eventId = $eventId ?? (string) Str::uuid();
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Get event name for serialization and routing.
     */
    abstract public function eventName(): string;

    /**
     * Convert event payload to array for broadcasting or queues.
     *
     * @return array<string, mixed>
     */
    abstract public function toPayload(): array;
}
