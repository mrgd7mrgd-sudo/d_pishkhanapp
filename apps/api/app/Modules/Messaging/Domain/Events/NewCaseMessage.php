<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Events;

use App\Modules\Messaging\Domain\Models\CaseMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time event when a new message is sent on a case (Architecture §5.7, TASK-072).
 * Broadcasts on private-case.{caseId} as 'message.new'.
 */
final class NewCaseMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CaseMessage $message
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("case.{$this->message->case_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /**
     * Payload strictly conforming to Architecture §5.7 (<4KB, zero PII).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'case_id' => $this->message->case_id,
            'sender_type' => $this->message->sender_type->value,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender_name,
            'body' => $this->message->body,
            'attachment_key' => $this->message->attachment_key,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
