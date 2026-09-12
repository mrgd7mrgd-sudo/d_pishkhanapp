<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Controllers;

use App\Modules\CaseWorkflow\Application\Actions\CreateCaseAction;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\CaseWorkflow\Http\Requests\CreateCaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class CaseController
{
    public function store(CreateCaseRequest $request, CreateCaseAction $action): JsonResponse
    {
        Gate::authorize('create', CaseRequest::class);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var array{
         *     service_id: string,
         *     dispatch_mode: string,
         *     office_id?: string|null,
         *     payment_method: string,
         *     delivery_preference: string,
         *     delivery_address_id?: string|null,
         *     on_behalf_of_delegation_id?: string|null,
         *     documents?: list<array{document_type_code: string, upload_id: string}>,
         *     commitment_signed: bool,
         *     citizen_location?: array{lat: float|int, lng: float|int}|null
         * } $validated */
        $validated = $request->validated();

        $case = $action->execute($citizen, $validated);

        return new JsonResponse([
            'data' => $this->formatCaseCreationResponse($case),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCaseCreationResponse(CaseRequest $case): array
    {
        $service = $case->service;
        $serviceTitle = '';
        $serviceId = $case->service_id;
        $serviceTag = 'in-person';
        $estimatedDays = 14;

        if ($service !== null) {
            $serviceId = $service->id;
            $serviceTitle = $service->title;
            $tags = is_array($service->tags) ? $service->tags : [];
            $serviceTag = count($tags) > 0 ? (string) $tags[0] : 'in-person';
            if ($service->estimated_days_max > 0) {
                $estimatedDays = (int) $service->estimated_days_max;
            }
        }

        $estimatedCompletion = $case->created_at->clone()->addDays($estimatedDays)->startOfDay()->toISOString();
        $deadline = $case->sla_deadline_at ?? $case->created_at->clone()->addSeconds(90);

        return [
            'id' => $case->id,
            'tracking_code' => $case->tracking_code,
            'status' => $case->status->value,
            'turn_owner' => $case->turn_owner->value,
            'turn_owner_label' => $case->status->value === CaseStatus::SEARCHING_OFFICE->value
                ? 'در حال یافتن دفتر مناسب'
                : $case->turn_owner->label(),
            'service' => [
                'id' => $serviceId,
                'title' => $serviceTitle,
                'tag' => $serviceTag,
            ],
            'assigned_office' => null,
            'current_step' => $case->current_step,
            'total_steps' => $case->total_steps,
            'fee_paid_rials' => $case->fee_paid_rials,
            'office_share_rials' => $case->office_share_rials,
            'platform_share_rials' => $case->platform_share_rials,
            'created_at' => $case->created_at->toISOString(),
            'estimated_completion' => $estimatedCompletion,
            'sla' => [
                'current_deadline' => $deadline->toISOString(),
                'phase' => 'dispatch',
            ],
            'timeline' => $case->timelineSteps->map(fn (CaseTimelineStep $step): array => [
                'id' => $step->id,
                'title' => $step->title,
                'description' => $step->description,
                'status' => $step->status->value,
                'turn_owner' => $step->turn_owner->value,
                'turn_owner_label' => $step->turn_owner_label,
                'occurred_at' => $step->occurred_at->toISOString(),
                'duration_typical_minutes' => $step->duration_typical_minutes,
            ])->values()->all(),
            'realtime_channel' => 'private-case.'.$case->id,
        ];
    }
}
