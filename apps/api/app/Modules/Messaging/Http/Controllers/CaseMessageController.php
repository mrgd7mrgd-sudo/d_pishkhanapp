<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Application\Actions\GetCaseMessagesAction;
use App\Modules\Messaging\Application\Actions\SendCaseMessageAction;
use App\Modules\Messaging\Http\Requests\SendCaseMessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CaseMessageController extends Controller
{
    public function index(Request $request, string $id, GetCaseMessagesAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        $result = $action->execute($user, $id);

        return new JsonResponse($result, 200);
    }

    public function store(SendCaseMessageRequest $request, string $id, SendCaseMessageAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['status' => 401, 'detail' => 'Unauthenticated.'], 401);
        }

        /** @var string $body */
        $body = $request->input('body');
        /** @var string|null $attachmentKey */
        $attachmentKey = $request->input('attachment_key');

        $message = $action->execute($user, $id, $body, $attachmentKey);

        return new JsonResponse([
            'id' => $message->id,
            'case_id' => $message->case_id,
            'sender_type' => $message->sender_type->value,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender_name,
            'body' => $message->body,
            'attachment_key' => $message->attachment_key,
            'read_at' => $message->read_at?->toISOString(),
            'created_at' => $message->created_at->toISOString(),
        ], 201);
    }
}
