<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Database\Seeders;

use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

final class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds (§6.1, §6.9, TASK-040).
     * Reference seeder for 25 document types. Idempotent.
     */
    public function run(): void
    {
        $jsonPath = App::databasePath('seeders/data/document_types.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $content = File::get($jsonPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        /** @var list<array{code: string, title: string, description?: string|null, accepted_mimes: list<string>, requires_original?: bool, validity_months?: int|null, is_active?: bool}> $documentTypes */
        $documentTypes = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        foreach ($documentTypes as $doc) {
            DocumentType::query()->updateOrCreate(
                ['code' => $doc['code']],
                [
                    'title' => $doc['title'],
                    'description' => $doc['description'] ?? null,
                    'accepted_mimes' => $doc['accepted_mimes'],
                    'requires_original' => $doc['requires_original'] ?? false,
                    'validity_months' => $doc['validity_months'] ?? null,
                    'is_active' => $doc['is_active'] ?? true,
                ]
            );
        }
    }
}
