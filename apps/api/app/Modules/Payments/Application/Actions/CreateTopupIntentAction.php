<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Actions;

use App\Integration\Payment\PaymentGateway as PaymentGatewayPort;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\Exceptions\PaymentGatewayUnavailableException;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Shared\Money\Money;
use Carbon\CarbonImmutable;

final class CreateTopupIntentAction
{
    public function __construct(
        private readonly PaymentGatewayPort $paymentGateway,
    ) {}

    /**
     * @return array{intent: PaymentIntent, redirect_url: string}
     */
    public function execute(
        Citizen $citizen,
        int $amountRials,
        string $returnUrl,
        ?PaymentGateway $gateway = null
    ): array {
        $amount = Money::fromRials($amountRials);
        $description = 'شارژ کیف پول شهروند';

        $result = $this->paymentGateway->createIntent(
            amount: $amount,
            description: $description,
            callbackUrl: $returnUrl,
            meta: [
                'citizen_id' => $citizen->id,
                'gateway_requested' => $gateway?->value,
            ]
        );

        if (! $result->isSuccess || $result->authority === null || $result->paymentUrl === null) {
            throw new PaymentGatewayUnavailableException($result->errorMessage);
        }

        $recordedGateway = PaymentGateway::tryFrom($result->gatewayName)
            ?? ($gateway ?? PaymentGateway::ZARINPAL);

        $intent = PaymentIntent::create([
            'citizen_id' => $citizen->id,
            'amount_rials' => $amountRials,
            'gateway' => $recordedGateway,
            'authority' => $result->authority,
            'status' => PaymentIntentStatus::REDIRECTED,
            'expires_at' => CarbonImmutable::now()->addMinutes(15),
            'metadata' => [
                'return_url' => $returnUrl,
                'type' => 'topup',
            ],
        ]);

        return [
            'intent' => $intent,
            'redirect_url' => $result->paymentUrl,
        ];
    }
}
