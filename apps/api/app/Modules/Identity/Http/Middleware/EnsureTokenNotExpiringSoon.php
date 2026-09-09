<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;

final class EnsureTokenNotExpiringSoon
{
    /**
     * If the authenticated Sanctum token has less than 24 hours remaining,
     * slide/extend its expires_at to 7 days from now (§7.2).
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $user = $request->user();
        if ($user === null) {
            return $response;
        }

        $currentToken = $user->currentAccessToken();
        if ($currentToken !== null && $currentToken->expires_at !== null) {
            $now = CarbonImmutable::now();
            $expiresAt = CarbonImmutable::instance($currentToken->expires_at);

            // If remaining lifetime is less than 24 hours (86400 seconds) and not yet expired
            if ($expiresAt->isFuture() && $now->diffInSeconds($expiresAt) < 86400) {
                $newExpiry = $now->addDays(7);
                $currentToken->forceFill(['expires_at' => $newExpiry])->save();
                $response->headers->set('X-Token-Refreshed', 'true');
                $response->headers->set('X-Token-Expires-At', $newExpiry->toIso8601String());
            }
        }

        return $response;
    }
}
