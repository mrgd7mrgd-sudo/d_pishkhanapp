<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Application\Actions\AssignCourierAction;
use App\Modules\Delivery\Application\Actions\ConfirmDeliveryAction;
use App\Modules\Delivery\Application\Actions\CreateDeliveryRequestAction;
use App\Modules\Delivery\Application\Actions\MarkFailedAction;
use App\Modules\Delivery\Application\Actions\MarkInTransitAction;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Delivery\Http\Requests\CreateDeliveryRequest;
use App\Modules\Delivery\Http\Resources\DeliveryResource;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeliveryController (Architecture §3.5, §6.1, §7.1, §7.3, TASK-095, TASK-096, TASK-103).
 * Endpoints for managing delivery lifecycle and OTP confirmation.
 */
final class DeliveryController
{
    /**
     * GET /deliveries
     */
    public function index(Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        $query = DeliveryRequest::query()
            ->with(['office'])
            ->where('office_id', $operator->office_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('delivery_status', (string) $request->query('status'));
        }

        if ($request->filled('courier_type')) {
            $query->where('courier_type', (string) $request->query('courier_type'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('tracking_barcode', 'like', "%{$search}%")
                    ->orWhere('destination_address', 'like', "%{$search}%")
                    ->orWhere('courier_name', 'like', "%{$search}%");
            });
        }

        $deliveries = $query->paginate(25);

        return new JsonResponse([
            'data' => DeliveryResource::collection($deliveries->items())->resolve(),
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
                'total' => $deliveries->total(),
            ],
        ]);
    }

    /**
     * GET /deliveries/ready-cases
     */
    public function readyCases(Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        $cases = CaseRequest::query()
            ->with(['citizen', 'service'])
            ->where('office_id', $operator->office_id)
            ->where('status', CaseStatus::READY_FOR_ISSUE->value)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        /** @var list<array<string, mixed>> $data */
        $data = [];
        foreach ($cases as $case) {
            /** @var CaseRequest $case */
            $data[] = [
                'id' => $case->id,
                'tracking_code' => $case->tracking_code,
                'service_title' => $case->service?->title,
                'citizen_name' => $case->citizen?->full_name,
                'citizen_mobile' => $case->citizen?->mobile,
                'delivery_preference' => $case->delivery_preference->value,
                'address' => $case->citizen?->address,
                'postal_code' => $case->citizen?->postal_code,
            ];
        }

        return new JsonResponse(['data' => $data]);
    }

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

    /**
     * POST /deliveries/{id}/assign-courier
     */
    public function assignCourier(string $id, Request $request, AssignCourierAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $validated = $request->validate([
            'courier_name' => ['required', 'string', 'max:128'],
            'courier_phone' => ['required', 'string', 'max:32'],
            'courier_plate' => ['nullable', 'string', 'max:32'],
        ]);

        $delivery = $action->execute($id, $validated, $operator);

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
            'message' => 'سفیر تحویل به مرسوله اختصاص یافت.',
        ]);
    }

    /**
     * POST /deliveries/{id}/in-transit
     */
    public function markInTransit(string $id, Request $request, MarkInTransitAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $validated = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $delivery = $action->execute(
            $id,
            $validated['location'] ?? null,
            $validated['note'] ?? null,
            $operator
        );

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
            'message' => 'وضعیت مرسوله به در مسیر ارسال تغییر یافت.',
        ]);
    }

    /**
     * POST /deliveries/{id}/confirm
     * Semi-public courier confirmation endpoint (Architecture §7.3).
     */
    public function confirm(string $id, Request $request, ConfirmDeliveryAction $action): JsonResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $operator = $request->user() instanceof Operator ? $request->user() : null;
        $delivery = $action->execute($id, (string) $validated['otp'], $operator);

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
            'message' => 'مرسوله با موفقیت تحویل داده شد و پرونده تکمیل گردید.',
        ]);
    }

    /**
     * POST /deliveries/{id}/fail
     */
    public function markFailed(string $id, Request $request, MarkFailedAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $delivery = $action->execute(
            $id,
            (string) $validated['reason'],
            $validated['location'] ?? null,
            $operator
        );

        return new JsonResponse([
            'data' => (new DeliveryResource($delivery))->resolve(),
            'message' => 'مرسوله به عنوان تحویل ناموفق ثبت و پرونده به دفتر بازگردانده شد.',
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
