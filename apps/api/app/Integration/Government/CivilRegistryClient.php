<?php

declare(strict_types=1);

namespace App\Integration\Government;

use App\Integration\Government\DTO\DocumentVerification;
use App\Integration\Government\DTO\PersonSummary;

/**
 * CivilRegistryClient Port (Architecture §8.5, TASK-075).
 * National civil registry client for person summary and identity verification.
 */
interface CivilRegistryClient
{
    public function getPersonSummary(string $nationalId, string $birthDate): PersonSummary;

    public function verifyBirthCertificate(string $nationalId, string $serial): DocumentVerification;
}
