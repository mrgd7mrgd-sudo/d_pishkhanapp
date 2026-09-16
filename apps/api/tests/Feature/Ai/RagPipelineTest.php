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
use App\Modules\AiAssistance\Application\AnswerGenerator;
use App\Modules\AiAssistance\Application\ContextRetriever;
use App\Modules\AiAssistance\Application\IntentClassifier;
use App\Modules\AiAssistance\Domain\EntityNameCollector;
use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\AiAssistance\Domain\Models\AiUsageRecord;
use App\Modules\AiAssistance\Domain\PiiRedactor;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
    ]);
});

test('RAG pipeline provides accurate answers with citations for national card inquiry (§5.6 #10, TASK-111, TASK-111-T)', function (): void {
    $nationalCardService = Service::query()->where('slug', 'id-national-card')->first();
    expect($nationalCardService)->not->toBeNull();

    $fakeDriver = new FakeDriver;
    $fakeDriver->setCustomResponse('complete', new AiResponse(
        content: 'برای صدور و تمدید کارت هوشمند ملی مدارک زیر لازم است: اصل شناسنامه عکس‌دار، کد پستی معتبر و حضور شخص متقاضی جهت ثبت بیومتریک.',
        model: 'google/gemini-2.5-flash',
        inputTokens: 380,
        outputTokens: 75,
        citations: [
            ['service_id' => (string) $nationalCardService->id, 'title' => $nationalCardService->title],
        ]
    ));

    $classifier = new IntentClassifier($fakeDriver);
    $retriever = new ContextRetriever;
    $generator = new AnswerGenerator(
        $classifier,
        $retriever,
        new PiiRedactor,
        new EntityNameCollector,
        $fakeDriver
    );

    $query = 'برای تعویض کارت ملی چه مدارکی لازم است؟';
    $result = $generator->generate($query);

    // 1. Assert intent is service_inquiry
    expect($result['intent'])->toBe('service_inquiry')
        ->and($result['confidence'])->toBeGreaterThanOrEqual(0.80);

    // 2. Assert citations include national card service
    expect($result['citations'])->toBeArray()->not->toBeEmpty();
    $serviceIds = collect($result['citations'])->where('type', 'service')->pluck('id')->all();
    expect($serviceIds)->toContain((string) $nationalCardService->id);

    // 3. Assert reply contains required information
    expect($result['reply'])->toContain('کارت هوشمند ملی')
        ->and($result['reply'])->toContain('شناسنامه');

    // 4. Assert usage shape
    expect($result['usage'])->toHaveKeys(['model', 'input_tokens', 'output_tokens', 'cost_rials'])
        ->and($result['usage']['cost_rials'])->toBeInt()->toBeGreaterThan(0);
});

test('RAG pipeline produces valid suggested_actions with existing DB IDs (§5.6 #10, TASK-111-T)', function (): void {
    $office = Office::query()->create([
        'code' => 'OFF-TEST-01',
        'name' => 'دفتر پیشخوان ولیعصر',
        'manager_name' => 'رضا مرادی',
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
        'is_online' => true,
        'rating' => 4.8,
        'address' => 'تهران، خیابان ولیعصر',
        'active_counters' => 5,
        'current_waiting_queue' => 2,
    ]);

    $fakeDriver = new FakeDriver;
    $generator = new AnswerGenerator(
        new IntentClassifier($fakeDriver),
        new ContextRetriever,
        new PiiRedactor,
        new EntityNameCollector,
        $fakeDriver
    );

    $result = $generator->generate('نزدیک‌ترین دفتر پیشخوان کجاست؟');

    expect($result['suggested_actions'])->toBeArray()->not->toBeEmpty();

    foreach ($result['suggested_actions'] as $action) {
        expect($action)->toHaveKeys(['type', 'label', 'payload']);

        if ($action['type'] === 'open_service') {
            expect(Service::query()->find($action['payload']['service_id']))->not->toBeNull();
        }

        if ($action['type'] === 'open_office') {
            expect(Office::query()->find($action['payload']['office_id']))->not->toBeNull();
        }
    }
});

test('RAG pipeline politely declines out-of-domain questions without hallucinations (§5.6, TASK-111-T)', function (): void {
    $fakeDriver = new FakeDriver;
    $generator = new AnswerGenerator(
        new IntentClassifier($fakeDriver),
        new ContextRetriever,
        new PiiRedactor,
        new EntityNameCollector,
        $fakeDriver
    );

    $outOfDomainQuery = 'یک شعر زیبا از حافظ برای من بخوان و درباره عرفان توضیح بده.';
    $result = $generator->generate($outOfDomainQuery);

    expect($result['intent'])->toBe('out_of_domain')
        ->and($result['confidence'])->toBeGreaterThanOrEqual(0.90)
        ->and($result['reply'])->toContain('من دستیار هوشمند خدمات پیشخوان دولت و امور شهروندی هستم')
        ->and($result['citations'])->toBeEmpty()
        ->and($result['suggested_actions'])->toBeEmpty()
        ->and($result['usage']['cost_rials'])->toBe(0);
});

