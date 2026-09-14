<?php

declare(strict_types=1);

namespace App\Modules\Messaging;

use App\Modules\Messaging\Domain\Models\CaseMessage;
use App\Modules\Messaging\Domain\Models\Notification;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use App\Modules\Messaging\Infrastructure\Policies\CaseMessagePolicy;
use App\Modules\Messaging\Infrastructure\Policies\NotificationPolicy;
use App\Modules\Messaging\Infrastructure\Policies\NotificationPreferencePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(CaseMessage::class, CaseMessagePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(NotificationPreference::class, NotificationPreferencePolicy::class);
    }
}
