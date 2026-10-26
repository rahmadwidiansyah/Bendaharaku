# Design: Collapsed Rail Action Alignment

## Problem

In the collapsed desktop M3 navigation rail, the create FAB is centered, but navigation indicators are aligned to the left edge of their row. At 1366×768, the FAB center is at x=47.5px while the first indicator center is at x=36px, producing a visible horizontal mismatch.

## Design

When the rail is collapsed, horizontally center each navigation item’s contents so its indicator aligns with the create FAB. Preserve each navigation row’s full width and existing vertical spacing. The expanded rail keeps its current leading alignment; mobile navigation is unchanged.

## Validation

Use Playwright to measure the FAB and navigation indicator centers in collapsed state, verify expanded navigation remains leading-aligned, and confirm mobile navigation is unchanged.
