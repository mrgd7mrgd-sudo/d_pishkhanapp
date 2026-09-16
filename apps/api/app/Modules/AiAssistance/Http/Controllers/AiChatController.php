<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Http\Controllers;

use App\Modules\AiAssistance\Application\AnswerGenerator;
use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\AiAssistance\Http\Requests\AiChatRequest;
use App\Modules\AiAssistance\Http\Resources\AiConversationResource;
use App\Modules\AiAssistance\Http\Resources\AiReplyResource;
use App\Modules\AiAssistance\Infrastructure\AiBudgetGuard;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AiChatController extends Controller
{
    public function __construct(
        private readonly AnswerGenerator $answerGenerator,
        private readonly AiBudgetGuard $budgetGuard
    ) {}

    /**
     * POST /api/v1/ai/chat (§5.6 #10, §7.5, TASK-112, TASK-113)
     */
    public function chat(AiChatRequest $request): JsonResponse|StreamedResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        // 30 msg/hour and 200 msg/day per citizen (§7.5, TASK-113)
        if (! $this->budgetGuard->checkRateLimit((string) $citizen->id)) {
            return response()->json([
                'message' => 'شما به سقف مجاز پیام‌های هوش مصنوعی (۳۰ پیام در ساعت یا ۲۰۰ پیام در روز) رسیده‌اید. لطفاً ساعتی دیگر مراجعه فرمایید.',
                'code' => 'AI_RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        $this->budgetGuard->incrementCitizenUsage((string) $citizen->id);

        $conversation = $this->resolveConversation($citizen, $request->input('conversation_id'), $request->input('message'));

        $message = (string) $request->input('message');
        $context = (array) ($request->input('context') ?? []);

        // SSE Streaming Support
        if (str_contains((string) $request->header('Accept'), 'text/event-stream')) {
            return $this->streamSseResponse($message, $context, $citizen, $conversation);
        }

        // Standard JSON Response
        $result = $this->answerGenerator->generate($message, $context, $citizen, $conversation);

        $assistantMessage = AiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', AiMessageRole::Assistant)
            ->latest('created_at')
            ->first();

        $data = array_merge($result, [
            'conversation_id' => (string) $conversation->id,
            'message_id' => $assistantMessage ? (string) $assistantMessage->id : 'msg_' . bin2hex(random_bytes(8)),
        ]);

        return (new AiReplyResource($data))->response();
    }

    /**
     * GET /api/v1/ai/conversations
     */
    public function indexConversations(Request $request): AnonymousResourceCollection
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $conversations = AiConversation::query()
            ->where('citizen_id', $citizen->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return AiConversationResource::collection($conversations);
    }

    /**
     * GET /api/v1/ai/conversations/{id}
     */
    public function showConversation(Request $request, string $id): AiConversationResource
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $conversation = AiConversation::query()
            ->where('id', $id)
            ->where('citizen_id', $citizen->id)
            ->with(['messages'])
            ->firstOrFail();

        return new AiConversationResource($conversation);
    }

    private function resolveConversation(Citizen $citizen, ?string $conversationId, string $firstMessage): AiConversation
    {
        if ($conversationId !== null && $conversationId !== '') {
            return AiConversation::query()
                ->where('id', $conversationId)
                ->where('citizen_id', $citizen->id)
                ->firstOrFail();
        }

        $title = mb_substr(trim($firstMessage), 0, 40);
        if ($title === '') {
            $title = 'گفتگو جدید';
        }

        return AiConversation::query()->create([
            'citizen_id' => $citizen->id,
            'channel' => AiChannel::Chat,
            'title' => $title,
        ]);
    }

    private function streamSseResponse(
        string $message,
        array $context,
        Citizen $citizen,
        AiConversation $conversation
    ): StreamedResponse {
        return new StreamedResponse(function () use ($message, $context, $citizen, $conversation): void {
            $result = $this->answerGenerator->generate($message, $context, $citizen, $conversation);

            $meta = [
                'conversation_id' => (string) $conversation->id,
                'intent' => $result['intent'],
                'confidence' => $result['confidence'],
            ];
            echo "event: meta\ndata: " . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            // Stream words in chunks
            $words = preg_split('/(\s+)/u', $result['reply'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$result['reply']];
            foreach ($words as $w) {
                if ($w === '') {
                    continue;
                }
                echo "event: token\ndata: " . json_encode(['chunk' => $w], JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            $assistantMessage = AiMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('role', AiMessageRole::Assistant)
                ->latest('created_at')
                ->first();

            $donePayload = [
                'conversation_id' => (string) $conversation->id,
                'message_id' => $assistantMessage ? (string) $assistantMessage->id : 'msg_' . bin2hex(random_bytes(8)),
                'reply' => $result['reply'],
                'citations' => $result['citations'],
                'suggested_actions' => $result['suggested_actions'],
                'usage' => $result['usage'],
            ];
            echo "event: done\ndata: " . json_encode($donePayload, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
