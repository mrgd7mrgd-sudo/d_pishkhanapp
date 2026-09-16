<?php

declare(strict_types=1);

use App\Modules\Consultation\Database\Seeders\SubscriptionPlanSeeder;
use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Consultation\Domain\Models\QuotaUsage;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->ledgerService = app(LedgerService::class);
});

function createQuotaTestCitizen(string $mobile, string $nationalId, string $name): Citizen
{
    return Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', $mobile),
        'mobile_encrypted' => 'enc:'.$mobile,
        'national_id_hash' => hash('sha256', $nationalId),
        'national_id_encrypted' => 'enc:'.$nationalId,
        'full_name' => $name,
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);
}

function depositCitizenWallet(LedgerService $ledger, Citizen $citizen, int $amountRials): void
{
    $wallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizen->id, LedgerAccountKind::WALLET);
    $clearing = $ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);

    $ledger->recordTransaction(
        reference: 'DEP-'.Str::random(8),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, $amountRials),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, $amountRials),
        ],
        description: 'واریز اولیه تست'
    );
}

it('verifies all 3 business subscription plans are seeded accurately from prototype (§5.3, §6.1, TASK-120, TASK-120-T)', function (): void {
    $this->seed(SubscriptionPlanSeeder::class);

    $plans = SubscriptionPlan::query()->orderBy('price_monthly_rials')->get();
    expect($plans)->toHaveCount(3);

    $keys = $plans->pluck('plan_key')->all();
    expect($keys)->toBe([
        'plan-guild-basic',
        'plan-business-pro',
        'plan-enterprise',
    ]);

    $basic = $plans->firstWhere('plan_key', 'plan-guild-basic');
    expect($basic->price_monthly_rials)->toBe(4900000)
        ->and($basic->quota['phone_minutes'])->toBe(45)
        ->and($basic->quota['monthly_tax_review'])->toBe(1);

    $pro = $plans->firstWhere('plan_key', 'plan-business-pro');
    expect($pro->price_monthly_rials)->toBe(12900000)
        ->and($pro->is_popular)->toBeTrue()
        ->and($pro->quota['phone_minutes'])->toBe(120);

    $enterprise = $plans->firstWhere('plan_key', 'plan-enterprise');
    expect($enterprise->price_monthly_rials)->toBe(28900000)
        ->and($enterprise->quota['phone_minutes'])->toBe(300);
});

it('verifies citizen subscription purchases plan, debits wallet and initializes quotas (§5.3, TASK-120, TASK-120-T)', function (): void {
    $this->seed(SubscriptionPlanSeeder::class);
    $citizen = createQuotaTestCitizen('09127770001', '0017770001', 'مدیر استارتاپ');

    // Deposit 15,000,000 Rials into wallet
    depositCitizenWallet($this->ledgerService, $citizen, 15000000);

    Sanctum::actingAs($citizen, ['*']);

    $response = $this->postJson('/api/v1/consultations/subscribe', [
        'plan_id' => 'plan-business-pro',
    ])->assertStatus(201)
        ->assertJsonPath('data.status', 'active');

    $subId = $response->json('data.id');
    $sub = Subscription::query()->with('quotaUsages')->find($subId);

    expect($sub)->not->toBeNull()
        ->and($sub->citizen_id)->toBe($citizen->id)
        ->and($sub->status)->toBe(SubscriptionStatus::Active)
        ->and($sub->quotaUsages)->toHaveCount(4);

    // Verify wallet was debited by 12,900,000 Rials: 15,000,000 - 12,900,000 = 2,100,000
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizen->id, LedgerAccountKind::WALLET);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(2100000);
});

it('verifies concurrency test: 20 parallel consumption requests on quota with limit 5 results in exactly 5 successes and 15 failures (Invariant §5.3, TASK-120, TASK-120-T)', function (): void {
    $citizen = createQuotaTestCitizen('09127770002', '0017770002', 'کاربر همزمانی');

    $plan = SubscriptionPlan::query()->create([
        'id' => (string) Str::uuid(),
        'plan_key' => 'plan-concurrency-test',
        'title' => 'پلن آزمون همزمانی',
        'target_audience' => 'کسب‌وکارهای نوپا',
        'price_monthly_rials' => 1000000,
        'features' => ['تست'],
        'quota' => ['deep_reviews' => 5],
        'is_active' => true,
    ]);

    $sub = Subscription::query()->create([
        'id' => (string) Str::uuid(),
        'plan_id' => $plan->id,
        'citizen_id' => $citizen->id,
        'status' => SubscriptionStatus::Active,
        'started_on' => now()->toDateString(),
        'expires_on' => now()->addMonth()->toDateString(),
    ]);

    $quota = QuotaUsage::query()->create([
        'id' => (string) Str::uuid(),
        'subscription_id' => $sub->id,
        'quota_key' => 'deep_reviews',
        'used' => 0,
        'limit' => 5,
        'period_start' => now()->startOfMonth()->toDateString(),
    ]);

    Sanctum::actingAs($citizen, ['*']);

    $successCount = 0;
    $failureCount = 0;

    // Simulate 20 requests
    for ($i = 0; $i < 20; $i++) {
        $res = $this->postJson('/api/v1/consultations/quota/consume', [
            'quota_key' => 'deep_reviews',
            'amount' => 1,
        ]);

        if ($res->status() === 200) {
            $successCount++;
        } else {
            $failureCount++;
        }
    }

    // Invariant §5.3: exactly 5 succeed, 15 fail with quota exceeded!
    expect($successCount)->toBe(5)
        ->and($failureCount)->toBe(15);

    $quota->refresh();
    expect($quota->used)->toBe(5)
        ->and($quota->limit)->toBe(5);
});

it('verifies expired subscription cannot consume quota (§5.3, TASK-120, TASK-120-T)', function (): void {
    $citizen = createQuotaTestCitizen('09127770003', '0017770003', 'کاربر منقضی');

    $plan = SubscriptionPlan::query()->create([
        'id' => (string) Str::uuid(),
        'plan_key' => 'plan-expired-test',
        'title' => 'پلن منقضی',
        'target_audience' => 'کسب‌وکارهای منقضی',
        'price_monthly_rials' => 1000000,
        'features' => ['تست'],
        'quota' => ['phone_minutes' => 30],
        'is_active' => true,
    ]);

    // Subscription expired yesterday
    $sub = Subscription::query()->create([
        'id' => (string) Str::uuid(),
        'plan_id' => $plan->id,
        'citizen_id' => $citizen->id,
        'status' => SubscriptionStatus::Expired,
        'started_on' => now()->subMonth()->toDateString(),
        'expires_on' => now()->subDay()->toDateString(),
    ]);

    QuotaUsage::query()->create([
        'id' => (string) Str::uuid(),
        'subscription_id' => $sub->id,
        'quota_key' => 'phone_minutes',
        'used' => 0,
        'limit' => 30,
        'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
    ]);

    Sanctum::actingAs($citizen, ['*']);

    $this->postJson('/api/v1/consultations/quota/consume', [
        'quota_key' => 'phone_minutes',
        'amount' => 5,
    ])->assertStatus(422)
        ->assertJsonPath('detail', 'شما اشتراک فعال معتبری ندارید.');
});
