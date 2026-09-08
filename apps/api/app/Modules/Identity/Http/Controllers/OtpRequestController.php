<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\SendOtpAction;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Http\Requests\SendOtpRequest;
use Illuminate\Http\JsonResponse;

final class OtpRequestController
{
    public function __construct(
        private readonly SendOtpAction $sendOtpAction
    ) {}

    /**
     * Request a one-time password challenge (§5.6 #1, TASK-026).
     */
    public function __invoke(SendOtpRequest $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $nationalId = $request->input('national_id') !== null ? (string) $request->input('national_id') : null;
        $purpose = OtpPurpose::from((string) $request->input('purpose'));
        $ip = $request->ip();

        $result = $this->sendOtpAction->execute(
            mobile: $mobile,
            nationalId: $nationalId,
            purpose: $purpose,
            ipAddress: $ip
        );

        return new JsonResponse([
            'data' => $result,
        ], 200);
    }
}
