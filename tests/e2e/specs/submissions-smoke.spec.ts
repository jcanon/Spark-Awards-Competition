import { test, expect } from '@playwright/test';

const base = process.env.E2E_BASE_URL || '';

test.describe('Submission Form Smoke', () => {
  test.skip(!base, 'Set E2E_BASE_URL to run smoke tests.');

  test('submission index page loads', async ({ page }) => {
    await page.goto('/submissions');
    await expect(page.locator('body')).toBeVisible();
  });
});
