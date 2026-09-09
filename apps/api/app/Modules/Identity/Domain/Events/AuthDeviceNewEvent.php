<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

use App\Shared\Audit\AuditableAction;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

final class AuthDeviceNewEvent extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $deviceName,
        public readonly array $context = [],
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return AuditableAction::AUTH_DEVICE_NEW->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'user_id' => (string) $this->user->getAuthIdentifier(),
            'user_type' => get_class($this->user),
            'device_name' => $this->deviceName,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
