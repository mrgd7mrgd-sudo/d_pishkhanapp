<?php

declare(strict_types=1);

namespace App\Modules\Delivery;

use App\Modules\Delivery\Console\Commands\SyncPostTrackingCommand;
use App\Modules\Delivery\Domain\Models\DeliveryEvent;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Delivery\Infrastructure\Policies\DeliveryEventPolicy;
use App\Modules\Delivery\Infrastructure\Policies\DeliveryRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class DeliveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Service bindings for Delivery module
    }

    public function boot(): void
    {
        Gate::policy(DeliveryRequest::class, DeliveryRequestPolicy::class);
        Gate::policy(DeliveryEvent::class, DeliveryEventPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPostTrackingCommand::class,
            ]);
        }
    }
}
