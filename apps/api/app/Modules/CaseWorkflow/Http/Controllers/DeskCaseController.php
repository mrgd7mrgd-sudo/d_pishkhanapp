<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Controllers;

use App\Modules\CaseWorkflow\Application\Actions\CompleteCaseAction;
use App\Modules\CaseWorkflow\Application\Actions\RejectCaseAction;
use App\Modules\CaseWorkflow\Application\Actions\RequestInquiryAction;
use App\Modules\CaseWorkflow\Application\Actions\ReturnCaseAction;
use App\Modules\CaseWorkflow\Application\Actions\StartReviewAction;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Http\Requests\RejectCaseRequest;
use App\Modules\CaseWorkflow\Http\Requests\ReturnCaseRequest;
use App\Modules\CaseWorkflow\Http\Resources\CaseDetailResource;
use App\Modules\CaseWorkflow\Http\Resources\CaseResource;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeskCaseController (Architecture §5.6, §7.3, TASK-074).
 * Operator desk case handling endpoints: list, detail, review, return, inquiry, complete, reject.
 */
final class DeskCaseController
{
    /**
     * GET /desk/cases
     */
    public function index(Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        $query = CaseRequest::query()
            ->with(['service.category', 'office'])
            ->where('office_id', $operator->office_id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $search = $request->query('q') ?? $request->query('search');
        if ($search !== null && $search !== '') {
            $query->where('tracking_code', 'like', "%{$search}%");
        }

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $cases = $query->cursorPaginate($perPage);

        return new JsonResponse([
            'data' => CaseResource::collection($cases->items())->resolve(),
            'meta' => [
                'per_page' => $cases->perPage(),
                'next_cursor' => $cases->nextCursor()?->encode(),
                'prev_cursor' => $cases->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * GET /desk/cases/{id}
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()
            ->with(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason'])
            ->where('id', $id)
            ->first();

        if (! $case || $case->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Case not found.',
            ], 404));
        }

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
        ]);
    }

    /**
     * POST /cases/{id}/review
     */
    public function review(string $id, Request $request, StartReviewAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $case = $action->execute($operator, $id);
        $case->load(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason']);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
            'message' => 'بررسی پرونده آغاز شد.',
        ]);
    }

    /**
     * POST /cases/{id}/return
     * Strictly conforms to Architecture §5.6 sample 7.
     */
    public function returnCase(string $id, ReturnCaseRequest $request, ReturnCaseAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);

        $result = $action->execute(
            operator: $operator,
            caseId: $id,
            reasonCode: (string) $request->validated('reason_code'),
            operatorNote: $request->validated('operator_note'),
            targetDocCode: $request->validated('target_document_type_code'),
            deadlineHours: $request->integer('deadline_hours', 72)
        );

        return new JsonResponse($result);
    }

    /**
     * POST /cases/{id}/inquiry
     */
    public function inquiry(string $id, Request $request, RequestInquiryAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $case = $action->execute($operator, $id);
        $case->load(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason']);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
            'message' => 'استعلام دولتی ارسال گردید.',
        ]);
    }

    /**
     * POST /cases/{id}/complete
     */
    public function complete(string $id, Request $request, CompleteCaseAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $case = $action->execute($operator, $id);
        $case->load(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason']);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
            'message' => 'پرونده با موفقیت تکمیل شد.',
        ]);
    }

    /**
     * POST /cases/{id}/reject
     */
    public function reject(string $id, RejectCaseRequest $request, RejectCaseAction $action): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        $case = $action->execute($operator, $id, $request->validated('reason'));
        $case->load(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason']);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
            'message' => 'پرونده رد شد.',
        ]);
    }

    private function resolveOperator(Request $request): Operator
    {
        $operator = $request->user();
        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        return $operator;
    }
}
