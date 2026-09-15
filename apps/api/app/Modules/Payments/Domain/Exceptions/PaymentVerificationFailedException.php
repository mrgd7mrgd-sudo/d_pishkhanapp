<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class PaymentVerificationFailedException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/payment-verification-failed',
            'title' => 'تأیید تراکنش در درگاه بانکی ناموفق بود',
            'status' => 422,
            'code' => 'PAYMENT_VERIFICATION_FAILED',
            'detail' => $detail ?? 'درگاه پرداخت بانکی صحت این تراکنش را تأیید نکرد یا تراکنش لغو شده است.',
        ], 422);

        parent::__construct($response);
    }
}
