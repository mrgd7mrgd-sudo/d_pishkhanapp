<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentExpiredException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-expired',
            'title' => 'مهلت پرداخت به پایان رسیده است',
            'status' => 410,
            'code' => 'PAYMENT_EXPIRED',
            'detail' => $detail ?? 'مهلت مجاز برای پرداخت این تراکنش منقضی شده است. لطفاً مجدداً درخواست شارژ ثبت کنید.',
        ], 410);

        parent::__construct($response);
    }
}
