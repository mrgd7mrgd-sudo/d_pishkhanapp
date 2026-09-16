<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryRequest
 */
final class DeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Note: OTP and otp_hash are STRICTLY excluded (Architecture §7.4, §7.7).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'case_id' => $this->case_id,
            'office_id' => $this->office_id,
            'doc_type' => $this->doc_type->value,
            'doc_type_label' => $this->doc_type->label(),
            'doc_type_name' => $this->doc_type_name,
            'doc_serial_number' => $this->doc_serial_number,
            'destination_address' => $this->destination_address,
            'destination_postal_code' => $this->destination_postal_code,
            'destination_zone' => $this->destination_zone,
            'courier_type' => $this->courier_type->value,
            'courier_type_label' => $this->courier_type->label(),
            'delivery_status' => $this->delivery_status->value,
            'delivery_status_label' => $this->delivery_status->label(),
            'courier_name' => $this->courier_name,
            'courier_phone' => $this->courier_phone,
            'courier_plate' => $this->courier_plate,
            'otp_expires_at' => $this->otp_expires_at?->toIso8601String(),
            'shipping_fee_rials' => $this->shipping_fee_rials,
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'require_old_doc_return' => $this->require_old_doc_return,
            'is_sealed_pack' => $this->is_sealed_pack,
            'security_note' => $this->security_note,
            'tracking_barcode' => $this->tracking_barcode,
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
