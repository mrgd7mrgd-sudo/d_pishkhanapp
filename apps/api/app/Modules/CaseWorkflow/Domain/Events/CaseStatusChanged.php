<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * CaseStatusChanged Broadcast Event (Architecture §5.7, TASK-071).
 * Broadcasts status changes to citizen and assigned office.
 */
final class CaseStatusChanged extends DomainEvent implements ShouldBroadcast
{
    public function __construct(
        public readonly CaseRequest $case,
        public readonly CaseStatus $from,
        public readonly CaseStatus $to,
        public readonly TransitionContext $context,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'case.status.changed';
    }

    public function broadcastAs(): string
    {
        return 'case.status.changed';
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("case.{$this->case->id}"),
            new PrivateChannel("citizen.{$this->case->citizen_id}"),
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
            'case_id' => $this->case->id,
            'tracking_code' => $this->case->tracking_code,
            'from' => $this->from->value,
            'to' => $this->to->value,
            'turn_owner' => $this->case->turn_owner->value,
            'turn_owner_label' => $this->case->turn_owner->label(),
            'reason_code' => $this->context->reasonCode,
            'headline' => $this->context->title,
            'at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
