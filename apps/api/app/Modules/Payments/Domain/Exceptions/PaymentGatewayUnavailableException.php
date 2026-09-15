<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentGatewayUnavailableException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-gateway-unavailable',
            'title' => ErrorCode::PAYMENT_GATEWAY_UNAVAILABLE->title(),
            'status' => 503,
            'code' => ErrorCode::PAYMENT_GATEWAY_UNAVAILABLE->value,
            'detail' => $detail ?? ErrorCode::PAYMENT_GATEWAY_UNAVAILABLE->defaultDetail(),
        ], 503);

        parent::__construct($response);
    }
}
