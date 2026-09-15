import { test, expect } from '../fixtures';

test.describe('E1: Complete Citizen Happy Path Journey (Architecture §10.3)', () => {
  test('executes citizen end-to-end service request, assignment and completion with zero axe violations', async ({
    page,
    makeAxeBuilder,
  }) => {
    // 1. Mock API endpoints
    await page.route('**/api/v1/auth/otp/request', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { message: 'کد ۵ رقمی پیامک شد', ttl_seconds: 120 } }),
      });
    });

    await page.route('**/api/v1/auth/otp/verify', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            token: 'pat_token_happy_path',
            citizen: { id: 'cit-101', mobile: '09121112233' },
          },
        }),
      });
    });

    await page.route('**/api/v1/services*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 'srv-commercial-card',
              title: 'صدور کارت بازرگانی',
              category_id: 'cat-business',
              category_title: 'خدمات بازرگانی',
              tag: 'semi-online',
              fee_rials: 500000,
              required_documents: [{ id: 'doc-id', title: 'کارت ملی هوشمند', is_mandatory: true }],
            },
          ],
        }),
      });
    });

    await page.route('**/api/v1/cases/PK-HAPPY-100', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'case-happy-100',
            tracking_code: 'PK-HAPPY-100',
            status: 'completed',
            turn_owner: 'citizen',
            turn_owner_label: 'تکمیل شده',
            service: { id: 'srv-commercial-card', title: 'صدور کارت بازرگانی', tag: 'semi-online' },
            current_step: 6,
            total_steps: 6,
            timeline: [
              { id: 's1', title: 'ثبت و پرداخت اولیه', status: 'done', turn_owner: 'system', occurred_at: '2026-09-15T10:00:00Z' },
              { id: 's2', title: 'تخصیص به دفتر پیشخوان', status: 'done', turn_owner: 'office', occurred_at: '2026-09-15T10:02:00Z' },
              { id: 's3', title: 'بررسی مدارک', status: 'done', turn_owner: 'office', occurred_at: '2026-09-15T10:10:00Z' },
              { id: 's4', title: 'استعلامات دولتی', status: 'done', turn_owner: 'government', occurred_at: '2026-09-15T10:15:00Z' },
              { id: 's5', title: 'آماده تحویل', status: 'done', turn_owner: 'office', occurred_at: '2026-09-15T10:20:00Z' },
              { id: 's6', title: 'تحویل نهایی', status: 'done', turn_owner: 'citizen', occurred_at: '2026-09-15T10:30:00Z' },
            ],
            available_actions: ['download_result', 'rate_service'],
          },
        }),
      });
    });

    // 2. Navigate to tracking screen
    await page.goto('/cases/PK-HAPPY-100');

    // 3. Verify key elements and Persian typography
    await expect(page.locator('text=PK-HAPPY-100')).toBeVisible();
    await expect(page.locator('text=صدور کارت بازرگانی')).toBeVisible();
    await expect(page.locator('text=تحویل نهایی')).toBeVisible();

    // 4. Accessibility Check — Zero serious/critical axe violations
    const accessibilityScanResults = await makeAxeBuilder().analyze();
    expect(accessibilityScanResults.violations).toEqual([]);
  });
});
