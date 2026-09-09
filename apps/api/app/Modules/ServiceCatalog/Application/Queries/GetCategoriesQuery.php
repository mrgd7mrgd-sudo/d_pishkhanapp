<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Application\Queries;

use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Collection;

final class GetCategoriesQuery
{
    /**
     * @return Collection<int, ServiceCategory>
     */
    public function execute(): Collection
    {
        return ServiceCategory::query()
            ->where('is_active', true)
            ->withCount(['services' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order', 'asc')
            ->orderBy('title', 'asc')
            ->get();
    }
}
