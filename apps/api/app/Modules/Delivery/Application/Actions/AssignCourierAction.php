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
 * Assigns courier details to a delivery request (Architecture §6.1, TASK-096).
 */
final class AssignCourierAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $deliveryId, array $data, ?Operator $operator = null): DeliveryRequest
    {
        $delivery = $this->findAuthorizedDelivery($deliveryId, $operator);

        $this->ensureCanAssignCourier($delivery);

        $courierName = (string) $data['courier_name'];
        $courierPhone = (string) $data['courier_phone'];
        $courierPlate = isset($data['courier_plate']) ? (string) $data['courier_plate'] : null;

        $delivery->courier_name = $courierName;
        $delivery->courier_phone = $courierPhone;
        $delivery->courier_plate = $courierPlate;
        $delivery->delivery_status = DeliveryStatus::COURIER_ASSIGNED;
        $delivery->save();

        $this->recordEvent($delivery, $courierName, $courierPhone);
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

    private function ensureCanAssignCourier(DeliveryRequest $delivery): void
    {
        $allowed = [DeliveryStatus::READY_FOR_DISPATCH, DeliveryStatus::COURIER_ASSIGNED];

        if (! in_array($delivery->delivery_status, $allowed, true)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'INVALID_DELIVERY_STATUS',
                'detail' => "Cannot assign courier from status [{$delivery->delivery_status->value}].",
            ], 422));
        }
    }

    private function recordEvent(DeliveryRequest $delivery, string $name, string $phone): void
    {
        $delivery->events()->create([
            'event' => DeliveryStatus::COURIER_ASSIGNED->value,
            'location' => $delivery->office->name ?? 'دفتر مبدأ',
            'note' => "سفیر اختصاص یافت: {$name} ({$phone})",
            'occurred_at' => Carbon::now(),
        ]);
    }

    private function recordAudit(DeliveryRequest $delivery, ?Operator $operator): void
    {
        AuditLogger::record(
            action: 'delivery.courier_assigned',
            subject: $delivery,
            changes: [
                'delivery_status' => DeliveryStatus::COURIER_ASSIGNED->value,
                'courier_name' => $delivery->courier_name,
            ],
            context: ['office_id' => $delivery->office_id],
            actorType: $operator ? 'operator' : 'system',
            actorId: $operator?->id
        );
    }
}
