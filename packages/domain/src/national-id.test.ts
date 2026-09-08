import { describe, it, expect } from 'vitest';
import { isValidIranianNationalId, normalizeDigits } from './national-id';

// Exactly the same 20 verified valid Iranian National IDs used in PHP test
const validNationalIds = [
  '0010350802', '0010350810', '0010350829', '0010350837', '0010350845',
  '0010350853', '0010350861', '0010350871', '0010350888', '0010350896',
  '0010350901', '0010350918', '0010350926', '0010350934', '0010350942',
  '0010350950', '0010350969', '0010350977', '0010350985', '0010350993',
];

// Exactly the same 20 verified invalid Iranian National IDs used in PHP test
const invalidNationalIds = [
  '0000000000', '1111111111', '2222222222', '3333333333', '4444444444',
  '5555555555', '6666666666', '7777777777', '8888888888', '9999999999',
  '0010350801', '0010350811', '1234567890', '0010350820', '0010350830',
  '12345', '00103508299', 'abcdefghij', '', ' 0010350820 ',
];

describe('Iranian National ID Check-Digit Algorithm (Parity with PHP, TASK-023)', () => {
  it('identifies all 20 valid national IDs correctly in TypeScript', () => {
    validNationalIds.forEach((nid) => {
      expect(isValidIranianNationalId(nid)).toBe(true);
    });
  });

  it('identifies all 20 invalid national IDs correctly in TypeScript', () => {
    invalidNationalIds.forEach((nid) => {
      expect(isValidIranianNationalId(nid)).toBe(false);
    });
  });

  it('normalizes Persian and Arabic digits properly', () => {
    const persian = '۰۰۱۰۳۵۰۸۲۹';
    expect(normalizeDigits(persian)).toBe('0010350829');
    expect(isValidIranianNationalId(persian)).toBe(true);

    const arabic = '٠٠١٠٣٥٠٨٢٩';
    expect(normalizeDigits(arabic)).toBe('0010350829');
    expect(isValidIranianNationalId(arabic)).toBe(true);
  });
});