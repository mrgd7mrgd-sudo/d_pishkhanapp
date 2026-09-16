<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain;

use App\Modules\Identity\Domain\Models\Citizen;

final class EntityNameCollector
{
    /**
     * Collects sensitive entity text values (e.g. citizen full name, father name, address) for redaction.
     *
     * @return array<string, string> [entityText => CategoryPrefix]
     */
    public function collectFromCitizen(?Citizen $citizen): array
    {
        if (! $citizen) {
            return [];
        }

        $entities = [];

        if (! empty($citizen->full_name)) {
            $entities[trim((string) $citizen->full_name)] = 'NAME';
        }

        if (! empty($citizen->father_name)) {
            $entities[trim((string) $citizen->father_name)] = 'FATHER_NAME';
        }

        if (! empty($citizen->address)) {
            $entities[trim((string) $citizen->address)] = 'ADDRESS';
        }

        return $entities;
    }

    /**
     * Collect additional custom known entity names.
     *
     * @param  array<string>  $names
     * @return array<string, string>
     */
    public function collectCustomNames(array $names): array
    {
        $entities = [];
        foreach ($names as $name) {
            $trimmed = trim($name);
            if ($trimmed !== '') {
                $entities[$trimmed] = 'NAME';
            }
        }

        return $entities;
    }
}
