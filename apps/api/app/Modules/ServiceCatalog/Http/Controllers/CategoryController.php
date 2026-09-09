<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Controllers;

use App\Modules\ServiceCatalog\Application\Queries\GetCategoriesQuery;
use App\Modules\ServiceCatalog\Http\Controllers\Concerns\HasHttpCache;
use App\Modules\ServiceCatalog\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CategoryController
{
    use HasHttpCache;

    public function __construct(
        private readonly GetCategoriesQuery $getCategoriesQuery
    ) {}

    /**
     * List all active service categories (§5.6, TASK-041).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $categories = $this->getCategoriesQuery->execute();
        $payload = [
            'data' => CategoryResource::collection($categories)->resolve(),
        ];

        return $this->cachedJsonResponse($request, $payload);
    }
}
