<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CaseWorkflowServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(CaseRequest::class, CaseRequestPolicy::class);
    }
}
