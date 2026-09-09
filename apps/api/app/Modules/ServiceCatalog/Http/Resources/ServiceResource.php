<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Resources;

use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceRequiredDoc;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
final class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array matching Architecture §5.6 Example 3 (TASK-041).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->category;
        $tags = is_array($this->tags) ? $this->tags : [];

        $minDays = (int) $this->estimated_days_min;
        $maxDays = (int) $this->estimated_days_max;
        $daysLabel = $minDays === $maxDays ? "{$minDays} روز کاری" : "{$minDays} تا {$maxDays} روز کاری";

        $requiresInPerson = in_array('in-person', $tags, true);
        $supportsDelivery = in_array('online', $tags, true) || in_array('semi-online', $tags, true);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => [
                'id' => $category !== null ? $category->id : $this->category_id,
                'title' => $category !== null ? $category->title : '',
                'color' => ($category !== null && $category->color !== null) ? $category->color : '#3b82f6',
                'icon' => ($category !== null && $category->icon_name !== null) ? $category->icon_name : 'Document',
            ],
            'tags' => $tags,
            'description' => $this->description,
            'requirements' => $this->requirements ?? [],
            'required_documents' => $this->formatRequiredDocuments(),
            'estimated_days' => [
                'min' => $minDays,
                'max' => $maxDays,
                'label' => $daysLabel,
            ],
            'fee_rials' => (int) $this->fee_rials,
            'department' => $this->department,
            'is_popular' => (bool) $this->is_popular,
            'is_new' => (bool) $this->is_new,
            'image' => [
                'avif' => "/img/services/{$this->slug}-640.avif",
                'webp' => "/img/services/{$this->slug}-640.webp",
                'width' => 640,
                'height' => 360,
                'blurhash' => 'L6PZfSi_.AyE_3t7t7R**0o#DgR4',
            ],
            'requires_in_person' => $requiresInPerson,
            'supports_delivery' => $supportsDelivery,
        ];
    }

    /**
     * @return list<array{code: string, title: string, is_mandatory: bool, accepts: list<string>}>
     */
    private function formatRequiredDocuments(): array
    {
        $docs = $this->relationLoaded('requiredDocs')
            ? $this->requiredDocs
            : $this->requiredDocs()->with('documentType')->get();

        $result = [];

        /** @var ServiceRequiredDoc $doc */
        foreach ($docs as $doc) {
            $docType = $doc->documentType;
            $result[] = [
                'code' => $doc->document_type_code,
                'title' => $docType !== null ? $docType->title : $doc->document_type_code,
                'is_mandatory' => (bool) $doc->is_mandatory,
                'accepts' => $docType !== null ? $docType->accepted_mimes : ['image/jpeg', 'image/png', 'application/pdf'],
            ];
        }

        return $result;
    }
}
