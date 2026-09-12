<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CaseCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly CaseRequest $case
    ) {}
}
