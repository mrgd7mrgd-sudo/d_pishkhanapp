<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutController
{
    /**
     * Revoke current access token and record audit event (§7.2, TASK-028).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if ($currentToken instanceof PersonalAccessToken) {
            $tokenId = $currentToken->id;
            $tokenName = $currentToken->name;

            // Delete current token
            $currentToken->delete();

            if ($user instanceof Model) {
                AuditLogger::record(
                    action: AuditableAction::AUTH_LOGOUT,
                    subject: $user,
                    changes: [
                        'token_id' => $tokenId,
                        'device_name' => $tokenName,
                    ]
                );
            }
        }

        return new JsonResponse([
            'message' => 'خروج با موفقیت انجام شد.',
        ], 200);
    }
}
