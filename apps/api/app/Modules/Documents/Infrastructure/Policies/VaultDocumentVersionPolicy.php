<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Policies;

use App\Modules\Documents\Domain\Models\VaultDocumentVersion;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class VaultDocumentVersionPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, VaultDocumentVersion $version): bool
    {
        return $user instanceof Citizen && $version->document?->citizen_id === $user->getAuthIdentifier();
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, VaultDocumentVersion $version): bool
    {
        return false; // Versions are strictly immutable
    }

    public function delete(?Authenticatable $user, VaultDocumentVersion $version): bool
    {
        return false; // Versions are strictly immutable
    }
}
