<?php

declare(strict_types=1);

namespace App\Modules\Consultation;

use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\AdvisorReview;
use App\Modules\Consultation\Domain\Models\AdvisorSpecialty;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorPolicy;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorReviewPolicy;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorSpecialtyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ConsultationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings for Consultation module
    }

    public function boot(): void
    {
        Gate::policy(Advisor::class, AdvisorPolicy::class);
        Gate::policy(AdvisorSpecialty::class, AdvisorSpecialtyPolicy::class);
        Gate::policy(AdvisorReview::class, AdvisorReviewPolicy::class);
    }
}
