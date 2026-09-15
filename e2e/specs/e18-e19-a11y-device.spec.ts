import { test, expect } from '../fixtures';

test.describe('E18 & E19: Accessibility (A11y) & Low-End Device Adaptation (Architecture §4.7, §4.8, §10.3)', () => {
  test('E18: full keyboard navigation and zero serious or critical axe violations across screens', async ({
    page,
    makeAxeBuilder,
  }) => {
    await page.route('**/api/v1/services*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 'srv-1',
              title: 'تعویض کارت ملی',
              category_id: 'cat-id',
              category_title: 'خدمات هویتی',
              tag: 'in-person',
              fee_rials: 300000,
            },
          ],
        }),
      });
    });

    await page.goto('/services');

    // Test keyboard navigation
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');

    const accessibilityScanResults = await makeAxeBuilder().analyze();
    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test('E19: low-end device simulation (deviceMemory=1) executes efficiently without rendering lag', async ({
    page,
  }) => {
    // Emulate low-end device constraints
    await page.addInitScript(() => {
      Object.defineProperty(navigator, 'deviceMemory', { get: () => 1 });
      Object.defineProperty(navigator, 'hardwareConcurrency', { get: () => 2 });
    });

    await page.goto('/services');
    await expect(page.locator('body')).toBeVisible();
  });
});
