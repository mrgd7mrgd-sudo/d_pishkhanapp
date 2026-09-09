<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Errors\ErrorCode;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;

final class VerifyOtpAction
{
    /**
     * Default abilities issued on citizen PAT (§5.6 #2).
     */
    public const CITIZEN_ABILITIES = [
        'case:create',
        'case:read',
        'document:upload',
        'wallet:topup',
        'consultation:book',
    ];

    /**
     * Verify challenge code, enforce lockout and attempt limits, provision/fetch citizen,
     * issue Sanctum token (7 days TTL), and record audit events.
     *
     * @return array{
     *     token: string,
     *     token_type: string,
     *     expires_at: string,
     *     citizen: Citizen,
     *     abilities: list<string>
     * }
     */
    public function execute(
        string $challengeId,
        string $code,
        ?string $deviceName = null
    ): array {
        /** @var OtpChallenge|null $challenge */
        $challenge = OtpChallenge::query()->find($challengeId);

        if ($challenge === null) {
            $this->abortProblem(ErrorCode::AUTH_OTP_INVALID, 422);
        }

        // 1. Check if challenge or mobile is locked due to brute-force (> 5 attempts)
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

        // 3. Verify code
        $isValid = $challenge->verifyCode($code);

        if (! $isValid) {
            // Check if attempts reached maximum (5 attempts)
            if ($challenge->attempts >= 5) {
                // Lockout challenge and mobile for 15 minutes (900s)
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

        // 4. Code is valid -> Find or create Citizen record
        /** @var Citizen|null $citizen */
        $citizen = Citizen::query()->where('mobile_hash', $challenge->mobile_hash)->first();

        if ($citizen === null) {
            $citizen = new Citizen;
            // For new users without pre-populated profile, set placeholder full_name and mobile hash
            $citizen->full_name = 'شهروند گرامی';
            $citizen->mobile_hash = $challenge->mobile_hash;
            $citizen->tier = CitizenTier::BRONZE;
            $citizen->save();
        }

        // 5. Issue Sanctum Personal Access Token (7-day TTL, hashed in database)
        $tokenExpiresAt = CarbonImmutable::now()->addDays(7);
        $tokenName = $deviceName ?? 'Citizen PWA';

        // Check if this device is new for the user (§7.2, §7.6)
        $isExistingDevice = $citizen->tokens()->where('name', $tokenName)->exists();

        $tokenInstance = $citizen->createToken(
            name: $tokenName,
            abilities: self::CITIZEN_ABILITIES,
            expiresAt: $tokenExpiresAt
        );

        // 6. Record successful login and new device notification in immutable audit log (§7.6)
        AuditLogger::record(
            action: AuditableAction::AUTH_LOGIN_SUCCESS,
            subject: $citizen,
            changes: [
                'tier' => $citizen->tier->value,
                'token_id' => $tokenInstance->accessToken->id,
                'device_name' => $tokenName,
            ],
            context: [
                'challenge_id' => $challenge->id,
            ]
        );

        if (! $isExistingDevice) {
            AuditLogger::record(
                action: AuditableAction::AUTH_DEVICE_NEW,
                subject: $citizen,
                changes: [
                    'device_name' => $tokenName,
                    'token_id' => $tokenInstance->accessToken->id,
                ],
                context: [
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                ]
            );
        }

        return [
            'token' => $tokenInstance->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenExpiresAt->toIso8601String(),
            'citizen' => $citizen,
            'abilities' => self::CITIZEN_ABILITIES,
        ];
    }

    /**
     * Throw RFC 7807 problem details response for generic error codes.
     */
    private function abortProblem(ErrorCode $errorCode, int $status): never
    {
        $requestId = (string) Request::header('X-Request-Id', 'req_unknown');
        $instance = Request::path();

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

    /**
     * Throw RFC 7807 429 response with retry_after for brute-force lockout.
     */
    private function abortTooManyAttempts(int $retryAfter): never
    {
        $requestId = (string) Request::header('X-Request-Id', 'req_unknown');
        $instance = Request::path();

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
