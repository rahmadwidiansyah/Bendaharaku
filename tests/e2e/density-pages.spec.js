import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

const M3_DENSITY_ROUTES = [
    { name: 'settings', path: '/settings' },
    { name: 'settings-profile', path: '/settings/account/profile' },
    { name: 'settings-security', path: '/settings/account/security' },
    { name: 'settings-preferences', path: '/settings/account/preferences' },
    { name: 'settings-appearance', path: '/settings/application/appearance' },
    { name: 'settings-notifications', path: '/settings/application/notifications' },
    { name: 'settings-finance-logic', path: '/settings/finance/logic' },
    { name: 'settings-finance-budget', path: '/settings/finance/budget' },
    { name: 'settings-privacy', path: '/settings/privacy/settings' },
    { name: 'settings-data', path: '/settings/privacy/data' },
    { name: 'settings-danger', path: '/settings/privacy/danger' },
    { name: 'settings-logs', path: '/settings/privacy/logs' },
    { name: 'settings-about', path: '/settings/system/about' },
    { name: 'settings-ai-models', path: '/settings/ai/models' },
    { name: 'settings-ai-bot', path: '/settings/ai/bot' },
    { name: 'settings-ai-memory', path: '/settings/ai/memory' },
    { name: 'settings-ai-status', path: '/settings/ai/status' },
    { name: 'settings-ai-memory-manage', path: '/settings/ai/memory/manage' },
    { name: 'settings-ai-integrations', path: '/settings/ai/integrations' },
    { name: 'settings-chat', path: '/settings/chat' },
    { name: 'budgeting', path: '/budgeting' },
    { name: 'analytics', path: '/analytics' },
    { name: 'liabilities', path: '/loans/debt' },
    { name: 'receivables', path: '/loans/piutang' },
    { name: 'chat', path: '/chat' },
];

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

for (const route of M3_DENSITY_ROUTES) {
    test(`compact page audit: ${route.name}`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await page.setViewportSize({ width: 1366, height: 768 });
        await gotoApp(page, route.path);

        const compact = await page.evaluate(() => ({
            isM3: document.documentElement.classList.contains('ui-m3'),
            rootFontSize: getComputedStyle(document.documentElement).fontSize,
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        }));

        expect(compact.isM3).toBe(true);
        expect(compact.rootFontSize).toBe('16px');
        expect(compact.scrollWidth).toBeLessThanOrEqual(compact.clientWidth + 1);
        expect(errors, errors.join('\n')).toEqual([]);

        await page.setViewportSize({ width: 1440, height: 900 });
        const largeDesktop = await page.evaluate(() => ({
            rootFontSize: getComputedStyle(document.documentElement).fontSize,
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        }));

        expect(largeDesktop.rootFontSize).toBe('16px');
        expect(largeDesktop.scrollWidth).toBeLessThanOrEqual(largeDesktop.clientWidth + 1);
    });
}

test('compact settings layouts use desktop width without shrinking mobile layout', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await gotoApp(page, '/settings');
    const settings = await page.locator('.m3-settings-page').evaluate((element) => ({
        width: element.getBoundingClientRect().width,
        columns: getComputedStyle(element.querySelector('.m3-settings-hub__sections')).gridTemplateColumns,
    }));

    expect(settings.width).toBeGreaterThan(1000);
    expect(settings.columns.trim().split(/\s+/)).toHaveLength(2);

    await gotoApp(page, '/settings/application/appearance');
    const appearance = await page.locator('.m3-settings-page').evaluate((element) => ({
        width: element.getBoundingClientRect().width,
        columns: getComputedStyle(element).gridTemplateColumns,
        backColumn: getComputedStyle(element.querySelector('.m3-settings-page__back')).gridColumn,
        sectionTops: [...element.querySelectorAll('.m3-settings-section')]
            .slice(0, 2)
            .map((section) => Math.round(section.getBoundingClientRect().top)),
    }));
    expect(appearance.width).toBeGreaterThan(1000);
    expect(appearance.columns.trim().split(/\s+/)).toHaveLength(2);
    expect(appearance.backColumn).toBe('1 / -1');
    expect(appearance.sectionTops[0]).toBe(appearance.sectionTops[1]);

    await page.setViewportSize({ width: 390, height: 844 });
    const mobile = await page.locator('.m3-settings-page').evaluate((element) => ({
        columns: getComputedStyle(element).gridTemplateColumns,
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
    }));
    expect(mobile.columns).toBe('none');
    expect(mobile.scrollWidth).toBeLessThanOrEqual(mobile.clientWidth + 1);
});

test('settings child pages use centered constrained containers', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });

    const childPages = M3_DENSITY_ROUTES.filter((route) => route.path.startsWith('/settings/'));
    for (const { path } of childPages) {
        await gotoApp(page, path);
        const layout = await page.evaluate(() => {
            const main = document.querySelector('.m3-main').getBoundingClientRect();
            const container = document.querySelector('.m3-main').firstElementChild;
            const bounds = container.getBoundingClientRect();
            const back = container.querySelector('.m3-settings-back, .m3-settings-page__back');
            return {
                centered: Math.abs((bounds.left - main.left) - (main.right - bounds.right)) <= 2,
                width: bounds.width,
                maxWidth: getComputedStyle(container).maxWidth,
                titleInside: container.contains(container.querySelector('.m3-page-title')),
                backInside: !back || container.contains(back),
            };
        });

        expect(layout.centered, `${path} should be centered`).toBe(true);
        expect(layout.width, `${path} should be constrained`).toBeLessThanOrEqual(1152);
        expect(layout.maxWidth, `${path} should declare a max-width`).not.toBe('none');
        expect(layout.titleInside, `${path} should keep its title in the container`).toBe(true);
        expect(layout.backInside, `${path} should keep its back button in the container`).toBe(true);
    }

    await page.setViewportSize({ width: 390, height: 844 });
    for (const path of ['/settings/account/profile', '/settings/ai/bot']) {
        await gotoApp(page, path);
        const mobile = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        }));
        expect(mobile.scrollWidth).toBeLessThanOrEqual(mobile.clientWidth + 1);
    }
});

