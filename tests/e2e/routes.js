export const ROUTES = [
    { name: 'dashboard', path: '/dashboard' },
    { name: 'transactions', path: '/transactions' },
    { name: 'transaction-create', path: '/transactions/create' },
    { name: 'wallets', path: '/wallets' },
    { name: 'categories', path: '/categories' },
    { name: 'analytics', path: '/analytics' },
    { name: 'budgeting', path: '/budgeting' },
    { name: 'settings', path: '/settings' },
    { name: 'settings-logs', path: '/settings/privacy/logs' },
    { name: 'settings-appearance', path: '/settings/application/appearance' },
];

export const STABLE_VISUAL_ROUTES = [
    { name: 'settings', path: '/settings' },
    { name: 'settings-appearance', path: '/settings/application/appearance' },
    { name: 'settings-profile', path: '/settings/account/profile' },
    { name: 'categories', path: '/categories' },
    { name: 'wallets', path: '/wallets' },
];

export async function blockThirdParty(page) {
    await page.route('**/pagead2.googlesyndication.com/**', (route) => route.abort());
    await page.route('**/googleads.g.doubleclick.net/**', (route) => route.abort());
}

export async function gotoApp(page, path) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await page.locator('main').first().waitFor({ state: 'visible' });
    await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => {});
    await page.waitForTimeout(250);
}
