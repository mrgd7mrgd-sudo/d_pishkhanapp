<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class ImageFile
{
    public function __construct(
        public string $content,
        public string $mimeType = 'image/jpeg',
        public ?string $filename = null
    ) {}
}
