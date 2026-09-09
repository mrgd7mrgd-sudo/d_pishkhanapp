<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Application\Queries;

use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use Illuminate\Database\Eloquent\Collection;

final class GetDocumentTypesQuery
{
    /**
     * @return Collection<int, DocumentType>
     */
    public function execute(): Collection
    {
        return DocumentType::query()
            ->where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();
    }
}
