import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

test('M3 compact desktop density keeps typography and controls unchanged', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await gotoApp(page, '/dashboard');

    const compactMetrics = await page.evaluate(() => {
        const dashboard = document.querySelector('.m3-dash');
        const card = document.querySelector('.m3-card--pad-md');
        const iconButton = [...document.querySelectorAll('.m3-icon-btn')]
            .find((element) => element.getClientRects().length > 0);

        return {
            isM3: document.documentElement.classList.contains('ui-m3'),
            rootFontSize: getComputedStyle(document.documentElement).fontSize,
            pageInset: dashboard ? getComputedStyle(dashboard).paddingTop : null,
            sectionGap: dashboard ? getComputedStyle(dashboard).gap : null,
            cardPadding: card ? getComputedStyle(card).paddingTop : null,
            iconButtonSize: iconButton ? iconButton.getBoundingClientRect().width : null,
        };
    });

    expect(compactMetrics.isM3).toBe(true);
    expect(compactMetrics.rootFontSize).toBe('16px');
    expect(compactMetrics.pageInset).toBe('12px');
    expect(compactMetrics.sectionGap).toBe('16px');
    expect(compactMetrics.cardPadding).toBe('12px');
    expect(compactMetrics.iconButtonSize).toBeGreaterThanOrEqual(40);

    await page.setViewportSize({ width: 1440, height: 900 });
    const largeDesktopMetrics = await page.evaluate(() => ({
        rootFontSize: getComputedStyle(document.documentElement).fontSize,
        pageInset: getComputedStyle(document.querySelector('.m3-dash')).paddingTop,
        cardPadding: getComputedStyle(document.querySelector('.m3-card--pad-md')).paddingTop,
    }));

    expect(largeDesktopMetrics.rootFontSize).toBe('16px');
    expect(largeDesktopMetrics.pageInset).toBe('20px');
    expect(largeDesktopMetrics.cardPadding).toBe('16px');
});
