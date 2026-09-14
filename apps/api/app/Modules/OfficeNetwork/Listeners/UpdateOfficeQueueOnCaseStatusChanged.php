<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Listeners;

use App\Modules\CaseWorkflow\Domain\Events\CaseStatusChanged;
use App\Modules\OfficeNetwork\Infrastructure\Cache\OfficeQueueCache;

final class UpdateOfficeQueueOnCaseStatusChanged
{
    public function __construct(
        private readonly OfficeQueueCache $queueCache
    ) {}

    public function handle(CaseStatusChanged $event): void
    {
        $officeId = $event->case->office_id;

        if ($officeId !== null) {
            $this->queueCache->calculate($officeId);
        }
    }
}
