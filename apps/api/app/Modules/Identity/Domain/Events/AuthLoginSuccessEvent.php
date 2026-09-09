<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

use App\Shared\Audit\AuditableAction;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

final class AuthLoginSuccessEvent extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly array $context = [],
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return AuditableAction::AUTH_LOGIN_SUCCESS->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'user_id' => (string) $this->user->getAuthIdentifier(),
            'user_type' => get_class($this->user),
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
