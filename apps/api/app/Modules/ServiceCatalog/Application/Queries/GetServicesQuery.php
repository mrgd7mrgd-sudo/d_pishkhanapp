<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Application\Queries;

use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Shared\Text\PersianNormalizer;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GetServicesQuery
{
    /**
     * Execute services query with filters, search, sorting, and keyset cursor pagination (§5.6, TASK-041).
     *
     * @param  array{category_id?: string|null, tag?: string|null, q?: string|null, sort?: string|null, limit?: int|null, cursor?: string|null}  $params
     * @return array{paginator: CursorPaginator<int, Service>, total_estimate: int}
     */
    public function execute(array $params): array
    {
        /** @var Builder<Service> $query */
        $query = Service::query()
            ->with(['category', 'requiredDocs.documentType'])
            ->where('is_active', true);

        // 1. Filter by category
        if (! empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // 2. Filter by tag
        if (! empty($params['tag'])) {
            $query->whereJsonContains('tags', $params['tag']);
        }

        // 3. Persian Search
        if (! empty($params['q'])) {
            $this->applySearch($query, (string) $params['q']);
        }

        // Total count before pagination
        $totalEstimate = (clone $query)->count();

        // 4. Sorting
        $this->applySorting($query, $params['sort'] ?? null);

        // 5. Pagination limit (clamped between 1 and 50)
        $limit = max(1, min((int) ($params['limit'] ?? 20), 50));

        // 6. Cursor Paginate
        $paginator = $query->cursorPaginate($limit, ['*'], 'cursor', $params['cursor'] ?? null);

        return [
            'paginator' => $paginator,
            'total_estimate' => $totalEstimate,
        ];
    }

    /**
     * @param  Builder<Service>  $query
     */
    private function applySearch(Builder $query, string $rawQuery): void
    {
        $normalized = PersianNormalizer::normalize($rawQuery);
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $query->where(function (Builder $sub) use ($normalized): void {
                $sub->whereRaw("search_vector @@ plainto_tsquery('persian', ?)", [$normalized])
                    ->orWhereRaw('title ILIKE ?', ["%{$normalized}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$normalized}%"]);
            });
        } else {
            $query->where(function (Builder $sub) use ($normalized): void {
                $sub->where('title', 'like', "%{$normalized}%")
                    ->orWhere('description', 'like', "%{$normalized}%");
            });
        }
    }

    /**
     * @param  Builder<Service>  $query
     */
    private function applySorting(Builder $query, ?string $sort): void
    {
        switch ($sort) {
            case '-is_popular':
                $query->orderBy('is_popular', 'desc')->orderBy('id', 'asc');
                break;
            case 'is_popular':
                $query->orderBy('is_popular', 'asc')->orderBy('id', 'asc');
                break;
            case '-fee_rials':
                $query->orderBy('fee_rials', 'desc')->orderBy('id', 'asc');
                break;
            case 'fee_rials':
                $query->orderBy('fee_rials', 'asc')->orderBy('id', 'asc');
                break;
            case 'title':
                $query->orderBy('title', 'asc')->orderBy('id', 'asc');
                break;
            case '-title':
                $query->orderBy('title', 'desc')->orderBy('id', 'asc');
                break;
            default:
                $query->orderBy('is_popular', 'desc')
                    ->orderBy('id', 'asc');
                break;
        }
    }
}
