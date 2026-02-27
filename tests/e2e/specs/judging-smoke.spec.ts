import { test, expect } from '@playwright/test';

const base = process.env.E2E_BASE_URL || '';

test.describe('Judging Entry Smoke', () => {
  test.skip(!base, 'Set E2E_BASE_URL to run smoke tests.');

  test('judging home page loads', async ({ page }) => {
    await page.goto('/judging');
    await expect(page.locator('body')).toBeVisible();
  });
});
