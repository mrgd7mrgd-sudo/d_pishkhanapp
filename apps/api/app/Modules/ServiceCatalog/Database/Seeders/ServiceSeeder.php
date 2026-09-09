<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Database\Seeders;

use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceRequiredDoc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

final class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds (§6.1, §6.9, TASK-040).
     * Reference seeder for 73 citizen services and required documents. Idempotent.
     */
    public function run(): void
    {
        $jsonPath = App::databasePath('seeders/data/services.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $content = File::get($jsonPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        /** @var list<array{category_id: string, slug: string, title: string, description: string, tags: list<string>, requirements?: list<string>, estimated_days_min?: int, estimated_days_max?: int, fee_rials?: int, office_share_percent?: float, department?: string|null, is_popular?: bool, is_new?: bool, is_active?: bool}> $services */
        $services = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        /** @var list<string> $availableDocCodes */
        $availableDocCodes = DocumentType::query()->pluck('code')->values()->all();

        foreach ($services as $s) {
            /** @var Service $service */
            $service = Service::query()->updateOrCreate(
                ['slug' => $s['slug']],
                [
                    'category_id' => $s['category_id'],
                    'title' => $s['title'],
                    'description' => $s['description'],
                    'tags' => $s['tags'],
                    'requirements' => $s['requirements'] ?? [],
                    'estimated_days_min' => $s['estimated_days_min'] ?? 1,
                    'estimated_days_max' => $s['estimated_days_max'] ?? 3,
                    'fee_rials' => $s['fee_rials'] ?? 0,
                    'office_share_percent' => $s['office_share_percent'] ?? 70.00,
                    'department' => $s['department'] ?? null,
                    'is_popular' => $s['is_popular'] ?? false,
                    'is_new' => $s['is_new'] ?? false,
                    'is_active' => $s['is_active'] ?? true,
                ]
            );

            // Seed required documents from requirements
            $matchedCodes = $this->matchRequiredDocumentCodes($s['requirements'] ?? [], $availableDocCodes);
            $order = 1;
            foreach ($matchedCodes as $docCode) {
                ServiceRequiredDoc::query()->updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'document_type_code' => $docCode,
                    ],
                    [
                        'is_mandatory' => true,
                        'sort_order' => $order++,
                    ]
                );
            }
        }
    }

    /**
     * @param  list<string>  $requirements
     * @param  list<string>  $availableCodes
     * @return list<string>
     */
    private function matchRequiredDocumentCodes(array $requirements, array $availableCodes): array
    {
        $text = implode(' ', $requirements);
        $codes = [];

        $rules = [
            'DOC_NATIONAL_CARD' => ['ملی', 'کارت هوشمند'],
            'DOC_BIRTH_CERT' => ['شناسنامه'],
            'DOC_PERSONAL_PHOTO' => ['عکس', 'پرسنلی'],
            'DOC_POSTAL_CERT' => ['پستی', 'نشانی'],
            'DOC_DRIVER_LICENSE' => ['گواهینامه'],
            'DOC_MILITARY_SERVICE' => ['پایان خدمت', 'نظام وظیفه', 'معافیت'],
            'DOC_HEALTH_CARD' => ['بهداشت', 'کارت بهداشت'],
            'DOC_PROPERTY_DEED' => ['ملک', 'سند'],
            'DOC_TAX_STATEMENT' => ['مالیات', 'اظهارنامه'],
            'DOC_BUSINESS_LICENSE' => ['پروانه', 'جواز'],
            'DOC_CRIMINAL_CLEARANCE' => ['سجل', 'کیفری', 'سوء پیشینه'],
        ];

        foreach ($rules as $code => $keywords) {
            if (! in_array($code, $availableCodes, true)) {
                continue;
            }
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $codes[] = $code;
                    break;
                }
            }
        }

        if (empty($codes) && in_array('DOC_NATIONAL_CARD', $availableCodes, true)) {
            $codes[] = 'DOC_NATIONAL_CARD';
        }

        return array_values(array_unique($codes));
    }
}
