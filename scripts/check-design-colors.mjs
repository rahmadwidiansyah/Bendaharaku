#!/usr/bin/env node
/**
 * Design color guard.
 *
 * Melarang warna finansial/dekoratif mentah di komponen. Semua warna
 * harus memakai token semantik dari resources/css/app.css.
 *
 * Aturan:
 *   1. Utility palette non-netral (red, green, amber, blue, dst.)
 *   2. Utility arbitrary hex (bg-[#fff], ring-[#121212], ...)
 *
 * Warna netral (gray/slate/zinc/neutral/stone/white/black) dan slot
 * accent (purple-*) tetap diizinkan.
 *
 * Bypass per baris: tambahkan komentar `design-color-allow`.
 */

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const ROOT = process.cwd();
const TARGET_DIR = join(ROOT, 'resources/js');
const EXTENSIONS = new Set(['.vue', '.js', '.ts']);

const PALETTE =
    'red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|fuchsia|pink|rose';
const PALETTE_RE = new RegExp(
    `\\b(?:bg|text|border|from|to|via|ring|placeholder|divide|shadow)-(?:${PALETTE})-[0-9]{2,3}\\b`,
    'g',
);
const HEX_UTILITY_RE =
    /\b(?:bg|text|border|from|to|via|ring|placeholder|divide|shadow)-\[#[0-9A-Fa-f]{3,8}\]/g;

function walk(dir) {
    const files = [];
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry);
        if (statSync(full).isDirectory()) {
            files.push(...walk(full));
        } else if (EXTENSIONS.has(full.slice(full.lastIndexOf('.')))) {
            files.push(full);
        }
    }
    return files;
}

const violations = [];

for (const file of walk(TARGET_DIR)) {
    const lines = readFileSync(file, 'utf8').split('\n');
    lines.forEach((line, index) => {
        if (line.includes('design-color-allow')) return;
        for (const re of [PALETTE_RE, HEX_UTILITY_RE]) {
            re.lastIndex = 0;
            for (const match of line.matchAll(re)) {
                violations.push({
                    file: relative(ROOT, file),
                    line: index + 1,
                    value: match[0],
                });
            }
        }
    });
}

if (violations.length === 0) {
    console.log('Design color guard: OK');
    process.exit(0);
}

console.error(`Design color guard: ${violations.length} pelanggaran\n`);
for (const v of violations) {
    console.error(`  ${v.file}:${v.line}  ${v.value}`);
}
console.error(
    '\nGunakan token semantik, mis. text-[var(--color-danger-text)], ' +
        'bg-[var(--color-income-bg)], atau text-[var(--color-provider-gemini)].',
);
process.exit(1);
