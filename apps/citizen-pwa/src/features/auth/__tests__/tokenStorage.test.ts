import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
  getAccessToken,
  setAccessToken,
  clearAccessToken,
  storeSwPat,
  getSwPat,
  clearSwPat,
} from '../model/tokenStorage';

describe('Token Storage Security & IndexedDB PAT (§7.2, D-12)', () => {
  beforeEach(async () => {
    clearAccessToken();
    await clearSwPat();
    vi.restoreAllMocks();
  });

  it('stores access token in-memory only and never writes to localStorage', () => {
    const localStorageSpy = vi.spyOn(Storage.prototype, 'setItem');

    expect(getAccessToken()).toBeNull();

    setAccessToken('pat_in_memory_secret_token_123');
    expect(getAccessToken()).toBe('pat_in_memory_secret_token_123');

    clearAccessToken();
    expect(getAccessToken()).toBeNull();

    // D-12 Constraint Verification: localStorage MUST NEVER receive any token
    expect(localStorageSpy).not.toHaveBeenCalled();
  });

  it('stores and retrieves short-lived Service Worker PAT in IndexedDB', async () => {
    const fakeToken = 'sw_pat_short_lived_15m';
    const expiresAt = new Date(Date.now() + 900_000).toISOString();

    await storeSwPat(fakeToken, expiresAt, 900);

    const retrieved = await getSwPat();
    expect(retrieved).toBe(fakeToken);

    await clearSwPat();
    const afterClear = await getSwPat();
    expect(afterClear).toBeNull();
  });

  it('automatically purges and returns null for expired Service Worker PAT', async () => {
    const fakeToken = 'expired_sw_pat';
    const expiredAt = new Date(Date.now() - 1000).toISOString();

    // ttlSeconds: -1 means already expired
    await storeSwPat(fakeToken, expiredAt, -1);

    const retrieved = await getSwPat();
    expect(retrieved).toBeNull();
  });
});
