<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * CaseTimelineAppended Broadcast Event (Architecture §5.7, TASK-071).
 * Broadcasts timeline progress on private-case.{caseId}.
 */
final class CaseTimelineAppended extends DomainEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly CaseTimelineStep $step,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'case.timeline.appended';
    }

    public function broadcastAs(): string
    {
        return 'case.timeline.appended';
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("case.{$this->step->case_id}"),
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
            'case_id' => $this->step->case_id,
            'step_id' => $this->step->id,
            'sequence' => $this->step->sequence,
            'title' => $this->step->title,
            'status' => $this->step->status->value,
            'turn_owner' => $this->step->turn_owner->value,
            'actor_type' => $this->step->actor_type->value,
            'at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
