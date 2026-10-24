import { test, expect } from '@playwright/test';
import { ROUTES, blockThirdParty, gotoApp } from './routes.js';

const IGNORED_CONSOLE_ERRORS = [
    'pagead2.googlesyndication.com',
    'googleads.g.doubleclick.net',
    'api/v1/budget',
    'Failed to load resource',
];

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

for (const route of ROUTES) {
    test(`layout: ${route.name}`, async ({ page }) => {
        const errors = [];

        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
            if (message.type() !== 'error') return;
            const text = message.text();
            if (IGNORED_CONSOLE_ERRORS.some((ignored) => text.includes(ignored))) return;
            errors.push(text);
        });

        await gotoApp(page, route.path);

        const geometry = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
            visibleH1: [...document.querySelectorAll('h1')].filter((el) => {
                const style = window.getComputedStyle(el);
                return style.display !== 'none' && style.visibility !== 'hidden' && el.getClientRects().length > 0;
            }).length,
            density: document.documentElement.getAttribute('data-density'),
        }));

        expect(geometry.scrollWidth).toBeLessThanOrEqual(geometry.clientWidth + 1);
        expect(geometry.visibleH1).toBe(1);
        expect(['comfortable', 'compact']).toContain(geometry.density);
        expect(errors, errors.join('\n')).toEqual([]);
    });
}
