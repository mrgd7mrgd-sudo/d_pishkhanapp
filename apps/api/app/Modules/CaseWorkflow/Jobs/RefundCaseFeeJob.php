<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Jobs;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * RefundCaseFeeJob (Architecture §5.8, TASK-067).
 * Executes full refund in the double-entry ledger when case dispatch is exhausted.
 */
final class RefundCaseFeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 45];

    public function __construct(
        public readonly string $caseId
    ) {
        $this->onQueue('ledger');
    }

    public function handle(LedgerService $ledgerService): void
    {
        $case = CaseRequest::query()->find($this->caseId);
        if ($case === null || $case->fee_paid_rials <= 0) {
            return;
        }

        DB::transaction(function () use ($case, $ledgerService): void {
            $wallet = $ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::CITIZEN,
                ownerId: $case->citizen_id,
                kind: LedgerAccountKind::WALLET
            );

            $escrow = $ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::PLATFORM,
                ownerId: null,
                kind: LedgerAccountKind::ESCROW
            );

            $entries = [
                new LedgerEntryData(
                    account: $escrow,
                    direction: LedgerDirection::DEBIT,
                    amountRials: $case->fee_paid_rials
                ),
                new LedgerEntryData(
                    account: $wallet,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $case->fee_paid_rials
                ),
            ];

            $ledgerService->recordTransaction(
                reference: 'FULL-REFUND-'.$case->tracking_code,
                type: LedgerTransactionType::REFUND,
                entries: $entries,
                description: "استرداد کامل وجه پرونده به دلیل نیافتن دفتر {$case->tracking_code}"
            );
        });
    }
}
