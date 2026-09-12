<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseDocumentPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseRequestPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseTimelineStepPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CaseWorkflowServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(CaseRequest::class, CaseRequestPolicy::class);
        Gate::policy(CaseTimelineStep::class, CaseTimelineStepPolicy::class);
        Gate::policy(CaseDocument::class, CaseDocumentPolicy::class);
    }
}
