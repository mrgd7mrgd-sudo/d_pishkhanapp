<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Jobs;

use App\Integration\Sms\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send SMS notifications (§8.3, TASK-073).
 * Queue: notifications (priority 2), tries: 5, exponential backoff.
 */
final class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 90, 300, 900];

    /**
     * @param  array<string, string|int>  $params
     */
    public function __construct(
        public readonly string $mobile,
        public readonly string $template,
        public readonly array $params = [],
        string $queueName = 'notifications'
    ) {
        $this->onQueue($queueName);
    }

    public function handle(SmsGateway $smsGateway): void
    {
        $result = $smsGateway->sendTemplate(
            $this->mobile,
            $this->template,
            $this->params
        );

        if (! $result->isSuccess) {
            throw new \RuntimeException("Failed to send SMS template [{$this->template}]: {$result->errorMessage}");
        }
    }
}
