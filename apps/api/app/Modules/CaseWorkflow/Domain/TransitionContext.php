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

    public static function dispatchExhausted(): self
    {
        return new self(
            title: 'عدم پذیرش در مهلت مقرر و لغو خودکار',
            description: 'هیچ دفتر پیشخوانی در شعاع و مهلت مقرر پرونده را نپذیرفت و پرونده لغو گردید.',
            stepStatus: TimelineStepStatus::FAILED,
            actorType: TimelineActorType::SYSTEM,
            metadata: ['reason' => 'dispatch_exhausted']
        );
    }
}
