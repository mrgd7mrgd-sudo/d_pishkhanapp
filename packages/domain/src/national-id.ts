/**
 * Official Iranian National ID Check-Digit Algorithm (Architecture §5.3, §5.6, TASK-023)
 * Exactly mirrors App\Modules\Identity\Domain\Rules\ValidIranianNationalId in backend.
 */

import { normalizeDigits } from './persian-text.js';

export { normalizeDigits };

/**
 * Validates whether a given national ID string conforms to the official check-digit algorithm.
 */
export function isValidIranianNationalId(rawCode: string): boolean {
  if (!rawCode) {
    return false;
  }

  const code = normalizeDigits(rawCode.trim());

  // Must be exactly 10 digits
  if (!/^\d{10}$/.test(code)) {
    return false;
  }

  // Reject repetitive numbers (0000000000, 1111111111, ..., 9999999999)
  for (let i = 0; i <= 9; i++) {
    if (code === i.toString().repeat(10)) {
      return false;
    }
  }

  // Check digit calculation
  const check = Number(code[9]);
  let sum = 0;

  for (let i = 0; i < 9; i++) {
    sum += Number(code[i]) * (10 - i);
  }

  const remainder = sum % 11;

  if (remainder < 2) {
    return check === remainder;
  }

  return check === 11 - remainder;
}