<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\OfficeNetwork\Database\Seeders\OfficeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database (§6.9, TASK-040).
     */
    public function run(): void
    {
        // Reference seeders (executed in production as well)
        $this->call([
            RoleSeeder::class,
            ProvinceSeeder::class,
            ServiceCategorySeeder::class,
            DocumentTypeSeeder::class,
            ServiceSeeder::class,
            OfficeSeeder::class,
        ]);

        // Demo seeders (strictly non-production)
        if (! app()->isProduction()) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
