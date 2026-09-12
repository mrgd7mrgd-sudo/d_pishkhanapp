/**
 * RFC 9562 UUID v7 generator for client-side offline idempotency (Architecture §4.6).
 * UUID v7 encodes a 48-bit UNIX timestamp (ms) + 74 bits of entropy.
 */
export function generateUuidV7(): string {
  const timestamp = BigInt(Date.now());
  const randomBytes = new Uint8Array(10);
  if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
    crypto.getRandomValues(randomBytes);
  } else {
    for (let i = 0; i < 10; i++) {
      randomBytes[i] = Math.floor(Math.random() * 256);
    }
  }

  // 48-bit timestamp in big-endian hex (12 chars)
  const timeHex = timestamp.toString(16).padStart(12, '0');

  // bytes 6-7: 4-bit version (0b0111 = 0x7) + 12-bit random
  const randA = ((randomBytes[0]! & 0x0f) | 0x70).toString(16).padStart(2, '0') +
    randomBytes[1]!.toString(16).padStart(2, '0');

  // bytes 8-9: 2-bit variant (0b10) + 14-bit random
  const randB = (((randomBytes[2]! & 0x3f) | 0x80).toString(16).padStart(2, '0')) +
    randomBytes[3]!.toString(16).padStart(2, '0');

  // bytes 10-15: 48-bit random
  let randC = '';
  for (let i = 4; i < 10; i++) {
    randC += randomBytes[i]!.toString(16).padStart(2, '0');
  }

  return `${timeHex.slice(0, 8)}-${timeHex.slice(8, 12)}-${randA}-${randB}-${randC}`;
}
