<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Controllers;

use App\Modules\Consultation\Application\Actions\EndSessionAction;
use App\Modules\Consultation\Application\Actions\StartSessionAction;
use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Consultation\Domain\SessionBillingCalculator;
use App\Modules\Consultation\Http\Resources\ConsultationSessionResource;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use InvalidArgumentException;

final class SessionController
{
    /**
     * GET /consultations/sessions
     * List user's consultation sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen && ! $user instanceof Operator) {
            throw new HttpResponseException(new JsonResponse(['status' => 401, 'detail' => 'احراز هویت الزامی است.'], 401));
        }

        $query = ConsultationSession::query()
            ->with(['advisor', 'linkedService'])
            ->orderByDesc('created_at');

        if ($user instanceof Citizen) {
            $query->where('citizen_id', $user->id);
        }

        $sessions = $query->paginate(15);

        return new JsonResponse([
            'data' => ConsultationSessionResource::collection($sessions->items())->resolve(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    /**
     * POST /consultations/sessions/start
     */
    public function start(Request $request, StartSessionAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'تنها شهروندان امکان آغاز جلسه مشاوره را دارند.',
            ], 403));
        }

        $validated = $request->validate([
            'advisor_id' => ['required', 'uuid'],
            'mode' => ['required', new Enum(ConsultationMode::class)],
            'linked_service_id' => ['nullable', 'uuid', 'exists:services,id'],
        ]);

        try {
            $session = $action->execute(
                citizen: $user,
                advisorId: (string) $validated['advisor_id'],
                mode: ConsultationMode::from((string) $validated['mode']),
                linkedServiceId: $validated['linked_service_id'] ?? null
            );

            return new JsonResponse([
                'data' => (new ConsultationSessionResource($session))->resolve(),
                'message' => 'جلسه مشاوره با موفقیت آغاز شد.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }

    /**
     * GET /consultations/sessions/{id}
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        /** @var ConsultationSession|null $session */
        $session = ConsultationSession::query()->with(['advisor', 'linkedService'])->find($id);

        if ($session === null) {
            throw new HttpResponseException(new JsonResponse(['status' => 404, 'detail' => 'جلسه یافت نشد.'], 404));
        }

        // Live meter estimate from server timestamp if session is active (§5.3)
        $liveDurationSeconds = $session->duration_seconds;
        $liveFeeRials = $session->total_fee_rials;

        if ($session->status->value === 'active' && $session->started_at !== null) {
            $now = CarbonImmutable::now();
            $billing = SessionBillingCalculator::calculate(
                $session->advisor,
                $session->mode,
                $session->started_at,
                $now
            );
            $liveDurationSeconds = $billing['duration_seconds'];
            $liveFeeRials = $billing['total_fee_rials'];
        }

        $data = (new ConsultationSessionResource($session))->resolve();
        $data['live_duration_seconds'] = $liveDurationSeconds;
        $data['live_fee_rials'] = $liveFeeRials;

        return new JsonResponse(['data' => $data]);
    }

    /**
     * POST /consultations/sessions/{id}/end
     */
    public function end(string $id, Request $request, EndSessionAction $action): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpResponseException(new JsonResponse(['status' => 401, 'detail' => 'احراز هویت الزامی است.'], 401));
        }

        $validated = $request->validate([
            'advisor_verdict' => ['nullable', 'string', 'max:2000'],
            'uploaded_docs_count' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $session = $action->execute(
                actor: $user,
                sessionId: $id,
                advisorVerdict: $validated['advisor_verdict'] ?? null,
                uploadedDocsCount: (int) ($validated['uploaded_docs_count'] ?? 0)
            );

            return new JsonResponse([
                'data' => (new ConsultationSessionResource($session))->resolve(),
                'message' => 'جلسه مشاوره خاتمه یافت و تسویه مالی انجام گردید.',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }
}
