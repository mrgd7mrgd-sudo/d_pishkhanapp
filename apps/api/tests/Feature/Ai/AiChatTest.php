<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
    ]);
});

test('POST /ai/chat matches Architecture §5.6 Example 10 response shape (§5.6, TASK-112, TASK-112-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456781';
    $citizen->full_name = 'محسن کاظمی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $payload = [
        'conversation_id' => null,
        'message' => 'برای تعویض کارت ملی چه مدارکی لازم است و نزدیک‌ترین دفتر کجاست؟',
        'context' => [
            'current_route' => '/services',
            'case_id' => null,
            'location' => [
                'lat' => 35.7480,
                'lng' => 51.4120,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/ai/chat', $payload);

    $response->assertStatus(200);

    $json = $response->json();
    expect($json)->toHaveKey('data');
    $data = $json['data'];

    // Verify all keys from Architecture §5.6 Example 10
    expect($data)->toHaveKeys([
        'conversation_id',
        'message_id',
        'reply',
        'intent',
        'confidence',
        'citations',
        'suggested_actions',
        'usage',
    ]);

    expect($data['conversation_id'])->toBeString()->not->toBeEmpty()
        ->and($data['message_id'])->toBeString()->not->toBeEmpty()
        ->and($data['reply'])->toBeString()->not->toBeEmpty()
        ->and($data['intent'])->toBeString()->not->toBeEmpty()
        ->and($data['confidence'])->toBeNumeric()
        ->and($data['citations'])->toBeArray()
        ->and($data['suggested_actions'])->toBeArray()
        ->and($data['usage'])->toHaveKeys(['model', 'input_tokens', 'output_tokens', 'cost_rials'])
        ->and($data['usage']['cost_rials'])->toBeInt()->toBeGreaterThan(0);
});

test('POST /ai/chat supports SSE streaming with text/event-stream (§5.6 #10, TASK-112-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456782';
    $citizen->full_name = 'سارا محمدی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $payload = [
        'conversation_id' => null,
        'message' => 'هزینه صدور شناسنامه المثنی چقدر است؟',
    ];

    $response = $this->post('/api/v1/ai/chat', $payload, [
        'Accept' => 'text/event-stream',
    ]);

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    $content = $response->streamedContent();
    expect($content)->toContain('event: meta')
        ->and($content)->toContain('event: token')
        ->and($content)->toContain('event: done');
});

test('POST /ai/chat response time complies with SLO p95 <= 4s (§9.5, TASK-112-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456783';
    $citizen->full_name = 'امیرحسین رضایی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $latencies = [];

    for ($i = 0; $i < 5; $i++) {
        $start = microtime(true);
        $res = $this->postJson('/api/v1/ai/chat', [
            'message' => 'مدارک کارت ملی چیست؟ شماره '.$i,
        ]);
        $durationMs = (microtime(true) - $start) * 1000;
        $res->assertStatus(200);
        $latencies[] = $durationMs;
    }

    sort($latencies);
    $p95 = $latencies[(int) floor(0.95 * count($latencies))];

    // Assert p95 <= 4000ms (SLO §9.5)
    expect($p95)->toBeLessThan(4000.0);
});

test('Citizen cannot read or append to another citizen conversation (404 isolation, TASK-112-T)', function (): void {
    $citizenA = new Citizen;
    $citizenA->national_id = '0010350802';
    $citizenA->mobile = '09123456784';
    $citizenA->full_name = 'شهروند الف';
    $citizenA->save();

    $citizenB = new Citizen;
    $citizenB->national_id = '0010350829';
    $citizenB->mobile = '09123456785';
    $citizenB->full_name = 'شهروند ب';
    $citizenB->save();

    $conversationA = AiConversation::query()->create([
        'citizen_id' => $citizenA->id,
        'channel' => AiChannel::Chat,
        'title' => 'مکالمه خصوصی شهروند الف',
    ]);

    // Citizen B tries to fetch Citizen A's conversation
    Sanctum::actingAs($citizenB);
    $getRes = $this->getJson('/api/v1/ai/conversations/'.$conversationA->id);
    $getRes->assertStatus(404);

    // Citizen B tries to send message to Citizen A's conversation
    $postRes = $this->postJson('/api/v1/ai/chat', [
        'conversation_id' => (string) $conversationA->id,
        'message' => 'تلاش غیرمجاز برای دسترسی به مکالمه دیگران',
    ]);
    $postRes->assertStatus(404);
});

test('Citizen can list their own conversations via GET /ai/conversations', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456786';
    $citizen->full_name = 'مریم راد';
    $citizen->save();

    AiConversation::query()->create([
        'citizen_id' => $citizen->id,
        'channel' => AiChannel::Chat,
        'title' => 'گفتگو ۱',
    ]);
    AiConversation::query()->create([
        'citizen_id' => $citizen->id,
        'channel' => AiChannel::Chat,
        'title' => 'گفتگو ۲',
    ]);

    Sanctum::actingAs($citizen);
    $res = $this->getJson('/api/v1/ai/conversations');

    $res->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
