<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class OfferExpiredException extends HttpResponseException
{
    public function __construct(?string $detail = null, int $status = 409)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/offer-expired',
            'title' => ErrorCode::OFFER_EXPIRED->title(),
            'status' => $status,
            'code' => ErrorCode::OFFER_EXPIRED->value,
            'detail' => $detail ?? ErrorCode::OFFER_EXPIRED->defaultDetail(),
        ], $status);

        parent::__construct($response);
    }
}
