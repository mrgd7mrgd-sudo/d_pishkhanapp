<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain;

use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;

final class TransitionContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly TimelineStepStatus $stepStatus = TimelineStepStatus::DONE,
        public readonly TimelineActorType $actorType = TimelineActorType::SYSTEM,
        public readonly ?string $actorId = null,
        public readonly ?string $reasonCode = null,
        public readonly array $metadata = [],
    ) {}
}
