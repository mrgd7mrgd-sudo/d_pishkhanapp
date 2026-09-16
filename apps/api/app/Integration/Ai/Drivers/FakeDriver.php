<?php

declare(strict_types=1);

namespace App\Integration\Ai\Drivers;

use App\Integration\Ai\AiChunk;
use App\Integration\Ai\AiProvider;
use App\Integration\Ai\AiRequest;
use App\Integration\Ai\AiResponse;
use App\Integration\Ai\AiTask;
use App\Integration\Ai\AudioFile;
use App\Integration\Ai\ImageAnalysisResult;
use App\Integration\Ai\ImageFile;
use App\Integration\Ai\TranscriptionResult;
use Generator;

final class FakeDriver implements AiProvider
{
    private bool $available = true;

    /**
     * @var array<string, mixed>
     */
    private array $customResponses = [];

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function setCustomResponse(string $key, mixed $response): void
    {
        $this->customResponses[$key] = $response;
    }

    public function complete(AiRequest $request): AiResponse
    {
        if (isset($this->customResponses['complete'])) {
            return $this->customResponses['complete'];
        }

        $messages = $request->messages;
        $lastMessage = ! empty($messages) ? end($messages)['content'] ?? '' : '';

        return match ($request->task) {
            AiTask::IntentClassification => new AiResponse(
                content: '{"intent":"ask_service_requirements","confidence":0.95}',
                model: $request->modelOverride ?? 'google/gemini-2.5-flash-lite',
                inputTokens: 35,
                outputTokens: 18,
            ),
            AiTask::CaseSummaryOperator => new AiResponse(
                content: 'خلاصه پرونده: مدارک هویتی بارگذاری شده و استعلام ثبت احوال موفق بوده است.',
                model: $request->modelOverride ?? 'anthropic/claude-sonnet-4.5',
                inputTokens: 120,
                outputTokens: 60,
            ),
            default => new AiResponse(
                content: 'پاسخ آزمایشی دستیار هوشمند: '.$lastMessage,
                model: $request->modelOverride ?? 'google/gemini-2.5-flash',
                inputTokens: 50,
                outputTokens: 30,
                citations: [
                    ['service_id' => 'srv-national-card', 'title' => 'تعویض کارت هوشمند ملی'],
                ],
                suggestedActions: [
                    ['type' => 'open_service', 'service_id' => 'srv-national-card', 'label' => 'مشاهده مراحل خدمت'],
                ],
            ),
        };
    }

    public function stream(AiRequest $request): Generator
    {
        $words = ['سلام', '،', ' من', ' دستیار', ' هوشمند', ' پیشخوان', ' هستم', '.'];
        foreach ($words as $word) {
            yield new AiChunk($word);
        }
        yield new AiChunk('', 'stop');
    }

    public function transcribe(AudioFile $audio, string $language = 'fa'): TranscriptionResult
    {
        return new TranscriptionResult(
            text: 'برای دریافت کارت بازرگانی چه مدارکی لازم است؟',
            language: $language,
            durationSeconds: 3.5
        );
    }

    public function analyzeImage(ImageFile $image, string $instruction): ImageAnalysisResult
    {
        return new ImageAnalysisResult(
            isAcceptable: true,
            qualityWarnings: [],
            attributes: ['detected_document' => 'national_id_card']
        );
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }
}
