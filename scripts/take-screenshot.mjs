import { chromium } from '@playwright/test';

async function main() {
    const email = process.env.E2E_EMAIL;
    const password = process.env.E2E_PASSWORD;
    if (!email || !password) {
        throw new Error('Set E2E_EMAIL and E2E_PASSWORD before running this script.');
    }

    const outPath = process.env.SCREENSHOT_PATH ?? 'dashboard-1366x768.png';
    const browser = await chromium.launch();
    const context = await browser.newContext({
        viewport: { width: 1366, height: 768 },
        colorScheme: 'dark',
    });
    const page = await context.newPage();

    console.log('Navigating to http://127.0.0.1:4000/login...');
    await page.goto('http://127.0.0.1:4000/login', { waitUntil: 'networkidle' });

    console.log(`Logging in as ${email}...`);
    await page.fill('#login-email', email);
    await page.fill('#login-password', password);
    await Promise.all([
        page.waitForURL(/\/dashboard/, { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);

    await page.waitForTimeout(2000); // let animations settle

    await page.screenshot({ path: outPath, fullPage: false });
    console.log(`Screenshot saved to ${outPath}`);

    // Check rail open
    const isRailOpen = await page.evaluate(() => document.querySelector('.m3-rail--open') !== null);
    console.log('Is rail open:', isRailOpen);

    // Toggle rail if collapsed to capture open state
    const toggleBtn = page.locator('.m3-rail__toggle');
    if (await toggleBtn.count() > 0) {
        await toggleBtn.click();
        await page.waitForTimeout(600);
        const outPathToggle = process.env.SCREENSHOT_TOGGLED_PATH ?? 'dashboard-1366x768-toggled.png';
        await page.screenshot({ path: outPathToggle, fullPage: false });
        console.log(`Screenshot toggled saved to ${outPathToggle}`);
    }

    await browser.close();
}

main().catch(err => {
    console.error('Error:', err);
    process.exit(1);
});
