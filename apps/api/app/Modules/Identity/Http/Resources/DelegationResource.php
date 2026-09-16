<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Domain\Models\Delegation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Delegation
 */
final class DelegationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'principal_citizen_id' => $this->principal_citizen_id,
            'delegate_citizen_id' => $this->delegate_citizen_id,
            'document_number' => $this->document_number,
            'status' => $this->status->value,
            'max_amount_rials' => (int) $this->max_amount_rials,
            'allowed_service_ids' => (array) ($this->allowed_service_ids ?? []),
            'principal_otp_verified' => (bool) $this->principal_otp_verified,
            'delegate_otp_verified' => (bool) $this->delegate_otp_verified,
            'valid_until' => $this->valid_until->toISOString(),
            'activated_at' => $this->activated_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'principal' => $this->relationLoaded('principal') && $this->principal !== null
                ? [
                    'id' => $this->principal->id,
                    'full_name' => $this->principal->full_name,
                ]
                : null,
            'delegate' => $this->relationLoaded('delegate') && $this->delegate !== null
                ? [
                    'id' => $this->delegate->id,
                    'full_name' => $this->delegate->full_name,
                ]
                : null,
        ];
    }
}
