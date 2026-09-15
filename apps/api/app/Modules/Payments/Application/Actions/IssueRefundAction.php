<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\RefundReason;
use App\Modules\Payments\Domain\Exceptions\RefundAlreadyProcessedException;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class IssueRefundAction
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    public function execute(
        CaseRequest $case,
        RefundReason|string $reason = RefundReason::DISPATCH_EXHAUSTED,
        ?int $customRefundRials = null
    ): LedgerTransaction {
        $reasonEnum = is_string($reason)
            ? (RefundReason::tryFrom($reason) ?? RefundReason::MANUAL)
            : $reason;

        if ($case->fee_paid_rials <= 0) {
            throw new InvalidArgumentException('مبلغ پرداختی پرونده برای استرداد باید بزرگتر از صفر باشد.');
        }

        return DB::transaction(function () use ($case, $reasonEnum, $customRefundRials): LedgerTransaction {
            // Rule: No duplicate refunds allowed (TASK-088-T)
            /** @var LedgerTransaction|null $existingRefund */
            $existingRefund = LedgerTransaction::query()
                ->where('case_id', $case->id)
                ->where('type', LedgerTransactionType::REFUND->value)
                ->lockForUpdate()
                ->first();

            if ($existingRefund !== null) {
                throw new RefundAlreadyProcessedException($case->id);
            }

            if ($customRefundRials !== null) {
                $refundRials = min($case->fee_paid_rials, max(0, $customRefundRials));
            } else {
                $ratio = $reasonEnum->refundRatio();
                $refundRials = (int) round($case->fee_paid_rials * $ratio);
            }

            $retainedRials = $case->fee_paid_rials - $refundRials;

            $escrow = $this->ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::PLATFORM,
                ownerId: null,
                kind: LedgerAccountKind::ESCROW
            );

            $wallet = $this->ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::CITIZEN,
                ownerId: $case->citizen_id,
                kind: LedgerAccountKind::WALLET
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
                    amountRials: $refundRials
                ),
            ];

            if ($retainedRials > 0) {
                $revenue = $this->ledgerService->getOrCreateAccount(
                    ownerType: LedgerOwnerType::PLATFORM,
                    ownerId: null,
                    kind: LedgerAccountKind::REVENUE
                );

                $entries[] = new LedgerEntryData(
                    account: $revenue,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $retainedRials
                );
            }

            $description = $reasonEnum->isFullRefund()
                ? "استرداد کامل وجه پرونده {$case->tracking_code}"
                : "استرداد جزئی وجه پرونده {$case->tracking_code}";

            return $this->ledgerService->recordTransaction(
                reference: 'REFUND-'.$case->tracking_code,
                type: LedgerTransactionType::REFUND,
                entries: $entries,
                description: $description,
                caseId: $case->id
            );
        });
    }
}
