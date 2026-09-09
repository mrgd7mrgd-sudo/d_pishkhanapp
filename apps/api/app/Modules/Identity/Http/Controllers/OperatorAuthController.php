<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\OperatorLoginAction;
use App\Modules\Identity\Application\Actions\VerifyOperatorOtpAction;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Http\Requests\OperatorLoginRequest;
use App\Modules\Identity\Http\Requests\VerifyOperatorOtpRequest;
use App\Modules\Identity\Http\Resources\OperatorResource;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class OperatorAuthController
{
    public function __construct(
        private readonly OperatorLoginAction $loginAction,
        private readonly VerifyOperatorOtpAction $verifyOtpAction
    ) {}

    /**
     * Operator Login Step 1: Validate Office Code + Username + Password, dispatch OTP.
     */
    public function login(OperatorLoginRequest $request): JsonResponse
    {
        $officeCode = (string) $request->input('office_code');
        $username = (string) $request->input('username');
        $password = (string) $request->input('password');
        $ipAddress = $request->ip();

        $result = $this->loginAction->execute(
            officeCode: $officeCode,
            username: $username,
            password: $password,
            ipAddress: $ipAddress
        );

        return new JsonResponse([
            'data' => $result,
        ], 200);
    }

    /**
     * Operator Login Step 2: Verify OTP, regenerate session, authenticate guard.
     */
    public function verifyOtp(VerifyOperatorOtpRequest $request): JsonResponse
    {
        $challengeId = (string) $request->input('challenge_id');
        $code = (string) $request->input('code');

        $result = $this->verifyOtpAction->execute(
            challengeId: $challengeId,
            code: $code,
            request: $request
        );

        return new JsonResponse([
            'data' => [
                'operator' => new OperatorResource($result['operator']),
                'session_id' => $result['session_id'],
            ],
        ], 200);
    }

    /**
     * Operator Logout: invalidate session, clear cookies, record audit log.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var Operator|null $operator */
        $operator = Auth::guard('operator')->user();

        if ($operator !== null) {
            AuditLogger::record(
                action: AuditableAction::AUTH_LOGOUT,
                subject: $operator,
                changes: ['operator_id' => $operator->id],
                context: [
                    'session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                ]
            );
        }

        Auth::guard('operator')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse([
            'data' => [
                'message' => 'خروج با موفقیت انجام شد.',
            ],
        ], 200);
    }

    /**
     * Get Current Authenticated Operator.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = Auth::guard('operator')->user();

        return new JsonResponse([
            'data' => new OperatorResource($operator),
        ], 200);
    }
}
