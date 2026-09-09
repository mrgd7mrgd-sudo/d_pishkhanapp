<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

use App\Shared\Audit\AuditableAction;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;

final class AuthOtpRequestedEvent extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $mobileMasked,
        public readonly string $purpose,
        public readonly ?string $challengeId = null,
        public readonly array $context = [],
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return AuditableAction::AUTH_OTP_REQUESTED->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'mobile_masked' => $this->mobileMasked,
            'purpose' => $this->purpose,
            'challenge_id' => $this->challengeId,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
