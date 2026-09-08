<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use App\Shared\Errors\ErrorCode;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class MultiTierOtpRateLimiter
{
    /**
     * Handle multi-tier rate limiting for OTP requests (§5.6 #1, §7.5).
     *
     * Limits:
     * - Mobile: 3 requests / 15 minutes (900 seconds)
     * - Client IP: 30 requests / 15 minutes (900 seconds)
     * - Subnet /24 (IPv4 only): 300 requests / 15 minutes (900 seconds)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mobile = (string) $request->input('mobile', '');
        $ip = (string) ($request->ip() ?? '127.0.0.1');

        // 1. Mobile Rate Limit (3 per 15 minutes)
        if ($mobile !== '') {
            $mobileKey = 'rl:otp:mobile:'.sha1($mobile);
            if (RateLimiter::tooManyAttempts($mobileKey, 3)) {
                $seconds = RateLimiter::availableIn($mobileKey);

                return $this->buildRateLimitResponse($request, $seconds, 3, 0);
            }
        }

        // 2. IP Rate Limit (30 per 15 minutes)
        $ipKey = 'rl:otp:ip:'.sha1($ip);
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return $this->buildRateLimitResponse($request, $seconds, 30, 0);
        }

        // 3. Subnet /24 Rate Limit for IPv4 (300 per 15 minutes)
        $subnet = $this->extractIpv4Subnet($ip);
        if ($subnet !== null) {
            $subnetKey = 'rl:otp:subnet:'.$subnet;
            if (RateLimiter::tooManyAttempts($subnetKey, 300)) {
                $seconds = RateLimiter::availableIn($subnetKey);

                return $this->buildRateLimitResponse($request, $seconds, 300, 0);
            }
        }

        // Hit all limiters for 15 minutes (900 seconds)
        if ($mobile !== '') {
            RateLimiter::hit('rl:otp:mobile:'.sha1($mobile), 900);
        }
        RateLimiter::hit($ipKey, 900);
        if ($subnet !== null) {
            RateLimiter::hit('rl:otp:subnet:'.$subnet, 900);
        }

        /** @var Response $response */
        $response = $next($request);

        // Add standard rate limit headers
        $remainingMobile = $mobile !== '' ? RateLimiter::remaining('rl:otp:mobile:'.sha1($mobile), 3) : 3;
        $response->headers->set('X-RateLimit-Limit', '3');
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remainingMobile));
        $response->headers->set('X-RateLimit-Reset', (string) (time() + 900));

        return $response;
    }

    /**
     * Build RFC 7807 problem details 429 response (§5.6 #1, §7.5).
     */
    private function buildRateLimitResponse(
        Request $request,
        int $retryAfter,
        int $limit,
        int $remaining
    ): JsonResponse {
        $requestId = (string) $request->header(RequestId::HEADER_NAME, 'req_unknown');
        $instance = $request->path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-otp-too-many',
            'title' => ErrorCode::AUTH_OTP_TOO_MANY->title(),
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
            'detail' => 'برای این شماره تا ۱۵ دقیقه دیگر امکان ارسال مجدد نیست.',
            'instance' => $instance,
            'request_id' => $requestId,
            'retry_after' => $retryAfter,
            'errors' => null,
        ], 429);

        $response->headers->set('Retry-After', (string) $retryAfter);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) (time() + $retryAfter));

        return $response;
    }

    /**
     * Extract IPv4 /24 subnet (e.g., 192.168.1.55 -> 192.168.1.0/24).
     */
    private function extractIpv4Subnet(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0].'.'.$parts[1].'.'.$parts[2].'.0_24';
            }
        }

        return null;
    }
}
