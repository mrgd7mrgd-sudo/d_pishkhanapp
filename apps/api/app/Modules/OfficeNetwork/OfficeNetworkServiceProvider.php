<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Infrastructure\Policies\OfficePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class OfficeNetworkServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Office::class, OfficePolicy::class);
    }
}
