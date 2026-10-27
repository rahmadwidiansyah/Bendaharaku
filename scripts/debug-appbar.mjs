import { chromium } from '@playwright/test';

async function main() {
    const email = process.env.E2E_EMAIL;
    const password = process.env.E2E_PASSWORD;
    if (!email || !password) {
        throw new Error('Set E2E_EMAIL and E2E_PASSWORD before running this script.');
    }

    const browser = await chromium.launch();
    const context = await browser.newContext({
        viewport: { width: 1366, height: 768 },
        colorScheme: 'dark',
    });
    const page = await context.newPage();

    await page.goto('http://127.0.0.1:4000/login', { waitUntil: 'networkidle' });
    await page.fill('#login-email', email);
    await page.fill('#login-password', password);
    await Promise.all([
        page.waitForURL(/\/dashboard/, { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    await page.waitForTimeout(1000);

    const toggleBtn = page.locator('.m3-rail__toggle');
    if (await toggleBtn.count() > 0) {
        await toggleBtn.click();
        await page.waitForTimeout(500);
    }

    const info = await page.evaluate(() => {
        const header = document.querySelector('.m3-appbar');
        const rail = document.querySelector('.m3-rail');
        const titleBlock = document.querySelector('.m3-appbar__title-block');
        return {
            headerClasses: header?.className,
            headerComputedPaddingLeft: window.getComputedStyle(header).paddingLeft,
            headerRect: header?.getBoundingClientRect(),
            railRect: rail?.getBoundingClientRect(),
            titleBlockRect: titleBlock?.getBoundingClientRect(),
            sizeRailToken: window.getComputedStyle(document.documentElement).getPropertyValue('--m3-size-rail'),
        };
    });

    console.log('App bar & rail debug info:', JSON.stringify(info, null, 2));

    await browser.close();
}

main().catch(err => {
    console.error(err);
    process.exit(1);
});
