import { describe, expect, it, vi, beforeEach } from 'vitest';
import 'fake-indexeddb/auto';
import { outboxDb } from '../outbox';
import { generateUuidV7 } from '../uuidv7';
import { flushOutbox } from '../sync';

describe('Offline Outbox & Background Sync (§4.6, TASK-065, TASK-065-T)', () => {
  beforeEach(async () => {
    await outboxDb.outbox.clear();
    vi.clearAllMocks();
  });

  it('generates valid RFC 9562 UUID v7 with 48-bit timestamp and version 7 indicator', () => {
    const id1 = generateUuidV7();
    const id2 = generateUuidV7();

    expect(id1).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i);
    expect(id2).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i);
    expect(id1).not.toBe(id2);
  });

  it('enqueues item into Dexie outbox table with pending status and UUID v7 id', async () => {
    const id = generateUuidV7();
    await outboxDb.outbox.add({
      id,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: { service_id: 'srv-10' },
      status: 'pending',
      attempts: 0,
    });

    const stored = await outboxDb.outbox.get(id);
    expect(stored).toBeDefined();
    expect(stored?.status).toBe('pending');
    expect(stored?.id).toBe(id);
  });

  it('flushes pending items with Idempotency-Key header matching outbox id and deletes on success', async () => {
    const id = generateUuidV7();
    await outboxDb.outbox.add({
      id,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: { service_id: 'srv-10' },
      status: 'pending',
      attempts: 0,
    });

    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: { id: 'created-case' } }),
    });
    globalThis.fetch = fetchMock;

    const result = await flushOutbox('mock-jwt-token');

    expect(result.sent).toBe(1);
    expect(result.failed).toBe(0);

    // Verify fetch was called with Idempotency-Key
    expect(fetchMock).toHaveBeenCalledWith(
      '/api/v1/cases',
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Idempotency-Key': id,
          Authorization: 'Bearer mock-jwt-token',
        }),
      })
    );

    const remaining = await outboxDb.outbox.get(id);
    expect(remaining).toBeUndefined();
  });

  it('increments attempts on network failure and marks as failed after 5 attempts', async () => {
    const id = generateUuidV7();
    await outboxDb.outbox.add({
      id,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: { service_id: 'srv-10' },
      status: 'pending',
      attempts: 4, // 4th attempt already
    });

    globalThis.fetch = vi.fn().mockRejectedValue(new Error('Network offline'));

    const result = await flushOutbox();
    expect(result.failed).toBe(1);

    const updated = await outboxDb.outbox.get(id);
    expect(updated?.attempts).toBe(5);
    expect(updated?.status).toBe('failed');
    expect(updated?.lastError).toContain('Network offline');
  });
});
