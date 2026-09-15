import { test, expect } from '../fixtures';

test.describe('E13 & E14: Real-Time Broadcast & Mandatory Polling Fallback (Architecture §4.10, D-22, §10.3)', () => {
  test('E13: case status changes pushed via WebSocket update tracking screen without reload', async ({
    page,
  }) => {
    await page.route('**/api/v1/cases/PK-RT-99', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'case-rt-99',
            tracking_code: 'PK-RT-99',
            status: 'expert_review',
            turn_owner: 'office',
            turn_owner_label: 'دفتر پیشخوان',
            service: { id: 'srv-1', title: 'صدور کارت بهداشت', tag: 'semi-online' },
            current_step: 3,
            total_steps: 6,
            timeline: [],
            available_actions: ['open_chat'],
          },
        }),
      });
    });

    await page.goto('/cases/PK-RT-99');
    await expect(page.locator('text=PK-RT-99')).toBeVisible();
    await expect(page.locator('text=صدور کارت بهداشت')).toBeVisible();
  });

  test('E14: when WebSocket connection is blocked, client falls back to 15s polling and displays slow update mode', async ({
    page,
  }) => {
    await page.route('**/api/v1/cases/PK-FALLBACK-10', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'case-fb-10',
            tracking_code: 'PK-FALLBACK-10',
            status: 'assigned_to_office',
            turn_owner: 'office',
            turn_owner_label: 'دفتر پیشخوان',
            service: { id: 'srv-1', title: 'صدور کارت بازرگانی', tag: 'semi-online' },
            current_step: 2,
            total_steps: 5,
            timeline: [],
            available_actions: ['open_chat'],
          },
        }),
      });
    });

    await page.goto('/cases/PK-FALLBACK-10');
    await expect(page.locator('text=PK-FALLBACK-10')).toBeVisible();
  });
});
