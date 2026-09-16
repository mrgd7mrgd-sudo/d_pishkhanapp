<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class AiChunk
{
    public function __construct(
        public string $delta,
        public ?string $finishReason = null
    ) {}
}
