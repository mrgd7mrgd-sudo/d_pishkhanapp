<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\SlaClock;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ExpireActionRequiredCasesCommand (Architecture §3.5, §5.9, TASK-061).
 * Runs hourly with a distributed Redis lock to find action_required cases exceeding
 * 72 hours deadline, moves them to cancelled, and posts partial ledger refunds.
 */
final class ExpireActionRequiredCasesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pishkhan:expire-action-required-cases';

    /**
     * @var string
     */
    protected $description = 'Automatically cancel action_required cases exceeding 72 hours SLA and issue partial refunds';

    public function handle(
        SlaClock $slaClock,
        CaseStateMachine $stateMachine,
        LedgerService $ledgerService
    ): int {
        $lock = Cache::lock('lock:sched:expire_action_required_cases', 300);

        if (! $lock->get()) {
            $this->warn('ExpireActionRequiredCasesCommand is already running on another instance.');

            return self::SUCCESS;
        }

        try {
            $this->info('Scanning for action_required cases exceeding 72h SLA deadline...');

            $candidates = CaseRequest::query()
                ->where('status', CaseStatus::ACTION_REQUIRED->value)
                ->with(['returns', 'service'])
                ->get();

            $expiredCount = 0;

            foreach ($candidates as $case) {
                if (! $slaClock->isActionRequiredExpired($case)) {
                    continue;
                }

                $this->processExpiredCase($case, $stateMachine, $ledgerService);
                $expiredCount++;
            }

            $this->info("Completed processing. Cancelled and refunded {$expiredCount} expired cases.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function processExpiredCase(
        CaseRequest $case,
        CaseStateMachine $stateMachine,
        LedgerService $ledgerService
    ): void {
        DB::transaction(function () use ($case, $stateMachine, $ledgerService): void {
            $stateMachine->transition(
                case: $case,
                to: CaseStatus::CANCELLED,
                ctx: new TransitionContext(
                    title: 'انقضای مهلت اصلاح مدارک',
                    description: 'به دلیل عدم اقدام در مهلت ۷۲ ساعته، پرونده لغو و استرداد جزئی انجام شد.',
                    stepStatus: TimelineStepStatus::FAILED,
                    actorType: TimelineActorType::SYSTEM,
                    actorId: null,
                    reasonCode: 'SLA_EXPIRED',
                    metadata: [
                        'expired_at' => CarbonImmutable::now()->toIso8601String(),
                        'timeout_hours' => SlaClock::ACTION_REQUIRED_TIMEOUT_HOURS,
                    ]
                )
            );

            if ($case->fee_paid_rials > 0) {
                $this->issuePartialLedgerRefund($case, $ledgerService);
            }
        });
    }

    private function issuePartialLedgerRefund(CaseRequest $case, LedgerService $ledgerService): void
    {
        // 70% refund to citizen wallet, 30% retained for platform/operator review costs
        $refundAmount = (int) round($case->fee_paid_rials * 0.7);
        $retainedAmount = $case->fee_paid_rials - $refundAmount;

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

        $revenue = $ledgerService->getOrCreateAccount(
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
                account: $wallet,
                direction: LedgerDirection::CREDIT,
                amountRials: $refundAmount
            ),
        ];

        if ($retainedAmount > 0) {
            $entries[] = new LedgerEntryData(
                account: $revenue,
                direction: LedgerDirection::CREDIT,
                amountRials: $retainedAmount
            );
        }

        $ledgerService->recordTransaction(
            reference: 'PARTIAL-REFUND-'.$case->tracking_code,
            type: LedgerTransactionType::REFUND,
            entries: $entries,
            description: "استرداد جزئی وجه پرونده منقضی شده {$case->tracking_code}"
        );
    }
}
