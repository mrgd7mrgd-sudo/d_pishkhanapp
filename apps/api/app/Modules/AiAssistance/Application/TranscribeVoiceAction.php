<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Application;

use App\Integration\Ai\AiProvider;
use App\Integration\Ai\AudioFile;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class TranscribeVoiceAction
{
    public const MAX_SIZE_BYTES = 1048576; // 1MB (§8.1.5)

    public const MAX_DURATION_SECONDS = 30; // 30s (§8.1.5)

    public function __construct(
        private readonly AiProvider $aiProvider,
        private readonly AnswerGenerator $answerGenerator
    ) {}

    /**
     * Transcribes Persian voice input and feeds into RAG pipeline (§8.1.5, TASK-114).
     *
     * @param  array<string, mixed>  $context
     * @return array{
     *     transcript: string,
     *     reply: string,
     *     suggested_actions: list<array{type: string, label: string, payload: array<string, mixed>}>,
     *     citations: list<array{type: string, id: string}>,
     *     usage: array{model: string, input_tokens: int, output_tokens: int, cost_rials: int}
     * }
     */
    public function execute(
        UploadedFile|string $audio,
        array $context = [],
        ?Citizen $citizen = null,
        ?AiConversation $conversation = null,
        int $durationSeconds = 0
    ): array {
        if ($durationSeconds > self::MAX_DURATION_SECONDS) {
            throw ValidationException::withMessages([
                'duration' => ['مدت زمان فایل صوتی نباید بیش از ۳۰ ثانیه باشد.'],
            ]);
        }

        $tempPath = null;
        try {
            if ($audio instanceof UploadedFile) {
                if ($audio->getSize() > self::MAX_SIZE_BYTES) {
                    throw ValidationException::withMessages([
                        'audio' => ['حجم فایل صوتی نباید بیش از ۱ مگابایت باشد.'],
                    ]);
                }

                $content = $audio->get();
                $mimeType = $audio->getMimeType() ?: 'audio/webm';
            } else {
                $tempPath = $audio;
                if (file_exists($tempPath) && filesize($tempPath) > self::MAX_SIZE_BYTES) {
                    throw ValidationException::withMessages([
                        'audio' => ['حجم فایل صوتی نباید بیش از ۱ مگابایت باشد.'],
                    ]);
                }

                $content = file_get_contents($audio);
                $mimeType = 'audio/webm';
            }

            $audioFile = new AudioFile(
                content: $content,
                mimeType: $mimeType,
                durationSeconds: $durationSeconds
            );

            // Step 1: Transcribe via Whisper large v3 port
            $transcriptionResult = $this->aiProvider->transcribe($audioFile, 'fa');
            $transcript = trim($transcriptionResult->text);

            if ($transcript === '') {
                $transcript = 'صوت ارسالی نامفهوم بود.';
            }

            // Step 2: Pass into RAG pipeline with PII redaction and restoration
            $answer = $this->answerGenerator->generate(
                message: $transcript,
                context: $context,
                citizen: $citizen,
                conversation: $conversation
            );

            return [
                'transcript' => $transcript,
                'reply' => $answer['reply'],
                'suggested_actions' => $answer['suggested_actions'],
                'citations' => $answer['citations'],
                'usage' => $answer['usage'],
            ];
        } finally {
            // Immediate deletion: audio files MUST NOT linger on disk (§8.1.5)
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
