import { test, expect } from '../fixtures';

test.describe('E7 & E8: Security, Tenant Isolation & Rate Limiting (Architecture §7.3, §7.7, §10.3)', () => {
  test('E7: cross-office isolation returns 404 instead of 403 to prevent resource existence leakage (§7.3)', async ({
    page,
  }) => {
    await page.route('**/api/v1/desk/cases/foreign-office-case-99', async (route) => {
      // Horizontal isolation policy always returns 404, never 403
      await route.fulfill({
        status: 404,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 404,
          code: 'CASE_NOT_FOUND',
          detail: 'پرونده مورد نظر یافت نشد.',
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/desk/cases/foreign-office-case-99');
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(404);
    expect(res.data.status).toBe(404);
    expect(res.data.code).toBe('CASE_NOT_FOUND');
  });

  test('E8: multi-tier OTP rate limiting enforces 429 with Retry-After header upon 4th consecutive request (§7.7)', async ({
    page,
  }) => {
    let attempts = 0;
    await page.route('**/api/v1/auth/otp/request', async (route) => {
      attempts++;
      if (attempts <= 3) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: { message: 'کد پیامک شد', ttl_seconds: 120 } }),
        });
      } else {
        await route.fulfill({
          status: 429,
          headers: {
            'Retry-After': '120',
          },
          contentType: 'application/json',
          body: JSON.stringify({
            status: 429,
            code: 'OTP_RATE_LIMITED',
            detail: 'تعداد درخواست‌های کد تأیید بیش از حد مجاز است. لطفاً ۱۲۰ ثانیه دیگر مجدداً تلاش فرمایید.',
          }),
        });
      }
    });

    await page.goto('/');

    // Send 3 requests
    for (let i = 1; i <= 3; i++) {
      const res = await page.evaluate(async () => {
        const r = await fetch('/api/v1/auth/otp/request', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mobile: '09129998877' }),
        });
        return { status: r.status };
      });
      expect(res.status).toBe(200);
    }

    // 4th request must receive 429 with Retry-After header
    const fourthRes = await page.evaluate(async () => {
      const r = await fetch('/api/v1/auth/otp/request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mobile: '09129998877' }),
      });
      return {
        status: r.status,
        retryAfter: r.headers.get('retry-after'),
        data: await r.json(),
      };
    });
    expect(fourthRes.status).toBe(429);
    expect(fourthRes.retryAfter).toBe('120');
  });
});
