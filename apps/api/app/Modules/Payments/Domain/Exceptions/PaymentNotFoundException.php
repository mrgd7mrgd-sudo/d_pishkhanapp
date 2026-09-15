<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentNotFoundException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-not-found',
            'title' => 'تراکنش پرداخت یافت نشد',
            'status' => 404,
            'code' => 'PAYMENT_NOT_FOUND',
            'detail' => $detail ?? 'شناسه پیگیری پرداخت ارائه شده در سامانه وجود ندارد.',
        ], 404);

        parent::__construct($response);
    }
}
