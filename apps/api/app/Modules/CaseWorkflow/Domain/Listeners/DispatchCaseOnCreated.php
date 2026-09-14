<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Listeners;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Events\CaseCreated;
use App\Modules\CaseWorkflow\Jobs\DispatchCaseJob;

final class DispatchCaseOnCreated
{
    public function handle(CaseCreated $event): void
    {
        if ($event->case->status === CaseStatus::SEARCHING_OFFICE && $event->case->office_id === null) {
            DispatchCaseJob::dispatch($event->case->id, 1);
        }
    }
}
