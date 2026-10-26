# Spec: Compact Desktop Scaling & Density Optimization (1366x768)

## Problem Statement
On laptops with 1366x768 resolution (and low viewport heights <= 820px), the interface appears "zoomed in" and oversized. Because default browser root font size is `16px`, standard 1rem-based M3 tokens (such as 64dp app bar, 96dp/256dp rail, 28dp headings, 32dp layout gaps) consume 70–80% of the available 600–640px vertical viewport height.

## Design Decisions
1. **Adaptive Root Scaling**:
   - For screens with `(min-width: 1024px) and (max-width: 1400px)` or `(min-width: 1024px) and (max-height: 820px)`:
     Set `html { font-size: 87.5%; }` (14px base, scaled proportionally).
   - This shrinks all `rem`-based typography, component dimensions, and spacing tokens by 12.5% while respecting user accessibility preferences.
2. **Compact Desktop Spacing & Layout Optimization**:
   - In `Dashboard.vue`, adjust desktop grid gap on compact viewports from `--m3-space-8` (32px) to `--m3-space-5` (20px) and padding from 20px to 16px.
   - In `AuthenticatedLayout.vue`, adjust content padding-bottom on compact viewports from `--m3-space-8` to `--m3-space-5`.
3. **Responsive Parity**:
   - On full desktop screens (>1400px width AND >820px height), default 100% (16px base) remains active.
   - Mobile and tablet (<1024px) remain completely untouched at 100% base.

## Affected Files
- `resources/css/app.css` (or `resources/css/m3-tokens.css`)
- `resources/js/Material/Layouts/AuthenticatedLayout.vue`
- `resources/js/Material/Pages/Dashboard.vue`
