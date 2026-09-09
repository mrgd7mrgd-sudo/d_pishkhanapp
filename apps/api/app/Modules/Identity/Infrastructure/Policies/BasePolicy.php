<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Optional pre-authorization hook.
     */
    public function before(?Authenticatable $user, string $ability): ?bool
    {
        return null;
    }

    protected function isCitizen(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    protected function isOperator(?Authenticatable $user): bool
    {
        return $user instanceof Operator;
    }

    protected function isSystemAdmin(?Authenticatable $user): bool
    {
        return $user instanceof Operator && $user->hasRole('system_admin');
    }

    protected function isAuditor(?Authenticatable $user): bool
    {
        return $user instanceof Operator && $user->hasRole('auditor');
    }
}
