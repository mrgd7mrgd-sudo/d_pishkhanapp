<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Exceptions\UnbalancedTransactionException;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\LedgerEntry;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use App\Shared\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Double-Entry Ledger Service (Architecture §6.1, §6.2, §8.2, TASK-054).
 * Strict equality rule: SUM(debit) = SUM(credit) enforced at service boundary.
 */
final class LedgerService
{
    /**
     * Record a balanced double-entry transaction.
     *
     * @param  list<LedgerEntryData>  $entries
     */
    public function recordTransaction(
        string $reference,
        LedgerTransactionType $type,
        array $entries,
        ?string $description = null,
        ?string $caseId = null,
        ?string $paymentIntentId = null,
        ?CarbonImmutable $postedAt = null,
    ): LedgerTransaction {
        $this->assertValidEntries($entries);

        return DB::transaction(function () use (
            $reference,
            $type,
            $entries,
            $description,
            $caseId,
            $paymentIntentId,
            $postedAt
        ): LedgerTransaction {
            $tx = LedgerTransaction::create([
                'id' => (string) Str::uuid(),
                'reference' => $reference,
                'type' => $type,
                'case_id' => $caseId,
                'payment_intent_id' => $paymentIntentId,
                'description' => $description,
                'posted_at' => $postedAt ?? now(),
            ]);

            $entryTimestamp = now();

            foreach ($entries as $entryData) {
                LedgerEntry::create([
                    'id' => (string) Str::uuid(),
                    'transaction_id' => $tx->id,
                    'account_id' => $entryData->accountId,
                    'direction' => $entryData->direction,
                    'amount_rials' => $entryData->amountRials,
                    'created_at' => $entryTimestamp,
                ]);
            }

            return $tx;
        });
    }

    /**
     * @param  list<LedgerEntryData>  $entries
     */
    private function assertValidEntries(array $entries): void
    {
        if (count($entries) < 2) {
            throw new UnbalancedTransactionException(
                message: 'تراکنش دفتر کل باید حداقل شامل دو ورودی (بدهکار و بستانکار) باشد.'
            );
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($entries as $entry) {
            if ($entry->amountRials <= 0) {
                throw new InvalidArgumentException('مبلغ ردیف سند دفتر کل باید بزرگتر از صفر باشد.');
            }

            if ($entry->direction === LedgerDirection::DEBIT) {
                $totalDebits += $entry->amountRials;
            } else {
                $totalCredits += $entry->amountRials;
            }
        }

        if ($totalDebits !== $totalCredits) {
            throw new UnbalancedTransactionException(
                totalDebits: $totalDebits,
                totalCredits: $totalCredits,
            );
        }
    }

    public function getOrCreateAccount(
        LedgerOwnerType $ownerType,
        ?string $ownerId,
        LedgerAccountKind $kind,
        string $currency = 'IRR'
    ): LedgerAccount {
        $query = LedgerAccount::query()
            ->where('owner_type', $ownerType->value)
            ->where('kind', $kind->value)
            ->where('currency', strtoupper($currency));

        if ($ownerId === null) {
            $query->whereNull('owner_id');
        } else {
            $query->where('owner_id', $ownerId);
        }

        /** @var LedgerAccount|null $existing */
        $existing = $query->first();

        if ($existing !== null) {
            return $existing;
        }

        return LedgerAccount::create([
            'id' => (string) Str::uuid(),
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'kind' => $kind,
            'currency' => strtoupper($currency),
        ]);
    }

    public function getBalanceRials(LedgerAccount|string $account): int
    {
        $accountId = $account instanceof LedgerAccount ? $account->id : $account;

        $credits = (int) DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('direction', LedgerDirection::CREDIT->value)
            ->sum('amount_rials');

        $debits = (int) DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('direction', LedgerDirection::DEBIT->value)
            ->sum('amount_rials');

        return $credits - $debits;
    }

    public function getBalance(LedgerAccount|string $account): Money
    {
        return Money::fromRials($this->getBalanceRials($account));
    }

    public function hasSufficientBalance(LedgerAccount|string $account, int $amountRials): bool
    {
        return $this->getBalanceRials($account) >= $amountRials;
    }
}
