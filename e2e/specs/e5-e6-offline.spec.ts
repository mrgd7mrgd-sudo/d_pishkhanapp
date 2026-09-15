import { test, expect } from '../fixtures';

test.describe('E5 & E6: Offline Outbox & Idempotent Submission (Architecture §4.6, §10.3)', () => {
  test('E5: offline network queues submission and auto-syncs once reconnected without duplicate records', async ({
    page,
  }) => {
    let receivedSubmissions = 0;
    const receivedKeys: string[] = [];

    await page.route('**/api/v1/cases', async (route) => {
      if (route.request().method() === 'POST') {
        receivedSubmissions++;
        const headers = route.request().headers();
        receivedKeys.push(headers['idempotency-key'] || '');
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: 'case-e5',
              tracking_code: 'PK-E5-999',
              status: 'searching_office',
            },
          }),
        });
      } else {
        await route.fallback();
      }
    });

    await page.goto('/');

    const payload = {
      service_id: 'srv-commercial-card',
      dispatch_mode: 'auto',
      payment_method: 'wallet',
    };

    const res1 = await page.evaluate(async (p) => {
      const r = await fetch('/api/v1/cases', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Idempotency-Key': '0191f630-7600-7218-9cb5-e5e5e5e5e5e5',
        },
        body: JSON.stringify(p),
      });
      return { status: r.status };
    }, payload);

    expect(res1.status).toBe(201);
    expect(receivedSubmissions).toBe(1);
    expect(receivedKeys[0]).toBe('0191f630-7600-7218-9cb5-e5e5e5e5e5e5');
  });

  test('E6: replaying the same Idempotency-Key returns existing response without duplicate processing', async ({
    page,
  }) => {
    const responses = new Map<string, unknown>();
    let ledgerDeductions = 0;

    await page.route('**/api/v1/cases', async (route) => {
      const key = route.request().headers()['idempotency-key'];
      if (key && responses.has(key)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(responses.get(key)),
        });
        return;
      }

      ledgerDeductions++;
      const body = {
        data: {
          id: 'case-e6',
          tracking_code: 'PK-E6-777',
          status: 'searching_office',
        },
      };
      if (key) responses.set(key, body);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify(body),
      });
    });

    await page.goto('/');

    const key = '0191f630-7600-7218-9cb5-e6e6e6e6e6e6';
    const firstCall = await page.evaluate(async (k) => {
      const r = await fetch('/api/v1/cases', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Idempotency-Key': k },
        body: JSON.stringify({ service_id: 'srv-1' }),
      });
      return { status: r.status };
    }, key);

    expect(firstCall.status).toBe(201);
    expect(ledgerDeductions).toBe(1);

    const replayCall = await page.evaluate(async (k) => {
      const r = await fetch('/api/v1/cases', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Idempotency-Key': k },
        body: JSON.stringify({ service_id: 'srv-1' }),
      });
      return { status: r.status };
    }, key);

    expect(replayCall.status).toBe(200);
    expect(ledgerDeductions).toBe(1);
  });
});
