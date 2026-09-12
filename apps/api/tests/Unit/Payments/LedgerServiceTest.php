<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Exceptions\UnbalancedTransactionException;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->ledger = new LedgerService;
});

test('it creates and retrieves accounts idempotently via getOrCreateAccount', function (): void {
    $citizenId = (string) Str::uuid();

    $account1 = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $citizenId,
        kind: LedgerAccountKind::WALLET,
    );

    expect($account1)->toBeInstanceOf(LedgerAccount::class)
        ->and($account1->owner_type)->toBe(LedgerOwnerType::CITIZEN)
        ->and($account1->owner_id)->toBe($citizenId)
        ->and($account1->kind)->toBe(LedgerAccountKind::WALLET)
        ->and($account1->currency)->toBe('IRR');

    // Second call with same parameters must return the existing account
    $account2 = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::CITIZEN,
        ownerId: $citizenId,
        kind: LedgerAccountKind::WALLET,
    );

    expect($account2->id)->toBe($account1->id)
        ->and(LedgerAccount::count())->toBe(1);

    // Platform account with null ownerId
    $platformRev = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::PLATFORM,
        ownerId: null,
        kind: LedgerAccountKind::REVENUE,
    );

    expect($platformRev->owner_id)->toBeNull()
        ->and($platformRev->kind)->toBe(LedgerAccountKind::REVENUE);

    // Second call for platform returns exact same
    $platformRev2 = $this->ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::PLATFORM,
        ownerId: null,
        kind: LedgerAccountKind::REVENUE,
    );

    expect($platformRev2->id)->toBe($platformRev->id);
});

test('it records balanced double-entry transactions successfully', function (): void {
    $gateway = $this->ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    // Topup 1,000,000 Rials: Gateway (debit), Citizen Wallet (credit)
    $tx = $this->ledger->recordTransaction(
        reference: 'topup_'.Str::random(12),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($gateway, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 1_000_000),
        ],
        description: 'شارژ کیف پول شهروند',
    );

    expect($tx)->toBeInstanceOf(LedgerTransaction::class)
        ->and($tx->type)->toBe(LedgerTransactionType::TOPUP)
        ->and($tx->entries)->toHaveCount(2);

    expect($this->ledger->getBalanceRials($wallet))->toBe(1_000_000)
        ->and($this->ledger->getBalance($wallet)->getAmountRials())->toBe(1_000_000)
        ->and($this->ledger->hasSufficientBalance($wallet, 500_000))->toBeTrue()
        ->and($this->ledger->hasSufficientBalance($wallet, 1_000_001))->toBeFalse();
});

test('it throws UnbalancedTransactionException when debits do not equal credits', function (): void {
    $gateway = $this->ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    expect(function () use ($gateway, $wallet): void {
        $this->ledger->recordTransaction(
            reference: 'unbalanced_'.Str::random(8),
            type: LedgerTransactionType::TOPUP,
            entries: [
                new LedgerEntryData($gateway, LedgerDirection::DEBIT, 1_000_000),
                new LedgerEntryData($wallet, LedgerDirection::CREDIT, 900_000),
            ],
        );
    })->toThrow(UnbalancedTransactionException::class);
});

test('it throws UnbalancedTransactionException when transaction has fewer than 2 entries', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    expect(function () use ($wallet): void {
        $this->ledger->recordTransaction(
            reference: 'single_entry_'.Str::random(8),
            type: LedgerTransactionType::TOPUP,
            entries: [
                new LedgerEntryData($wallet, LedgerDirection::CREDIT, 100_000),
            ],
        );
    })->toThrow(UnbalancedTransactionException::class);
});

test('it throws InvalidArgumentException when an entry amount is zero or negative', function (): void {
    $gateway = $this->ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET);

    expect(function () use ($gateway, $wallet): void {
        $this->ledger->recordTransaction(
            reference: 'zero_amount_'.Str::random(8),
            type: LedgerTransactionType::TOPUP,
            entries: [
                new LedgerEntryData($gateway, LedgerDirection::DEBIT, 0),
                new LedgerEntryData($wallet, LedgerDirection::CREDIT, 0),
            ],
        );
    })->toThrow(InvalidArgumentException::class, 'مبلغ ردیف سند دفتر کل باید بزرگتر از صفر باشد.');
});

test('property-based test: 1000 random transactions strictly satisfy SUM(debit) = SUM(credit) (DoD: TASK-054-T)', function (): void {
    $accounts = [
        $this->ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING),
        $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE),
        $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW),
        $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, (string) Str::uuid(), LedgerAccountKind::WALLET),
        $this->ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, (string) Str::uuid(), LedgerAccountKind::PAYABLE),
    ];

    $txCount = 1000;
    $grandTotalDebits = 0;
    $grandTotalCredits = 0;

    for ($i = 0; $i < $txCount; $i++) {
        // Generate random amounts for 2 to 4 entries such that debits = credits
        $amount = random_int(1_000, 10_000_000);

        // Pick two distinct accounts
        $sourceIndex = random_int(0, count($accounts) - 1);
        do {
            $destIndex = random_int(0, count($accounts) - 1);
        } while ($destIndex === $sourceIndex);

        $sourceAccount = $accounts[$sourceIndex];
        $destAccount = $accounts[$destIndex];

        $entries = [
            new LedgerEntryData($sourceAccount, LedgerDirection::DEBIT, $amount),
            new LedgerEntryData($destAccount, LedgerDirection::CREDIT, $amount),
        ];

        // Randomly test multi-legged split (e.g. 70% / 30%)
        if ($i % 3 === 0 && count($accounts) >= 3) {
            $split1 = intdiv($amount * 70, 100);
            $split2 = $amount - $split1;

            do {
                $destIndex2 = random_int(0, count($accounts) - 1);
            } while ($destIndex2 === $sourceIndex || $destIndex2 === $destIndex);

            $destAccount2 = $accounts[$destIndex2];

            $entries = [
                new LedgerEntryData($sourceAccount, LedgerDirection::DEBIT, $amount),
                new LedgerEntryData($destAccount, LedgerDirection::CREDIT, $split1),
                new LedgerEntryData($destAccount2, LedgerDirection::CREDIT, $split2),
            ];
        }

        $tx = $this->ledger->recordTransaction(
            reference: 'prop_tx_'.$i.'_'.Str::random(6),
            type: LedgerTransactionType::SERVICE_FEE,
            entries: $entries,
        );

        $txDebits = 0;
        $txCredits = 0;
        foreach ($entries as $e) {
            if ($e->direction === LedgerDirection::DEBIT) {
                $txDebits += $e->amountRials;
            } else {
                $txCredits += $e->amountRials;
            }
        }

        // Each individual transaction must be perfectly balanced
        expect($txDebits)->toBe($txCredits);

        $grandTotalDebits += $txDebits;
        $grandTotalCredits += $txCredits;
    }

    // Verify grand totals in DB across all 1000 transactions
    $dbDebits = (int) DB::table('ledger_entries')
        ->where('direction', LedgerDirection::DEBIT->value)
        ->sum('amount_rials');

    $dbCredits = (int) DB::table('ledger_entries')
        ->where('direction', LedgerDirection::CREDIT->value)
        ->sum('amount_rials');

    expect($dbDebits)->toBe($dbCredits)
        ->and($dbDebits)->toBe($grandTotalDebits)
        ->and($dbCredits)->toBe($grandTotalCredits)
        ->and(LedgerTransaction::count())->toBe($txCount);
});
