<?php

declare(strict_types=1);

namespace App\Integration\Government;

use App\Integration\Government\DTO\ShahkarResult;

/**
 * IdentityVerifier Port (Architecture §8.5, TASK-075).
 * Shahkar verification for mobile and national ID matching.
 */
interface IdentityVerifier
{
    public function verifyMobileOwnership(string $nationalId, string $mobile): ShahkarResult;
}
