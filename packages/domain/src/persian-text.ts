/**
 * Persian Text Normalizer (Architecture §6.3, §6.4, TASK-038).
 * Exactly mirrors App\Shared\Text\PersianNormalizer in backend.
 */

const ARABIC_YEH_REGEX = /[يىئ]/gu;
const PERSIAN_YEH = 'ی';

const ARABIC_KAF_REGEX = /[كڪ]/gu;
const PERSIAN_KAF = 'ک';

const ARABIC_TEH_MARBUTA_REGEX = /ة/gu;
const PERSIAN_HEH = 'ه';

const ALEF_VARIANTS_REGEX = /[أإآ]/gu;
const PERSIAN_ALEF = 'ا';

/**
 * Arabic/Persian diacritics (harakat/tashkeel) Unicode range \u064B-\u0655 and \u0670.
 */
const DIACRITICS_REGEX = /[\u064B-\u0655\u0670]/gu;

/**
 * Zero-width non-joiner and directional markers.
 */
const ZWNJ_REGEX = /[\u200C\u200D\u200E\u200F\uFEFF]/gu;

const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

/**
 * Convert Persian and Arabic digits to standard ASCII digits (0-9).
 */
export function normalizeDigits(input: string): string {
  let result = input;
  for (let i = 0; i < 10; i++) {
    const p = PERSIAN_DIGITS[i];
    const a = ARABIC_DIGITS[i];
    if (p) {
      result = result.replaceAll(p, i.toString());
    }
    if (a) {
      result = result.replaceAll(a, i.toString());
    }
  }
  return result;
}

/**
 * Remove all diacritics (harakat/tashkeel) without altering other characters.
 */
export function removeDiacritics(input: string): string {
  return input.replace(DIACRITICS_REGEX, '');
}

/**
 * Normalize Persian text:
 * - Unify Yeh ('ي'/'ى' -> 'ی')
 * - Unify Kaf ('ك' -> 'ک')
 * - Convert Teh Marbuta ('ة' -> 'ه')
 * - Unify Alef forms ('أ'/'إ'/'آ' -> 'ا')
 * - Remove diacritics / tashkeel
 * - Convert half-space (ZWNJ) to regular space
 * - Convert Persian & Arabic digits to ASCII English digits
 * - Collapse multiple whitespaces and trim
 */
export function normalizePersianText(input: string): string {
  if (!input) {
    return '';
  }

  let text = input;

  // 1. Unify Yeh
  text = text.replace(ARABIC_YEH_REGEX, PERSIAN_YEH);

  // 2. Unify Kaf
  text = text.replace(ARABIC_KAF_REGEX, PERSIAN_KAF);

  // 3. Unify Teh Marbuta
  text = text.replace(ARABIC_TEH_MARBUTA_REGEX, PERSIAN_HEH);

  // 4. Unify Alef forms
  text = text.replace(ALEF_VARIANTS_REGEX, PERSIAN_ALEF);

  // 5. Remove diacritics
  text = text.replace(DIACRITICS_REGEX, '');

  // 5. Convert ZWNJ and control characters to space
  text = text.replace(ZWNJ_REGEX, ' ');

  // 6. Convert Persian and Arabic digits to ASCII
  text = normalizeDigits(text);

  // 7. Collapse multiple spaces and trim
  return text.replace(/\s+/gu, ' ').trim();
}

/**
 * Extract trigrams from a string using PostgreSQL pg_trgm convention (padded with 2 leading spaces, 1 trailing).
 */
export function extractTrigrams(input: string): string[] {
  const normalized = `  ${normalizePersianText(input).toLowerCase()} `;
  const chars = Array.from(normalized);

  if (chars.length < 3) {
    return [];
  }

  const trigrams: string[] = [];
  for (let i = 0; i <= chars.length - 3; i++) {
    trigrams.push(chars.slice(i, i + 3).join(''));
  }

  return trigrams;
}

/**
 * Calculate trigram similarity (0.0 to 1.0) between two strings, mimicking PostgreSQL pg_trgm similarity().
 */
export function trigramSimilarity(str1: string, str2: string): number {
  const tri1 = extractTrigrams(str1);
  const tri2 = extractTrigrams(str2);

  if (tri1.length === 0 && tri2.length === 0) {
    return 1.0;
  }

  if (tri1.length === 0 || tri2.length === 0) {
    return 0.0;
  }

  const set1 = new Map<string, number>();
  for (const t of tri1) {
    set1.set(t, (set1.get(t) ?? 0) + 1);
  }

  const set2 = new Map<string, number>();
  for (const t of tri2) {
    set2.set(t, (set2.get(t) ?? 0) + 1);
  }

  const allKeys = new Set<string>([...set1.keys(), ...set2.keys()]);
  let intersection = 0;
  let union = 0;

  for (const key of allKeys) {
    const count1 = set1.get(key) ?? 0;
    const count2 = set2.get(key) ?? 0;
    intersection += Math.min(count1, count2);
    union += Math.max(count1, count2);
  }

  if (union === 0) {
    return 0.0;
  }

  return Number((intersection / union).toFixed(4));
}
