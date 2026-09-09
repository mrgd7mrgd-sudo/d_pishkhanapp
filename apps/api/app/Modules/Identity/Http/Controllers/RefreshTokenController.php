<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\VerifyOtpAction;
use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefreshTokenController
{
    /**
     * Issue a short-lived PAT (15-minute TTL) for Service Worker Background Sync (§7.2, TASK-028).
     * Accepts request from authenticated citizen (via Bearer token or HttpOnly cookie).
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Citizen|null $user */
        $user = $request->user();

        if ($user === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/problems/unauthenticated',
                'title' => 'احراز هویت انجام نشده است',
                'status' => 401,
                'code' => 'AUTH_UNAUTHENTICATED',
                'detail' => 'برای دریافت توکن تازه، ابتدا باید وارد حساب کاربری خود شوید.',
                'instance' => $request->path(),
                'request_id' => (string) $request->header('X-Request-Id', 'req_unknown'),
                'errors' => null,
            ], 401);
        }

        $shortLivedExpiresAt = CarbonImmutable::now()->addMinutes(15);
        $token = $user->createToken(
            name: 'Service Worker Background Sync',
            abilities: VerifyOtpAction::CITIZEN_ABILITIES,
            expiresAt: $shortLivedExpiresAt
        );

        return new JsonResponse([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $shortLivedExpiresAt->toIso8601String(),
                'ttl_seconds' => 900,
            ],
        ], 200);
    }
}
