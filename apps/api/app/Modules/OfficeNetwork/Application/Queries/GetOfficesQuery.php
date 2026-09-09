<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Queries;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class GetOfficesQuery
{
    /**
     * Execute offices query with filters, sorting, and cursor pagination (§5.6 #4, TASK-043).
     *
     * @param  array{only_online?: bool|null, province_code?: string|null, category_id?: string|null, sort?: string|null, limit?: int|null, cursor?: string|null}  $params
     * @return array{paginator: CursorPaginator<int, Office>, total_estimate: int}
     */
    public function execute(array $params): array
    {
        /** @var Builder<Office> $query */
        $query = Office::query()
            ->with(['specialties', 'medals', 'serviceCoverages']);

        // 1. Online only filter
        if (! empty($params['only_online'])) {
            $query->where('is_online', true)
                ->where('membership_status', 'registered_online');
        }

        // 2. Province filter
        if (! empty($params['province_code'])) {
            $query->where('province_code', $params['province_code']);
        }

        // 3. Category coverage filter
        if (! empty($params['category_id'])) {
            $categoryId = $params['category_id'];
            $query->whereHas('serviceCoverages', function (Builder $sub) use ($categoryId): void {
                $sub->where('category_id', $categoryId)->where('is_active', true);
            });
        }

        $totalEstimate = (clone $query)->count();

        // 4. Sorting
        $this->applySorting($query, $params['sort'] ?? null);

        // 5. Pagination
        $limit = max(1, min((int) ($params['limit'] ?? 20), 50));
        $paginator = $query->cursorPaginate($limit, ['*'], 'cursor', $params['cursor'] ?? null);

        return [
            'paginator' => $paginator,
            'total_estimate' => $totalEstimate,
        ];
    }

    /**
     * @param  Builder<Office>  $query
     */
    private function applySorting(Builder $query, ?string $sort): void
    {
        switch ($sort) {
            case '-rating':
                $query->orderBy('rating', 'desc')->orderBy('id', 'asc');
                break;
            case 'rating':
                $query->orderBy('rating', 'asc')->orderBy('id', 'asc');
                break;
            case 'name':
                $query->orderBy('name', 'asc')->orderBy('id', 'asc');
                break;
            case '-review_count':
                $query->orderBy('review_count', 'desc')->orderBy('id', 'asc');
                break;
            default:
                $query->orderBy('rating', 'desc')->orderBy('id', 'asc');
                break;
        }
    }
}
