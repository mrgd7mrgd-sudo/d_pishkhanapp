<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Citizen
 */
final class CitizenResource extends JsonResource
{
    /**
     * Transform the resource into an array (§5.6 #2, §7.7 PII Minimization & Masking).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'national_id_masked' => $this->maskNationalId($this->national_id),
            'mobile_masked' => $this->maskMobile($this->mobile),
            'tier' => $this->tier->value,
            'tier_name' => $this->tier->label(),
            'sana_verified' => (bool) $this->sana_verified,
            'digital_signature_active' => (bool) $this->digital_signature_active,
            'credit_score' => (int) $this->credit_score,
            'wallet_balance_rials' => 0, // In Phase 1, ledger initialized; default 0 rials
            'province_code' => $this->province_code,
            'city_code' => $this->city_id,
        ];
    }

    /**
     * Mask Iranian national ID: e.g., 0082345671 -> 008****671 (§5.6 #2, §7.7)
     */
    private function maskNationalId(?string $nationalId): ?string
    {
        if ($nationalId === null || strlen($nationalId) < 10) {
            return $nationalId;
        }

        return substr($nationalId, 0, 3).'****'.substr($nationalId, -3);
    }

    /**
     * Mask mobile number: e.g., 09123456781 -> 0912***6781 (§5.6 #2, §7.7)
     */
    private function maskMobile(?string $mobile): ?string
    {
        if ($mobile === null || strlen($mobile) < 7) {
            return $mobile;
        }

        return substr($mobile, 0, 4).'***'.substr($mobile, -4);
    }
}
