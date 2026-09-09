<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Resources;

use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceCategory
 */
final class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array (§5.6, TASK-041).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_title' => $this->short_title,
            'icon_name' => $this->icon_name ?? 'Document',
            'color' => $this->color ?? '#3b82f6',
            'badge' => $this->badge,
            'description' => $this->description,
            'services_count' => (int) ($this->services_count ?? $this->services()->where('is_active', true)->count()),
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
