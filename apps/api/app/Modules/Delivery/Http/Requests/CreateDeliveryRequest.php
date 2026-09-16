<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryPaymentMethod;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Foundation\Http\FormRequest;

final class CreateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Operator;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'case_id' => ['required', 'string'],
            'doc_type' => ['required', 'string', 'in:'.implode(',', DeliveryDocType::values())],
            'doc_type_name' => ['nullable', 'string', 'max:128'],
            'doc_serial_number' => ['nullable', 'string', 'max:64'],
            'destination_address' => ['required', 'string', 'max:1000'],
            'destination_postal_code' => ['required', 'string', 'digits:10'],
            'destination_zone' => ['nullable', 'string', 'max:64'],
            'courier_type' => ['required', 'string', 'in:'.implode(',', CourierType::values())],
            'shipping_fee_rials' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:'.implode(',', DeliveryPaymentMethod::values())],
            'require_old_doc_return' => ['nullable', 'boolean'],
            'is_sealed_pack' => ['nullable', 'boolean'],
            'security_note' => ['nullable', 'string', 'max:1000'],
            'tracking_barcode' => ['nullable', 'string', 'max:64'],
        ];
    }
}
