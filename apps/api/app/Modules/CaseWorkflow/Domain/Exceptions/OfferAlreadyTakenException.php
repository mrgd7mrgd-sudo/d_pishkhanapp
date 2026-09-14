<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class OfferAlreadyTakenException extends HttpResponseException
{
    public function __construct(?string $detail = null)
    {
        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/offer-already-taken',
            'title' => ErrorCode::OFFER_ALREADY_TAKEN->title(),
            'status' => 409,
            'code' => ErrorCode::OFFER_ALREADY_TAKEN->value,
            'detail' => $detail ?? ErrorCode::OFFER_ALREADY_TAKEN->defaultDetail(),
        ], 409);

        parent::__construct($response);
    }
}
