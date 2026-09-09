<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Errors\ErrorCode;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request as RequestFacade;

final class VerifyOperatorOtpAction
{
    /**
     * Verify operator OTP code, regenerate session, authenticate guard, and update last_login_at.
     *
     * @return array{
     *     operator: Operator,
     *     session_id: string
     * }
     */
    public function execute(
        string $challengeId,
        string $code,
        Request $request
    ): array {
        /** @var OtpChallenge|null $challenge */
        $challenge = OtpChallenge::query()->find($challengeId);

        if ($challenge === null) {
            $this->abortProblem(ErrorCode::AUTH_OTP_INVALID, 422);
        }

        // 1. Check brute-force lockout
        $lockoutKey = 'lockout:otp:challenge:'.$challenge->id;
        $mobileLockoutKey = 'lockout:otp:mobile:'.$challenge->mobile_hash;

        if (RateLimiter::tooManyAttempts($lockoutKey, 1) || RateLimiter::tooManyAttempts($mobileLockoutKey, 1)) {
            $seconds = max(
                RateLimiter::availableIn($lockoutKey),
                RateLimiter::availableIn($mobileLockoutKey)
            );

            $this->abortTooManyAttempts($seconds);
        }

        // 2. Check expiration (120s TTL)
        if ($challenge->isExpired()) {
            AuditLogger::record(
                action: AuditableAction::AUTH_LOGIN_FAILED,
                subject: $challenge,
                changes: ['reason' => 'expired_challenge'],
                context: ['challenge_id' => $challenge->id]
            );

            $this->abortProblem(ErrorCode::AUTH_OTP_EXPIRED, 410);
        }

        // 3. Verify OTP code
        $isValid = $challenge->verifyCode($code);

        if (! $isValid) {
            if ($challenge->attempts >= 5) {
                RateLimiter::hit($lockoutKey, 900);
                RateLimiter::hit($mobileLockoutKey, 900);

                AuditLogger::record(
                    action: AuditableAction::AUTH_LOGIN_FAILED,
                    subject: $challenge,
                    changes: ['reason' => 'brute_force_lockout', 'attempts' => $challenge->attempts],
                    context: ['challenge_id' => $challenge->id]
                );

                $this->abortTooManyAttempts(900);
            }

            AuditLogger::record(
                action: AuditableAction::AUTH_LOGIN_FAILED,
                subject: $challenge,
                changes: ['reason' => 'invalid_code', 'attempt_number' => $challenge->attempts],
                context: ['challenge_id' => $challenge->id]
            );

            $this->abortProblem(ErrorCode::AUTH_OTP_INVALID, 422);
        }

        // 4. Locate operator by mobile hash
        /** @var Operator|null $operator */
        $operator = Operator::query()->where('mobile_hash', $challenge->mobile_hash)->first();

        if ($operator === null || ! $operator->is_active) {
            $this->abortProblem(ErrorCode::AUTH_OTP_INVALID, 422);
        }

        // 5. Session Fixation Prevention & Login
        $request->session()->regenerate();
        Auth::guard('operator')->login($operator);

        // Record session metadata
        $request->session()->put('operator_id', $operator->id);
        $request->session()->put('last_activity', CarbonImmutable::now()->timestamp);

        // 6. Update operator last_login_at
        $operator->update([
            'last_login_at' => CarbonImmutable::now(),
        ]);

        // 7. Audit Log
        AuditLogger::record(
            action: AuditableAction::AUTH_LOGIN_SUCCESS,
            subject: $operator,
            changes: [
                'role' => $operator->role->value,
                'office_id' => $operator->office_id,
                'counter_number' => $operator->counter_number,
            ],
            context: [
                'challenge_id' => $challenge->id,
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
            ]
        );

        return [
            'operator' => $operator,
            'session_id' => $request->session()->getId(),
        ];
    }

    private function abortProblem(ErrorCode $errorCode, int $status): never
    {
        $requestId = (string) RequestFacade::header('X-Request-Id', 'req_unknown');
        $instance = RequestFacade::path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/'.strtolower(str_replace('_', '-', $errorCode->value)),
            'title' => $errorCode->title(),
            'status' => $status,
            'code' => $errorCode->value,
            'detail' => $errorCode->defaultDetail(),
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], $status);

        throw new HttpResponseException($response);
    }

    private function abortTooManyAttempts(int $retryAfter): never
    {
        $requestId = (string) RequestFacade::header('X-Request-Id', 'req_unknown');
        $instance = RequestFacade::path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-otp-too-many',
            'title' => ErrorCode::AUTH_OTP_TOO_MANY->title(),
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
            'detail' => 'به دلیل تلاش‌های ناموفق مکرر، حساب به مدت ۱۵ دقیقه مسدود گردید.',
            'instance' => $instance,
            'request_id' => $requestId,
            'retry_after' => $retryAfter,
            'errors' => null,
        ], 429);

        $response->headers->set('Retry-After', (string) $retryAfter);
        $response->headers->set('X-RateLimit-Limit', '5');
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) (time() + $retryAfter));

        throw new HttpResponseException($response);
    }
}
