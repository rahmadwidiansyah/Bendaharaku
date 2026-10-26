# Adaptive M3 Desktop Density

## Status

Approved design direction: adaptive density per component, applied across all M3 pages. The browser remains at 100%.

## Problem

At laptop desktop sizes, especially 1366×768, the M3 interface feels too large and spreads information across too much vertical space. Browser zoom makes the layout feel denser, but also scales text and interactive controls indiscriminately. The existing M3 shell and pages already use design tokens and some compact-desktop rules, so the solution should extend those patterns instead of applying global scaling.

## Goals

- Make every M3 page more comfortable to scan at laptop desktop sizes without changing browser zoom.
- Use available desktop width and height more efficiently, reducing excess page padding, section gaps, and component spacing where the content allows.
- Preserve readable M3 typography, semantic financial colors, keyboard access, and comfortable interactive targets.
- Keep the current tablet and mobile layouts and navigation behavior.

## Non-goals

- Changing browser zoom, root font size, or globally scaling all `rem` values.
- Changing page information architecture, hiding information, or introducing nested scrolling to fit a viewport.
- Changing business rules, transaction data flow, APIs, loading behavior, or mobile/tablet density.
- Redesigning the M3 visual language or replacing its semantic design tokens.

## Design

### Responsive density profile

Add a compact desktop density profile for M3 pages at laptop-sized desktop viewports. Reuse the existing compact desktop range—desktop width from 1024px through 1400px, or desktop height up to 820px—so the profile responds to constrained laptop space rather than browser identity or zoom state.

Keep the browser's root font scale and M3 type roles unchanged. Keep existing interactive target sizes and never reduce them below the M3 touch-target token. Adjust layout spacing, page insets, section gaps, card padding, and dense list-row spacing through existing or narrowly added semantic M3 tokens. Do not introduce ad-hoc pixel values in page components.

### Shared shell

Apply density to the M3 shell's desktop content margins and available width alongside the navigation rail. Keep the app bar and rail interaction behavior intact; respect an explicit saved rail preference. Density rules must be scoped to the M3 UI and must not affect the Default UI.

### Page coverage

Apply the profile across all M3 page roots and their directly related components, not only the dashboard. Adapt each page according to its content:

- Dashboard: compact section spacing and card/list geometry while retaining the existing summary and history composition.
- Finance lists and detail pages (transactions, wallets, categories, budgets, and loans): use desktop width effectively and reduce unnecessary vertical spacing without sacrificing row readability or form-control usability.
- Analytics, chat, search, and settings: reduce excess page insets and section spacing while preserving each page's existing information hierarchy and interaction patterns.

Use a shared token/profile where components have the same density need; use narrowly scoped page rules where a page's content has a distinct layout constraint. Do not force unrelated components into one universal compact layout.

### Responsive and accessibility behavior

The compact profile applies only to desktop M3 pages. Existing tablet/mobile breakpoints, single-column behavior, bottom navigation, and mobile controls remain unchanged. Preserve keyboard navigation, focus indicators, screen-reader labels, loading skeleton behavior, semantic financial colors with labels/icons, and browser text scaling.

The page remains normally scrollable when content exceeds the viewport. Do not clip content or add internal scrolling solely to make more fit above the fold.

## State and failure behavior

No new persisted preference or user setting is introduced. The compact density profile is derived from viewport dimensions. Existing rail preference handling and all data-loading, empty, and error states remain unchanged.

## Validation

- At 1366×768 with browser zoom at 100%, use Playwright to inspect every M3 route and compare the density profile against the current layout.
- Confirm representative dashboard, list, detail, form, analytics, chat, search, and settings pages retain readable text, usable controls, and visible hierarchy.
- Check a larger desktop viewport to ensure compact rules do not activate inappropriately or leave layouts sparse.
- Check tablet and mobile viewports for unchanged navigation, layout flow, and absence of horizontal overflow.
- Verify keyboard focus, saved rail preference behavior, loading skeletons, and page errors remain unaffected.

## Success criteria

1. The compact desktop experience is achieved without changing browser zoom or forcing a smaller root font size.
2. M3 page insets, component spacing, and list density adapt to constrained laptop viewports, while typography and interactive target sizes remain accessible.
3. The profile covers all M3 page roots, with page-specific adjustments where content requires them; no information is hidden to achieve density.
4. Larger desktop, tablet, and mobile behavior remains appropriate, and no horizontal overflow or unintended nested scrolling is introduced.
