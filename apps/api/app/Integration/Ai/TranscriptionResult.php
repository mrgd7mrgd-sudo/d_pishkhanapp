<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class TranscriptionResult
{
    public function __construct(
        public string $text,
        public string $language = 'fa',
        public ?float $durationSeconds = null
    ) {}
}
