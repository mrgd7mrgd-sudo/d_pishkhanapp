<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Controllers;

use App\Modules\CaseWorkflow\Application\Actions\AcceptOfferAction;
use App\Modules\CaseWorkflow\Application\Actions\DeclineOfferAction;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\Identity\Domain\Models\Operator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OfferController (Architecture §5.8, §5.5, §11, TASK-069).
 * Operator desk offer management: listing active offers, competitive acceptance, and decline.
 */
final class OfferController
{
    /**
     * GET /desk/offers
     * List active pending offers for the authenticated operator's office.
     */
    public function index(Request $request): JsonResponse
    {
        $operator = $request->user();
        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        $now = CarbonImmutable::now();

        $offers = DispatchOffer::query()
            ->with(['case'])
            ->where('office_id', $operator->office_id)
            ->where('status', DispatchOfferStatus::PENDING->value)
            ->where('expires_at', '>', $now)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DispatchOffer $offer) use ($now): array {
                $case = $offer->case;
                $remainingSeconds = max(0, $now->diffInSeconds($offer->expires_at, false));

                return [
                    'id' => $offer->id,
                    'case_id' => $offer->case_id,
                    'tracking_code' => $case?->tracking_code,
                    'service_id' => $case?->service_id,
                    'province_code' => $case?->province_code,
                    'city' => $case?->city,
                    'round' => $offer->round,
                    'status' => $offer->status->value,
                    'created_at' => $offer->created_at->toIso8601String(),
                    'expires_at' => $offer->expires_at->toIso8601String(),
                    'remaining_seconds' => $remainingSeconds,
                ];
            });

        return new JsonResponse([
            'data' => $offers,
        ]);
    }

    /**
     * POST /offers/{id}/accept
     * Competitive acceptance of dispatch offer.
     */
    public function accept(string $id, Request $request, AcceptOfferAction $action): JsonResponse
    {
        $operator = $request->user();
        if (! $operator instanceof Operator) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        /** @var DispatchOffer|null $offer */
        $offer = DispatchOffer::query()->find($id);
        if ($offer === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'پیشنهاد یافت نشد.',
            ], 404));
        }

        $result = $action->execute($offer, $operator);

        return new JsonResponse([
            'data' => $result,
            'message' => 'پیشنهاد با موفقیت پذیرفته شد.',
        ]);
    }

    /**
     * POST /offers/{id}/decline
     * Decline of dispatch offer.
     */
    public function decline(string $id, Request $request, DeclineOfferAction $action): JsonResponse
    {
        $operator = $request->user();
        if (! $operator instanceof Operator) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        /** @var DispatchOffer|null $offer */
        $offer = DispatchOffer::query()->find($id);
        if ($offer === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'پیشنهاد یافت نشد.',
            ], 404));
        }

        $result = $action->execute($offer, $operator);

        return new JsonResponse([
            'data' => $result,
            'message' => 'پیشنهاد رد شد.',
        ]);
    }
}
