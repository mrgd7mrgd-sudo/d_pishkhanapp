<?php

declare(strict_types=1);

namespace App\Shared\Text;

/**
 * Persian Text Normalizer (Architecture §6.3, §6.4, TASK-038).
 * Ensures identical text processing across backend and frontend (mirrored in packages/domain/src/persian-text.ts).
 */
final class PersianNormalizer
{
    private const ARABIC_YEH = ['ي', 'ى', 'ئ'];

    private const PERSIAN_YEH = 'ی';

    private const ARABIC_KAF = ['ك', 'ڪ'];

    private const PERSIAN_KAF = 'ک';

    private const ARABIC_TEH_MARBUTA = 'ة';

    private const PERSIAN_HEH = 'ه';

    private const ALEF_VARIANTS = ['أ', 'إ', 'آ'];

    private const PERSIAN_ALEF = 'ا';

    /**
     * Arabic/Persian diacritics (harakat/tashkeel) Unicode points.
     */
    private const DIACRITICS_REGEX = '/[\x{064B}-\x{0655}\x{0670}]/u';

    /**
     * Zero-width non-joiner and directional markers.
     */
    private const ZWNJ_REGEX = '/[\x{200C}\x{200D}\x{200E}\x{200F}\x{FEFF}]/u';

    /**
     * Normalize Persian text:
     * - Unify Yeh ('ي'/'ى' -> 'ی')
     * - Unify Kaf ('ك' -> 'ک')
     * - Convert Teh Marbuta ('ة' -> 'ه')
     * - Unify Alef forms ('أ'/'إ'/'آ' -> 'ا')
     * - Remove diacritics / tashkeel (فتحه، ضمه، کسره، تنوین، تشدید، سکون)
     * - Convert half-space (ZWNJ) to regular space
     * - Convert Persian & Arabic digits to ASCII English digits
     * - Collapse multiple whitespaces and trim
     */
    public static function normalize(string $input): string
    {
        if ($input === '') {
            return '';
        }

        // 1. Unify Yeh
        $text = str_replace(self::ARABIC_YEH, self::PERSIAN_YEH, $input);

        // 2. Unify Kaf
        $text = str_replace(self::ARABIC_KAF, self::PERSIAN_KAF, $text);

        // 3. Unify Teh Marbuta
        $text = str_replace(self::ARABIC_TEH_MARBUTA, self::PERSIAN_HEH, $text);

        // 4. Unify Alef variants
        $text = str_replace(self::ALEF_VARIANTS, self::PERSIAN_ALEF, $text);

        // 5. Remove diacritics
        $text = (string) preg_replace(self::DIACRITICS_REGEX, '', $text);

        // 5. Convert ZWNJ and control characters to space
        $text = (string) preg_replace(self::ZWNJ_REGEX, ' ', $text);

        // 6. Convert Persian and Arabic digits to ASCII
        $text = self::normalizeDigits($text);

        // 7. Collapse multiple spaces and trim
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Convert Persian and Arabic digits to standard ASCII digits (0-9).
     */
    public static function normalizeDigits(string $input): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $asciiDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $text = str_replace($persianDigits, $asciiDigits, $input);

        return str_replace($arabicDigits, $asciiDigits, $text);
    }

    /**
     * Remove all diacritics (harakat) without altering other characters.
     */
    public static function removeDiacritics(string $input): string
    {
        return (string) preg_replace(self::DIACRITICS_REGEX, '', $input);
    }

    /**
     * Extract trigrams from a string using PostgreSQL pg_trgm convention (padded with 2 leading spaces, 1 trailing).
     *
     * @return list<string>
     */
    public static function extractTrigrams(string $input): array
    {
        $normalized = '  '.mb_strtolower(self::normalize($input)).' ';
        $length = mb_strlen($normalized);

        if ($length < 3) {
            return [];
        }

        $trigrams = [];
        for ($i = 0; $i <= $length - 3; $i++) {
            $trigrams[] = mb_substr($normalized, $i, 3);
        }

        return $trigrams;
    }

    /**
     * Calculate trigram similarity (0.0 to 1.0) between two strings, mimicking PostgreSQL pg_trgm similarity().
     */
    public static function trigramSimilarity(string $str1, string $str2): float
    {
        $tri1 = self::extractTrigrams($str1);
        $tri2 = self::extractTrigrams($str2);

        if (empty($tri1) && empty($tri2)) {
            return 1.0;
        }

        if (empty($tri1) || empty($tri2)) {
            return 0.0;
        }

        $set1 = array_count_values($tri1);
        $set2 = array_count_values($tri2);

        // Calculate intersection count and union count
        $allKeys = array_unique(array_merge(array_keys($set1), array_keys($set2)));
        $intersection = 0;
        $union = 0;

        foreach ($allKeys as $key) {
            $count1 = $set1[$key] ?? 0;
            $count2 = $set2[$key] ?? 0;
            $intersection += min($count1, $count2);
            $union += max($count1, $count2);
        }

        return $union === 0 ? 0.0 : round($intersection / $union, 4);
    }
}
