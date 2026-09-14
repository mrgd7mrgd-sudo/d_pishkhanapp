<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork;

use App\Modules\CaseWorkflow\Domain\Events\CaseStatusChanged;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeAnnouncement;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficeAnnouncementPolicy;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficeMedalPolicy;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficePolicy;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficeServiceCoveragePolicy;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficeSpecialtyPolicy;
use App\Modules\OfficeNetwork\Listeners\UpdateOfficeQueueOnCaseStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class OfficeNetworkServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(OfficeServiceCoverage::class, OfficeServiceCoveragePolicy::class);
        Gate::policy(OfficeSpecialty::class, OfficeSpecialtyPolicy::class);
        Gate::policy(OfficeMedal::class, OfficeMedalPolicy::class);
        Gate::policy(OfficeAnnouncement::class, OfficeAnnouncementPolicy::class);

        Event::listen(
            CaseStatusChanged::class,
            UpdateOfficeQueueOnCaseStatusChanged::class
        );
    }
}
