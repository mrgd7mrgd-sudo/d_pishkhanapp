<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Controllers;

use App\Modules\Consultation\Application\Actions\ApproveAdvisorAction;
use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Http\Resources\AdvisorResource;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class AdminAdvisorController
{
    /**
     * GET /admin/advisors/applications
     * List all applications for review by system admin.
     */
    public function applications(Request $request): JsonResponse
    {
        $this->ensureSystemAdmin($request);

        $query = Advisor::query()->with(['specialties', 'citizen'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('application_status', (string) $request->query('status'));
        }

        $perPage = min(50, max(1, $request->integer('per_page', 20)));
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
     * POST /admin/advisors/{id}/approve
     * Approve advisor application and grant advisor role (Invariant §5.3).
     */
    public function approve(string $id, Request $request, ApproveAdvisorAction $action): JsonResponse
    {
        $admin = $this->ensureSystemAdmin($request);

        try {
            $advisor = $action->execute($admin, $id);

            return new JsonResponse([
                'data' => (new AdvisorResource($advisor))->resolve(),
                'message' => 'مشاور با موفقیت تأیید شد و دسترسی‌های مربوطه به وی اعطا گردید.',
            ]);
        } catch (InvalidArgumentException $e) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422));
        }
    }

    /**
     * POST /admin/advisors/{id}/reject
     */
    public function reject(string $id, Request $request): JsonResponse
    {
        $this->ensureSystemAdmin($request);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        /** @var Advisor|null $advisor */
        $advisor = Advisor::query()->find($id);
        if ($advisor === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'مشاور یافت نشد.',
            ], 404));
        }

        $advisor->update([
            'application_status' => AdvisorApplicationStatus::Rejected,
            'is_verified' => false,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return new JsonResponse([
            'data' => (new AdvisorResource($advisor))->resolve(),
            'message' => 'درخواست مشاور رد شد.',
        ]);
    }

    private function ensureSystemAdmin(Request $request): Operator
    {
        $user = $request->user();
        if (! $user instanceof Operator || ! $user->hasRole('system_admin')) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'دسترسی فقط برای مدیر سامانه مجاز است.',
            ], 403));
        }

        return $user;
    }
}
