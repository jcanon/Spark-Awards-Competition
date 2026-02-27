import { defineConfig } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || '';

export default defineConfig({
  testDir: './specs',
  timeout: 30_000,
  retries: 0,
  use: {
    baseURL: baseURL || undefined,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  reporter: [['list']],
});
