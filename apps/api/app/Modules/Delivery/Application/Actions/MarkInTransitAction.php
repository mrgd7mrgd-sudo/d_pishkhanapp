<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Application\Actions;

use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Models\Operator;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Transitions delivery request into in_transit status (Architecture §6.1, TASK-096).
 */
final class MarkInTransitAction
{
    public function execute(
        string $deliveryId,
        ?string $location = null,
        ?string $note = null,
        ?Operator $operator = null
    ): DeliveryRequest {
        $delivery = $this->findAuthorizedDelivery($deliveryId, $operator);

        $this->ensureCanMarkInTransit($delivery);

        $delivery->delivery_status = DeliveryStatus::IN_TRANSIT;
        if ($delivery->dispatched_at === null) {
            $delivery->dispatched_at = Carbon::now();
        }
        $delivery->save();

        $this->recordEvent($delivery, $location, $note);
        $this->recordAudit($delivery, $operator);

        return $delivery;
    }

    private function findAuthorizedDelivery(string $deliveryId, ?Operator $operator): DeliveryRequest
    {
        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()->where('id', $deliveryId)->first();

        if ($delivery === null || ($operator !== null && $delivery->office_id !== $operator->office_id)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        return $delivery;
    }

    private function ensureCanMarkInTransit(DeliveryRequest $delivery): void
    {
        $allowed = [DeliveryStatus::READY_FOR_DISPATCH, DeliveryStatus::COURIER_ASSIGNED];

        if (! in_array($delivery->delivery_status, $allowed, true)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'INVALID_DELIVERY_STATUS',
                'detail' => "Cannot mark in transit from status [{$delivery->delivery_status->value}].",
            ], 422));
        }
    }

    private function recordEvent(DeliveryRequest $delivery, ?string $location, ?string $note): void
    {
        $delivery->events()->create([
            'event' => DeliveryStatus::IN_TRANSIT->value,
            'location' => $location ?? 'در مسیر ارسال',
            'note' => $note ?? 'مرسوله به پیک تحویل داده شد و در مسیر مقصد است.',
            'occurred_at' => Carbon::now(),
        ]);
    }

    private function recordAudit(DeliveryRequest $delivery, ?Operator $operator): void
    {
        AuditLogger::record(
            action: 'delivery.in_transit',
            subject: $delivery,
            changes: [
                'delivery_status' => DeliveryStatus::IN_TRANSIT->value,
                'dispatched_at' => $delivery->dispatched_at?->toIso8601String(),
            ],
            context: ['office_id' => $delivery->office_id],
            actorType: $operator ? 'operator' : 'system',
            actorId: $operator?->id
        );
    }
}
