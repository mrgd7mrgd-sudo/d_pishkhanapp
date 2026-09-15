<?php

declare(strict_types=1);

namespace App\Modules\Payments\Jobs;

use App\Integration\Payment\PaymentGateway as PaymentGatewayPort;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Shared\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reconciliation job for abandoned payment intents (Architecture §5.9, §8.2, TASK-087).
 * Scans redirected payment intents older than 10 minutes and reconciles them with the gateway.
 */
final class ReconcilePaymentIntentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly ?int $olderThanMinutes = 10,
    ) {
        $this->onQueue('ledger');
    }

    /**
     * @return array{reconciled: int, failed: int, skipped: int}
     */
    public function handle(
        PaymentGatewayPort $paymentGateway,
        LedgerService $ledgerService
    ): array {
        $minutes = $this->olderThanMinutes ?? 10;
        $threshold = Carbon::now()->subMinutes($minutes);

        /** @var Collection<int, PaymentIntent> $intents */
        $intents = PaymentIntent::query()
            ->pendingReconciliation($threshold)
            ->get();

        $stats = [
            'reconciled' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($intents as $intent) {
            try {
                $expectedMoney = Money::fromRials($intent->amount_rials);

                $verifyResult = $paymentGateway->verify(
                    authority: $intent->authority,
                    expectedAmount: $expectedMoney
                );

                if ($verifyResult->isSuccess) {
                    $reconciled = DB::transaction(function () use ($intent, $verifyResult, $ledgerService): bool {
                        /** @var PaymentIntent $lockedIntent */
                        $lockedIntent = PaymentIntent::query()
                            ->where('id', $intent->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lockedIntent->status === PaymentIntentStatus::PAID) {
                            return false;
                        }

                        $lockedIntent->update([
                            'status' => PaymentIntentStatus::PAID,
                            'ref_id' => $verifyResult->refId,
                            'card_pan_masked' => $verifyResult->cardPanMasked,
                            'verified_at' => CarbonImmutable::now(),
                        ]);

                        $gatewayAccount = $ledgerService->getOrCreateAccount(
                            ownerType: LedgerOwnerType::GATEWAY,
                            ownerId: null,
                            kind: LedgerAccountKind::CLEARING
                        );

                        $walletAccount = $ledgerService->getOrCreateAccount(
                            ownerType: LedgerOwnerType::CITIZEN,
                            ownerId: $lockedIntent->citizen_id,
                            kind: LedgerAccountKind::WALLET
                        );

                        $ledgerService->recordTransaction(
                            reference: 'reconcile_'.$lockedIntent->id,
                            type: LedgerTransactionType::TOPUP,
                            entries: [
                                new LedgerEntryData($gatewayAccount, LedgerDirection::DEBIT, $lockedIntent->amount_rials),
                                new LedgerEntryData($walletAccount, LedgerDirection::CREDIT, $lockedIntent->amount_rials),
                            ],
                            description: 'مغایرت‌گیری و شارژ خودکار کیف پول شهروند',
                            paymentIntentId: $lockedIntent->id,
                        );

                        Log::info('Successfully reconciled abandoned payment intent.', [
                            'intent_id' => $lockedIntent->id,
                            'citizen_id' => $lockedIntent->citizen_id,
                            'amount_rials' => $lockedIntent->amount_rials,
                            'ref_id' => $verifyResult->refId,
                        ]);

                        return true;
                    });

                    if ($reconciled) {
                        $stats['reconciled']++;
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    // Gateway confirmed payment was not completed or failed
                    $intent->update(['status' => PaymentIntentStatus::FAILED]);
                    $stats['failed']++;
                }
            } catch (Throwable $e) {
                Log::error('Error reconciling payment intent: '.$intent->id, [
                    'error' => $e->getMessage(),
                ]);
                $stats['skipped']++;
            }
        }

        return $stats;
    }
}