test('RAG pipeline ensures zero PII in LLM prompt and restores citizen name on return (§5.6, §8.1.3, TASK-111-T)', function (): void {
    $citizen = new Citizen;
    $citizen->id = '01J8XE00000000000000000001';
    $citizen->full_name = 'کیوان خسروی';
    $citizen->father_name = 'محمدرضا';
    $citizen->address = 'تهران، خیابان سهروردی شمالی، پلاک ۱۲';

    $capturedPrompt = '';

    $interceptingDriver = new class($capturedPrompt) implements AiProvider
    {
        public function __construct(public string &$captured) {}

        public function complete(AiRequest $request): AiResponse
        {
            $this->captured = $request->messages[1]['content'] ?? '';

            // AI echoes the token back
            return new AiResponse(
                content: 'سلام جناب [NAME_1]، مدارک شما با کد ملی [NID_1] بررسی شد.',
                model: 'google/gemini-2.5-flash',
                inputTokens: 120,
                outputTokens: 40
            );
        }

        public function stream(AiRequest $request): Generator
        {
            yield new AiChunk('');
        }

        public function transcribe(AudioFile $audio, string $language = 'fa'): TranscriptionResult
        {
            return new TranscriptionResult('', 'fa', 0.0);
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

    $generator = new AnswerGenerator(
        new IntentClassifier($interceptingDriver),
        new ContextRetriever,
        new PiiRedactor,
        new EntityNameCollector,
        $interceptingDriver
    );

    $rawQuery = 'سلام، من کیوان خسروی هستم با کد ملی 0010350802 و موبایل 09123456789. مدارک لازم برای کارت ملی چیست؟';

    $result = $generator->generate($rawQuery, [], $citizen);

    // 1. Assert ZERO raw PII reached the outgoing prompt to the LLM
    expect($capturedPrompt)->not->toBeEmpty()
        ->and($capturedPrompt)->not->toContain('کیوان خسروی')
        ->and($capturedPrompt)->not->toContain('0010350802')
        ->and($capturedPrompt)->not->toContain('09123456789')
        ->and($capturedPrompt)->toContain('[NAME_1]')
        ->and($capturedPrompt)->toContain('[NID_1]')
        ->and($capturedPrompt)->toContain('[MOBILE_1]');

    // 2. Assert restored reply received by the citizen contains their actual name
    expect($result['reply'])->toContain('کیوان خسروی')
        ->and($result['reply'])->toContain('0010350802')
        ->and($result['reply'])->not->toContain('[NAME_1]')
        ->and($result['reply'])->not->toContain('[NID_1]');
});

test('RAG pipeline persists conversation messages and usage records (§5.6, §6.1, TASK-111-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456789';
    $citizen->full_name = 'زهرا ابراهیمی';
    $citizen->save();

    $conversation = AiConversation::query()->create([
        'citizen_id' => $citizen->id,
        'channel' => AiChannel::Chat,
        'title' => 'گفتگو درباره کارت ملی',
    ]);

    $fakeDriver = new FakeDriver;
    $generator = new AnswerGenerator(
        new IntentClassifier($fakeDriver),
        new ContextRetriever,
        new PiiRedactor,
        new EntityNameCollector,
        $fakeDriver
    );

    $result = $generator->generate('مدارک تعویض کارت ملی چیست؟', [], $citizen, $conversation);

    // Check message persistence
    $messages = AiMessage::query()->where('conversation_id', $conversation->id)->get();
    expect($messages)->toHaveCount(2);

    $userMsg = $messages->firstWhere('role', AiMessageRole::User);
    expect($userMsg)->not->toBeNull()
        ->and($userMsg->content)->toBe('مدارک تعویض کارت ملی چیست؟');

    $assistantMsg = $messages->firstWhere('role', AiMessageRole::Assistant);
    expect($assistantMsg)->not->toBeNull()
        ->and($assistantMsg->content)->toBe($result['reply']);

    // Check usage record persistence
    $usage = AiUsageRecord::query()->where('conversation_id', $conversation->id)->first();
    expect($usage)->not->toBeNull()
        ->and($usage->model)->toBe($result['usage']['model'])
        ->and($usage->cost_rials)->toBe($result['usage']['cost_rials'])
        ->and($usage->cost_rials)->toBeGreaterThan(0);
});
