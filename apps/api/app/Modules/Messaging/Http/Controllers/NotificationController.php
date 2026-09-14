<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Application\Actions\GetNotificationsAction;
use App\Modules\Messaging\Application\Actions\MarkNotificationReadAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NotificationController extends Controller
{
    public function index(Request $request, GetNotificationsAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        $unreadOnly = $request->boolean('unread_only');
        $limit = (int) $request->input('limit', 20);

        $result = $action->execute($user, $unreadOnly, $limit);

        return new JsonResponse($result, 200);
    }

    public function markAsRead(Request $request, string $id, MarkNotificationReadAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        $notification = $action->execute($user, $id);

        return new JsonResponse([
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'payload' => $notification->payload,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at->toISOString(),
        ], 200);
    }
}
