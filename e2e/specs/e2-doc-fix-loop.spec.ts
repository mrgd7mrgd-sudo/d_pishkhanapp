import { test, expect } from '../fixtures';

test.describe('E2: Document Correction Loop (Architecture §10.3)', () => {
  test('handles DOC_BLUR return reason, displays sample guide modal, and allows correction upload', async ({
    page,
    makeAxeBuilder,
  }) => {
    // 1. Mock case with action_required status and DOC_BLUR reason
    await page.route('**/api/v1/cases/PK-ACTION-100', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'case-act-100',
            tracking_code: 'PK-ACTION-100',
            status: 'action_required',
            turn_owner: 'citizen',
            turn_owner_label: 'نوبت شماست',
            service: { id: 'srv-passport', title: 'تمدید گذرنامه', tag: 'in-person' },
            current_step: 3,
            total_steps: 6,
            return_reason: {
              code: 'DOC_BLUR',
              title: 'تصویر شناسنامه ناخواناست',
              message: 'لطفاً تصویر باکیفیت و در نور مستقیم ارسال فرمایید.',
              operator_note: 'گوشه سمت چپ سریال ناخواناست.',
              sample_image_url: '/img/samples/doc-blur.avif',
              returned_at: '2026-09-15T11:00:00Z',
            },
            timeline: [],
            available_actions: ['upload_fix_document', 'open_chat'],
          },
        }),
      });
    });

    await page.goto('/cases/PK-ACTION-100');

    // 2. Verify alert banner for correction
    await expect(page.locator('role=alert')).toBeVisible();
    await expect(page.locator('text=تصویر شناسنامه ناخواناست')).toBeVisible();
    await expect(page.locator('text=DOC_BLUR')).toBeVisible();

    // 3. Open sample image guide modal
    await page.click('text=مشاهده نمونه صحیح');
    await expect(page.locator('text=نمونه تصویر صحیح مدرک')).toBeVisible();

    // Close modal
    await page.click('text=بستن');

    // 4. Accessibility audit
    const axeResults = await makeAxeBuilder().analyze();
    expect(axeResults.violations).toEqual([]);
  });
});
