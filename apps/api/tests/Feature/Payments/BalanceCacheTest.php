<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerBalanceSnapshot;
use App\Modules\Payments\Infrastructure\BalanceCache;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست کش موجودی';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->ledger = app(LedgerService::class);
    $this->balanceCache = app(BalanceCache::class);

    $this->wallet = $this->ledger->getOrCreateAccount(
        LedgerOwnerType::CITIZEN,
        $this->citizen->id,
        LedgerAccountKind::WALLET
    );

    $this->clearing = $this->ledger->getOrCreateAccount(
        LedgerOwnerType::PLATFORM,
        null,
        LedgerAccountKind::CLEARING
    );

    Cache::flush();
});

test('property-based test: cached balance strictly matches SUM(ledger_entries) across 1000 random transactions', function (): void {
    // Seed initial balance of 100M Rials to allow both debits and credits
    $initialBalance = 100_000_000;
    $this->ledger->recordTransaction(
        reference: 'init_prop_test',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, $initialBalance),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, $initialBalance),
        ]
    );

    $runningBalance = $initialBalance;

    for ($i = 1; $i <= 1000; $i++) {
        $amount = random_int(1_000, 50_000);
        $isCredit = ($runningBalance < 5_000_000) || (random_int(0, 1) === 1);

        if ($isCredit) {
            $this->ledger->recordTransaction(
                reference: "tx_prop_credit_{$i}",
                type: LedgerTransactionType::TOPUP,
                entries: [
                    new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, $amount),
                    new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, $amount),
                ]
            );
            $runningBalance += $amount;
        } else {
            $this->ledger->recordTransaction(
                reference: "tx_prop_debit_{$i}",
                type: LedgerTransactionType::SERVICE_FEE,
                entries: [
                    new LedgerEntryData($this->wallet, LedgerDirection::DEBIT, $amount),
                    new LedgerEntryData($this->clearing, LedgerDirection::CREDIT, $amount),
                ]
            );
            $runningBalance -= $amount;
        }

        // Invalidate cache on each transaction to simulate write lifecycle
        $this->balanceCache->invalidate($this->citizen->id);

        // Every 50 iterations, verify strict 100% equality
        if ($i % 50 === 0 || $i === 1000) {
            $cachedBalance = $this->balanceCache->getBalance($this->citizen->id);
            $actualLedgerSum = $this->ledger->getBalanceRials($this->wallet);

            expect($cachedBalance)->toBe($actualLedgerSum)
                ->and($cachedBalance)->toBe($runningBalance);
        }
    }
});

test('redis resilience: flushing Redis cache does not corrupt data, only causes temporary cache miss and transparent recalculation', function (): void {
    $this->ledger->recordTransaction(
        reference: 'tx_resilience_test',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, 5_000_000),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, 5_000_000),
        ]
    );

    // Initial cache population
    $balance1 = $this->balanceCache->getBalance($this->citizen->id);
    expect($balance1)->toBe(5_000_000)
        ->and(Cache::has("wallet:{$this->citizen->id}:balance"))->toBeTrue();

    // Completely flush Redis / Cache
    Cache::flush();
    expect(Cache::has("wallet:{$this->citizen->id}:balance"))->toBeFalse();

    // Read again: must seamlessly recalculate from SUM(ledger_entries) without loss
    $balance2 = $this->balanceCache->getBalance($this->citizen->id);
    expect($balance2)->toBe(5_000_000)
        ->and(Cache::has("wallet:{$this->citizen->id}:balance"))->toBeTrue();
});

test('performance benchmark: cached balance read p95 < 20ms', function (): void {
    $this->ledger->recordTransaction(
        reference: 'tx_bench_test',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, 10_000_000),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, 10_000_000),
        ]
    );

    // Warm the cache
    $this->balanceCache->getBalance($this->citizen->id);

    $latenciesMs = [];

    for ($i = 0; $i < 100; $i++) {
        $start = hrtime(true);
        $balance = $this->balanceCache->getBalance($this->citizen->id);
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        expect($balance)->toBe(10_000_000);
        $latenciesMs[] = $durationMs;
    }

    sort($latenciesMs);
    $p95Index = (int) ceil(0.95 * count($latenciesMs)) - 1;
    $p95 = $latenciesMs[$p95Index];

    // Benchmark requirement (§9.5, TASK-091-T): p95 < 20ms
    expect($p95)->toBeLessThan(20.0);
});

test('snapshot balances command creates materialized snapshots and computes snapshot + delta accurately', function (): void {
    // 1. Initial entries before snapshot: 3,000,000 Rials
    $this->ledger->recordTransaction(
        reference: 'tx_snap_initial',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, 3_000_000),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, 3_000_000),
        ]
    );

    // 2. Execute snapshot command
    $exitCode = Artisan::call('payments:snapshot-balances', [
        '--account' => $this->wallet->id,
    ]);

    expect($exitCode)->toBe(0);

    /** @var LedgerBalanceSnapshot|null $snapshot */
    $snapshot = LedgerBalanceSnapshot::query()
        ->where('account_id', $this->wallet->id)
        ->first();

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->balance_rials)->toBe(3_000_000);

    // Travel 1 minute forward to guarantee delta created_at > snapshot_at
    $this->travel(1)->minutes();

    // 3. New entries after snapshot: +2,000,000 credit, -500,000 debit (net +1,500,000)
    $this->ledger->recordTransaction(
        reference: 'tx_snap_delta_credit',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, 2_000_000),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, 2_000_000),
        ]
    );

    $this->ledger->recordTransaction(
        reference: 'tx_snap_delta_debit',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($this->wallet, LedgerDirection::DEBIT, 500_000),
            new LedgerEntryData($this->clearing, LedgerDirection::CREDIT, 500_000),
        ]
    );

    // 4. Verify computeAccountBalance uses snapshot + delta and exactly equals SUM(entries)
    $computedFromSnapshot = $this->balanceCache->computeAccountBalance($this->wallet);
    $fullLedgerSum = $this->ledger->getBalanceRials($this->wallet);

    expect($computedFromSnapshot)->toBe(4_500_000)
        ->and($computedFromSnapshot)->toBe($fullLedgerSum);
});

test('weekly refresh materialized views command executes cleanly', function (): void {
    $exitCode = Artisan::call('pishkhan:refresh-materialized-views');
    expect($exitCode)->toBe(0);
});

test('GET /wallet/balance integrates with BalanceCache and returns exact Rials and Tomans', function (): void {
    $this->ledger->recordTransaction(
        reference: 'tx_api_balance_test',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->clearing, LedgerDirection::DEBIT, 7_500_000),
            new LedgerEntryData($this->wallet, LedgerDirection::CREDIT, 7_500_000),
        ]
    );

    Sanctum::actingAs($this->citizen);

    $response = $this->getJson('/api/v1/wallet/balance');

    $response->assertOk()
        ->assertJsonPath('data.balance_rials', 7_500_000)
        ->assertJsonPath('data.balance_toman', 750_000)
        ->assertJsonPath('data.currency', 'IRR');
});
