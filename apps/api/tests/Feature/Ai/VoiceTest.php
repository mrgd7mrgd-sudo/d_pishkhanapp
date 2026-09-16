<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Integration\Ai\AiChunk;
use App\Integration\Ai\AiProvider;
use App\Integration\Ai\AiRequest;
use App\Integration\Ai\AiResponse;
use App\Integration\Ai\AudioFile;
use App\Integration\Ai\Drivers\FakeDriver;
use App\Integration\Ai\ImageAnalysisResult;
use App\Integration\Ai\ImageFile;
use App\Integration\Ai\TranscriptionResult;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed([
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
    ]);
});

test('POST /ai/voice: 10 Persian audio inquiries are transcribed and processed with high accuracy (§8.1.5, TASK-114, TASK-114-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456781';
    $citizen->full_name = 'کیوان خسروی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $scenarios = [
        1 => 'برای تعویض کارت هوشمند ملی چه مدارکی لازم است؟',
        2 => 'هزینه صدور شناسنامه المثنی چقدر است؟',
        3 => 'نزدیک‌ترین دفتر پیشخوان آنلاین کجاست؟',
        4 => 'ساعت کاری دفاتر پیشخوان در روزهای پنج‌شنبه چگونه است؟',
        5 => 'پیگیری وضعیت پرونده با کد رهگیری CR-1405-12345',
        6 => 'مدارک مورد نیاز جهت دریافت گواهینامه رانندگی چیست؟',
        7 => 'چگونه می‌توانم نوبت حضوری در دفتر پیشخوان رزرو کنم؟',
        8 => 'برای تغییر آدرس و کد پستی به چه مدارکی نیاز است؟',
        9 => 'شرایط صدور و تمدید گذرنامه زیارتی چیست؟',
        10 => 'آیا امکان تحویل پستی مدارک توسط پیک وجود دارد؟',
    ];

    /** @var FakeDriver $aiProvider */
    $aiProvider = app(AiProvider::class);

    foreach ($scenarios as $num => $expectedTranscript) {
        // Set mock transcription for this sample
        $aiProvider->setCustomResponse('transcribe', new TranscriptionResult(
            text: $expectedTranscript,
            language: 'fa',
            durationSeconds: 4.5
        ));

        $fixturePath = base_path("tests/fixtures/audio/sample_{$num}.webm");
        expect(file_exists($fixturePath))->toBeTrue("Fixture sample_{$num}.webm must exist");

        $file = new UploadedFile(
            path: $fixturePath,
            originalName: "sample_{$num}.webm",
            mimeType: 'audio/webm',
            error: null,
            test: true
        );

        $response = $this->post('/api/v1/ai/voice', [
            'audio' => $file,
            'duration_seconds' => 5,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveKeys(['transcript', 'reply', 'suggested_actions', 'citations', 'usage', 'conversation_id'])
            ->and($data['transcript'])->toBe($expectedTranscript)
            ->and($data['reply'])->toBeString()->not->toBeEmpty();
    }
});

test('POST /ai/voice rejects audio files exceeding 1MB (422, §8.1.5, TASK-114-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456782';
    $citizen->full_name = 'مریم قاسمی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    // Create a 1.2MB dummy file (1250 KB)
    $oversizedFile = UploadedFile::fake()->create('heavy_audio.webm', 1250, 'audio/webm');

    $response = $this->postJson('/api/v1/ai/voice', [
        'audio' => $oversizedFile,
        'duration_seconds' => 10,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['audio']);
});

test('POST /ai/voice rejects audio exceeding 30 seconds duration (422, §8.1.5, TASK-114-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456783';
    $citizen->full_name = 'امیر صادقی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $fixturePath = base_path('tests/fixtures/audio/sample_1.webm');
    $file = new UploadedFile($fixturePath, 'sample_1.webm', 'audio/webm', null, true);

    $response = $this->postJson('/api/v1/ai/voice', [
        'audio' => $file,
        'duration_seconds' => 35, // exceeds 30s
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['duration_seconds']);
});

test('POST /ai/voice leaves zero audio files lingering on disk (§8.1.5, TASK-114-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456784';
    $citizen->full_name = 'علی نوری';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $tempDir = sys_get_temp_dir();
    $beforeFiles = glob($tempDir.'/*pishkhan_audio*') ?: [];

    $fixturePath = base_path('tests/fixtures/audio/sample_1.webm');
    $file = new UploadedFile($fixturePath, 'sample_1.webm', 'audio/webm', null, true);

    $response = $this->post('/api/v1/ai/voice', [
        'audio' => $file,
        'duration_seconds' => 6,
    ]);

    $response->assertStatus(200);

    $afterFiles = glob($tempDir.'/*pishkhan_audio*') ?: [];
    expect(count($afterFiles))->toBe(count($beforeFiles));
});

test('POST /ai/voice guarantees zero PII leak when transcribed voice contains sensitive data (§8.1.3, §8.1.5, TASK-114-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456789';
    $citizen->full_name = 'کیوان خسروی';
    $citizen->father_name = 'محمدرضا';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $capturedPrompt = '';

    $interceptingDriver = new class($capturedPrompt) implements AiProvider
    {
        public function __construct(public string &$captured) {}

        public function transcribe(AudioFile $audio, string $language = 'fa'): TranscriptionResult
        {
            return new TranscriptionResult(
                text: 'من کیوان خسروی هستم با کد ملی 0010350802 و شماره 09123456789، مدارک کارت ملی چیست؟',
                language: 'fa',
                durationSeconds: 7.0
            );
        }

        public function complete(AiRequest $request): AiResponse
        {
            $this->captured = $request->messages[1]['content'] ?? '';

            return new AiResponse(
                content: 'سلام جناب [NAME_1]، مدارک شما برای کارت ملی ثبت شد.',
                model: 'google/gemini-2.5-flash',
                inputTokens: 100,
                outputTokens: 30
            );
        }

        public function stream(AiRequest $request): Generator
        {
            yield new AiChunk('');
        }

        public function analyzeImage(ImageFile $image, string $instruction): ImageAnalysisResult
        {
            return new ImageAnalysisResult(true, [], []);
        }

        public function isAvailable(): bool
        {
            return true;
        }
    };

    app()->instance(AiProvider::class, $interceptingDriver);

    $fixturePath = base_path('tests/fixtures/audio/sample_1.webm');
    $file = new UploadedFile($fixturePath, 'sample_1.webm', 'audio/webm', null, true);

    $response = $this->post('/api/v1/ai/voice', [
        'audio' => $file,
        'duration_seconds' => 7,
    ]);

    $response->assertStatus(200);

    // 1. Assert zero PII in outgoing prompt to LLM
    expect($capturedPrompt)->not->toBeEmpty()
        ->and($capturedPrompt)->not->toContain('کیوان خسروی')
        ->and($capturedPrompt)->not->toContain('0010350802')
        ->and($capturedPrompt)->not->toContain('09123456789')
        ->and($capturedPrompt)->toContain('[NAME_1]')
        ->and($capturedPrompt)->toContain('[NID_1]')
        ->and($capturedPrompt)->toContain('[MOBILE_1]');

    // 2. Assert restored reply received by citizen has their name
    $data = $response->json('data');
    expect($data['reply'])->toContain('کیوان خسروی')
        ->and($data['reply'])->not->toContain('[NAME_1]');
});
