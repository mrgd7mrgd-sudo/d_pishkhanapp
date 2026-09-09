<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

use App\Shared\Audit\AuditableAction;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;

final class AuthLoginFailedEvent extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $identifierMasked,
        public readonly string $reason,
        public readonly array $context = [],
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return AuditableAction::AUTH_LOGIN_FAILED->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'identifier_masked' => $this->identifierMasked,
            'reason' => $this->reason,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
