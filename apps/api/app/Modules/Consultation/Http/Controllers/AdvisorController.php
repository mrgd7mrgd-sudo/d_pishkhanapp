<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Controllers;

use App\Modules\Consultation\Application\Actions\SubmitAdvisorApplicationAction;
use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Http\Requests\SubmitAdvisorApplicationRequest;
use App\Modules\Consultation\Http\Resources\AdvisorResource;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class AdvisorController
{
    /**
     * GET /advisors
     * Public list of approved advisors (§6.1, TASK-118).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Advisor::query()
            ->with(['specialties'])
            ->where('application_status', AdvisorApplicationStatus::Approved);

        if ($request->filled('category')) {
            $query->where('category', (string) $request->query('category'));
        }

        if ($request->boolean('online_only')) {
            $query->where('is_online', true);
        }

        $sort = (string) $request->query('sort', 'rating_desc');
        match ($sort) {
            'rating_desc' => $query->orderByDesc('rating')->orderByDesc('review_count'),
            'price_asc' => $query->orderBy('price_text_chat_rials'),
            'price_desc' => $query->orderByDesc('price_text_chat_rials'),
            'experience_desc' => $query->orderByDesc('experience_years'),
            default => $query->orderByDesc('rating'),
        };

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $advisors = $query->paginate($perPage);

        return new JsonResponse([
            'data' => AdvisorResource::collection($advisors->items())->resolve(),
            'meta' => [
                'current_page' => $advisors->currentPage(),
                'last_page' => $advisors->lastPage(),
                'per_page' => $advisors->perPage(),
                'total' => $advisors->total(),
            ],
        ]);
    }

    /**
     * GET /advisors/{id}
     */
    public function show(string $id): JsonResponse
    {
        /** @var Advisor|null $advisor */
        $advisor = Advisor::query()
            ->with(['specialties', 'reviews'])
            ->where('id', $id)
            ->where('application_status', AdvisorApplicationStatus::Approved)
            ->first();

        if ($advisor === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'مشاور مورد نظر یافت نشد یا هنوز تأیید نشده است.',
            ], 404));
        }

        return new JsonResponse([
            'data' => (new AdvisorResource($advisor))->resolve(),
        ]);
    }

    /**
     * POST /advisors/apply
     */
    public function apply(
        SubmitAdvisorApplicationRequest $request,
        SubmitAdvisorApplicationAction $action
    ): JsonResponse {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'فقط شهروندان احراز هویت شده می‌توانند درخواست مشاوره ثبت کنند.',
            ], 403));
        }

        try {
            $advisor = $action->execute(
                citizen: $user,
                displayName: (string) $request->validated('display_name'),
                title: (string) $request->validated('title'),
                category: ConsultationCategory::from((string) $request->validated('category')),
                licenseNumber: (string) $request->validated('license_number'),
                experienceYears: (int) $request->validated('experience_years'),
                priceTextChatRials: (int) $request->validated('price_text_chat_rials'),
                pricePhonePerMinuteRials: (int) $request->validated('price_phone_per_minute_rials'),
                priceDeepReviewRials: (int) $request->validated('price_deep_review_rials'),
                bio: (string) $request->validated('bio'),
                specialties: (array) ($request->validated('specialties') ?? []),
                credentialsBadge: $request->validated('credentials_badge')
            );

            return new JsonResponse([
                'data' => (new AdvisorResource($advisor))->resolve(),
                'message' => 'درخواست همکاری مشاوره شما با موفقیت ثبت شد و پس از بررسی مدارک فعال خواهد شد.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }
}
