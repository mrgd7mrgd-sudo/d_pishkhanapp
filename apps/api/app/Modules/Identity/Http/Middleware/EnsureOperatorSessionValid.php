<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class EnsureOperatorSessionValid
{
    /**
     * Maximum idle inactivity time in seconds (30 minutes = 1800s) (§7.2).
     */
    private const MAX_IDLE_SECONDS = 1800;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::guard('operator')->check()) {
            $this->abortUnauthenticated($request);
        }

        $lastActivity = $request->session()->get('last_activity');
        /** @var int $now */
        $now = (int) CarbonImmutable::now()->timestamp;

        if ($lastActivity !== null) {
            /** @var int $lastActivitySeconds */
            $lastActivitySeconds = is_numeric($lastActivity) ? (int) $lastActivity : 0;
            $idleDuration = $now - $lastActivitySeconds;
            if ($idleDuration > self::MAX_IDLE_SECONDS) {
                Auth::guard('operator')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->abortSessionExpired($request);
            }
        }

        // Update last activity timestamp for active session
        $request->session()->put('last_activity', $now);

        return $next($request);
    }

    private function abortUnauthenticated(Request $request): never
    {
        $requestId = (string) $request->header('X-Request-Id', 'req_unknown');
        $instance = $request->path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-unauthenticated',
            'title' => 'احراز هویت اپراتور انجام نشده است',
            'status' => 401,
            'code' => 'AUTH_UNAUTHENTICATED',
            'detail' => 'نشست کاری اپراتور معتبر نیست یا منقضی شده است.',
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], 401);

        throw new HttpResponseException($response);
    }

    private function abortSessionExpired(Request $request): never
    {
        $requestId = (string) $request->header('X-Request-Id', 'req_unknown');
        $instance = $request->path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-session-expired',
            'title' => 'نشست کاری منقضی شده است',
            'status' => 401,
            'code' => 'AUTH_SESSION_EXPIRED',
            'detail' => 'به دلیل عدم فعالیت بیش از ۳۰ دقیقه، نشست کاری شما منقضی گردید.',
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], 401);

        throw new HttpResponseException($response);
    }
}
