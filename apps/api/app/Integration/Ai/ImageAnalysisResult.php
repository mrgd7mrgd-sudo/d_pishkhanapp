<?php

declare(strict_types=1);

namespace App\Integration\Ai;

final readonly class ImageAnalysisResult
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $qualityWarnings
     */
    public function __construct(
        public bool $isAcceptable,
        public array $qualityWarnings = [],
        public array $attributes = []
    ) {}
}
