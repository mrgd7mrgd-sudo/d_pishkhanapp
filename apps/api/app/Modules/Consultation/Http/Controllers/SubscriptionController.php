<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Controllers;

use App\Modules\Consultation\Application\Actions\ConsumeQuotaAction;
use App\Modules\Consultation\Application\Actions\SubscribeAction;
use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Consultation\Http\Resources\SubscriptionPlanResource;
use App\Modules\Consultation\Http\Resources\SubscriptionResource;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class SubscriptionController
{
    /**
     * GET /consultations/plans
     * Public listing of active business subscription plans (§6.1, TASK-120).
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->where('is_active', true)->get();

        return new JsonResponse([
            'data' => SubscriptionPlanResource::collection($plans)->resolve(),
        ]);
    }

    /**
     * GET /consultations/my-subscription
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        /** @var Subscription|null $sub */
        $sub = Subscription::query()
            ->with(['plan', 'quotaUsages'])
            ->where('citizen_id', $user->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();

        if ($sub === null) {
            return new JsonResponse(['data' => null, 'message' => 'اشتراک فعالی برای شما یافت نشد.']);
        }

        return new JsonResponse([
            'data' => (new SubscriptionResource($sub))->resolve(),
        ]);
    }

    /**
     * POST /consultations/subscribe
     */
    public function subscribe(Request $request, SubscribeAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'تنها شهروندان می‌توانند اشتراک خریداری کنند.'], 403));
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'string'],
        ]);

        try {
            $subscription = $action->execute($user, (string) $validated['plan_id']);

            return new JsonResponse([
                'data' => (new SubscriptionResource($subscription))->resolve(),
                'message' => 'اشتراک کسب‌وکار با موفقیت فعال شد.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }

    /**
     * POST /consultations/quota/consume
     */
    public function consumeQuota(Request $request, ConsumeQuotaAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        $validated = $request->validate([
            'quota_key' => ['required', 'string'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $quota = $action->execute(
                citizen: $user,
                quotaKey: (string) $validated['quota_key'],
                amount: (int) ($validated['amount'] ?? 1)
            );

            return new JsonResponse([
                'data' => [
                    'quota_key' => $quota->quota_key,
                    'used' => (int) $quota->used,
                    'limit' => (int) $quota->limit,
                    'remaining' => max(0, (int) ($quota->limit - $quota->used)),
                ],
                'message' => 'سهمیه با موفقیت کسر شد.',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }
}
