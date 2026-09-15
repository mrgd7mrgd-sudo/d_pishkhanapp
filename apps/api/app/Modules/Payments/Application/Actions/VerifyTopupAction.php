<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Actions;

use App\Integration\Payment\PaymentGateway as PaymentGatewayPort;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\Exceptions\PaymentAlreadyVerifiedException;
use App\Modules\Payments\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payments\Domain\Exceptions\PaymentExpiredException;
use App\Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use App\Modules\Payments\Domain\Exceptions\PaymentVerificationFailedException;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Shared\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class VerifyTopupAction
{
    public function __construct(
        private readonly PaymentGatewayPort $paymentGateway,
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * @return array{intent: PaymentIntent, wallet_balance_rials: int}
     */
    public function execute(
        Citizen $citizen,
        string $authority,
        ?PaymentGateway $gateway = null
    ): array {
        $query = PaymentIntent::query()->where('authority', $authority);

        if ($gateway !== null) {
            $query->where('gateway', $gateway->value);
        }

        /** @var PaymentIntent|null $intent */
        $intent = $query->first();

        if ($intent === null) {
            throw new PaymentNotFoundException;
        }

        if ($intent->citizen_id !== $citizen->id) {
            throw new AuthorizationException('شما مجاز به تأیید این تراکنش پرداخت نمی‌باشید.');
        }

        if ($intent->status === PaymentIntentStatus::PAID) {
            throw new PaymentAlreadyVerifiedException;
        }

        if ($intent->status === PaymentIntentStatus::EXPIRED || $intent->expires_at->isPast()) {
            if ($intent->status !== PaymentIntentStatus::EXPIRED) {
                $intent->update(['status' => PaymentIntentStatus::EXPIRED]);
            }
            throw new PaymentExpiredException;
        }

        $expectedMoney = Money::fromRials($intent->amount_rials);

        // Rule 2: Verify only via direct call to payment gateway (outside DB lock)
        $verifyResult = $this->paymentGateway->verify(
            authority: $intent->authority,
            expectedAmount: $expectedMoney
        );

        if (! $verifyResult->isSuccess) {
            $intent->update(['status' => PaymentIntentStatus::FAILED]);
            throw new PaymentVerificationFailedException($verifyResult->errorMessage);
        }

        // Rule 3: Strict amount comparison between gateway result and server intent
        $verifiedAmount = $verifyResult->amountRials;
        if ($verifiedAmount !== null && $verifiedAmount !== $intent->amount_rials) {
            $intent->update(['status' => PaymentIntentStatus::FAILED]);
            Log::critical('SECURITY ALERT: Payment amount mismatch detected!', [
                'intent_id' => $intent->id,
                'expected_rials' => $intent->amount_rials,
                'received_rials' => $verifiedAmount,
                'authority' => $intent->authority,
                'citizen_id' => $citizen->id,
            ]);
            throw new PaymentAmountMismatchException($intent->amount_rials, $verifiedAmount);
        }

        // Rule 4 & 5: Atomic state transition & double-entry ledger booking with lock
        return DB::transaction(function () use ($intent, $citizen, $verifyResult): array {
            /** @var PaymentIntent $lockedIntent */
            $lockedIntent = PaymentIntent::query()
                ->where('id', $intent->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedIntent->status === PaymentIntentStatus::PAID) {
                throw new PaymentAlreadyVerifiedException;
            }

            $lockedIntent->update([
                'status' => PaymentIntentStatus::PAID,
                'ref_id' => $verifyResult->refId,
                'card_pan_masked' => $verifyResult->cardPanMasked,
                'verified_at' => CarbonImmutable::now(),
            ]);

            $gatewayAccount = $this->ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::GATEWAY,
                ownerId: null,
                kind: LedgerAccountKind::CLEARING
            );

            $walletAccount = $this->ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::CITIZEN,
                ownerId: $citizen->id,
                kind: LedgerAccountKind::WALLET
            );

            $this->ledgerService->recordTransaction(
                reference: 'topup_'.$lockedIntent->id,
                type: LedgerTransactionType::TOPUP,
                entries: [
                    new LedgerEntryData($gatewayAccount, LedgerDirection::DEBIT, $lockedIntent->amount_rials),
                    new LedgerEntryData($walletAccount, LedgerDirection::CREDIT, $lockedIntent->amount_rials),
                ],
                description: 'شارژ کیف پول شهروند',
                paymentIntentId: $lockedIntent->id,
            );

            $newBalanceRials = $this->ledgerService->getBalanceRials($walletAccount);

            return [
                'intent' => $lockedIntent,
                'wallet_balance_rials' => $newBalanceRials,
            ];
        });
    }
}
