<?php

declare(strict_types=1);

namespace App\Modules\Consultation;

use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\AdvisorReview;
use App\Modules\Consultation\Domain\Models\AdvisorSpecialty;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Consultation\Domain\Models\QuotaUsage;
use App\Modules\Consultation\Domain\Models\SessionMessage;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorPolicy;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorReviewPolicy;
use App\Modules\Consultation\Infrastructure\Policies\AdvisorSpecialtyPolicy;
use App\Modules\Consultation\Infrastructure\Policies\ConsultationSessionPolicy;
use App\Modules\Consultation\Infrastructure\Policies\QuotaUsagePolicy;
use App\Modules\Consultation\Infrastructure\Policies\SessionMessagePolicy;
use App\Modules\Consultation\Infrastructure\Policies\SubscriptionPlanPolicy;
use App\Modules\Consultation\Infrastructure\Policies\SubscriptionPolicy;
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
        Gate::policy(ConsultationSession::class, ConsultationSessionPolicy::class);
        Gate::policy(SessionMessage::class, SessionMessagePolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(QuotaUsage::class, QuotaUsagePolicy::class);
    }
}
