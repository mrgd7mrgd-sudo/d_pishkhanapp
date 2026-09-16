<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\ActivateDelegationAction;
use App\Modules\Identity\Application\Actions\CreateDelegationAction;
use App\Modules\Identity\Application\Actions\RevokeDelegationAction;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Modules\Identity\Http\Resources\DelegationResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class DelegationController
{
    /**
     * GET /profile/delegations
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        $delegationsGiven = Delegation::query()
            ->with(['delegate'])
            ->where('principal_citizen_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $delegationsReceived = Delegation::query()
            ->with(['principal'])
            ->where('delegate_citizen_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return new JsonResponse([
            'data' => [
                'given' => DelegationResource::collection($delegationsGiven)->resolve(),
                'received' => DelegationResource::collection($delegationsReceived)->resolve(),
            ],
        ]);
    }

    /**
     * POST /profile/delegations
     */
    public function store(Request $request, CreateDelegationAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        $validated = $request->validate([
            'delegate_national_id_or_mobile' => ['required', 'string'],
            'valid_until' => ['required', 'date', 'after:now'],
            'max_amount_rials' => ['required', 'integer', 'min:1'],
            'allowed_service_ids' => ['nullable', 'array'],
            'allowed_service_ids.*' => ['string'],
            'document_number' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $result = $action->execute(
                principal: $user,
                delegateNationalIdOrMobile: (string) $validated['delegate_national_id_or_mobile'],
                validUntil: CarbonImmutable::parse((string) $validated['valid_until']),
                maxAmountRials: (int) $validated['max_amount_rials'],
                allowedServiceIds: $validated['allowed_service_ids'] ?? null,
                documentNumber: $validated['document_number'] ?? null
            );

            return new JsonResponse([
                'data' => (new DelegationResource($result['delegation']))->resolve(),
                // OTP codes are delivered to each party via SMS (TASK-121).
                // Only the automated testing environment may observe them — never staging/production.
                'meta' => app()->environment('testing') ? [
                    'principal_otp' => $result['principal_otp'],
                    'delegate_otp' => $result['delegate_otp'],
                ] : null,
                'message' => 'قرارداد نمایندگی ثبت شد و کدهای تایید پیامکی برای طرفین ارسال گردید.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }

    /**
     * POST /profile/delegations/{id}/activate
     */
    public function activate(string $id, Request $request, ActivateDelegationAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $delegation = $action->execute($user, $id, (string) $validated['otp_code']);

            return new JsonResponse([
                'data' => (new DelegationResource($delegation))->resolve(),
                'message' => $delegation->status->value === 'active'
                    ? 'نمایندگی با تایید دوطرفه با موفقیت فعال گردید.'
                    : 'کد تایید شما ثبت شد؛ پس از تایید طرف مقابل نمایندگی فعال خواهد شد.',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }

    /**
     * POST /profile/delegations/{id}/revoke
     */
    public function revoke(string $id, Request $request, RevokeDelegationAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse(['status' => 403, 'detail' => 'دسترسی غیرمجاز.'], 403));
        }

        try {
            $delegation = $action->execute($user, $id);

            return new JsonResponse([
                'data' => (new DelegationResource($delegation))->resolve(),
                'message' => 'نمایندگی با موفقیت ابطال گردید.',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }
}
