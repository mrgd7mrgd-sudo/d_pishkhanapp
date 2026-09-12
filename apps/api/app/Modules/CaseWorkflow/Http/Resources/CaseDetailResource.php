<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Resources;

use App\Modules\CaseWorkflow\Application\Queries\AvailableActionsResolver;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

/**
 * @mixin CaseRequest
 */
final class CaseDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = $this->service;
        $serviceId = $this->service_id;
        $serviceTitle = '';
        $serviceCategory = 'general';
        $serviceTag = 'in-person';

        if ($service !== null) {
            $serviceId = $service->id;
            $serviceTitle = $service->title;
            $serviceCategory = $service->category_id;
            $tags = is_array($service->tags) ? $service->tags : [];
            $serviceTag = count($tags) > 0 ? (string) $tags[0] : 'in-person';
        }

        $resolver = App::make(AvailableActionsResolver::class);
        $actions = $resolver->resolve($this->resource, $request->user());

        /** @var CaseReturn|null $latestReturn */
        $latestReturn = $this->returns->last();

        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status->value,
            'turn_owner' => $this->turn_owner->value,
            'turn_owner_label' => $this->resolveTurnOwnerLabel(),
            'last_change_text' => $this->resolveLastChangeText($latestReturn),
            'service' => [
                'id' => $serviceId,
                'title' => $serviceTitle,
                'category' => $serviceCategory,
                'tag' => $serviceTag,
            ],
            'assigned_office' => $this->resolveAssignedOffice(),
            'current_step' => $this->current_step,
            'total_steps' => $this->total_steps,
            'return_reason' => $this->resolveReturnReason($latestReturn),
            'sla' => $this->resolveSla(),
            'documents' => $this->documents->map(fn (CaseDocument $doc): array => [
                'id' => $doc->id,
                'document_type_code' => $doc->document_type_code,
                'title' => $doc->documentType !== null ? $doc->documentType->title : $doc->document_type_code,
                'status' => $doc->status->value,
                'reason_code' => $doc->reason_code,
                'version' => $doc->version,
                'quality_warnings' => $doc->quality_warnings,
                'uploaded_at' => $doc->uploaded_at->toISOString(),
            ])->values()->all(),
            'timeline' => TimelineStepResource::collection($this->timelineSteps)->resolve(),
            'available_actions' => $actions,
            'realtime_channel' => 'private-case.'.$this->id,
        ];
    }

    private function resolveTurnOwnerLabel(): string
    {
        if ($this->turn_owner === TurnOwner::CITIZEN) {
            return 'نوبت شماست';
        }

        if ($this->status->value === CaseStatus::SEARCHING_OFFICE->value) {
            return 'در حال یافتن دفتر مناسب';
        }

        return $this->turn_owner->label();
    }

    private function resolveLastChangeText(?CaseReturn $latestReturn): string
    {
        if ($latestReturn !== null) {
            $officeName = $this->office !== null ? $this->office->name : 'دفتر پیشخوان دولت';

            return "{$officeName} مدرک شما را برای اصلاح بازگرداند.";
        }

        $latestStep = $this->timelineSteps->last();

        return $latestStep !== null && $latestStep->description !== null
            ? $latestStep->description
            : 'درخواست شما با موفقیت ثبت شد.';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveAssignedOffice(): ?array
    {
        if ($this->office === null) {
            return null;
        }

        $coords = null;
        if ($this->office->location !== null && is_array($this->office->location)) {
            $coords = $this->office->location;
        }

        return [
            'id' => $this->office->id,
            'name' => $this->office->name,
            'phone' => $this->office->phone ?? '',
            'rating' => (float) ($this->office->rating ?? 5.0),
            'coords' => $coords,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveReturnReason(?CaseReturn $latestReturn): ?array
    {
        if ($latestReturn === null) {
            return null;
        }

        $reason = $latestReturn->returnReason;
        $code = $latestReturn->reason_code->value;
        $title = 'نقص در مدارک';
        $message = 'لطفاً مدارک خود را مجدداً بررسی و بارگذاری نمایید.';
        $sampleImageUrl = '/img/samples/doc-blur.avif';

        if ($reason !== null) {
            $code = $reason->code;
            $title = $reason->title;
            $message = $reason->default_message;
            $sampleImageUrl = $reason->sample_image_url ?? $sampleImageUrl;
        }

        return [
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'operator_note' => $latestReturn->operator_note,
            'target_document_type_code' => $latestReturn->target_document_type_code,
            'sample_image_url' => $sampleImageUrl,
            'returned_at' => $latestReturn->created_at->toISOString(),
        ];
    }

    /**
     * @return array{current_deadline: string, remaining_seconds: int, phase: string}
     */
    private function resolveSla(): array
    {
        $deadline = $this->sla_deadline_at ?? $this->created_at->clone()->addHours(72);
        $remainingSeconds = max(0, (int) CarbonImmutable::now()->diffInSeconds($deadline, false));

        $phase = match ($this->status) {
            CaseStatus::ACTION_REQUIRED => 'citizen_fix',
            CaseStatus::SEARCHING_OFFICE => 'dispatch',
            default => 'processing',
        };

        return [
            'current_deadline' => (string) $deadline->toISOString(),
            'remaining_seconds' => $remainingSeconds,
            'phase' => $phase,
        ];
    }
}
