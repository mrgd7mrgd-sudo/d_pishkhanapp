<?php

declare(strict_types=1);

namespace App\Modules\Payments\Jobs;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\FeeSplitCalculator;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SettleCaseFeeJob (Architecture §8.2, §5.9, TASK-089).
 * Splits case fee between office payable account and platform revenue upon case completion.
 */
final class SettleCaseFeeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

    public function handle(
        LedgerService $ledgerService,
        FeeSplitCalculator $calculator
    ): void {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->with('service')->find($this->caseId);

        if ($case === null || $case->fee_paid_rials <= 0 || $case->office_id === null) {
            return;
        }

        DB::transaction(function () use ($case, $ledgerService, $calculator): void {
            // Idempotency check: Ensure case fee has not already been settled
            /** @var LedgerTransaction|null $existingSettlement */
            $existingSettlement = LedgerTransaction::query()
                ->where('case_id', $case->id)
                ->where('reference', 'SETTLE-'.$case->tracking_code)
                ->lockForUpdate()
                ->first();

            if ($existingSettlement !== null) {
                Log::info('Case fee has already been settled, skipping.', [
                    'case_id' => $case->id,
                    'transaction_id' => $existingSettlement->id,
                ]);

                return;
            }

            $officeSharePercent = (float) ($case->service->office_share_percent ?? 50.0);
            $split = $calculator->calculateServiceFeeSplit($case->fee_paid_rials, $officeSharePercent);

            $officeShare = $split['office_share_rials'];
            $platformShare = $split['platform_share_rials'];

            $escrow = $ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::PLATFORM,
                ownerId: null,
                kind: LedgerAccountKind::ESCROW
            );

            $officePayable = $ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::OFFICE,
                ownerId: $case->office_id,
                kind: LedgerAccountKind::PAYABLE
            );

            $platformRevenue = $ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::PLATFORM,
                ownerId: null,
                kind: LedgerAccountKind::REVENUE
            );

            $entries = [
                new LedgerEntryData(
                    account: $escrow,
                    direction: LedgerDirection::DEBIT,
                    amountRials: $case->fee_paid_rials
                ),
                new LedgerEntryData(
                    account: $officePayable,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $officeShare
                ),
                new LedgerEntryData(
                    account: $platformRevenue,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $platformShare
                ),
            ];

            $ledgerService->recordTransaction(
                reference: 'SETTLE-'.$case->tracking_code,
                type: LedgerTransactionType::SERVICE_FEE,
                entries: $entries,
                description: "تسویه قطعی کارمزد پرونده {$case->tracking_code}: سهم دفتر {$officeShare} ریال، سهم پلتفرم {$platformShare} ریال",
                caseId: $case->id
            );

            Log::info('Successfully settled case fee between office and platform.', [
                'case_id' => $case->id,
                'office_id' => $case->office_id,
                'total_fee' => $case->fee_paid_rials,
                'office_share' => $officeShare,
                'platform_share' => $platformShare,
            ]);
        });
    }
}
