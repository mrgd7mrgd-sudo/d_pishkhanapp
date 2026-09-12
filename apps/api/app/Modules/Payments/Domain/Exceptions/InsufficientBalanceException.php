<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class InsufficientBalanceException extends HttpResponseException
{
    public function __construct(
        public readonly int $requiredRials,
        public readonly int $availableRials,
        ?string $detail = null,
    ) {
        $shortfallRials = max(0, $requiredRials - $availableRials);
        $requiredToman = number_format((int) floor($requiredRials / 10));
        $availableToman = number_format((int) floor($availableRials / 10));

        $defaultDetail = "برای این خدمت {$requiredToman} تومان لازم است؛ موجودی شما {$availableToman} تومان است.";

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/wallet-insufficient-balance',
            'title' => ErrorCode::WALLET_INSUFFICIENT_BALANCE->title(),
            'status' => 402,
            'code' => ErrorCode::WALLET_INSUFFICIENT_BALANCE->value,
            'detail' => $detail ?? $defaultDetail,
            'meta' => [
                'required_rials' => $requiredRials,
                'available_rials' => $availableRials,
                'shortfall_rials' => $shortfallRials,
                'topup_url' => '/api/v1/wallet/topup',
            ],
        ], 402);

        parent::__construct($response);
    }
}
