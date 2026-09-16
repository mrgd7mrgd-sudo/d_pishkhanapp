<?php

declare(strict_types=1);

namespace App\Integration\Ai;

use Generator;

/**
 * AiProvider Port Interface (§8.0, §8.1.1, TASK-109)
 */
interface AiProvider
{
    public function complete(AiRequest $request): AiResponse;

    /**
     * @return Generator<AiChunk>
     */
    public function stream(AiRequest $request): Generator;

    public function transcribe(AudioFile $audio, string $language = 'fa'): TranscriptionResult;

    public function analyzeImage(ImageFile $image, string $instruction): ImageAnalysisResult;

    public function isAvailable(): bool;
}
