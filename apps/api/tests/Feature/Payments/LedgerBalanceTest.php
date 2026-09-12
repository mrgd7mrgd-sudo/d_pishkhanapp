<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerEntry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;

uses(RefreshDatabase::class);

test('architecture and schema rule: no writable balance column exists in any database table (DoD: D - §6.2)', function (): void {
    $tables = [
        'ledger_accounts',
        'ledger_entries',
        'ledger_transactions',
        'citizens',
        'offices',
        'case_requests',
        'gov_inquiries',
        'case_returns',
    ];

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $columns = Schema::getColumnListing($table);
            expect($columns)->not->toContain('balance', "Table [{$table}] must not contain a writable 'balance' column.");
        }
    }
});

test('immutability rule: UPDATE or DELETE on ledger_entries throws error (DoD: TASK-054-T)', function (): void {
    $ledger = new LedgerService;
    $gateway = $ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $wallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    $tx = $ledger->recordTransaction(
        reference: 'immut_tx_'.Str::random(8),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($gateway, LedgerDirection::DEBIT, 500_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 500_000),
        ],
    );

    /** @var LedgerEntry $entry */
    $entry = $tx->entries->first();
    expect($entry)->not->toBeNull();

    // 1. Eloquent mutation attempt MUST throw LogicException
    expect(function () use ($entry): void {
        $entry->update(['amount_rials' => 999_999]);
    })->toThrow(LogicException::class, 'Ledger entries are strictly immutable. UPDATE is prohibited.');

    // 2. Direct database UPDATE query MUST trigger database-level rejection
    expect(function () use ($entry): void {
        DB::table('ledger_entries')->where('id', $entry->id)->update(['amount_rials' => 999_999]);
    })->toThrow(QueryException::class);

    // 3. Direct database DELETE query MUST trigger database-level rejection
    expect(function () use ($entry): void {
        DB::table('ledger_entries')->where('id', $entry->id)->delete();
    })->toThrow(QueryException::class);

    // 4. Eloquent delete attempt MUST throw LogicException
    expect(function () use ($entry): void {
        $entry->delete();
    })->toThrow(LogicException::class, 'Ledger entries are strictly immutable. DELETE is prohibited.');
});

test('it handles realistic case fee split and derives exact account balances', function (): void {
    $ledger = new LedgerService;
    $citizenId = (string) Str::uuid();
    $officeId = (string) Str::uuid();

    $gatewayAcc = $ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $citizenWallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizenId, LedgerAccountKind::WALLET);
    $officePayable = $ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, $officeId, LedgerAccountKind::PAYABLE);
    $platformRevenue = $ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    // Step 1: Citizen tops up 1,000,000 Rials
    $ledger->recordTransaction(
        reference: 'topup_'.Str::random(10),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($gatewayAcc, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($citizenWallet, LedgerDirection::CREDIT, 1_000_000),
        ],
        description: 'افزایش اعتبار کیف پول',
    );

    expect($ledger->getBalanceRials($citizenWallet))->toBe(1_000_000);

    // Step 2: Case fee 500,000 Rials (Office 70% = 350,000, Platform 30% = 150,000)
    $ledger->recordTransaction(
        reference: 'case_fee_'.Str::random(10),
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($citizenWallet, LedgerDirection::DEBIT, 500_000),
            new LedgerEntryData($officePayable, LedgerDirection::CREDIT, 350_000),
            new LedgerEntryData($platformRevenue, LedgerDirection::CREDIT, 150_000),
        ],
        description: 'کارمزد پرونده خدمت شهروندی',
    );

    // Check balances
    expect($ledger->getBalanceRials($citizenWallet))->toBe(500_000)
        ->and($ledger->getBalanceRials($officePayable))->toBe(350_000)
        ->and($ledger->getBalanceRials($platformRevenue))->toBe(150_000);

    // Total net balance of entire ledger ecosystem must be zero
    $totalDebits = (int) DB::table('ledger_entries')->where('direction', 'debit')->sum('amount_rials');
    $totalCredits = (int) DB::table('ledger_entries')->where('direction', 'credit')->sum('amount_rials');
    expect($totalDebits)->toBe($totalCredits)
        ->and($totalDebits)->toBe(1_500_000);
});

test('ledger models have correct relationships', function (): void {
    $ledger = new LedgerService;
    $gateway = $ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $wallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    $tx = $ledger->recordTransaction(
        reference: 'rel_tx_'.Str::random(8),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($gateway, LedgerDirection::DEBIT, 200_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 200_000),
        ],
    );

    expect($tx->entries)->toHaveCount(2)
        ->and($wallet->entries)->toHaveCount(1)
        ->and($gateway->entries)->toHaveCount(1);

    /** @var LedgerEntry $entry */
    $entry = $wallet->entries->first();
    expect($entry->account->id)->toBe($wallet->id)
        ->and($entry->transaction->id)->toBe($tx->id);
});
