<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Integration\Payment\Drivers\FakeDriver;
use App\Integration\Payment\PaymentGateway;
use App\Integration\Payment\PaymentVerificationResult;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند آزمایشی';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->citizenB = new Citizen;
    $this->citizenB->national_id = '0010350810';
    $this->citizenB->mobile = '09129998877';
    $this->citizenB->full_name = 'شهروند مهاجم';
    $this->citizenB->tier = CitizenTier::BRONZE;
    $this->citizenB->province_code = 'THR';
    $this->citizenB->save();

    $this->ledger = app(LedgerService::class);
    $this->gatewayDriver = app(PaymentGateway::class);
});

test('happy path: POST /wallet/topup creates intent and returns redirect url matching architecture §5.6 example 9', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    $response = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 5_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'payment_intent_id',
                'redirect_url',
                'authority',
                'expires_at',
                'amount_rials',
            ],
        ]);

    $intentId = (string) $response->json('data.payment_intent_id');
    $authority = (string) $response->json('data.authority');
    $amountRials = (int) $response->json('data.amount_rials');

    expect($amountRials)->toBe(5_000_000)
        ->and($authority)->not->toBeEmpty();

    $this->assertDatabaseHas('payment_intents', [
        'id' => $intentId,
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 5_000_000,
        'authority' => $authority,
        'status' => 'redirected',
    ]);
});

test('happy path: POST /wallet/topup/verify verifies intent and credits citizen wallet ledger account', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    // 1. Create topup intent
    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 5_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $topupRes->assertStatus(201);
    $authority = (string) $topupRes->json('data.authority');

    // 2. Verify payment intent
    $verifyRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
        'status' => 'OK',
    ]);

    $verifyRes->assertStatus(200)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.amount_rials', 5_000_000)
        ->assertJsonPath('data.wallet_balance_rials', 5_000_000)
        ->assertJsonStructure([
            'data' => [
                'payment_intent_id',
                'status',
                'amount_rials',
                'ref_id',
                'card_pan_masked',
                'verified_at',
                'wallet_balance_rials',
            ],
        ]);

    // 3. Verify ledger entries & balance
    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(5_000_000);

    // Verify double entry equality
    $totalDebits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
    $totalCredits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');
    expect($totalDebits)->toBe(5_000_000)
        ->and($totalCredits)->toBe(5_000_000);

    // 4. Verify balance endpoint
    $balanceRes = $this->getJson('/api/v1/wallet/balance');
    $balanceRes->assertStatus(200)
        ->assertJsonPath('data.balance_rials', 5_000_000)
        ->assertJsonPath('data.balance_toman', 500_000);
});

test('security rule 1: client amount tampering in verify request has zero effect', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    // Topup intent created for 1,000,000 Rials
    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 1_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $authority = (string) $topupRes->json('data.authority');

    // Attacker passes huge amount in verify payload
    $verifyRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
        'amount_rials' => 999_999_999,
        'amount' => 999_999_999,
    ]);

    $verifyRes->assertStatus(200)
        ->assertJsonPath('data.amount_rials', 1_000_000)
        ->assertJsonPath('data.wallet_balance_rials', 1_000_000);

    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(1_000_000);
});

test('security rule 2: fake callback Status=OK without gateway confirmation makes zero deposit to wallet', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 2_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $authority = (string) $topupRes->json('data.authority');
    $intentId = (string) $topupRes->json('data.payment_intent_id');

    // Simulate gateway failure despite browser returning Status=OK
    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(
            PaymentVerificationResult::failure('fake', 'کاربر از پرداخت انصراف داد یا تراکنش ناموفق بود.')
        );
    }

    $verifyRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
        'status' => 'OK',
    ]);

    $verifyRes->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_VERIFICATION_FAILED');

    // Intent must be marked failed
    $this->assertDatabaseHas('payment_intents', [
        'id' => $intentId,
        'status' => 'failed',
    ]);

    // Ledger must have zero entries and wallet balance must remain 0
    expect(DB::table('ledger_entries')->count())->toBe(0);

    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(0);

    // Reset override
    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(null);
    }
});

test('security rule 3: amount mismatch returned from gateway is rejected and logs critical alert', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'SECURITY ALERT: Payment amount mismatch detected!')
                && $context['expected_rials'] === 5_000_000
                && $context['received_rials'] === 50_000;
        });

    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 5_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $authority = (string) $topupRes->json('data.authority');
    $intentId = (string) $topupRes->json('data.payment_intent_id');

    // Gateway reports success but only 50,000 Rials instead of 5,000,000
    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(
            PaymentVerificationResult::success(
                gatewayName: 'fake',
                refId: 'REF-MISMATCH-123',
                amountRials: 50_000,
                cardPanMasked: '603799******1111'
            )
        );
    }

    $verifyRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
    ]);

    $verifyRes->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_AMOUNT_MISMATCH');

    $this->assertDatabaseHas('payment_intents', [
        'id' => $intentId,
        'status' => 'failed',
    ]);

    // Zero balance credited
    expect(DB::table('ledger_entries')->count())->toBe(0);

    // Reset override
    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(null);
    }
});

test('security rule 4: authority is strictly single-use; replay attempts return 409 and do not double deposit', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 3_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $authority = (string) $topupRes->json('data.authority');

    // First verification: success
    $firstVerify = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
    ]);
    $firstVerify->assertStatus(200);

    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(3_000_000)
        ->and(DB::table('ledger_entries')->count())->toBe(2);

    // Second verification (Replay attack / double-click): must return 409 Conflict
    $replayVerify = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
    ]);
    $replayVerify->assertStatus(409)
        ->assertJsonPath('code', 'PAYMENT_ALREADY_VERIFIED');

    // Balance must NOT double, must remain exactly 3,000,000 Rials
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(3_000_000)
        ->and(DB::table('ledger_entries')->count())->toBe(2);
});

test('security rule: cross-citizen payment intent verification is strictly prohibited (403)', function (): void {
    // Citizen A initiates topup
    Sanctum::actingAs($this->citizen, ['*']);
    $topupRes = $this->postJson('/api/v1/wallet/topup', [
        'amount_rials' => 4_000_000,
        'gateway' => 'zarinpal',
        'return_url' => 'https://app.pishkhan.ir/wallet/callback',
    ]);
    $authority = (string) $topupRes->json('data.authority');

    // Citizen B tries to verify Citizen A's intent
    Sanctum::actingAs($this->citizenB, ['*']);
    $attackRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $authority,
    ]);

    $attackRes->assertStatus(403);
    expect(DB::table('ledger_entries')->count())->toBe(0);
});

test('expired payment intent cannot be verified (410)', function (): void {
    Sanctum::actingAs($this->citizen, ['*']);

    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 1_000_000,
        'gateway' => 'zarinpal',
        'authority' => 'EXPIRED-AUTH-TEST',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->subMinutes(5),
    ]);

    $verifyRes = $this->postJson('/api/v1/wallet/topup/verify', [
        'authority' => $intent->authority,
    ]);

    $verifyRes->assertStatus(410)
        ->assertJsonPath('code', 'PAYMENT_EXPIRED');

    expect($intent->fresh()->status)->toBe(PaymentIntentStatus::EXPIRED)
        ->and(DB::table('ledger_entries')->count())->toBe(0);
});
