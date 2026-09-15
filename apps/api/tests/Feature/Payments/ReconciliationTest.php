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
use App\Modules\Payments\Domain\Enums\PaymentGateway as PaymentGatewayEnum;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Modules\Payments\Jobs\ReconcilePaymentIntentsJob;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند آزمایشی مغایرت';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->ledger = app(LedgerService::class);
    $this->gatewayDriver = app(PaymentGateway::class);
});

test('user closed browser mid-payment: abandoned intent older than 10m is rescued and wallet credited', function (): void {
    // 1. Citizen initiated 3,500,000 Rials payment 15 minutes ago, but closed browser before callback
    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 3_500_000,
        'gateway' => PaymentGatewayEnum::ZARINPAL,
        'authority' => 'ABANDONED-AUTH-999',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);
    DB::table('payment_intents')->where('id', $intent->id)->update([
        'created_at' => Carbon::now()->subMinutes(15),
    ]);

    // Fast-forward or run reconciliation job
    $job = new ReconcilePaymentIntentsJob(olderThanMinutes: 10);
    $stats = $job->handle($this->gatewayDriver, $this->ledger);

    expect($stats['reconciled'])->toBe(1)
        ->and($stats['failed'])->toBe(0);

    // Intent is now marked paid
    $freshIntent = $intent->fresh();
    expect($freshIntent)->not->toBeNull()
        ->and($freshIntent->status)->toBe(PaymentIntentStatus::PAID)
        ->and($freshIntent->ref_id)->not->toBeNull()
        ->and($freshIntent->verified_at)->not->toBeNull();

    // Citizen wallet is credited
    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(3_500_000);

    // Invariant: SUM(debit) == SUM(credit)
    $totalDebits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
    $totalCredits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');
    expect($totalDebits)->toBe(3_500_000)
        ->and($totalCredits)->toBe(3_500_000);
});

test('unsuccessful abandoned intent is marked failed without crediting wallet', function (): void {
    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 1_000_000,
        'gateway' => PaymentGatewayEnum::ZARINPAL,
        'authority' => 'FAIL-ABANDONED-123',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);
    DB::table('payment_intents')->where('id', $intent->id)->update([
        'created_at' => Carbon::now()->subMinutes(20),
    ]);

    // Force gateway to return failure
    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(
            PaymentVerificationResult::failure('fake', 'پرداخت ناموفق بود')
        );
    }

    $job = new ReconcilePaymentIntentsJob(olderThanMinutes: 10);
    $stats = $job->handle($this->gatewayDriver, $this->ledger);

    expect($stats['failed'])->toBe(1)
        ->and($stats['reconciled'])->toBe(0);

    $freshIntent = $intent->fresh();
    expect($freshIntent->status)->toBe(PaymentIntentStatus::FAILED);

    // Ledger untouched
    expect(DB::table('ledger_entries')->count())->toBe(0);

    if ($this->gatewayDriver instanceof FakeDriver) {
        $this->gatewayDriver->overrideVerificationResult(null);
    }
});

test('idempotency: running reconciliation a second time does not create duplicate ledger entries', function (): void {
    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 2_000_000,
        'gateway' => PaymentGatewayEnum::ZARINPAL,
        'authority' => 'RECON-IDEM-456',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);
    DB::table('payment_intents')->where('id', $intent->id)->update([
        'created_at' => Carbon::now()->subMinutes(12),
    ]);

    $job = new ReconcilePaymentIntentsJob(olderThanMinutes: 10);

    // First run: reconciles
    $stats1 = $job->handle($this->gatewayDriver, $this->ledger);
    expect($stats1['reconciled'])->toBe(1);

    $walletAccount = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $this->citizen->id,
        kind: LedgerAccountKind::WALLET
    );
    expect($this->ledger->getBalanceRials($walletAccount))->toBe(2_000_000)
        ->and(DB::table('ledger_entries')->count())->toBe(2);

    // Second run: should find nothing pending (status is already paid)
    $stats2 = $job->handle($this->gatewayDriver, $this->ledger);
    expect($stats2['reconciled'])->toBe(0)
        ->and($stats2['skipped'])->toBe(0)
        ->and($this->ledger->getBalanceRials($walletAccount))->toBe(2_000_000)
        ->and(DB::table('ledger_entries')->count())->toBe(2);
});

test('recent intents under 10 minutes are not prematurely reconciled', function (): void {
    // Intent created 3 minutes ago
    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 5_000_000,
        'gateway' => PaymentGatewayEnum::ZARINPAL,
        'authority' => 'RECENT-AUTH-789',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);
    DB::table('payment_intents')->where('id', $intent->id)->update([
        'created_at' => Carbon::now()->subMinutes(3),
    ]);

    $job = new ReconcilePaymentIntentsJob(olderThanMinutes: 10);
    $stats = $job->handle($this->gatewayDriver, $this->ledger);

    expect($stats['reconciled'])->toBe(0)
        ->and($stats['failed'])->toBe(0)
        ->and($intent->fresh()->status)->toBe(PaymentIntentStatus::REDIRECTED)
        ->and(DB::table('ledger_entries')->count())->toBe(0);
});

test('artisan command payments:reconcile executes successfully', function (): void {
    $intent = PaymentIntent::create([
        'citizen_id' => $this->citizen->id,
        'amount_rials' => 1_500_000,
        'gateway' => PaymentGatewayEnum::ZARINPAL,
        'authority' => 'ARTISAN-AUTH-111',
        'status' => PaymentIntentStatus::REDIRECTED,
        'expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);
    DB::table('payment_intents')->where('id', $intent->id)->update([
        'created_at' => Carbon::now()->subMinutes(15),
    ]);

    $this->artisan('payments:reconcile', ['--older-than' => 10])
        ->assertSuccessful()
        ->expectsOutputToContain('Reconciliation finished: 1 reconciled, 0 marked failed.');
});
