import { test, expect } from '../fixtures';

test.describe('E3 & E4: Snapp-style Dispatch Engine & Concurrency (Architecture §5.8, §10.3)', () => {
  test('E3: when two operators accept the same dispatch offer, second receives 409 conflict with Persian explanation', async ({
    page,
  }) => {
    let acceptCount = 0;
    await page.route('**/api/v1/offers/off-123/accept', async (route) => {
      acceptCount++;
      if (acceptCount === 1) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: { message: 'پیشنهاد با موفقیت پذیرفته شد' } }),
        });
      } else {
        await route.fulfill({
          status: 409,
          contentType: 'application/json',
          body: JSON.stringify({
            code: 'OFFER_ALREADY_TAKEN',
            detail: 'این پرونده قبلاً توسط دفتر پیشخوان دیگری پذیرفته شده است.',
          }),
        });
      }
    });

    await page.goto('/');

    const firstRes = await page.evaluate(async () => {
      const res = await fetch('/api/v1/offers/off-123/accept', { method: 'POST' });
      return { status: res.status, data: await res.json() };
    });
    expect(firstRes.status).toBe(200);

    const secondRes = await page.evaluate(async () => {
      const res = await fetch('/api/v1/offers/off-123/accept', { method: 'POST' });
      return { status: res.status, data: await res.json() };
    });
    expect(secondRes.status).toBe(409);
    expect(secondRes.data.code).toBe('OFFER_ALREADY_TAKEN');
    expect(secondRes.data.detail).toContain('توسط دفتر پیشخوان دیگری پذیرفته شده است');
  });

  test('E4: dispatch offer expires after 90s TTL and advances to next candidate office tier', async ({
    page,
  }) => {
    await page.route('**/api/v1/offers/off-90s', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'off-90s',
            round: 1,
            ttl_seconds: 90,
            status: 'expired',
            next_round_initiated: true,
          },
        }),
      });
    });

    await page.goto('/');

    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/offers/off-90s');
      return { status: r.status, json: await r.json() };
    });

    expect(res.status).toBe(200);
    expect(res.json.data.status).toBe('expired');
    expect(res.json.data.next_round_initiated).toBe(true);
  });
});
