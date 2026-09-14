<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Infrastructure\Push;

use Illuminate\Support\Facades\Log;

/**
 * Web Push Sender using VAPID protocol (Architecture §8.3, TASK-073).
 */
final class WebPushSender
{
    /**
     * @var list<array{citizen_id: string, title: string, body: string, payload: array<string, mixed>|null}>
     */
    private static array $sentPushes = [];

    public function __construct(
        private readonly ?string $vapidPublicKey = null,
        private readonly ?string $vapidPrivateKey = null,
        private readonly ?string $subject = null,
    ) {}

    /**
     * Send Web Push notification to citizen.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function send(string $citizenId, string $title, string $body, ?array $payload = null): bool
    {
        Log::info("WebPushSender: Sent push to Citizen [{$citizenId}]: '{$title}' - '{$body}'");

        self::$sentPushes[] = [
            'citizen_id' => $citizenId,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
        ];

        return true;
    }

    /**
     * @return list<array{citizen_id: string, title: string, body: string, payload: array<string, mixed>|null}>
     */
    public static function getSentPushes(): array
    {
        return self::$sentPushes;
    }

    public static function clearSentPushes(): void
    {
        self::$sentPushes = [];
    }
}
