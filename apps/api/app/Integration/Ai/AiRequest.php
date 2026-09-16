<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class AiRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public AiTask $task,
        public array $messages,
        public ?string $modelOverride = null,
        public int $maxTokens = 1024,
        public float $temperature = 0.7,
        public array $parameters = []
    ) {}
}
