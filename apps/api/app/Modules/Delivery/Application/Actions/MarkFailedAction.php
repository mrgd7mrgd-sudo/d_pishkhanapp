<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Models\Operator;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Marks delivery as failed and returns case back to ready_for_issue (Architecture §3.5, §6.1, TASK-096).
 */
final class MarkFailedAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    public function execute(
        string $deliveryId,
        string $reason,
        ?string $location = null,
        ?Operator $operator = null
    ): DeliveryRequest {
        $delivery = $this->findAuthorizedDelivery($deliveryId, $operator);

        $this->ensureCanMarkFailed($delivery);

        return DB::transaction(function () use ($delivery, $reason, $location, $operator): DeliveryRequest {
            $this->updateDeliveryToFailed($delivery, $reason);
            $this->recordEvent($delivery, $reason, $location);
            $this->revertAssociatedCase($delivery, $reason, $operator);
            $this->recordAudit($delivery, $operator);

            return $delivery;
        });
    }

    private function findAuthorizedDelivery(string $deliveryId, ?Operator $operator): DeliveryRequest
    {
        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()->with('case')->where('id', $deliveryId)->first();

        if ($delivery === null || ($operator !== null && $delivery->office_id !== $operator->office_id)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        return $delivery;
    }

    private function ensureCanMarkFailed(DeliveryRequest $delivery): void
    {
        if ($delivery->delivery_status === DeliveryStatus::DELIVERED) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'INVALID_DELIVERY_STATUS',
                'detail' => 'Cannot mark an already delivered package as failed.',
            ], 422));
        }
    }

    private function updateDeliveryToFailed(DeliveryRequest $delivery, string $reason): void
    {
        $delivery->delivery_status = DeliveryStatus::FAILED;
        $note = $delivery->security_note ? $delivery->security_note.' | ' : '';
        $delivery->security_note = $note."علت عدم تحویل: {$reason}";
        $delivery->otp_hash = null;
        $delivery->otp_expires_at = null;
        $delivery->save();
    }

    private function recordEvent(DeliveryRequest $delivery, string $reason, ?string $location): void
    {
        $delivery->events()->create([
            'event' => DeliveryStatus::FAILED->value,
            'location' => $location ?? 'دفتر پیشخوان مبدأ',
            'note' => "تحویل ناموفق: {$reason}",
            'occurred_at' => Carbon::now(),
        ]);
    }

    private function revertAssociatedCase(DeliveryRequest $delivery, string $reason, ?Operator $operator): void
    {
        $case = $delivery->case;
        if ($case !== null && $case->status === CaseStatus::DELIVERING) {
            $context = new TransitionContext(
                title: 'تحویل ناموفق و بازگشت پرونده به دفتر',
                description: "ارسال مرسوله ناموفق بود ({$reason}) و جهت اقدام مجدد به دفتر بازگردانده شد.",
                stepStatus: TimelineStepStatus::FAILED,
                actorType: $operator ? TimelineActorType::OPERATOR : TimelineActorType::SYSTEM,
                actorId: $operator?->id,
                reasonCode: 'DELIVERY_FAILED'
            );

            $this->stateMachine->transition($case, CaseStatus::READY_FOR_ISSUE, $context);
        }
    }

    private function recordAudit(DeliveryRequest $delivery, ?Operator $operator): void
    {
        AuditLogger::record(
            action: 'delivery.failed',
            subject: $delivery,
            changes: [
                'delivery_status' => DeliveryStatus::FAILED->value,
                'security_note' => $delivery->security_note,
            ],
            context: ['office_id' => $delivery->office_id],
            actorType: $operator ? 'operator' : 'system',
            actorId: $operator?->id
        );
    }
}
