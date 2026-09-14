<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Infrastructure\Cache\OfficeQueueCache;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * QueueController (Architecture §5.7, §6.7, TASK-076).
 * Live queue endpoint for operator desk with 30s Redis cache and DB resilience.
 */
final class QueueController
{
    public function __construct(
        private readonly OfficeQueueCache $queueCache
    ) {}

    public function show(Request $request): JsonResponse
    {
        $operator = $request->user();

        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز به صف دفتر.',
            ], 404));
        }

        $queueData = $this->queueCache->getOrCalculate($operator->office_id);

        return new JsonResponse([
            'data' => $queueData,
        ]);
    }
}
