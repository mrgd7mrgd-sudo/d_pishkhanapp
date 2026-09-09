import { describe, it, expect, beforeEach } from 'vitest';
import 'fake-indexeddb/auto';
import { QueryClient } from '@tanstack/react-query';
import { idbStorage } from '../idb-storage';
import { initQueryClientPersistence } from '../sw-strategies';

describe('IndexedDB Offline Storage & Query Persistence (§4.6, TASK-048, TASK-048-T)', () => {
  beforeEach(async () => {
    await idbStorage.removeItem('test-key');
  });

  it('stores, retrieves, and removes data from IndexedDB key-value store', async () => {
    const initial = await idbStorage.getItem('test-key');
    expect(initial).toBeNull();

    await idbStorage.setItem('test-key', JSON.stringify({ servicesCount: 24 }));
    const stored = await idbStorage.getItem('test-key');
    expect(stored).not.toBeNull();
    expect(JSON.parse(stored!)).toEqual({ servicesCount: 24 });

    await idbStorage.removeItem('test-key');
    const afterDelete = await idbStorage.getItem('test-key');
    expect(afterDelete).toBeNull();
  });

  it('initializes query client persistence without throwing error', () => {
    const testClient = new QueryClient({
      defaultOptions: {
        queries: {
          gcTime: 1000 * 60 * 60 * 24,
        },
      },
    });

    expect(() => initQueryClientPersistence(testClient)).not.toThrow();
  });
});
