<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Contracts\Auth\Authenticatable;

final class OperatorPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        if ($user instanceof Operator) {
            return $user->hasAnyRole(['system_admin', 'auditor', 'office_manager']);
        }

        return false;
    }

    public function view(?Authenticatable $user, Operator $operator): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($user->id === $operator->id) {
            return true;
        }

        if ($user->hasRole('office_manager') && $user->office_id !== null && $user->office_id === $operator->office_id) {
            return true;
        }

        return $user->hasAnyRole(['system_admin', 'auditor']);
    }

    public function create(?Authenticatable $user): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        return $user->hasAnyRole(['office_manager', 'system_admin']);
    }

    public function update(?Authenticatable $user, Operator $operator): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($user->id === $operator->id) {
            return true;
        }

        if ($user->hasRole('office_manager') && $user->office_id !== null && $user->office_id === $operator->office_id) {
            return true;
        }

        return $user->hasRole('system_admin');
    }

    public function delete(?Authenticatable $user, Operator $operator): bool
    {
        return $this->isSystemAdmin($user);
    }
}
