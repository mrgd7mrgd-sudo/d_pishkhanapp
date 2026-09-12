<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\CaseWorkflow\Domain\Models\GovInquiry;
use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseDocumentPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseRequestPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseReturnPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\CaseTimelineStepPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\GovInquiryPolicy;
use App\Modules\CaseWorkflow\Infrastructure\Policies\ReturnReasonPolicy;
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
        Gate::policy(ReturnReason::class, ReturnReasonPolicy::class);
        Gate::policy(CaseReturn::class, CaseReturnPolicy::class);
        Gate::policy(GovInquiry::class, GovInquiryPolicy::class);
    }
}
