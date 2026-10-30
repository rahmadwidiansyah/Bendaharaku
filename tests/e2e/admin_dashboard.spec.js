import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

test('admin dashboard loads MAU stats and charts', async ({ page }) => {
    await gotoApp(page, '/admin');
    await expect(page.locator('main h1')).toContainText('Admin Dashboard');
    await expect(page.locator('text=MAU (30 hari)')).toBeVisible();
    await expect(page.locator('text=Total customer')).toBeVisible();
    await expect(page.locator('text=Transaksi').first()).toBeVisible();
    await expect(page.locator('text=Perlu perhatian').first()).toBeVisible();
    await expect(page.locator('canvas').first()).toBeVisible();
});

test('user management search, sorting and detail', async ({ page }) => {
    await gotoApp(page, '/admin/users');
    await expect(page.locator('main h1')).toContainText('User Management');

    const nameHeader = page.getByRole('columnheader', { name: 'User' }).getByRole('button');
    await nameHeader.click();
    await expect(page).toHaveURL(/sort=name/);
    await expect(page).toHaveURL(/direction=asc/);

    await nameHeader.click();
    await expect(page).toHaveURL(/direction=desc/);

    await page.locator('tbody tr').first().getByTitle('Lihat detail').click();
    await expect(page).toHaveURL(/\/admin\/users\/\d+/);
    await expect(page.locator('text=Aktivitas')).toBeVisible();
    await expect(page.locator('text=Kontak')).toBeVisible();
});

test('support search interface', async ({ page }) => {
    await gotoApp(page, '/admin/support');
    await expect(page.locator('main h1')).toContainText('Support Tools');
    await expect(page.getByPlaceholder(/Nama, email/)).toBeVisible();
});

test('ai logs viewer and detail', async ({ page }) => {
    await gotoApp(page, '/admin/ai/logs');
    await expect(page.locator('main h1')).toContainText('AI Operations Logs');

    const firstRow = page.locator('tbody tr').first();
    if (await firstRow.count()) {
        await firstRow.getByTitle('Lihat detail').click();
        await expect(page.locator('text=Ringkasan')).toBeVisible();
    }
});

test('audit logs viewer and detail', async ({ page }) => {
    await gotoApp(page, '/admin/audit-logs');
    await expect(page.locator('main h1')).toContainText('Audit Logs');

    const firstRow = page.locator('tbody tr').first();
    if (await firstRow.count()) {
        await firstRow.getByTitle('Lihat detail').click();
        await expect(page.locator('text=Ringkasan event')).toBeVisible();
    }
});

test('global search palette opens with keyboard shortcut', async ({ page }) => {
    await gotoApp(page, '/admin');
    await page.keyboard.press('Control+k');
    await expect(page.getByRole('dialog', { name: 'Pencarian global' })).toBeVisible();
    const input = page.getByPlaceholder(/Cari user, log/);
    await input.fill('test');
    await expect(input).toHaveValue('test');
    await expect(page.getByRole('dialog')).toBeVisible();
});
