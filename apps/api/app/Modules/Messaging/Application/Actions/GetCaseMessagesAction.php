<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Domain\Enums\MessageSenderType;
use App\Modules\Messaging\Domain\Models\CaseMessage;
use App\Modules\Messaging\Infrastructure\Policies\CaseMessagePolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class GetCaseMessagesAction
{
    public function __construct(
        private readonly CaseMessagePolicy $policy
    ) {}

    /**
     * @return array{case_id: string, unread_count: int, items: list<array<string, mixed>>}
     */
    public function execute(Authenticatable $user, string $caseId): array
    {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->where('id', $caseId)->first();

        if (! $case || ! $this->policy->canAccessCase($user, $case)) {
            // Horizontal Isolation §7.3: Always 404, never 403
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Case not found.',
            ], 404));
        }

        // Calculate unread count for current viewer before marking read
        $unreadCount = $this->calculateUnreadCount($user, $case->id);

        // Mark unread messages sent by the counter-party as read
        $this->markMessagesAsRead($user, $case->id);

        /** @var Collection<int, CaseMessage> $messages */
        $messages = CaseMessage::query()
            ->where('case_id', $case->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $items = $messages->map(fn (CaseMessage $msg): array => [
            'id' => $msg->id,
            'case_id' => $msg->case_id,
            'sender_type' => $msg->sender_type->value,
            'sender_id' => $msg->sender_id,
            'sender_name' => $msg->sender_name,
            'body' => $msg->body,
            'attachment_key' => $msg->attachment_key,
            'read_at' => $msg->read_at?->toISOString(),
            'created_at' => $msg->created_at->toISOString(),
        ])->values()->all();

        return [
            'case_id' => $case->id,
            'unread_count' => $unreadCount,
            'items' => $items,
        ];
    }

    private function calculateUnreadCount(Authenticatable $user, string $caseId): int
    {
        $query = CaseMessage::query()
            ->where('case_id', $caseId)
            ->whereNull('read_at');

        if ($user instanceof Citizen) {
            $query->where('sender_type', '!=', MessageSenderType::CITIZEN->value);
        } elseif ($user instanceof Operator) {
            $query->where('sender_type', MessageSenderType::CITIZEN->value);
        }

        return $query->count();
    }

    private function markMessagesAsRead(Authenticatable $user, string $caseId): void
    {
        $query = CaseMessage::query()
            ->where('case_id', $caseId)
            ->whereNull('read_at');

        if ($user instanceof Citizen) {
            $query->where('sender_type', '!=', MessageSenderType::CITIZEN->value);
        } elseif ($user instanceof Operator) {
            $query->where('sender_type', MessageSenderType::CITIZEN->value);
        }

        $query->update(['read_at' => Carbon::now()]);
    }
}
