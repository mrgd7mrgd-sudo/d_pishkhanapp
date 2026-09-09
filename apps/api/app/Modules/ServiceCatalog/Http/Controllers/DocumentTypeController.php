<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Controllers;

use App\Modules\ServiceCatalog\Application\Queries\GetDocumentTypesQuery;
use App\Modules\ServiceCatalog\Http\Controllers\Concerns\HasHttpCache;
use App\Modules\ServiceCatalog\Http\Resources\DocumentTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class DocumentTypeController
{
    use HasHttpCache;

    public function __construct(
        private readonly GetDocumentTypesQuery $getDocumentTypesQuery
    ) {}

    /**
     * List all active document types (§5.6, TASK-041).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $documentTypes = $this->getDocumentTypesQuery->execute();
        $payload = [
            'data' => DocumentTypeResource::collection($documentTypes)->resolve(),
        ];

        return $this->cachedJsonResponse($request, $payload);
    }
}
