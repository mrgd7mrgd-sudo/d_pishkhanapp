<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\QuotaUsage;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class QuotaUsagePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, QuotaUsage $usage): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $sub = $usage->subscription;

        return $user instanceof Citizen && $sub !== null && $sub->citizen_id === $user->id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, QuotaUsage $usage): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, QuotaUsage $usage): bool
    {
        return $this->isSystemAdmin($user);
    }
}
