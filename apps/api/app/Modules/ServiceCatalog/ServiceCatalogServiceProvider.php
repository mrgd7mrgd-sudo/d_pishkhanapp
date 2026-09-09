<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog;

use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Modules\ServiceCatalog\Domain\Models\ServiceRequiredDoc;
use App\Modules\ServiceCatalog\Infrastructure\Policies\DocumentTypePolicy;
use App\Modules\ServiceCatalog\Infrastructure\Policies\ServiceCategoryPolicy;
use App\Modules\ServiceCatalog\Infrastructure\Policies\ServicePolicy;
use App\Modules\ServiceCatalog\Infrastructure\Policies\ServiceRequiredDocPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ServiceCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Module service bindings
    }

    public function boot(): void
    {
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(DocumentType::class, DocumentTypePolicy::class);
        Gate::policy(ServiceRequiredDoc::class, ServiceRequiredDocPolicy::class);
    }
}
