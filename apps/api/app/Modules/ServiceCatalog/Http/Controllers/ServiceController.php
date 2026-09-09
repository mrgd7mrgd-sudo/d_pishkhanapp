<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Controllers;

use App\Modules\ServiceCatalog\Application\Queries\GetServicesQuery;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Http\Controllers\Concerns\HasHttpCache;
use App\Modules\ServiceCatalog\Http\Resources\ServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ServiceController
{
    use HasHttpCache;

    public function __construct(
        private readonly GetServicesQuery $getServicesQuery
    ) {}

    /**
     * List services with filters, search, sorting, and cursor pagination (§5.6, TASK-041).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $categoryId = $request->input('filter.category_id') ?? $request->input('category_id');
        $tag = $request->input('filter.tag') ?? $request->input('tag');

        $result = $this->getServicesQuery->execute([
            'category_id' => is_string($categoryId) ? $categoryId : null,
            'tag' => is_string($tag) ? $tag : null,
            'q' => $request->string('q')->trim()->value() ?: null,
            'sort' => $request->string('sort')->trim()->value() ?: null,
            'limit' => $request->has('limit') ? $request->integer('limit') : null,
            'cursor' => $request->string('cursor')->trim()->value() ?: null,
        ]);

        $paginator = $result['paginator'];

        $payload = [
            'data' => ServiceResource::collection($paginator->items())->resolve(),
            'meta' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'total_estimate' => $result['total_estimate'],
            ],
        ];

        return $this->cachedJsonResponse($request, $payload);
    }

    /**
     * Get a single service by slug (§5.6, TASK-041).
     */
    public function show(Request $request, string $slug): JsonResponse|Response
    {
        $service = Service::query()
            ->with(['category', 'requiredDocs.documentType'])
            ->where('slug', $slug)
            ->first();

        if ($service === null) {
            return new JsonResponse([
                'type' => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'title' => 'خدمت مورد نظر یافت نشد',
                'status' => 404,
                'detail' => "خدمتی با شناسه '{$slug}' در کاتالوگ خدمات یافت نشد.",
                'code' => 'SERVICE_NOT_FOUND',
            ], 404);
        }

        $payload = [
            'data' => (new ServiceResource($service))->resolve(),
        ];

        return $this->cachedJsonResponse($request, $payload);
    }
}
