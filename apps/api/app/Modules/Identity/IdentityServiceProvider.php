<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Modules\Identity\Infrastructure\Policies\CitizenPolicy;
use App\Modules\Identity\Infrastructure\Policies\OperatorPolicy;
use App\Modules\Identity\Infrastructure\Policies\OtpChallengePolicy;
use App\Modules\Identity\Listeners\AuditAuthEventsListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Citizen::class, CitizenPolicy::class);
        Gate::policy(Operator::class, OperatorPolicy::class);
        Gate::policy(OtpChallenge::class, OtpChallengePolicy::class);

        Event::subscribe(AuditAuthEventsListener::class);
    }
}
