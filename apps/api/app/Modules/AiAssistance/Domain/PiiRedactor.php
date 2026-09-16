<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain;

use App\Modules\Identity\Domain\Rules\ValidIranianNationalId;

/**
 * PiiRedactor: Mandatory PII anonymization layer (§8.1.3, §7.7, TASK-110).
 *
 * Replaces National ID, Mobile, Postal Code, Bank Card, IBAN, Tracking Code,
 * and known Persian entities with opaque tokens before sending prompt to AI.
 */
final class PiiRedactor
{
    private const PATTERNS = [
        'MOBILE' => '/\b09\d{9}\b/u',
        'CARD' => '/\b(?:\d[ -]?){15}\d\b/u',
        'IBAN' => '/\bIR\d{24}\b/iu',
        'TRACKING' => '/\b(?:CR|PK)-\d{4}-\d{5}\b/u',
    ];

    /**
     * Redacts text by replacing sensitive PII with tokens.
     *
     * @param  array<string, string>  $knownEntities  [entityText => tokenPrefix]
     */
    public function redact(string $text, RedactionMap $map, array $knownEntities = []): string
    {
        $result = $text;

        // 1. First, replace known database entities (full names, father names, addresses)
        // Sort descending by length to prevent partial sub-string collisions
        uksort($knownEntities, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($knownEntities as $entity => $prefix) {
            $trimmed = trim($entity);
            if ($trimmed === '' || mb_strlen($trimmed) < 3) {
                continue;
            }

            if (str_contains($result, $trimmed)) {
                $token = $map->getOrAssignToken($prefix, $trimmed);
                $result = str_replace($trimmed, $token, $result);
            }
        }

        // 2. Redact regex-based structured patterns (Mobile, Card, IBAN, Tracking)
        foreach (self::PATTERNS as $prefix => $pattern) {
            $result = (string) preg_replace_callback($pattern, function (array $matches) use ($map, $prefix): string {
                $raw = $matches[0];

                return $map->getOrAssignToken($prefix, $raw);
            }, $result);
        }

        // 3. Disambiguate 10-digit codes: National ID (valid checksum) vs Postal Code (§8.1.3)
        // Matches 10 contiguous digits, or 5-5 digits with optional space/hyphen
        $tenDigitPattern = '/\b\d{5}[-\s]?\d{5}\b/u';
        $result = (string) preg_replace_callback($tenDigitPattern, function (array $matches) use ($map): string {
            $raw = $matches[0];
            $clean = preg_replace('/\D/', '', $raw) ?? '';

            if (strlen($clean) === 10) {
                if (ValidIranianNationalId::isValid($clean)) {
                    return $map->getOrAssignToken('NID', $raw);
                }

                return $map->getOrAssignToken('POSTAL', $raw);
            }

            return $raw;
        }, $result);

        return $result;
    }

    /**
     * Restores redacted tokens back to original values in the response (Server-side only).
     */
    public function restore(string $text, RedactionMap $map): string
    {
        $result = $text;

        foreach ($map->getMap() as $token => $originalValue) {
            $result = str_replace($token, $originalValue, $result);
        }

        return $result;
    }
}
