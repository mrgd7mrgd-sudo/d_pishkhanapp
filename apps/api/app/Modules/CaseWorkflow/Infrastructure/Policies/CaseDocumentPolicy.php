<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\Identity\Domain\DelegationAuthorizer;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class CaseDocumentPolicy extends BasePolicy
{
    public function __construct(
        private readonly DelegationAuthorizer $delegations,
    ) {}

    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, CaseDocument $doc): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $case = $doc->caseRequest ?? $doc->case;
        if ($case === null) {
            return false;
        }

        if ($user instanceof Citizen) {
            if ($user->id === $case->citizen_id) {
                return true;
            }

            return $this->delegations->isAuthorizedFor($user, $case->citizen_id, $case->service_id);
        }

        if ($user instanceof Operator) {
            return true;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function update(?Authenticatable $user, CaseDocument $doc): bool
    {
        return $user instanceof Operator || $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, CaseDocument $doc): bool
    {
        return $this->isSystemAdmin($user);
    }
}
