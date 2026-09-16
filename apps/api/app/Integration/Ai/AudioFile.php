<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class AudioFile
{
    public function __construct(
        public string $content,
        public string $mimeType = 'audio/webm',
        public int $durationSeconds = 0
    ) {}
}
