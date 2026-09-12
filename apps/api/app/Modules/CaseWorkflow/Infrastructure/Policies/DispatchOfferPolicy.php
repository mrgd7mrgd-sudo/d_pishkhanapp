<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class DispatchOfferPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Operator || $this->isSystemAdmin($user);
    }

    public function view(?Authenticatable $user, DispatchOffer $offer): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Operator && $user->office_id === $offer->office_id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, DispatchOffer $offer): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Operator && $user->office_id === $offer->office_id;
    }

    public function delete(?Authenticatable $user, DispatchOffer $offer): bool
    {
        return $this->isSystemAdmin($user);
    }
}
