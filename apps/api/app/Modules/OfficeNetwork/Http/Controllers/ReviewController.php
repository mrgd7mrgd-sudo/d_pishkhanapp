<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Application\Actions\SubmitOfficeReviewAction;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use App\Modules\OfficeNetwork\Http\Requests\SubmitOfficeReviewRequest;
use App\Modules\OfficeNetwork\Http\Resources\OfficeReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ReviewController
{
    /**
     * GET /offices/{id}/reviews - public list of reviews for an office.
     */
    public function index(Request $request, string $id): AnonymousResourceCollection
    {
        $office = Office::findOrFail($id);

        $query = OfficeReview::query()
            ->with(['citizen', 'caseRequest.service'])
            ->where('office_id', $office->id)
            ->where('is_verified', true)
            ->orderByDesc('created_at');

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        if ($request->boolean('with_reply')) {
            $query->whereNotNull('manager_reply');
        }

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $reviews = $query->paginate($perPage);

        return OfficeReviewResource::collection($reviews);
    }

    /**
     * POST /reviews - citizen submits review for their completed case.
     */
    public function store(SubmitOfficeReviewRequest $request, SubmitOfficeReviewAction $action): JsonResponse
    {
        Gate::authorize('create', OfficeReview::class);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var list<string>|null $tags */
        $tags = $request->input('tags');

        $review = $action->execute(
            citizen: $citizen,
            caseId: (string) $request->input('case_id'),
            rating: $request->integer('rating'),
            comment: (string) $request->input('comment'),
            tags: is_array($tags) ? $tags : null
        );

        $review->load(['citizen', 'caseRequest.service', 'office']);

        return (new OfficeReviewResource($review))
            ->response()
            ->setStatusCode(201);
    }
}
