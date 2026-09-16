<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficeReviewPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, OfficeReview $review): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function reply(?Authenticatable $user, OfficeReview $review): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $isManager = $user->hasRole('office_manager') || $user->role === OperatorRole::MANAGER;

        return $isManager && $user->office_id === $review->office_id;
    }

    public function update(?Authenticatable $user, OfficeReview $review): bool
    {
        return $this->reply($user, $review);
    }

    public function delete(?Authenticatable $user, OfficeReview $review): bool
    {
        return $this->isSystemAdmin($user);
    }
}
