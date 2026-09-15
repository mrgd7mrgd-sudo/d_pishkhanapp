<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentAmountMismatchException extends HttpResponseException
{
    public function __construct(int $expectedRials, int $actualRials)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-amount-mismatch',
            'title' => 'مغایرت بحرانی در مبلغ پرداخت',
            'status' => 422,
            'code' => 'PAYMENT_AMOUNT_MISMATCH',
            'detail' => 'مبلغ تاییدشده توسط درگاه بانکی با مبلغ ثبت‌شده برای این تراکنش تطابق ندارد.',
            'meta' => [
                'expected_rials' => $expectedRials,
                'actual_rials' => $actualRials,
            ],
        ], 422);

        parent::__construct($response);
    }
}
