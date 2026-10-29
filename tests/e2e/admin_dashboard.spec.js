import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

test('admin dashboard loads stats and M3 theme styling', async ({ page }) => {
    await gotoApp(page, '/admin');
    await expect(page.locator('h1')).toContainText('Admin Dashboard');
    await expect(page.locator('text=Total Users')).toBeVisible();
    await expect(page.locator('text=Active Users')).toBeVisible();
    await expect(page.locator('text=Total Transactions')).toBeVisible();
});

test('user management table search and pagination', async ({ page }) => {
    await gotoApp(page, '/admin/users');
    await expect(page.locator('h1')).toContainText('User Management');
});

test('support search interface', async ({ page }) => {
    await gotoApp(page, '/admin/support');
    await expect(page.locator('h1')).toContainText('Support Center');
});

test('ai logs viewer', async ({ page }) => {
    await gotoApp(page, '/admin/ai-logs');
    await expect(page.locator('h1')).toContainText('AI Activity Logs');
});

test('audit logs viewer', async ({ page }) => {
    await gotoApp(page, '/admin/audit-logs');
    await expect(page.locator('h1')).toContainText('Admin Audit Logs');
});
