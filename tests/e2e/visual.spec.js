import { test, expect } from '@playwright/test';
import { STABLE_VISUAL_ROUTES, blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

for (const route of STABLE_VISUAL_ROUTES) {
    test(`visual: ${route.name}`, async ({ page }) => {
        await gotoApp(page, route.path);

        await expect(page).toHaveScreenshot(`visual-${route.name}.png`, {
            fullPage: true,
            animations: 'disabled',
        });
    });
}
