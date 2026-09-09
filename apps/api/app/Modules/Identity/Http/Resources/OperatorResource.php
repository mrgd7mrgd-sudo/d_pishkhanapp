<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Operator
 */
final class OperatorResource extends JsonResource
{
    /**
     * Transform the operator resource into an array (§7.7 PII Minimization).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'office_id' => $this->office_id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'national_id_masked' => $this->maskNationalId($this->national_id),
            'mobile_masked' => $this->maskMobile($this->mobile),
            'role' => $this->role->value,
            'role_name' => $this->role->label(),
            'counter_number' => $this->counter_number,
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at !== null ? $this->last_login_at->toIso8601String() : null,
        ];
    }

    private function maskNationalId(?string $nationalId): ?string
    {
        if ($nationalId === null || strlen($nationalId) < 10) {
            return $nationalId;
        }

        return substr($nationalId, 0, 3).'****'.substr($nationalId, -3);
    }

    private function maskMobile(?string $mobile): ?string
    {
        if ($mobile === null || strlen($mobile) < 7) {
            return $mobile;
        }

        return substr($mobile, 0, 4).'***'.substr($mobile, -4);
    }
}
