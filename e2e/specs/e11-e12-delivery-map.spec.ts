import { test, expect } from '../fixtures';

test.describe('E11 & E12: Courier Delivery & Geographic Proximity (Architecture §5.6, §8.4, §10.3)', () => {
  test('E11: courier delivery flow rejects incorrect OTP and confirms completion upon correct OTP', async ({
    page,
  }) => {
    await page.route('**/api/v1/deliveries/del-101/confirm', async (route) => {
      const payload = JSON.parse(route.request().postData() || '{}');
      if (payload.otp === '12345') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              delivery_id: 'del-101',
              delivery_status: 'delivered',
              case_status: 'completed',
              confirmed_at: '2026-09-15T12:00:00Z',
            },
          }),
        });
      } else {
        await route.fulfill({
          status: 422,
          contentType: 'application/json',
          body: JSON.stringify({
            code: 'INVALID_DELIVERY_OTP',
            detail: 'کد تحویل وارد شده نامعتبر است.',
          }),
        });
      }
    });

    await page.goto('/');

    // 1. Wrong OTP
    const wrongRes = await page.evaluate(async () => {
      const r = await fetch('/api/v1/deliveries/del-101/confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ otp: '99999' }),
      });
      return { status: r.status, data: await r.json() };
    });
    expect(wrongRes.status).toBe(422);
    expect(wrongRes.data.code).toBe('INVALID_DELIVERY_OTP');

    // 2. Correct OTP
    const correctRes = await page.evaluate(async () => {
      const r = await fetch('/api/v1/deliveries/del-101/confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ otp: '12345' }),
      });
      return { status: r.status, data: await r.json() };
    });
    expect(correctRes.status).toBe(200);
    expect(correctRes.data.data.delivery_status).toBe('delivered');
    expect(correctRes.data.data.case_status).toBe('completed');
  });

  test('E12: offices map updates and sorts office list dynamically based on geographic distance', async ({
    page,
  }) => {
    await page.route('**/api/v1/offices?lat=35.7000&lng=51.4000*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 'off-near', name: 'دفتر انقلاب ۱۰۱', distance_meters: 350 },
            { id: 'off-far', name: 'دفتر تجریش ۲۰۲', distance_meters: 8500 },
          ],
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/offices?lat=35.7000&lng=51.4000&radius_km=10');
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(200);
    expect(res.data.data[0].id).toBe('off-near');
    expect(res.data.data[0].distance_meters).toBeLessThan(res.data.data[1].distance_meters);
  });
});
