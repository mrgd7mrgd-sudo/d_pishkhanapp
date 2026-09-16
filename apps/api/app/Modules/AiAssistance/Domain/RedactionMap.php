<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain;

final class RedactionMap
{
    /**
     * Maps placeholder tokens to original values: ['[NID_1]' => '0012345678']
     *
     * @var array<string, string>
     */
    private array $forwardMap = [];

    /**
     * Maps original values to placeholder tokens: ['0012345678' => '[NID_1]']
     *
     * @var array<string, string>
     */
    private array $reverseMap = [];

    /**
     * Counter per category prefix (e.g. NID => 1, MOBILE => 1)
     *
     * @var array<string, int>
     */
    private array $counters = [];

    public function getOrAssignToken(string $prefix, string $originalValue): string
    {
        $normalized = trim($originalValue);

        if (isset($this->reverseMap[$normalized])) {
            return $this->reverseMap[$normalized];
        }

        $counter = ($this->counters[$prefix] ?? 0) + 1;
        $this->counters[$prefix] = $counter;

        $token = "[{$prefix}_{$counter}]";
        $this->forwardMap[$token] = $normalized;
        $this->reverseMap[$normalized] = $token;

        return $token;
    }

    public function restoreToken(string $token): ?string
    {
        return $this->forwardMap[$token] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getMap(): array
    {
        return $this->forwardMap;
    }

    public function isEmpty(): bool
    {
        return empty($this->forwardMap);
    }
}
