<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class DeviceController
{
    /**
     * List all active sessions/devices for the authenticated user (§7.2, TASK-028).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            $this->abortUnauthorized();
        }

        $currentToken = $user->currentAccessToken();
        $currentTokenId = $currentToken instanceof PersonalAccessToken ? $currentToken->id : null;

        /** @var Collection<int, PersonalAccessToken> $tokens */
        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->get();

        $devices = $tokens->map(function (PersonalAccessToken $token) use ($currentTokenId): array {
            return [
                'id' => (string) $token->id,
                'name' => $token->name,
                'is_current' => $token->id === $currentTokenId,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ];
        });

        return new JsonResponse([
            'data' => $devices,
        ], 200);
    }

    /**
     * Revoke a specific device/token (§7.2, TASK-028).
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            $this->abortUnauthorized();
        }

        /** @var PersonalAccessToken|null $token */
        $token = $user->tokens()->where('id', $id)->first();

        if ($token === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/problems/resource-not-found',
                'title' => 'منبع مورد نظر یافت نشد',
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'دستگاه یا نشست مورد نظر یافت نشد.',
                'instance' => $request->path(),
                'request_id' => (string) $request->header('X-Request-Id', 'req_unknown'),
                'errors' => null,
            ], 404);
        }

        $tokenName = $token->name;
        $token->delete();

        AuditLogger::record(
            action: AuditableAction::AUTH_TOKEN_REVOKED,
            subject: $user,
            changes: [
                'token_id' => $id,
                'device_name' => $tokenName,
                'revoked_by' => 'user_action',
            ]
        );

        return new JsonResponse([
            'message' => 'نشست دستگاه با موفقیت خاتمه یافت.',
        ], 200);
    }

    private function abortUnauthorized(): never
    {
        throw new HttpResponseException(new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/unauthenticated',
            'title' => 'احراز هویت انجام نشده است',
            'status' => 401,
            'code' => 'AUTH_UNAUTHENTICATED',
            'detail' => 'برای دسترسی به این بخش، ابتدا باید وارد حساب کاربری خود شوید.',
            'instance' => \Illuminate\Support\Facades\Request::path(),
            'request_id' => (string) \Illuminate\Support\Facades\Request::header('X-Request-Id', 'req_unknown'),
            'errors' => null,
        ], 401));
    }
}
