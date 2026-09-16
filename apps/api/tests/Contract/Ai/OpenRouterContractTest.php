<?php

declare(strict_types=1);

namespace Tests\Contract\Ai;

use App\Integration\Ai\AiRequest;
use App\Integration\Ai\AiResponse;
use App\Integration\Ai\AiTask;
use App\Integration\Ai\AudioFile;
use App\Integration\Ai\Drivers\FakeDriver;
use App\Integration\Ai\Drivers\OpenRouterDriver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

test('OpenRouterDriver resolves models according to Architecture §8.1.2 task-to-model table', function (): void {
    $modelMap = config('pishkhan.ai.models');
    $driver = new OpenRouterDriver(
        proxyUrl: 'https://proxy.test:8443',
        modelMap: $modelMap
    );

    // 1. Chatbot Response: primary google/gemini-2.5-flash, fallback anthropic/claude-haiku-4.5
    expect($driver->resolveModel(AiTask::ChatbotResponse, false))->toBe('google/gemini-2.5-flash')
        ->and($driver->resolveModel(AiTask::ChatbotResponse, true))->toBe('anthropic/claude-haiku-4.5');

    // 2. Intent Classification: primary google/gemini-2.5-flash-lite, fallback openai/gpt-4o-mini
    expect($driver->resolveModel(AiTask::IntentClassification, false))->toBe('google/gemini-2.5-flash-lite')
        ->and($driver->resolveModel(AiTask::IntentClassification, true))->toBe('openai/gpt-4o-mini');

    // 3. Persian Voice Transcription: primary openai/whisper-large-v3, fallback google/gemini-2.5-flash
    expect($driver->resolveModel(AiTask::PersianVoiceTranscription, false))->toBe('openai/whisper-large-v3')
        ->and($driver->resolveModel(AiTask::PersianVoiceTranscription, true))->toBe('google/gemini-2.5-flash');

    // 4. Document Quality Vision: primary google/gemini-2.5-flash, fallback local_opencv
    expect($driver->resolveModel(AiTask::DocumentQualityVision, false))->toBe('google/gemini-2.5-flash')
        ->and($driver->resolveModel(AiTask::DocumentQualityVision, true))->toBe('local_opencv');

    // 5. Case Summary Operator: primary anthropic/claude-sonnet-4.5, fallback google/gemini-2.5-pro
    expect($driver->resolveModel(AiTask::CaseSummaryOperator, false))->toBe('anthropic/claude-sonnet-4.5')
        ->and($driver->resolveModel(AiTask::CaseSummaryOperator, true))->toBe('google/gemini-2.5-pro');
});

test('OpenRouterDriver contract: completes chat with recorded upstream response shape (§8.0, §8.1.2)', function (): void {
    Http::fake([
        'https://proxy.test:8443/v1/chat/completions' => Http::response([
            'id' => 'gen-test-999',
            'model' => 'google/gemini-2.5-flash',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'برای تعویض شناسنامه، به همراه داشتن اصل شناسنامه و دو قطعه عکس الزامی است.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 42,
                'completion_tokens' => 28,
                'total_tokens' => 70,
            ],
        ], 200),
    ]);

    $driver = new OpenRouterDriver(
        proxyUrl: 'https://proxy.test:8443',
        modelMap: config('pishkhan.ai.models')
    );

    $req = new AiRequest(
        task: AiTask::ChatbotResponse,
        messages: [
            ['role' => 'user', 'content' => 'مدارک شناسنامه چیست؟'],
        ]
    );

    $resp = $driver->complete($req);

    expect($resp)->toBeInstanceOf(AiResponse::class)
        ->and($resp->content)->toContain('اصل شناسنامه')
        ->and($resp->model)->toBe('google/gemini-2.5-flash')
        ->and($resp->inputTokens)->toBe(42)
        ->and($resp->outputTokens)->toBe(28);
});

