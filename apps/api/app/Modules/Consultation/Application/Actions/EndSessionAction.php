<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Enums\ConsultationSessionStatus;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Consultation\Domain\SessionBillingCalculator;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class EndSessionAction
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * End session and settle payment in double-entry ledger (§5.3, §8.2, E21).
     *
     * Invariant §5.3: Duration and billing are computed strictly from server timestamps!
     * Revenue settlement: 80% to advisor payable / 20% to platform revenue.
     */
    public function execute(
        Authenticatable $actor,
        string $sessionId,
        ?string $advisorVerdict = null,
        int $uploadedDocsCount = 0
    ): ConsultationSession {
        /** @var ConsultationSession|null $session */
        $session = ConsultationSession::query()->with(['advisor', 'citizen'])->find($sessionId);
        if ($session === null) {
            throw new InvalidArgumentException('جلسه مشاوره یافت نشد.');
        }

        if ($session->status !== ConsultationSessionStatus::Active) {
            throw new InvalidArgumentException('فقط جلسات فعال می‌توانند خاتمه یابند.');
        }

        $isCitizen = $actor instanceof Citizen && $actor->id === $session->citizen_id;
        $isAdvisor = $session->advisor->citizen_id === $actor->getAuthIdentifier();

        if (! $isCitizen && ! $isAdvisor) {
            throw new InvalidArgumentException('شما مجاز به اتمام این جلسه مشاوره نیستید.');
        }

        return DB::transaction(function () use ($actor, $session, $advisorVerdict, $uploadedDocsCount): ConsultationSession {
            // Lock citizen wallet to prevent double spending
            $citizenWallet = $this->ledgerService->getOrCreateAccount(
                LedgerOwnerType::CITIZEN,
                $session->citizen_id,
                LedgerAccountKind::WALLET
            );

            // Server timestamp strictly (§5.3 Invariant)
            $endedAt = CarbonImmutable::now();
            $startedAt = CarbonImmutable::instance($session->started_at ?? $endedAt);

            $billing = SessionBillingCalculator::calculate(
                advisor: $session->advisor,
                mode: $session->mode,
                startedAt: $startedAt,
                endedAt: $endedAt
            );

            $totalFeeRials = $billing['total_fee_rials'];
            $advisorFeeRials = $billing['advisor_fee_rials'];
            $platformFeeRials = $billing['platform_fee_rials'];

            // Settle in Double-Entry Ledger (§8.2, E21) if totalFee > 0
            if ($totalFeeRials > 0) {
                if (! $this->ledgerService->hasSufficientBalance($citizenWallet, $totalFeeRials)) {
                    throw new InvalidArgumentException('موجودی کیف پول شهروند برای تسویه جلسه مشاوره کافی نیست.');
                }

                $advisorPayable = $this->ledgerService->getOrCreateAccount(
                    LedgerOwnerType::ADVISOR,
                    $session->advisor_id,
                    LedgerAccountKind::PAYABLE
                );

                $platformRevenue = $this->ledgerService->getOrCreateAccount(
                    LedgerOwnerType::PLATFORM,
                    null,
                    LedgerAccountKind::REVENUE
                );

                // Multi-leg transaction:
                // Debit Citizen Wallet: totalFeeRials
                // Credit Advisor Payable (80%): advisorFeeRials
                // Credit Platform Revenue (20%): platformFeeRials
                // Total Debit = totalFeeRials == Total Credit (advisorFeeRials + platformFeeRials)
                $this->ledgerService->recordTransaction(
                    reference: 'CONSULT-'.$session->tracking_code,
                    type: LedgerTransactionType::CONSULTATION_FEE,
                    entries: [
                        new LedgerEntryData($citizenWallet, LedgerDirection::DEBIT, $totalFeeRials),
                        new LedgerEntryData($advisorPayable, LedgerDirection::CREDIT, $advisorFeeRials),
                        new LedgerEntryData($platformRevenue, LedgerDirection::CREDIT, $platformFeeRials),
                    ],
                    description: "تسویه حق‌المشاوره جلسه {$session->tracking_code} ({$session->mode->value})",
                    postedAt: $endedAt
                );
            }

            $session->update([
                'status' => ConsultationSessionStatus::Completed,
                'duration_seconds' => $billing['duration_seconds'],
                'total_fee_rials' => $totalFeeRials,
                'ended_at' => $endedAt,
                'advisor_verdict' => $advisorVerdict ?? $session->advisor_verdict,
                'uploaded_docs_count' => max($uploadedDocsCount, $session->uploaded_docs_count),
            ]);

            // Increment advisor consultation count
            $session->advisor->increment('consultation_count');

            AuditLogger::record(
                action: AuditableAction::LEDGER_POSTED,
                subject: $session,
                changes: [
                    'session_id' => $session->id,
                    'status' => ConsultationSessionStatus::Completed->value,
                    'duration_seconds' => $billing['duration_seconds'],
                    'total_fee_rials' => $totalFeeRials,
                    'advisor_fee_rials' => $advisorFeeRials,
                    'platform_fee_rials' => $platformFeeRials,
                ],
                actorType: get_class($actor),
                actorId: (string) $actor->getAuthIdentifier()
            );

            return $session->fresh(['advisor', 'citizen', 'linkedService']);
        });
    }
}
