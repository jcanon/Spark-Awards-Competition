import { test, expect } from '@playwright/test';

const base = process.env.E2E_BASE_URL || '';

test.describe('Admin Submission Edit Smoke', () => {
  test.skip(!base, 'Set E2E_BASE_URL to run smoke tests.');

  test('admin dashboard page loads', async ({ page }) => {
    await page.goto('/admin');
    await expect(page.locator('body')).toBeVisible();
  });
});