test('OpenRouterDriver automatically fails over to fallback model when primary model fails (§8.1.2)', function (): void {
    $callSequence = [];

    Http::fake([
        'https://proxy.test:8443/v1/chat/completions' => function ($request) use (&$callSequence) {
            $data = json_decode($request->body(), true);
            $model = $data['model'] ?? '';
            $callSequence[] = $model;

            if ($model === 'google/gemini-2.5-flash') {
                // Primary model 503 error
                return Http::response(['error' => 'upstream overloaded'], 503);
            }

            // Fallback model succeeds
            return Http::response([
                'id' => 'gen-fallback-111',
                'model' => 'anthropic/claude-haiku-4.5',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'پاسخ از مدل پشتیبان دریافت شد.',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 30,
                    'completion_tokens' => 15,
                ],
            ], 200);
        },
    ]);

    $driver = new OpenRouterDriver(
        proxyUrl: 'https://proxy.test:8443',
        modelMap: config('pishkhan.ai.models')
    );

    $req = new AiRequest(
        task: AiTask::ChatbotResponse,
        messages: [
            ['role' => 'user', 'content' => 'وضعیت سرور چیست؟'],
        ]
    );

    $resp = $driver->complete($req);

    expect($callSequence)->toEqual(['google/gemini-2.5-flash', 'anthropic/claude-haiku-4.5'])
        ->and($resp->model)->toBe('anthropic/claude-haiku-4.5')
        ->and($resp->content)->toBe('پاسخ از مدل پشتیبان دریافت شد.');
});

test('OpenRouterDriver transcribes audio and checks availability (§8.1.1)', function (): void {
    Http::fake([
        'https://proxy.test:8443/v1/audio/transcriptions' => Http::response([
            'text' => 'صدور کارت ملی هوشمند در دفتر پیشخوان',
            'duration' => 4.2,
        ], 200),
        'https://proxy.test:8443/healthz' => Http::response(['status' => 'ok'], 200),
    ]);

    $driver = new OpenRouterDriver(
        proxyUrl: 'https://proxy.test:8443',
        modelMap: config('pishkhan.ai.models')
    );

    // 1. isAvailable
    expect($driver->isAvailable())->toBeTrue();

    // 2. transcribe
    $res = $driver->transcribe(new AudioFile('fake-audio-binary-data', 'audio/webm', 4));
    expect($res->text)->toBe('صدور کارت ملی هوشمند در دفتر پیشخوان')
        ->and($res->durationSeconds)->toBe(4.2)
        ->and($res->language)->toBe('fa');
});

test('FakeDriver provides deterministic offline responses for tests and development (§8.0, §8.1.1)', function (): void {
    $driver = new FakeDriver;

    expect($driver->isAvailable())->toBeTrue();

    // 1. Intent Classification
    $intentRes = $driver->complete(new AiRequest(
        task: AiTask::IntentClassification,
        messages: [['role' => 'user', 'content' => 'نوبت می‌خواهم']]
    ));
    expect($intentRes->content)->toContain('ask_service_requirements');

    // 2. Chatbot Response with citations and suggestedActions
    $chatRes = $driver->complete(new AiRequest(
        task: AiTask::ChatbotResponse,
        messages: [['role' => 'user', 'content' => 'سلام']]
    ));
    expect($chatRes->citations)->not->toBeEmpty()
        ->and($chatRes->suggestedActions)->not->toBeEmpty();

    // 3. Streaming
    $chunks = [];
    foreach ($driver->stream(new AiRequest(task: AiTask::ChatbotResponse, messages: [])) as $chunk) {
        $chunks[] = $chunk->delta;
    }
    expect(implode('', $chunks))->toContain('دستیار هوشمند');
});

test('architecture: no domain module references or calls OpenRouter directly (§8.0, §8.1.4, TASK-109-T)', function (): void {
    $domainPath = app_path('Modules');
    $phpFiles = File::allFiles($domainPath);

    foreach ($phpFiles as $file) {
        $content = $file->getContents();
        expect($content)->not->toContain('openrouter.ai')
            ->and($content)->not->toContain('OpenRouterDriver');
    }
});
