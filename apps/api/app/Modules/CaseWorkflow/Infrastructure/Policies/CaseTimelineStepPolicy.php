<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class CaseTimelineStepPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, CaseTimelineStep $step): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Operator || $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, CaseTimelineStep $step): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, CaseTimelineStep $step): bool
    {
        return $this->isSystemAdmin($user);
    }
}
