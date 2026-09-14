<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Application\Actions\GetNotificationPreferencesAction;
use App\Modules\Messaging\Application\Actions\UpdateNotificationPreferencesAction;
use App\Modules\Messaging\Http\Requests\UpdateNotificationPreferencesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NotificationPreferenceController extends Controller
{
    public function index(Request $request, GetNotificationPreferencesAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        $preferences = $action->execute($user);

        return new JsonResponse(['preferences' => $preferences], 200);
    }

    public function update(
        UpdateNotificationPreferencesRequest $request,
        UpdateNotificationPreferencesAction $action
    ): JsonResponse {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        /** @var list<array{notification_type: string, sms_enabled: bool, push_enabled: bool}> $preferencesInput */
        $preferencesInput = $request->input('preferences');

        $updated = $action->execute($user, $preferencesInput);

        return new JsonResponse(['preferences' => $updated], 200);
    }
}
