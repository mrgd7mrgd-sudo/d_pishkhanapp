<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Jobs;

use App\Modules\Messaging\Infrastructure\Push\WebPushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send Web Push notifications (§8.3, TASK-073).
 * Queue: notifications (priority 2), tries: 5, exponential backoff.
 */
final class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 90, 300, 900];

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public readonly string $citizenId,
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $payload = null,
        string $queueName = 'notifications'
    ) {
        $this->onQueue($queueName);
    }

    public function handle(WebPushSender $pushSender): void
    {
        $success = $pushSender->send(
            $this->citizenId,
            $this->title,
            $this->body,
            $this->payload
        );

        if (! $success) {
            throw new \RuntimeException("Failed to deliver Web Push notification to citizen [{$this->citizenId}]");
        }
    }
}
