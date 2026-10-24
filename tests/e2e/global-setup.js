import { chromium } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:4000';

const ACCOUNTS = {
    default: 'e2e-default@example.com',
    m3: 'e2e-m3@example.com',
};

export default async function globalSetup() {
    const authDir = path.join(process.cwd(), 'tests/e2e/.auth');
    fs.mkdirSync(authDir, { recursive: true });

    const browser = await chromium.launch();

    try {
        for (const [name, email] of Object.entries(ACCOUNTS)) {
            const context = await browser.newContext({ baseURL });
            const page = await context.newPage();

            await page.goto('/login');
            await page.fill('#login-email', email);
            await page.fill('#login-password', 'password');
            await Promise.all([
                page.waitForURL(/\/dashboard/, { timeout: 30_000 }),
                page.locator('form button[type="submit"]').click(),
            ]);
            await page.locator('main').first().waitFor({ state: 'visible' });

            await context.storageState({ path: path.join(authDir, `${name}.json`) });
            await context.close();
        }
    } finally {
        await browser.close();
    }
}
