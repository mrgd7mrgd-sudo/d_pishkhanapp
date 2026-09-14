<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Domain\Enums\MessageSenderType;
use App\Modules\Messaging\Domain\Events\NewCaseMessage;
use App\Modules\Messaging\Domain\Models\CaseMessage;
use App\Modules\Messaging\Infrastructure\Policies\CaseMessagePolicy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class SendCaseMessageAction
{
    public function __construct(
        private readonly CaseMessagePolicy $policy
    ) {}

    public function execute(
        Authenticatable $user,
        string $caseId,
        string $body,
        ?string $attachmentKey = null
    ): CaseMessage {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->where('id', $caseId)->first();

        if (! $case || ! $this->policy->canAccessCase($user, $case)) {
            // Horizontal Isolation §7.3: Always 404, never 403
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Case not found.',
            ], 404));
        }

        [$senderType, $senderId, $senderName] = $this->resolveSender($user);

        $message = CaseMessage::create([
            'id' => (string) Str::uuid(),
            'case_id' => $case->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'body' => $body,
            'attachment_key' => $attachmentKey,
            'read_at' => null,
        ]);

        // Broadcast real-time event
        NewCaseMessage::dispatch($message);

        return $message;
    }

    /**
     * @return array{0: MessageSenderType, 1: string|null, 2: string}
     */
    private function resolveSender(Authenticatable $user): array
    {
        if ($user instanceof Citizen) {
            return [
                MessageSenderType::CITIZEN,
                $user->id,
                $user->full_name ?? 'شهروند',
            ];
        }

        if ($user instanceof Operator) {
            return [
                MessageSenderType::OPERATOR,
                $user->id,
                $user->full_name ?? 'کارشناس دفتر',
            ];
        }

        return [
            MessageSenderType::SYSTEM,
            null,
            'سامانه هوشمند',
        ];
    }
}
