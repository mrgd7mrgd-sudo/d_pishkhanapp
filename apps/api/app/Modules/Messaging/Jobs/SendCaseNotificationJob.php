<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Jobs;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Models\Notification;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Dispatches multi-channel case notifications respecting citizen preferences (§8.3, §5.8, TASK-073).
 * Queue: notifications (priority 2).
 */
final class SendCaseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 90, 300, 900];

    /**
     * @param  array<string, string|int>  $smsParams
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public readonly string $citizenId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $smsTemplate = null,
        public readonly array $smsParams = [],
        public readonly ?array $payload = null,
        string $queueName = 'notifications'
    ) {
        $this->onQueue($queueName);
    }

    public function handle(): void
    {
        // 1. Create in-app Notification record
        Notification::create([
            'id' => (string) Str::uuid(),
            'citizen_id' => $this->citizenId,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload,
            'read_at' => null,
        ]);

        // 2. Check user Notification Preferences
        /** @var NotificationPreference|null $preference */
        $preference = NotificationPreference::query()
            ->where('citizen_id', $this->citizenId)
            ->where('notification_type', $this->type)
            ->first();

        $smsEnabled = $preference ? $preference->sms_enabled : true;
        $pushEnabled = $preference ? $preference->push_enabled : true;

        // 3. Dispatch Web Push if enabled
        if ($pushEnabled) {
            SendPushJob::dispatch(
                $this->citizenId,
                $this->title,
                $this->body,
                $this->payload
            );
        }

        // 4. Dispatch SMS if enabled and template is provided
        if ($smsEnabled && $this->smsTemplate !== null) {
            /** @var Citizen|null $citizen */
            $citizen = Citizen::query()->find($this->citizenId);
            $mobile = $citizen?->mobile;

            if ($mobile) {
                SendSmsJob::dispatch(
                    $mobile,
                    $this->smsTemplate,
                    $this->smsParams
                );
            }
        }
    }
}
