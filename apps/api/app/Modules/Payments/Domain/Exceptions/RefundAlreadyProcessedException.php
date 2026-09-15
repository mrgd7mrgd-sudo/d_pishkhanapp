<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class RefundAlreadyProcessedException extends HttpResponseException
{
    public function __construct(string $caseId)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/refund-already-processed',
            'title' => 'استرداد وجه قبلاً انجام شده است',
            'status' => 409,
            'code' => 'REFUND_ALREADY_PROCESSED',
            'detail' => 'وجه این پرونده پیش‌تر استرداد شده و امکان پردازش مجدد وجود ندارد.',
            'meta' => [
                'case_id' => $caseId,
            ],
        ], 409);

        parent::__construct($response);
    }
}
