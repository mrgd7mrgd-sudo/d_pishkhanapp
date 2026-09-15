<?php

declare(strict_types=1);

namespace App\Modules\Payments\Console\Commands;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\LedgerBalanceSnapshot;
use App\Modules\Payments\Infrastructure\BalanceCache;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SnapshotBalancesCommand (Architecture §5.9, §6.7, §9.2, TASK-091).
 * Creates materialized balance checkpoints for all ledger accounts and pre-warms wallet cache.
 */
final class SnapshotBalancesCommand extends Command
{
    protected $signature = 'payments:snapshot-balances
                            {--account= : Snapshot balance for a specific account ID only}';

    protected $description = 'Create daily balance snapshots for ledger accounts and refresh wallet balance cache';

    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly BalanceCache $balanceCache
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting ledger balance snapshotting...');

        $accounts = $this->resolveAccounts();
        $this->info("Found {$accounts->count()} ledger accounts to snapshot.");

        $snapshotsCreated = 0;
        $snapshotTimestamp = now();

        foreach ($accounts as $account) {
            $this->snapshotAccount($account, $snapshotTimestamp);
            $snapshotsCreated++;
        }

        $this->info("Completed snapshotting. {$snapshotsCreated} account snapshots created.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, LedgerAccount>
     */
    private function resolveAccounts(): Collection
    {
        /** @var string|null $accountId */
        $accountId = $this->option('account');

        if ($accountId !== null && $accountId !== '') {
            /** @var Collection<int, LedgerAccount> $accounts */
            $accounts = LedgerAccount::query()->where('id', $accountId)->get();

            return $accounts;
        }

        /** @var Collection<int, LedgerAccount> $accounts */
        $accounts = LedgerAccount::query()->get();

        return $accounts;
    }

    private function snapshotAccount(LedgerAccount $account, \DateTimeInterface $snapshotAt): LedgerBalanceSnapshot
    {
        $balanceRials = $this->ledgerService->getBalanceRials($account);

        /** @var string|null $latestTxId */
        $latestTxId = DB::table('ledger_entries')
            ->where('account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->value('transaction_id');

        /** @var LedgerBalanceSnapshot $snapshot */
        $snapshot = LedgerBalanceSnapshot::query()->create([
            'account_id' => $account->id,
            'balance_rials' => $balanceRials,
            'as_of_transaction_id' => $latestTxId,
            'snapshot_at' => $snapshotAt,
        ]);

        // Pre-warm Redis cache for citizen wallets
        if (
            $account->owner_type === LedgerOwnerType::CITIZEN
            && $account->kind === LedgerAccountKind::WALLET
            && $account->owner_id !== null
        ) {
            $this->balanceCache->set($account->owner_id, $balanceRials);
        }

        Log::debug("Created balance snapshot for account {$account->id}: {$balanceRials} Rials.");

        return $snapshot;
    }
}
