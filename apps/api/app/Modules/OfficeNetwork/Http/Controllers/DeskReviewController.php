<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Application\Actions\ReplyOfficeReviewAction;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use App\Modules\OfficeNetwork\Http\Requests\ReplyOfficeReviewRequest;
use App\Modules\OfficeNetwork\Http\Resources\OfficeReviewResource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * DeskReviewController (§4.4, §7.3, TASK-101).
 * Handles office reviews on the operator desk: listing, filtering, and manager replies.
 */
final class DeskReviewController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $operator = $this->resolveOperator($request);

        $query = OfficeReview::query()
            ->with(['citizen', 'caseRequest.service'])
            ->where('office_id', $operator->office_id)
            ->orderByDesc('created_at');

        $this->applyFilters($query, $request);

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $reviews = $query->paginate($perPage);

        return OfficeReviewResource::collection($reviews);
    }

    public function show(Request $request, string $id): OfficeReviewResource
    {
        $operator = $this->resolveOperator($request);

        /** @var OfficeReview|null $review */
        $review = OfficeReview::query()
            ->with(['citizen', 'caseRequest.service', 'office'])
            ->where('id', $id)
            ->where('office_id', $operator->office_id)
            ->first();

        if ($review === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نظر یافت نشد.',
            ], 404));
        }

        return new OfficeReviewResource($review);
    }

    public function reply(
        ReplyOfficeReviewRequest $request,
        string $id,
        ReplyOfficeReviewAction $action
    ): OfficeReviewResource {
        $operator = $this->resolveOperator($request);

        /** @var OfficeReview|null $review */
        $review = OfficeReview::query()
            ->where('id', $id)
            ->where('office_id', $operator->office_id)
            ->first();

        if ($review === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نظر یافت نشد.',
            ], 404));
        }

        $updated = $action->execute(
            operator: $operator,
            review: $review,
            replyText: (string) $request->validated('reply')
        );

        $updated->load(['citizen', 'caseRequest.service', 'office']);

        return new OfficeReviewResource($updated);
    }

    /**
     * @param  Builder<OfficeReview>  $query
     */
    private function applyFilters($query, Request $request): void
    {
        $filter = (string) ($request->query('filter') ?? '');

        if ($filter === 'with_reply' || $filter === 'withReply') {
            $query->whereNotNull('manager_reply');
        } elseif ($filter === 'need_reply' || $filter === 'needReply') {
            $query->whereNull('manager_reply');
        } elseif ($filter === '5star') {
            $query->where('rating', 5);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        $search = $request->query('q') ?? $request->query('search');
        if ($search !== null && $search !== '') {
            $query->where('comment', 'like', "%{$search}%");
        }
    }

    public function slaStats(Request $request): JsonResponse
    {
        $operator = $this->resolveOperator($request);
        /** @var Office|null $office */
        $office = Office::query()->find($operator->office_id);
        if ($office === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دفتر مربوطه یافت نشد.',
            ], 404));
        }

        $currentScore = (float) $office->sla_score;

        // Daily points for the last 30 days
        $days = 30;
        $trend = [];
        $now = CarbonImmutable::now();

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->subDays($i);
            $dayStart = $day->startOfDay();
            $dayEnd = $day->endOfDay();

            $breachesCount = OfficeSlaEvent::query()
                ->where('office_id', $office->id)
                ->where('is_breach', true)
                ->whereBetween('occurred_at', [$dayStart, $dayEnd])
                ->count();

            $dayPenalties = (float) OfficeSlaEvent::query()
                ->where('office_id', $office->id)
                ->where('is_breach', true)
                ->whereBetween('occurred_at', [$dayStart, $dayEnd])
                ->sum('penalty_points');

            $trend[] = [
                'date' => $day->format('Y-m-d'),
                'breaches_count' => $breachesCount,
                'penalties' => $dayPenalties,
            ];
        }

        $breachBreakdown = OfficeSlaEvent::query()
            ->where('office_id', $office->id)
            ->where('is_breach', true)
            ->where('occurred_at', '>=', $now->subDays($days))
            ->selectRaw('event_type, count(*) as count, sum(penalty_points) as total_penalty')
            ->groupBy('event_type')
            ->get();

        return new JsonResponse([
            'data' => [
                'current_score' => $currentScore,
                'window_days' => $days,
                'trend' => $trend,
                'breakdown' => $breachBreakdown,
            ],
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
