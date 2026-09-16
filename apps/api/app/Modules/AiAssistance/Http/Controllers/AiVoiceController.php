<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Http\Controllers;

use App\Modules\AiAssistance\Application\TranscribeVoiceAction;
use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Infrastructure\AiBudgetGuard;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

final class AiVoiceController extends Controller
{
    public function __construct(
        private readonly TranscribeVoiceAction $transcribeAction,
        private readonly AiBudgetGuard $budgetGuard
    ) {}

    /**
     * POST /api/v1/ai/voice (§8.1.5, TASK-114)
     */
    public function transcribe(Request $request): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        // 1. Rate limiting
        if (! $this->budgetGuard->checkRateLimit((string) $citizen->id)) {
            return response()->json([
                'message' => 'شما به سقف مجاز پیام‌های هوش مصنوعی (۳۰ پیام در ساعت یا ۲۰۰ پیام در روز) رسیده‌اید. لطفاً ساعتی دیگر مراجعه فرمایید.',
                'code' => 'AI_RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        // 2. Validate multipart request: max 1MB (1024 KB), max 30s
        $request->validate([
            'audio' => ['required', 'file', 'max:1024'],
            'duration_seconds' => ['nullable', 'integer', 'max:30'],
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'context' => ['nullable', 'array'],
        ], [
            'audio.max' => 'حجم فایل صوتی نباید بیش از ۱ مگابایت باشد.',
            'duration_seconds.max' => 'مدت زمان صوت نباید بیش از ۳۰ ثانیه باشد.',
        ]);

        $durationSeconds = (int) $request->input('duration_seconds', 0);
        if ($durationSeconds > 30) {
            throw ValidationException::withMessages([
                'duration_seconds' => ['مدت زمان صوت نباید بیش از ۳۰ ثانیه باشد.'],
            ]);
        }

        $this->budgetGuard->incrementCitizenUsage((string) $citizen->id);

        $conversation = $this->resolveConversation($citizen, $request->input('conversation_id'));

        /** @var UploadedFile $audioFile */
        $audioFile = $request->file('audio');
        $context = (array) ($request->input('context') ?? []);

        $result = $this->transcribeAction->execute(
            audio: $audioFile,
            context: $context,
            citizen: $citizen,
            conversation: $conversation,
            durationSeconds: $durationSeconds
        );

        return response()->json([
            'data' => array_merge($result, [
                'conversation_id' => (string) $conversation->id,
            ]),
        ]);
    }

    private function resolveConversation(Citizen $citizen, ?string $conversationId): AiConversation
    {
        if ($conversationId !== null && $conversationId !== '') {
            return AiConversation::query()
                ->where('id', $conversationId)
                ->where('citizen_id', $citizen->id)
                ->firstOrFail();
        }

        return AiConversation::query()->create([
            'citizen_id' => $citizen->id,
            'channel' => AiChannel::Voice,
            'title' => 'دستیار صوتی',
        ]);
    }
}
