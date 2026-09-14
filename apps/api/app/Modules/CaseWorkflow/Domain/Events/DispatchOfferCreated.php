<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Shared\Events\DomainEvent;
use Carbon\CarbonImmutable;

final class DispatchOfferCreated extends DomainEvent
{
    public function __construct(
        public readonly DispatchOffer $offer,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    public function eventName(): string
    {
        return 'offer.new';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'offer_id' => $this->offer->id,
            'case_id' => $this->offer->case_id,
            'office_id' => $this->offer->office_id,
            'round' => $this->offer->round,
            'status' => $this->offer->status->value,
            'expires_at' => $this->offer->expires_at->toIso8601String(),
        ];
    }
}
