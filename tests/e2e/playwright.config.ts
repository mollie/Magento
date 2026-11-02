import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.MAGENTO_URL ?? process.env.DDEV_PRIMARY_URL;

if (baseURL === undefined) {
  throw new Error('Set MAGENTO_URL to the shop url, or run the tests inside DDEV.');
}

export default defineConfig({
  testDir: './specs',
  timeout: 180_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
