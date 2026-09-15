<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\LedgerBalanceSnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * BalanceCache (Architecture §6.7, §9.2, TASK-091).
 * High-performance Redis caching layer for citizen wallet balances with daily snapshots + delta calculation.
 *
 * NOTE: This is an accelerator cache, NOT the source of truth.
 * Source of truth is always immutable double-entry ledger entries (SUM(ledger_entries)).
 */
final class BalanceCache
{
    public const CACHE_TTL_SECONDS = 300; // 5 minutes (Architecture §6.7)

    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Get wallet balance in Rials for citizen, reading from Redis cache or falling back to ledger entries.
     */
    public function getBalance(string $citizenId): int
    {
        $cacheKey = $this->makeCacheKey($citizenId);

        /** @var int|string|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return (int) $cached;
        }

        $account = $this->ledgerService->getOrCreateAccount(
            ownerType: LedgerOwnerType::CITIZEN,
            ownerId: $citizenId,
            kind: LedgerAccountKind::WALLET
        );

        $balance = $this->computeAccountBalance($account);

        Cache::put($cacheKey, $balance, self::CACHE_TTL_SECONDS);

        return $balance;
    }

    /**
     * Invalidate wallet balance cache for citizen.
     */
    public function invalidate(string $citizenId): void
    {
        Cache::forget($this->makeCacheKey($citizenId));
    }

    /**
     * Explicitly set wallet balance in cache.
     */
    public function set(string $citizenId, int $balanceRials): void
    {
        Cache::put($this->makeCacheKey($citizenId), $balanceRials, self::CACHE_TTL_SECONDS);
    }

    /**
     * Invalidate cache if given account belongs to a citizen wallet.
     */
    public function invalidateForAccount(LedgerAccount $account): void
    {
        if (
            $account->owner_type === LedgerOwnerType::CITIZEN
            && $account->kind === LedgerAccountKind::WALLET
            && $account->owner_id !== null
        ) {
            $this->invalidate($account->owner_id);
        }
    }

    /**
     * Compute materialized balance for any ledger account using latest snapshot + delta since snapshot.
     */
    public function computeAccountBalance(LedgerAccount|string $account): int
    {
        $accountId = $account instanceof LedgerAccount ? $account->id : $account;

        /** @var LedgerBalanceSnapshot|null $latestSnapshot */
        $latestSnapshot = LedgerBalanceSnapshot::query()
            ->where('account_id', $accountId)
            ->orderBy('snapshot_at', 'desc')
            ->first();

        if ($latestSnapshot === null) {
            return $this->ledgerService->getBalanceRials($accountId);
        }

        // Calculate delta entries strictly after snapshot timestamp
        $deltaCredits = (int) DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('direction', LedgerDirection::CREDIT->value)
            ->where('created_at', '>', $latestSnapshot->snapshot_at)
            ->sum('amount_rials');

        $deltaDebits = (int) DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('direction', LedgerDirection::DEBIT->value)
            ->where('created_at', '>', $latestSnapshot->snapshot_at)
            ->sum('amount_rials');

        return $latestSnapshot->balance_rials + ($deltaCredits - $deltaDebits);
    }

    /**
     * Invalidate and re-compute fresh balance into cache.
     */
    public function warm(string $citizenId): int
    {
        $this->invalidate($citizenId);

        return $this->getBalance($citizenId);
    }

    private function makeCacheKey(string $citizenId): string
    {
        return "wallet:{$citizenId}:balance";
    }
}
