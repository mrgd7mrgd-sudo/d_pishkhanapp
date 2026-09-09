<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\VerifyOtpAction;
use App\Modules\Identity\Http\Requests\VerifyOtpRequest;
use App\Modules\Identity\Http\Resources\CitizenResource;
use Illuminate\Http\JsonResponse;

final class OtpVerifyController
{
    public function __construct(
        private readonly VerifyOtpAction $verifyOtpAction
    ) {}

    /**
     * Verify OTP and return bearer authentication token (§5.6 #2, TASK-027).
     */
    public function __invoke(VerifyOtpRequest $request): JsonResponse
    {
        $challengeId = (string) $request->input('challenge_id');
        $code = (string) $request->input('code');
        $deviceName = $request->input('device_name') !== null ? (string) $request->input('device_name') : null;

        $result = $this->verifyOtpAction->execute(
            challengeId: $challengeId,
            code: $code,
            deviceName: $deviceName
        );

        return new JsonResponse([
            'data' => [
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'citizen' => new CitizenResource($result['citizen']),
                'abilities' => $result['abilities'],
            ],
        ], 200);
    }
}
