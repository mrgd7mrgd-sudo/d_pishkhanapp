import { describe, it, expect, vi, beforeEach } from 'vitest';
import 'fake-indexeddb/auto';
import { outboxDb } from '../src/shared/offline/outbox';
import { generateUuidV7 } from '../src/shared/offline/uuidv7';
import { flushOutbox } from '../src/shared/offline/sync';

describe('Offline Submit E2E Scenario (Architecture §4.6, §5.6 E5 & E6, TASK-065, TASK-065-T)', () => {
  beforeEach(async () => {
    await outboxDb.outbox.clear();
    vi.clearAllMocks();
  });

  it('E5: offline network -> queue submission -> restore network -> auto-submit exactly once', async () => {
    // 1. Citizen is offline in a metro tunnel
    const idempotencyKey = generateUuidV7();
    const casePayload = {
      service_id: 'srv-passport',
      dispatch_mode: 'auto',
      payment_method: 'wallet',
      delivery_preference: 'courier',
    };

    // User submits form while offline -> enqueued into outbox
    await outboxDb.outbox.add({
      id: idempotencyKey,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: casePayload,
      status: 'pending',
      attempts: 0,
    });

    const pendingCount = await outboxDb.outbox.where('status').equals('pending').count();
    expect(pendingCount).toBe(1);

    // 2. Simulated network reconnect & server receiver
    let serverReceivedCases = 0;
    const serverReceivedKeys: string[] = [];

    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      if (url === '/api/v1/cases' && init?.method === 'POST') {
        serverReceivedCases++;
        const headers = init.headers as Record<string, string>;
        serverReceivedKeys.push(headers['Idempotency-Key'] ?? '');
        return Promise.resolve({
          ok: true,
          status: 201,
          json: async () => ({ data: { id: 'case-uuid', tracking_code: 'PK-E5-123', status: 'searching_office' } }),
        });
      }
      return Promise.resolve({ ok: false, status: 404 });
    });

    // 3. Auto sync triggers on online event / SW sync
    const flushResult = await flushOutbox('mock-pat-15min');

    // Exactly 1 case created on server
    expect(flushResult.sent).toBe(1);
    expect(serverReceivedCases).toBe(1);
    expect(serverReceivedKeys[0]).toBe(idempotencyKey);

    // Outbox queue is now empty
    const remainingCount = await outboxDb.outbox.count();
    expect(remainingCount).toBe(0);
  });

  it('E6: resending the same idempotency key returns existing response without duplicate charge', async () => {
    const fixedIdempotencyKey = generateUuidV7();
    let ledgerDeductions = 0;

    // Mock API server with idempotency caching
    const serverResponses = new Map<string, { status: number; body: unknown }>();

    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      const headers = init?.headers as Record<string, string>;
      const key = headers['Idempotency-Key'];

      if (serverResponses.has(key)) {
        // Idempotent replay: return cached response without re-executing ledger deduction
        const cached = serverResponses.get(key)!;
        return Promise.resolve({
          ok: true,
          status: 200,
          json: async () => cached.body,
        });
      }

      // First execution: deduct from ledger and cache
      ledgerDeductions++;
      const responsePayload = { id: 'case-e6', tracking_code: 'PK-E6-555', status: 'searching_office' };
      serverResponses.set(key, { status: 200, body: { data: responsePayload } });

      return Promise.resolve({
        ok: true,
        status: 201,
        json: async () => ({ data: responsePayload }),
      });
    });

    // First attempt
    await outboxDb.outbox.add({
      id: fixedIdempotencyKey,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: { service_id: 'srv-1' },
      status: 'pending',
      attempts: 0,
    });

    await flushOutbox();
    expect(ledgerDeductions).toBe(1);

    // Duplicate attempt with same Idempotency-Key
    await outboxDb.outbox.add({
      id: fixedIdempotencyKey,
      createdAt: Date.now(),
      endpoint: '/api/v1/cases',
      method: 'POST',
      body: { service_id: 'srv-1' },
      status: 'pending',
      attempts: 0,
    });

    await flushOutbox();
    // Ledger deduction did NOT increase; exactly one charge recorded
    expect(ledgerDeductions).toBe(1);
  });
});
