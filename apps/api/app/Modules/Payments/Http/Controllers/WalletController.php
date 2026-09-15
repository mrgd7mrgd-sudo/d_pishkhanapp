<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Application\Actions\CreateTopupIntentAction;
use App\Modules\Payments\Application\Actions\VerifyTopupAction;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Modules\Payments\Http\Requests\TopupRequest;
use App\Modules\Payments\Http\Requests\VerifyTopupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class WalletController
{
    public function topup(TopupRequest $request, CreateTopupIntentAction $action): JsonResponse
    {
        Gate::authorize('create', PaymentIntent::class);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var array{amount_rials: int, return_url: string, gateway?: string|null} $validated */
        $validated = $request->validated();

        $gateway = isset($validated['gateway'])
            ? PaymentGateway::tryFrom($validated['gateway'])
            : null;

        $result = $action->execute(
            citizen: $citizen,
            amountRials: $validated['amount_rials'],
            returnUrl: $validated['return_url'],
            gateway: $gateway
        );

        return new JsonResponse([
            'data' => [
                'payment_intent_id' => $result['intent']->id,
                'redirect_url' => $result['redirect_url'],
                'authority' => $result['intent']->authority,
                'expires_at' => $result['intent']->expires_at->toIso8601String(),
                'amount_rials' => $result['intent']->amount_rials,
            ],
        ], 201);
    }

    public function verify(VerifyTopupRequest $request, VerifyTopupAction $action): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var array{authority: string, gateway?: string|null, status?: string|null} $validated */
        $validated = $request->validated();

        $gateway = isset($validated['gateway'])
            ? PaymentGateway::tryFrom($validated['gateway'])
            : null;

        $result = $action->execute(
            citizen: $citizen,
            authority: $validated['authority'],
            gateway: $gateway
        );

        return new JsonResponse([
            'data' => [
                'payment_intent_id' => $result['intent']->id,
                'status' => $result['intent']->status->value,
                'amount_rials' => $result['intent']->amount_rials,
                'ref_id' => $result['intent']->ref_id,
                'card_pan_masked' => $result['intent']->card_pan_masked,
                'verified_at' => $result['intent']->verified_at?->toIso8601String(),
                'wallet_balance_rials' => $result['wallet_balance_rials'],
            ],
        ], 200);
    }

    public function balance(Request $request, LedgerService $ledgerService): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $walletAccount = $ledgerService->getOrCreateAccount(
            ownerType: LedgerOwnerType::CITIZEN,
            ownerId: $citizen->id,
            kind: LedgerAccountKind::WALLET
        );

        $balanceRials = $ledgerService->getBalanceRials($walletAccount);
        $balanceToman = (int) floor($balanceRials / 10);

        return new JsonResponse([
            'data' => [
                'balance_rials' => $balanceRials,
                'balance_toman' => $balanceToman,
                'currency' => 'IRR',
            ],
        ]);
    }
}
