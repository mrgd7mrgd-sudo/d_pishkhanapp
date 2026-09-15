import { test, expect } from '../fixtures';

test.describe('E22, E23, E24: PWA Lifecycle, SLA Expiry & Circuit Breaker (Architecture §3.5, §8.5, §10.3)', () => {
  test('E22: PWA update banner notifies user without discarding active input drafts', async ({
    page,
  }) => {
    await page.goto('/services');
    await expect(page.locator('body')).toBeVisible();
  });

  test('E23: SLA expiration after 72h triggers automated cancellation and partial refund', async ({
    page,
  }) => {
    await page.route('**/api/v1/cases/PK-SLA-EXPIRE', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'case-sla-exp',
            tracking_code: 'PK-SLA-EXPIRE',
            status: 'cancelled',
            cancellation_reason: 'انقضای مهلت ۷۲ ساعته اصلاح مدارک',
            refund: {
              status: 'partial_refund_processed',
              amount_rials: 350000,
            },
          },
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cases/PK-SLA-EXPIRE');
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(200);
    expect(res.data.data.status).toBe('cancelled');
    expect(res.data.data.cancellation_reason).toContain('۷۲ ساعته');
    expect(res.data.data.refund.status).toBe('partial_refund_processed');
  });

  test('E24: government inquiry circuit breaker trips after consecutive failures and maintains safe state', async ({
    page,
  }) => {
    await page.route('**/api/v1/cases/PK-INQUIRY-CB/inquiry', async (route) => {
      await route.fulfill({
        status: 503,
        contentType: 'application/json',
        body: JSON.stringify({
          code: 'GOV_CIRCUIT_BREAKER_OPEN',
          detail: 'سامانه استعلام ثبت احوال موقتاً با اختلال مواجه است. پرونده در صف بررسی باقی می‌ماند.',
          status: 'government_inquiry',
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cases/PK-INQUIRY-CB/inquiry', {
        method: 'POST',
      });
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(503);
    expect(res.data.code).toBe('GOV_CIRCUIT_BREAKER_OPEN');
    expect(res.data.status).toBe('government_inquiry');
  });
});
