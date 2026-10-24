import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:4000';
const isCI = Boolean(process.env.CI);

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: isCI ? 1 : 0,
    timeout: 60_000,
    expect: {
        timeout: 10_000,
        toHaveScreenshot: {
            animations: 'disabled',
            maxDiffPixelRatio: 0.02,
        },
    },
    reporter: isCI
        ? [['github'], ['html', { open: 'never' }]]
        : [['list'], ['html', { open: 'never' }]],
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        ...devices['Desktop Chrome'],
        baseURL,
        viewport: { width: 1440, height: 900 },
        colorScheme: 'light',
        reducedMotion: 'reduce',
        locale: 'en-US',
        timezoneId: 'Asia/Jakarta',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'default-light',
            use: {
                storageState: 'tests/e2e/.auth/default.json',
                colorScheme: 'light',
            },
        },
        {
            name: 'default-dark',
            use: {
                storageState: 'tests/e2e/.auth/default.json',
                colorScheme: 'dark',
            },
        },
        {
            name: 'm3-light',
            use: {
                storageState: 'tests/e2e/.auth/m3.json',
                colorScheme: 'light',
            },
        },
        {
            name: 'm3-dark',
            use: {
                storageState: 'tests/e2e/.auth/m3.json',
                colorScheme: 'dark',
            },
        },
    ],
});
