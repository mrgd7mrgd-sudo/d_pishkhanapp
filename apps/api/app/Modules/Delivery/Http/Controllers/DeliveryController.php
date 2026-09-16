<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers;

use App\Modules\Delivery\Application\Actions\CreateDeliveryRequestAction;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Delivery\Http\Requests\CreateDeliveryRequest;
use App\Modules\Delivery\Http\Resources\DeliveryResource;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeliveryController (Architecture §3.5, §6.1, TASK-095).
 * Endpoints for managing delivery requests.
 */
final class DeliveryController
{
    /**
     * POST /deliveries
     */
    public function store(CreateDeliveryRequest $request, CreateDeliveryRequestAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $delivery = $action->execute($operator, $request->validated());

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
            'message' => 'درخواست تحویل با موفقیت ثبت شد.',
        ], 201);
    }

    /**
     * GET /deliveries/{id}
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()
            ->with(['events', 'office'])
            ->where('id', $id)
            ->orWhere('tracking_barcode', $id)
            ->first();

        if ($delivery === null || $delivery->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
        ]);
    }

    private function resolveOperator(Request $request): Operator
    {
        $user = $request->user();

        if (! $user instanceof Operator) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'User is not an operator.',
            ], 403));
        }

        return $user;
    }
}
