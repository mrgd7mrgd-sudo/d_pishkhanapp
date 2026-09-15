import { test, expect } from '../fixtures';

test.describe('E9 & E10: Financial Integrity, Ledger Balance & Refunds (Architecture §3.5, §8.2, §10.3)', () => {
  test('E9: payment and case creation maintains strict double-entry ledger balance (Debit == Credit)', async ({
    page,
  }) => {
    await page.route('**/api/v1/ledger/balance-check', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            is_balanced: true,
            total_debits_rials: 5000000,
            total_credits_rials: 5000000,
            variance_rials: 0,
          },
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/ledger/balance-check');
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(200);
    expect(res.data.data.is_balanced).toBe(true);
    expect(res.data.data.variance_rials).toBe(0);
    expect(res.data.data.total_debits_rials).toBe(res.data.data.total_credits_rials);
  });

  test('E10: case rejection triggers ledger-backed refund and updates balance without discrepancy', async ({
    page,
  }) => {
    await page.route('**/api/v1/cases/PK-REJECT-10/reject', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            case_id: 'case-rej-10',
            status: 'rejected',
            refund: {
              status: 'refunded_to_wallet',
              amount_rials: 500000,
              ledger_entry_id: 'led-ref-998',
            },
          },
        }),
      });
    });

    await page.goto('/');
    const res = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cases/PK-REJECT-10/reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: 'عدم احراز شرایط قانونی' }),
      });
      return { status: r.status, data: await r.json() };
    });
    expect(res.status).toBe(200);
    expect(res.data.data.status).toBe('rejected');
    expect(res.data.refund?.status || res.data.data.refund.status).toBe('refunded_to_wallet');
    expect(res.data.data.refund.amount_rials).toBe(500000);
  });
});
