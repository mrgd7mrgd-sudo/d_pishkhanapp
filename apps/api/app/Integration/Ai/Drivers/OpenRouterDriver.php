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
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenRouterDriver implements AiProvider
{
    /**
     * @param  array<string, array{primary: string, fallback: string, max_tokens?: int}>  $modelMap
     */
    public function __construct(
        private readonly string $proxyUrl,
        private readonly array $modelMap = [],
        private readonly ?string $clientCertPath = null,
        private readonly ?string $clientKeyPath = null,
        private readonly ?string $caCertPath = null,
        private readonly int $timeoutSeconds = 30
    ) {}

    public function resolveModel(AiTask $task, bool $useFallback = false): string
    {
        $config = $this->modelMap[$task->value] ?? null;
        if (! $config) {
            return match ($task) {
                AiTask::ChatbotResponse => 'google/gemini-2.5-flash',
                AiTask::IntentClassification => 'google/gemini-2.5-flash-lite',
                AiTask::PersianVoiceTranscription => 'openai/whisper-large-v3',
                AiTask::DocumentQualityVision => 'google/gemini-2.5-flash',
                AiTask::CaseSummaryOperator => 'anthropic/claude-sonnet-4.5',
            };
        }

        return $useFallback ? $config['fallback'] : $config['primary'];
    }

    public function complete(AiRequest $request): AiResponse
    {
        $model = $request->modelOverride ?? $this->resolveModel($request->task, false);

        try {
            return $this->executeChatCompletion($request, $model);
        } catch (RuntimeException $e) {
            // Automatic Fallback to secondary model (§8.1.2)
            $fallbackModel = $this->resolveModel($request->task, true);
            if ($fallbackModel !== $model) {
                return $this->executeChatCompletion($request, $fallbackModel);
            }
            throw $e;
        }
    }

    private function executeChatCompletion(AiRequest $request, string $model): AiResponse
    {
        $url = rtrim($this->proxyUrl, '/').'/v1/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $request->messages,
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
        ];

        $httpRequest = Http::timeout($this->timeoutSeconds)
            ->acceptJson();

        if ($this->clientCertPath && $this->clientKeyPath) {
            $httpRequest = $httpRequest->withOptions([
                'cert' => [$this->clientCertPath, ''],
                'ssl_key' => $this->clientKeyPath,
                'verify' => $this->caCertPath ?: true,
            ]);
        }

        $response = $httpRequest->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException("OpenRouter upstream error: HTTP {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        return new AiResponse(
            content: $content,
            model: $data['model'] ?? $model,
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
        );
    }

    public function stream(AiRequest $request): Generator
    {
        // For streaming, callers consume chunks from proxy SSE endpoint
        $response = $this->complete($request);
        yield new AiChunk($response->content);
        yield new AiChunk('', 'stop');
    }

    public function transcribe(AudioFile $audio, string $language = 'fa'): TranscriptionResult
    {
        $url = rtrim($this->proxyUrl, '/').'/v1/audio/transcriptions';

        $httpRequest = Http::timeout($this->timeoutSeconds);

        if ($this->clientCertPath && $this->clientKeyPath) {
            $httpRequest = $httpRequest->withOptions([
                'cert' => [$this->clientCertPath, ''],
                'ssl_key' => $this->clientKeyPath,
                'verify' => $this->caCertPath ?: true,
            ]);
        }

        $response = $httpRequest->attach('file', $audio->content, 'audio.webm')
            ->post($url, [
                'model' => $this->resolveModel(AiTask::PersianVoiceTranscription),
                'language' => $language,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Transcription failed: HTTP {$response->status()}");
        }

        $data = $response->json();

        return new TranscriptionResult(
            text: $data['text'] ?? '',
            language: $language,
            durationSeconds: (float) ($data['duration'] ?? 0.0)
        );
    }

    public function analyzeImage(ImageFile $image, string $instruction): ImageAnalysisResult
    {
        $model = $this->resolveModel(AiTask::DocumentQualityVision);
        $request = new AiRequest(
            task: AiTask::DocumentQualityVision,
            messages: [
                [
                    'role' => 'user',
                    'content' => $instruction,
                ],
            ],
            modelOverride: $model
        );

        $response = $this->complete($request);

        return new ImageAnalysisResult(
            isAcceptable: ! str_contains(strtolower($response->content), 'unacceptable'),
            qualityWarnings: [],
            attributes: ['analysis' => $response->content]
        );
    }

    public function isAvailable(): bool
    {
        try {
            $url = rtrim($this->proxyUrl, '/').'/healthz';
            $res = Http::timeout(3)->get($url);

            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
