<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Database\Seeders;

use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

final class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds (§6.1, §6.9, TASK-040).
     * Reference seeder for 10 service categories. Idempotent.
     */
    public function run(): void
    {
        $jsonPath = App::databasePath('seeders/data/service_categories.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $content = File::get($jsonPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        /** @var list<array{id: string, title: string, short_title?: string|null, icon_name?: string|null, color?: string|null, badge?: string|null, description?: string|null, sort_order?: int, is_active?: bool}> $categories */
        $categories = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        foreach ($categories as $cat) {
            ServiceCategory::query()->updateOrCreate(
                ['id' => $cat['id']],
                [
                    'title' => $cat['title'],
                    'short_title' => $cat['short_title'] ?? null,
                    'icon_name' => $cat['icon_name'] ?? null,
                    'color' => $cat['color'] ?? null,
                    'badge' => $cat['badge'] ?? null,
                    'description' => $cat['description'] ?? null,
                    'sort_order' => $cat['sort_order'] ?? 0,
                    'is_active' => $cat['is_active'] ?? true,
                ]
            );
        }
    }
}
