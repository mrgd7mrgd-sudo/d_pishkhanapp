<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Resources;

use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentType
 */
final class DocumentTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array (§5.6, TASK-041).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'accepted_mimes' => $this->accepted_mimes,
            'requires_original' => (bool) $this->requires_original,
            'validity_months' => $this->validity_months !== null ? (int) $this->validity_months : null,
        ];
    }
}