test('chat fills the desktop viewport', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await gotoApp(page, '/chat');
    const chat = await page.locator('.m3-chat').evaluate((element) => {
        const rect = element.getBoundingClientRect();
        return {
            bottom: Math.round(rect.bottom),
            viewportHeight: window.innerHeight,
            scrollHeight: document.documentElement.scrollHeight,
        };
    });
    expect(chat.bottom).toBe(chat.viewportHeight);
    expect(chat.scrollHeight).toBeLessThanOrEqual(chat.viewportHeight + 1);
});

test('activity log descriptions do not overflow tablet or mobile widths', async ({ page }) => {
    for (const viewport of [{ width: 768, height: 1024 }, { width: 390, height: 844 }]) {
        await page.setViewportSize(viewport);
        await gotoApp(page, '/settings/privacy/logs');
        const metrics = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        }));
        expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.clientWidth + 1);
    }
});

test('analytics charts adapt vertically only on compact desktop', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await gotoApp(page, '/analytics');

    const compact = await page.evaluate(() => ({
        primary: document.querySelector('.m3-ana__bar-box').getBoundingClientRect().height,
        summary: document.querySelector('.m3-ana__cumulative-wrap').getBoundingClientRect().height,
    }));
    expect(compact).toEqual({ primary: 180, summary: 96 });

    await page.setViewportSize({ width: 1440, height: 900 });
    const largeDesktop = await page.evaluate(() => ({
        primary: document.querySelector('.m3-ana__bar-box').getBoundingClientRect().height,
        summary: document.querySelector('.m3-ana__cumulative-wrap').getBoundingClientRect().height,
    }));
    expect(largeDesktop).toEqual({ primary: 220, summary: 120 });
});

test('analytics uses its wide-screen layout at 1920x1080', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });
    await gotoApp(page, '/analytics');

    const layout = await page.evaluate(() => {
        const grid = document.querySelector('.m3-ana__grid');
        const left = document.querySelector('.m3-ana__left').getBoundingClientRect();
        const aside = document.querySelector('.m3-ana__aside').getBoundingClientRect();
        const columns = getComputedStyle(grid).gridTemplateColumns
            .split(' ')
            .filter(Boolean)
            .map((column) => Number.parseFloat(column));

        return {
            columns,
            sideBySide: left.left < aside.left && Math.abs(left.top - aside.top) <= 2,
            chartHeights: {
                primary: document.querySelector('.m3-ana__bar-box').getBoundingClientRect().height,
                summary: document.querySelector('.m3-ana__cumulative-wrap').getBoundingClientRect().height,
            },
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        };
    });

    expect(layout.columns).toHaveLength(2);
    expect(layout.columns[0]).toBeLessThanOrEqual(1152);
    expect(layout.sideBySide).toBe(true);
    expect(layout.chartHeights).toEqual({ primary: 176, summary: 96 });
    expect(layout.scrollWidth).toBeLessThanOrEqual(layout.clientWidth + 1);
});

test('analytics adapts to a scaled 1920x1080 desktop viewport', async ({ page }) => {
    await page.setViewportSize({ width: 1536, height: 864 });
    await gotoApp(page, '/analytics');

    const layout = await page.evaluate(() => {
        const grid = document.querySelector('.m3-ana__grid');
        const left = document.querySelector('.m3-ana__left').getBoundingClientRect();
        const aside = document.querySelector('.m3-ana__aside').getBoundingClientRect();
        const columns = getComputedStyle(grid).gridTemplateColumns
            .split(' ')
            .filter(Boolean)
            .map((column) => Number.parseFloat(column));

        return {
            columns,
            sideBySide: left.left < aside.left && Math.abs(left.top - aside.top) <= 2,
            chartHeights: {
                primary: document.querySelector('.m3-ana__bar-box').getBoundingClientRect().height,
                summary: document.querySelector('.m3-ana__cumulative-wrap').getBoundingClientRect().height,
            },
            pageBottom: Math.round(document.querySelector('.m3-ana').getBoundingClientRect().bottom),
            viewportHeight: window.innerHeight,
            rootFontSize: getComputedStyle(document.documentElement).fontSize,
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        };
    });

    expect(layout.columns).toHaveLength(2);
    expect(layout.sideBySide).toBe(true);
    expect(layout.chartHeights).toEqual({ primary: 176, summary: 96 });
    expect(layout.pageBottom).toBeLessThanOrEqual(layout.viewportHeight);
    expect(layout.rootFontSize).toBe('16px');
    expect(layout.scrollWidth).toBeLessThanOrEqual(layout.clientWidth + 1);
});
