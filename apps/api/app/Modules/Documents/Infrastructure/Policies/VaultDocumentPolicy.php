<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Policies;

use App\Modules\Documents\Domain\Models\VaultDocument;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class VaultDocumentPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, VaultDocument $doc): bool
    {
        return $user instanceof Citizen && $doc->citizen_id === $user->getAuthIdentifier();
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, VaultDocument $doc): bool
    {
        return $user instanceof Citizen && $doc->citizen_id === $user->getAuthIdentifier();
    }

    public function delete(?Authenticatable $user, VaultDocument $doc): bool
    {
        return $user instanceof Citizen && $doc->citizen_id === $user->getAuthIdentifier();
    }
}
