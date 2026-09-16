<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Integration\Ai\AiProvider;
use App\Integration\Ai\Drivers\FakeDriver;
use App\Modules\AiAssistance\Infrastructure\AiBudgetGuard;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('E2E Scenario E16: When AI proxy is down, system provides catalog fallback response instead of 500 error (§8.1.4, §9.4, §10.3 E16, TASK-113, TASK-113-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456781';
    $citizen->full_name = 'بهرام رادان';
    $citizen->save();

    Sanctum::actingAs($citizen);

    // Simulate AI proxy down
    /** @var FakeDriver $aiProvider */
    $aiProvider = app(AiProvider::class);
    $aiProvider->setAvailable(false);

    expect($aiProvider->isAvailable())->toBeFalse();

    $response = $this->postJson('/api/v1/ai/chat', [
        'message' => 'مدارک لازم برای کارت ملی هوشمند چیست؟',
    ]);

    // Must NOT return 500
    $response->assertStatus(200);

    $json = $response->json('data');
    expect($json)->not->toBeNull()
        ->and($json['reply'])->toContain('پاسخ پشتیبان سامانه پیشخوان')
        ->and($json['reply'])->toContain('کارت هوشمند ملی')
        ->and($json['usage']['model'])->toBe('catalog-fallback')
        ->and($json['usage']['cost_rials'])->toBe(0);

    // Citations and suggested actions are preserved from catalog
    expect($json['citations'])->toBeArray()->not->toBeEmpty()
        ->and($json['suggested_actions'])->toBeArray()->not->toBeEmpty();
});

test('Citizen rate limiting: 31st message in hour returns 429 with Persian message (§7.5, TASK-113-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456782';
    $citizen->full_name = 'مریم قاسمی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $budgetGuard = app(AiBudgetGuard::class);

    // Simulate 30 messages already sent this hour
    for ($i = 0; $i < 30; $i++) {
        $budgetGuard->incrementCitizenUsage((string) $citizen->id);
    }

    // 31st request should be rejected with 429
    $response = $this->postJson('/api/v1/ai/chat', [
        'message' => 'پیام سی و یکم در این ساعت',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'code' => 'AI_RATE_LIMIT_EXCEEDED',
        ]);

    expect($response->json('message'))->toContain('سقف مجاز');
});

test('Monthly budget exhaustion triggers fallback response and warning threshold at 80% (§6.7, §9.4, §9.6, TASK-113-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123456783';
    $citizen->full_name = 'رضا یزدانی';
    $citizen->save();

    Sanctum::actingAs($citizen);

    $budgetGuard = app(AiBudgetGuard::class);
    $monthlyBudget = $budgetGuard->getMonthlyBudget();

    // 1. Check warning threshold at 80%
    $budgetGuard->recordUsage((int) ($monthlyBudget * 0.82));
    expect($budgetGuard->isWarningThresholdReached())->toBeTrue()
        ->and($budgetGuard->isBudgetAvailable())->toBeTrue();

    // 2. Exhaust budget completely (100%+)
    $budgetGuard->recordUsage((int) ($monthlyBudget * 0.20));
    expect($budgetGuard->isBudgetAvailable())->toBeFalse();

    // 3. Request should return 200 with catalog-fallback instead of error
    $response = $this->postJson('/api/v1/ai/chat', [
        'message' => 'مدارک صدور شناسنامه چیست؟',
    ]);

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data['usage']['model'])->toBe('catalog-fallback')
        ->and($data['reply'])->toContain('پاسخ پشتیبان سامانه پیشخوان');
});

test('AiProvider::isAvailable returns actual driver state (§8.1.1, TASK-113-T)', function (): void {
    $driver = new FakeDriver;
    expect($driver->isAvailable())->toBeTrue();

    $driver->setAvailable(false);
    expect($driver->isAvailable())->toBeFalse();

    $driver->setAvailable(true);
    expect($driver->isAvailable())->toBeTrue();
});
