import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

test('density preference applies, persists, and reverts', async ({ page }) => {
    const html = page.locator('html');

    const waitForSave = () =>
        page.waitForResponse(
            (response) =>
                response.request().method() === 'PATCH' &&
                response.url().includes('/settings/application/appearance') &&
                response.status() === 200,
        );

    const chooseDensity = async (label, expected) => {
        if ((await html.getAttribute('data-density')) === expected) return;
        await Promise.all([waitForSave(), page.getByText(label, { exact: true }).click()]);
        await expect(html).toHaveAttribute('data-density', expected);
    };

    await gotoApp(page, '/settings/application/appearance');

    try {
        await chooseDensity('Comfortable', 'comfortable');
        await chooseDensity('Compact', 'compact');

        const compactToken = await page.evaluate(() =>
            getComputedStyle(document.documentElement)
                .getPropertyValue('--ui-row-padding-y')
                .trim(),
        );
        expect(parseFloat(compactToken)).toBe(0.5);

        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.locator('main').first().waitFor({ state: 'visible' });
        await expect(html).toHaveAttribute('data-density', 'compact');

        await gotoApp(page, '/transactions');
        await expect(html).toHaveAttribute('data-density', 'compact');
    } finally {
        await gotoApp(page, '/settings/application/appearance');
        if ((await html.getAttribute('data-density')) === 'compact') {
            await Promise.all([
                waitForSave(),
                page.getByText('Comfortable', { exact: true }).click(),
            ]);
        }
        await expect(html).toHaveAttribute('data-density', 'comfortable');
    }
});
