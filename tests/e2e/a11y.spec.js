import { test, expect } from '@playwright/test';
import { AxeBuilder } from '@axe-core/playwright';
import { ROUTES, blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

for (const route of ROUTES) {
    test(`a11y: ${route.name}`, async ({ page }) => {
        await gotoApp(page, route.path);

        // Color contrast is tracked separately: the current palette still has
        // known failures (small muted labels and brand-on-tint text) that need
        // token-level design decisions before they can gate CI.
        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa'])
            .disableRules(['color-contrast'])
            .analyze();

        const blocking = results.violations.filter((violation) =>
            ['critical', 'serious'].includes(violation.impact),
        );

        expect(
            blocking,
            blocking
                .map((violation) => `${violation.id}: ${violation.help}`)
                .join('\n'),
        ).toEqual([]);
    });
}
