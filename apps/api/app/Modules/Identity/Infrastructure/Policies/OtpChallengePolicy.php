<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\OtpChallenge;
use Illuminate\Contracts\Auth\Authenticatable;

final class OtpChallengePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user) || $this->isAuditor($user);
    }

    public function view(?Authenticatable $user, OtpChallenge $otpChallenge): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, OtpChallenge $otpChallenge): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, OtpChallenge $otpChallenge): bool
    {
        return $this->isSystemAdmin($user);
    }
}
