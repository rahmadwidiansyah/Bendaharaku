# Design: Adaptive M3 Desktop Density

## Status

Approved direction: option A — two-pane dashboard with measured density.

## Problem

At 1366×768, the M3 dashboard feels oversized and requires too much scrolling to inspect its information. A Playwright inspection of the running app found a forced 14px root font size, an expanded navigation rail, a dashboard width of about 1008px, and a document height of about 3414px. The current viewport rule scales all `rem`-based values together, including typography, component geometry, and the content-width cap; it does not target the actual layout bottleneck.

The existing `2026-10-26-compact-desktop-scaling-design.md` proposes that global scaling approach. This design supersedes that approach for the M3 shell and dashboard.

## Goals

- Make the dashboard's primary information easier to scan at laptop desktop sizes, especially 1366×768.
- Use available horizontal space more effectively while keeping the financial summary and transaction history visible together.
- Increase useful information in the first viewport without making text or interactive targets uncomfortably small.
- Preserve M3 behavior, user navigation preferences, semantic financial colors, and mobile/tablet layouts.

## Non-goals

- Changing transaction data, business rules, APIs, or loading behavior.
- Redesigning every dashboard card or changing dashboard information architecture.
- Introducing nested scrolling, hiding dashboard information, or changing mobile/tablet navigation.
- Making the app bar or interactive controls shorter than their M3 defaults.

## Design

### Responsive shell

Remove the viewport-specific root-font-size override. Let semantic type and component tokens retain the browser's normal scale, including user accessibility settings. Apply compact density through existing spacing tokens and targeted component styles instead of shrinking every `rem` value.

Keep the M3 desktop navigation rail. On compact laptop-sized desktop viewports, initialize it collapsed only when no rail preference has been saved; continue to honor the user's stored open/closed choice and the existing toggle. Keep the current responsive navigation behavior below desktop breakpoints.

Let the content region use the available width alongside either rail state. Adjust its maximum width and desktop margins so the dashboard does not remain artificially narrow after removing global scale. Keep the app bar at its standard M3 geometry.

### Dashboard composition

Retain the two-pane arrangement selected during brainstorming: financial summary on the supporting side and transaction history as the primary, wider area. Use a responsive feed composition consistent with M3 large-window layouts.

Keep net worth as the single visual focal point. Reduce excess vertical gaps and card padding with design tokens, and make the income/expense summary more space-efficient without removing its labels, icons, or semantic colors. Tune transaction-row spacing so more complete rows are visible at 1366×768 while preserving readable text and accessible targets. Upcoming debt information remains available in normal page flow.

Do not introduce a fixed-height transaction panel or internal scrollbar. The page remains normally scrollable for older transactions and supporting content.

### M3 and accessibility

The M3 Expressive guidance classifies 1366 CSS pixels as a large-window layout at standard web density: use an adaptive multi-column composition and a navigation rail. Preserve the default app bar and component geometry; adapt margins, spacing, and content arrangement rather than applying a blanket zoom.

Continue to use existing M3 design tokens and financial color roles. Keep labels/icons alongside semantic colors, preserve keyboard access to the rail toggle, and respect reduced-motion and browser text-scaling behavior already supported by the app.

## Scope and implementation boundaries

- `resources/css/app.css`: remove the compact-desktop root font-size override.
- `resources/css/m3-tokens.css` and `resources/js/Material/Layouts/AuthenticatedLayout.vue`: tune responsive content width, margins, and first-visit rail state while preserving saved preferences.
- `resources/js/Material/Pages/Dashboard.vue` and its dashboard summary components: apply targeted desktop spacing and density adjustments.
- Mobile/tablet breakpoints, transaction data flow, skeletons, and business behavior remain unchanged.

## State and failure behavior

The rail preference remains in the existing local storage key. A missing preference uses the compact-desktop default; an explicit saved preference wins. If local storage is unavailable, preserve the app's established behavior rather than adding a silent fallback.

No data-fetching or error-state behavior changes are introduced. Existing loading skeletons, empty states, and transaction load errors remain owned by their current components.

## Validation

- Use Playwright at 1366×768 to compare the same dashboard data before and after: the dashboard should use more of the available content width and reveal more complete transaction rows without reducing semantic body text or hiding summary information.
- Check a larger desktop viewport to ensure density does not make the dashboard feel sparse or stretched.
- Check mobile and tablet viewports to confirm their navigation and single-column flow are unchanged.
- Verify expanded/collapsed rail behavior, persistence of an explicit preference, keyboard access, and absence of horizontal overflow.

## Success criteria

1. At 1366×768, the app does not force a smaller global root font size; app-bar and control geometry remain at their M3 defaults.
2. The dashboard's content width responds to available viewport space and the selected rail state instead of being capped by the former globally scaled width.
3. The first viewport retains net worth, income/expense, and transaction history while showing more complete recent transaction rows than the current layout.
4. Explicit rail preferences persist; mobile/tablet layout and financial color semantics remain unchanged.
