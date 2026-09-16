<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class CaseRequestPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, CaseRequest $case): bool
    {
        if ($user instanceof Citizen) {
            if ($user->id === $case->citizen_id) {
                return true;
            }

            return \App\Modules\Identity\Domain\Models\Delegation::query()
                ->where('principal_citizen_id', $case->citizen_id)
                ->where('delegate_citizen_id', $user->id)
                ->where('status', \App\Modules\Identity\Domain\Enums\DelegationStatus::Active)
                ->where('valid_until', '>', \Carbon\CarbonImmutable::now())
                ->get()
                ->contains(fn (\App\Modules\Identity\Domain\Models\Delegation $d) => $d->isServiceAllowed($case->service_id));
        }

        if ($user instanceof Operator) {
            if ($this->isSystemAdmin($user) || $user->hasRole('auditor')) {
                return true;
            }

            return $case->office_id !== null && $user->office_id === $case->office_id;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, CaseRequest $case): bool
    {
        if ($user instanceof Citizen) {
            return $user->id === $case->citizen_id;
        }

        if ($user instanceof Operator) {
            if ($this->isSystemAdmin($user)) {
                return true;
            }

            return $case->office_id !== null && $user->office_id === $case->office_id;
        }

        return false;
    }

    public function delete(?Authenticatable $user, CaseRequest $case): bool
    {
        return $this->isSystemAdmin($user);
    }
}
