<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DelegationUsed Event (Architecture §7.1 T4, §7.3, TASK-122).
 * Dispatched whenever a delegate performs an authorized operation on behalf of a principal.
 */
final class DelegationUsed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Delegation $delegation,
        public readonly Citizen $delegate,
        public readonly ?CaseRequest $case = null,
        public readonly string $action = 'case_created'
    ) {}
}
