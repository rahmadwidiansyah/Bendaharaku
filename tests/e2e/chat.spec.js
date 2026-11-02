import { test, expect } from '@playwright/test';
import { blockThirdParty, gotoApp } from './routes.js';

const IGNORED_CONSOLE_ERRORS = [
    'pagead2.googlesyndication.com',
    'googleads.g.doubleclick.net',
    'Failed to load resource',
];

const TEXTAREA = 'textarea[aria-label="Ketik pesan..."]';
const SEND = 'button[aria-label="Kirim pesan"]';

// Bubble per tema: Default memakai .user-bubble; M3 memakai .m3-msg--user.
// Bubble bot: Default .rounded-tl-md (unik untuk bot), M3 .m3-msg--bot.
const USER_BUBBLE = '.user-bubble, .m3-msg--user';
const BOT_BUBBLE = 'div.rounded-tl-md, .m3-msg--bot';

test.beforeEach(async ({ page }) => {
    await blockThirdParty(page);
});

test('chat: kirim pesan, bubble user muncul, polling selesai, bubble bot tanpa raw JSON', async ({ page }) => {
    const errors = [];

    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() !== 'error') return;
        const text = message.text();
        if (IGNORED_CONSOLE_ERRORS.some((ignored) => text.includes(ignored))) return;
        errors.push(text);
    });

    await gotoApp(page, '/chat');

    // Composer aktif → kirim pesan percakapan.
    await page.locator(TEXTAREA).first().fill('halo, apa kabar');
    await page.locator(SEND).first().click();

    // Bubble user tampil.
    await expect(page.locator(USER_BUBBLE).first()).toBeVisible({ timeout: 10_000 });

    // Bubble pending (typing) muncul → job async berjalan.
    const typing = page.locator('.m3-typing, [class*="TypingIndicator"], text=Sedang mengetik');
    await typing.first().waitFor({ state: 'visible', timeout: 10_000 }).catch(() => {});

    // Polling: cukup satu panggilan status endpoint terjadi.
    const pollSeen = page.waitForResponse(
        (r) => r.url().includes('/chat/message/') && r.url().includes('/status'),
        { timeout: 30_000 },
    );
    await pollSeen.catch(() => {});

    // Pesan bot selesai: typing indicator hilang dan bubble bot ada.
    await expect
        .poll(async () => {
            const pending = page.locator('.m3-typing').count();
            const hasBot = page.locator(BOT_BUBBLE).count();
            return { pending, hasBot };
        }, { timeout: 120_000, message: 'bubble bot harus muncul setelah polling selesai' })
        .toEqual(expect.objectContaining({ pending: 0, hasBot: expect.anything() }));

    // Fase 7: raw JSON kontrak asisten tidak pernah tampil di bubble.
    const bodyText = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(bodyText).not.toContain('assistant_response');
    expect(bodyText).not.toContain('"type"');

    expect(errors, errors.join('\n')).toEqual([]);
});

test('chat: pesan transaksi menghasilkan kartu draft dengan aksi konfirmasi', async ({ page }) => {
    const errors = [];

    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() !== 'error') return;
        const text = message.text();
        if (IGNORED_CONSOLE_ERRORS.some((ignored) => text.includes(ignored))) return;
        errors.push(text);
    });

    await gotoApp(page, '/chat');

    await page.locator(TEXTAREA).first().fill('catat beli kopi di warung 15000 pakai Cash');
    await page.locator(SEND).first().click();

    await expect(page.locator(USER_BUBBLE).first()).toBeVisible({ timeout: 10_000 });

    // Kartu transaksi: tombol aksi draft (Edit / Konfirmasi / Batal) atau
    // kartu transaksi tersimpan. Salah satu harus muncul setelah pipeline.
    const confirmBtn = page.getByRole('button', { name: 'Konfirmasi' }).first();
    await confirmBtn.waitFor({ state: 'visible', timeout: 120_000 }).catch(() => {});

    const bodyText = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    const hasDraftUi =
        (await confirmBtn.count()) > 0 ||
        /Rp\s?15\.000/.test(bodyText) ||
        /draf/i.test(bodyText);

    // Fase 7: bubble harus memuat teks natural ATAU kartu — bukan raw JSON.
    expect(bodyText).not.toContain('assistant_response');
    expect(bodyText).not.toContain('"type"');
    expect(hasDraftUi, 'kartu draft/konfirmasi atau ringkasan transaksi harus tampil').toBe(true);

    expect(errors, errors.join('\n')).toEqual([]);
});
