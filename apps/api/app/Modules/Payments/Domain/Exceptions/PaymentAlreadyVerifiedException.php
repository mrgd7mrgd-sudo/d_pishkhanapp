<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentAlreadyVerifiedException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-already-verified',
            'title' => 'تراکنش قبلاً تأیید شده است',
            'status' => 409,
            'code' => 'PAYMENT_ALREADY_VERIFIED',
            'detail' => $detail ?? 'این تراکنش پرداخت قبلاً تأیید و به حساب کیف پول منظور گردیده است.',
        ], 409);

        parent::__construct($response);
    }
}
