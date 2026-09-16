<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class AiResponse
{
    /**
     * @param  array<int, array{service_id?: string, title?: string, url?: string}>  $citations
     * @param  array<int, array{type: string, label: string, service_id?: string}>  $suggestedActions
     */
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public array $citations = [],
        public array $suggestedActions = []
    ) {}
}
