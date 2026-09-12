<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class ReturnReasonPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, ReturnReason $reason): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, ReturnReason $reason): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, ReturnReason $reason): bool
    {
        return $this->isSystemAdmin($user);
    }
}
