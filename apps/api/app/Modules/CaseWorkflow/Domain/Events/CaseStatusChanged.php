<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;

final class CaseStatusChanged extends DomainEvent
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
        return 'case.status_changed';
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
            'reason_code' => $this->context->reasonCode,
        ];
    }
}
