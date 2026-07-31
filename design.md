# Bendaharaku Design System v2.0

> **Mobile-first. Desktop seadanya.**
> A premium, elegant, and expressive design system for a finance-oriented application.

---

## 1. Design Philosophy

Our design is guided by a mobile-first philosophy, ensuring a seamless and intuitive experience on the primary user device. The desktop experience is an adaptive extension of the mobile design, not a separate entity.

### 1.1. Dual-Theme Approach

Bendaharaku employs a dual-theme system: **Dark Mode** and **Light Mode**. Both are treated as first-class citizens, each with a unique, intentionally designed visual identity.

-   **Dark Mode**: Optimized for low-light environments, focusing on high-contrast content set against a true-black background for OLED screens. It's immersive and focused.
-   **Light Mode**: Designed for clarity and comfort in well-lit environments. It uses a sophisticated surface hierarchy, nuanced shadows, and defined borders to create a sense of depth, order, and premium quality.

**Previous Philosophy (Deprecated):**
~~"Light Mode only changes CSS Variables."~~

**New Philosophy:**
> "Light Mode is intentionally designed with its own elevation, shadow, border, interaction, and visual hierarchy while sharing the same component architecture as Dark Mode."

### 1.2. Core Principles
- **Premium & Elegant**: The UI should feel crafted and high-quality, avoiding generic, "bootstrap-like" aesthetics.
- **Expressive**: The design language should be distinctive and memorable, reflecting the Bendaharaku brand.
- **High Contrast & Accessible**: We prioritize readability and usability for everyone, ensuring all text meets WCAG AA standards at minimum.
- **Finance-Oriented**: The design must instill trust and clarity, presenting financial data in a way that is easy to understand and act upon.
- **Not Flat**: We use layers, shadows, and materials to create a tangible and intuitive spatial model.

---

## 2. Elevation System

Elevation is a fundamental part of our design system, creating a clear and predictable spatial model that communicates hierarchy and interaction priority. It defines how surfaces are layered in z-space, how they are visually distinguished, and how they behave in both Light and Dark modes.

**Why**: A well-defined elevation system prevents a "flat" and confusing UI. It helps users understand the relationship between different UI elements, what is interactive, and what has focus.

**How**: Elevation is expressed through a combination of `z-index`, `box-shadow`, `border`, `background-color`, `opacity`, and `backdrop-filter` (blur). Light Mode relies more heavily on nuanced shadows and borders, while Dark Mode uses changes in surface brightness and glows.

### 2.1. Elevation Levels

Our system is composed of 10 distinct levels, from the base layer to the highest-level notifications. Each level has a specific purpose and a corresponding set of visual properties defined by design tokens.

| Level | Purpose | z-index | Shadow Token (Light) | Border Token | Blur | Opacity | Interaction Priority |
|-------|---------|---------|----------------------|--------------|------|---------|----------------------|
| **0** | **Base** | `0` | `none` | `none` | `none` | `1` | None. The foundational layer. |
| **1** | **Card** | `10` | `shadow-card` | `border-default` | `none` | `1` | Primary content containers. |
| **2** | **Sticky Section** | `20` | `shadow-sm` | `border-subtle` | `none` | `1` | Persists at top/bottom of a scroll view. |
| **3** | **Header** | `30` | `shadow-glass` | `border-glass` | `20px` | `0.85` | High. Persistent global navigation. |
| **4** | **Bottom Navigation** | `40` | `shadow-glass` | `border-glass` | `20px` | `0.85` | High. Persistent global navigation. |
| **5** | **Floating Action Button (FAB)** | `50` | `shadow-fab` | `none` | `none` | `1` | Highest. The primary screen action. |
| **6** | **Bottom Sheet / Drawer** | `60` | `shadow-modal` | `border-default` | `none` | `1` | Blocks screen interaction until dismissed. |
| **7** | **Modal** | `70` | `shadow-modal` | `border-default` | `none` | `1` | Blocks all app interaction until dismissed. |
| **8** | **Popover / Menu** | `80` | `shadow-modal` | `border-default` | `20px` | `0.85` | Contextual actions, dismissible on outside click. |
| **9** | **Toast / Notification** | `90` | `shadow-modal` | `none` | `none` | `1` | Highest ephemeral layer. Does not block interaction. |

*Note: A scrim/backdrop with `z-index` `Level - 1` is used for Modals and Bottom Sheets to de-emphasize the content below.*

### 2.2. Light Mode vs. Dark Mode Elevation

The expression of elevation changes significantly between modes to maintain the desired aesthetic and usability.

-   **Light Mode**:
    -   **Why**: To create a sense of depth, tangibility, and premium quality in a well-lit environment.
    -   **How**: Relies on a complex system of multi-layered, soft, diffuse shadows (`shadow-card`, `shadow-modal`). Borders (`border-default`) are used to give crisp definition to surfaces. The base surface (`--color-surface-base`) is a cool off-white to make pure white cards pop.

-   **Dark Mode**:
    -   **Why**: To create focus and reduce eye strain in low-light environments, while avoiding the "glowing gray" effect of overly complex shadows on a black background.
    -   **How**: Relies on changes in surface brightness. A higher elevation level has a lighter background color (e.g., Level 1 is `#111827`, Level 2 is `#1f2937`). Shadows are used more sparingly and are often simpler glows or sharp outlines (`shadow-black/70`). Borders (`rgba(255,255,255,0.1)`) are subtle and used to define shapes against the true-black background.

### 2.3. Glassmorphism (Level 3, 4, 8)

Glassmorphism is a specific style applied to surfaces that float above scrolling content, providing both aesthetic appeal and contextual awareness.

-   **Why**: To create a premium, modern feel for persistent navigation and contextual menus, allowing them to remain legible while hinting at the content landscape beneath.
-   **When**: Used for `Header` (Level 3), `Bottom Navigation` (Level 4), and `Popovers/Menus` (Level 8). It should not be used for primary content cards.
-   **How**: By combining a semi-transparent background, a `backdrop-filter: blur()`, and a subtle inner border.

| Property | Light Mode | Dark Mode | Token |
|----------|------------|-----------|-------|
| **Background** | `rgba(248, 250, 252, 0.85)` | `rgba(31, 41, 55, 0.8)` | `--color-glass-bg` |
| **Backdrop Blur** | `20px` | `24px` | `--blur-glass` |
| **Border** | `1px solid rgba(255, 255, 255, 0.5)` | `1px solid rgba(255, 255, 255, 0.15)` | `--color-glass-border` |
| **Shadow** | `shadow-glass` | `shadow-nav` | `shadow-glass` |

---

## 3. Color System

Our color system is built on a foundation of design tokens, managed via CSS Custom Properties. Dark and Light modes have independent token specifications.

### 3.1. Light Mode Color Tokens

Designed for clarity, contrast, and a premium feel.

#### Surface Colors (Light Mode)
| Token | Value | Usage |
|-------|-------|-------|
| `--color-surface-base` | `#F5F7FB` | Root background. A very light, cool gray that prevents pure white from feeling sterile. |
| `--color-surface-raised` | `#FFFFFF` | The primary surface for content cards. Pure white for maximum contrast with content. |
| `--color-surface-overlay` | `#FFFFFF` | Background for larger content panels. Sits above Base. |
| `--color-surface-muted` | `#EEF2F7` | Used for hover states on list items or subtle backgrounds for secondary information. |
| `--color-surface-subtle` | `#F8FAFC` | An even more subtle background, used for grouping elements within a raised surface. |

#### Text Colors (Light Mode)
| Token | Value | Usage |
|-------|-------|-------|
| `--color-text-primary` | `#0F172A` | Main text, headings. A near-black for excellent readability. |
| `--color-text-secondary`| `#475569` | Sub-headings, labels, secondary info. High contrast but clearly secondary. |
| `--color-text-muted` | `#64748B` | Timestamps, placeholder text. Still accessible (AA contrast) but clearly tertiary. |
| `--color-text-disabled` | `#94A3B8` | Text for disabled elements. |

#### Border Colors (Light Mode)
| Token | Value | Usage |
|-------|-------|-------|
| `--color-border-default` | `#E2E8F0` | Standard borders for cards and inputs. Provides clear separation. |
| `--color-border-subtle` | `#F1F5F9` | Dividers within a card or between list items. Very low contrast. |
| `--color-border-strong` | `#CBD5E1` | Used for hover/focus states on inputs or to highlight an active region. |
| `--color-border-interactive`| `var(--color-brand)` | Border for focused interactive elements. |

### 3.2. Dark Mode Color Tokens

Optimized for OLED screens and low-light environments.

| Token | Dark Value | Usage |
|-------|-----------|-------|
| `--color-surface-base` | `#000000` | Root background. |
| `--color-surface-raised` | `#111827` | Card backgrounds. |
| `--color-surface-overlay` | `#1f2937` | Main content panel, sidebar. |
| `--color-text-primary` | `#ffffff` | Main text, headings. |
| `--color-text-secondary` | `#9ca3af` | Labels, secondary info. |
| `--color-border-default` | `rgba(255,255,255,0.1)` | Standard card/panel borders. |

### 3.3. Financial Semantic Color Guidelines

In a financial application, color is not merely decorative; it is a critical part of the language used to communicate information. The Bendaharaku Semantic Color System ensures that financial data is instantly recognizable, consistent, and accessible across the entire application. Users should be able to identify the nature of a transaction by its color alone, even before reading any text.

**Official Semantic Meanings:**
The following color-to-meaning associations are fixed and must never be altered or used for other purposes. This is the official financial language of Bendaharaku.

-   **Green**: Income, positive cash flow, credit.
-   **Red**: Expense, negative cash flow, debit.
-   **Amber/Yellow**: Debt (Hutang), liabilities, money owed to others.
-   **Purple**: Receivable (Piutang), assets, money owed to you.
-   **Blue**: Transfer, internal movement of funds between accounts.

#### Universal Application
These semantic colors must be applied to **all** visual elements related to a financial type, not just text. This includes, but is not limited to:
-   Icons
-   Nominal Amounts
-   Badges & Pills
-   Borders & Accents
-   Backgrounds
-   Graphs & Charts
-   Progress Indicators
-   Calendar Dots
-   Notification Indicators
-   Empty State Illustrations

---

#### Financial Semantic Color Matrix

This matrix defines the specific color tokens for each semantic type across different UI elements and states. All colors are defined as design tokens to ensure consistency and theme-ability.

| Semantic | Meaning | Element | Light Mode Token | Dark Mode Token | Notes |
|---|---|---|---|---|---|
| **Green** | **Income** | **Text/Icon/Amount** | `var(--color-income-text)` | `var(--color-income-text-dark)` | High-contrast, readable color for primary information. |
| | | **Background** | `var(--color-income-bg)` | `var(--color-income-bg-dark)` | Subtle background for cards, badges, and highlights. |
| | | **Border** | `var(--color-income-border)` | `var(--color-income-border-dark)` | Used for card borders and dividers. |
| | | **Chart/Graph** | `var(--color-income-chart)` | `var(--color-income-chart-dark)` | The primary color for representing income in visualizations. |
| | | **Hover/Active** | `var(--color-income-bg-hover)` | `var(--color-income-bg-hover-dark)` | A slightly darker/brighter background for interactive states. |
| **Red** | **Expense** | **Text/Icon/Amount** | `var(--color-expense-text)` | `var(--color-expense-text-dark)` | High-contrast red for expense figures. |
| | | **Background** | `var(--color-expense-bg)` | `var(--color-expense-bg-dark)` | Subtle background for expense-related components. |
| | | **Border** | `var(--color-expense-border)` | `var(--color-expense-border-dark)` | Used for card borders and dividers. |
| | | **Chart/Graph** | `var(--color-expense-chart)` | `var(--color-expense-chart-dark)` | The primary color for representing expenses in visualizations. |
| | | **Hover/Active** | `var(--color-expense-bg-hover)` | `var(--color-expense-bg-hover-dark)` | Interactive state background. |
| **Amber** | **Debt** | **Text/Icon/Amount** | `var(--color-debt-text)` | `var(--color-debt-text-dark)` | High-contrast amber/yellow for debt figures. |
| | | **Background** | `var(--color-debt-bg)` | `var(--color-debt-bg-dark)` | Subtle background for debt-related components. |
| | | **Border** | `var(--color-debt-border)` | `var(--color-debt-border-dark)` | Used for card borders and dividers. |
| | | **Chart/Graph** | `var(--color-debt-chart)` | `var(--color-debt-chart-dark)` | The primary color for representing debt in visualizations. |
| | | **Hover/Active** | `var(--color-debt-bg-hover)` | `var(--color-debt-bg-hover-dark)` | Interactive state background. |
| **Purple** | **Receivable** | **Text/Icon/Amount** | `var(--color-receivable-text)` | `var(--color-receivable-text-dark)` | High-contrast purple for receivable figures. |
| | | **Background** | `var(--color-receivable-bg)` | `var(--color-receivable-bg-dark)` | Subtle background for receivable components. |
| | | **Border** | `var(--color-receivable-border)` | `var(--color-receivable-border-dark)` | Used for card borders and dividers. |
| | | **Chart/Graph** | `var(--color-receivable-chart)` | `var(--color-receivable-chart-dark)` | The primary color for representing receivables in visualizations. |
| | | **Hover/Active** | `var(--color-receivable-bg-hover)` | `var(--color-receivable-bg-hover-dark)` | Interactive state background. |
| **Blue** | **Transfer** | **Text/Icon/Amount** | `var(--color-transfer-text)` | `var(--color-transfer-text-dark)` | High-contrast blue for transfer figures. |
| | | **Background** | `var(--color-transfer-bg)` | `var(--color-transfer-bg-dark)` | Subtle background for transfer-related components. |
| | | **Border** | `var(--color-transfer-border)` | `var(--color-transfer-border-dark)` | Used for card borders and dividers. |
| | | **Chart/Graph** | `var(--color-transfer-chart)` | `var(--color-transfer-chart-dark)` | The primary color for representing transfers in visualizations. |
| | | **Hover/Active** | `var(--color-transfer-bg-hover)` | `var(--color-transfer-bg-hover-dark)` | Interactive state background. |

---

#### Implementation Rules & Examples

##### Transaction Rules
Every transaction item, whether in a list, detail view, or widget, must consistently use its semantic color.

-   **Income**: Green icon (e.g., `ArrowDownLeft`), green amount text.
-   **Expense**: Red icon (e.g., `ArrowUpRight`), red amount text.
-   **Debt**: Amber icon (e.g., `AlertTriangle`), amber amount text, and often an amber badge.
-   **Receivable**: Purple icon (e.g., `Wallet`), purple amount text, and often a purple badge.
-   **Transfer**: Blue icon (e.g., `ArrowLeftRight`), blue amount text.
-   **Negative Balance**: If a wallet's balance is negative, the nominal amount must be displayed in red, using `var(--color-expense-text)`.

**Bad Practice**: Never mix colors. An income transaction must not have a red icon. A debt item must not have green text.

##### Dashboard Rules
Dashboard components that summarize financial data must use semantic colors as accents.

-   **Mini Cashflow Cards**: The "Income" card must use a green background/border accent. The "Expense" card must use a red background/border accent.
-   **Charts & Graphs**: All charts (Bar, Pie, Line, etc.) must use the official semantic chart colors. A bar representing income must be green. The legend and tooltip for that data point must also use the green semantic color.
-   **Progress Bars**: A progress bar showing budget spending should use the red (Expense) color to fill the bar.
-   **Insights & Reports**: Any nominal value or percentage change displayed in a summary card must inherit its semantic color (e.g., "+15% This Month" for income should be green).

##### Icon Rules
Icons are a primary vehicle for semantic meaning. They must inherit the appropriate color.

| Meaning | Icon Example | Color |
|---|---|---|
| **Income** | `ArrowDownLeft`, `TrendingUp` | Green (`var(--color-income-text)`) |
| **Expense** | `ArrowUpRight`, `TrendingDown` | Red (`var(--color-expense-text)`) |
| **Debt** | `AlertTriangle`, `Clock` | Amber (`var(--color-debt-text)`) |
| **Receivable** | `Wallet`, `UserCheck` | Purple (`var(--color-receivable-text)`) |
| **Transfer** | `ArrowLeftRight`, `RefreshCw` | Blue (`var(--color-transfer-text)`) |

##### Badge & Pill Rules
Badges are used to convey status or category and must follow the semantic color system.

-   **Debt Badge**: Should use `var(--color-debt-bg)` for its background and `var(--color-debt-text)` for its text.
-   **Receivable Badge**: Should use `var(--color-receivable-bg)` for its background and `var(--color-receivable-text)` for its text.

##### Calendar Rules
Dots or indicators within calendar cells must use semantic colors to provide an at-a-glance summary of the day's financial activities.

-   **Green dot**: Indicates one or more income transactions on that day.
-   **Red dot**: Indicates one or more expense transactions.
-   **Amber dot**: Indicates a debt payment is due or was made.

**Priority Rule**: If multiple event types occur on the same day, the order of importance for display is **Expense > Income > Debt/Receivable > Transfer**. If space is limited to one dot, it should represent the highest priority event (e.g., if there is any expense, the dot is red).

---

#### Accessibility
Relying on color alone is not accessible. The semantic color system must always be paired with other signifiers.

1.  **WCAG AA Compliance**: All semantic color combinations (e.g., text on background) must meet a minimum contrast ratio of 4.5:1. This is non-negotiable and is handled by the specific token values for Light and Dark modes.
2.  **Always Pair with Icons and Labels**: The color reinforces the meaning, but the primary meaning is carried by a label ("Income") and/or an icon (`ArrowDownLeft`).
3.  **Use Patterns for Charts**: For complex charts, supplement colors with patterns (e.g., stripes for expenses, dots for income) to ensure they are distinguishable by users with color vision deficiencies.

---

#### AI Implementation Rules
These rules are mandatory for any AI agent generating or modifying UI components.

1.  **Never hardcode financial colors.** Always use the official semantic design tokens (e.g., `var(--color-income-text)`).
2.  **Never use arbitrary Tailwind CSS colors for financial data.** (e.g., `text-green-500` is forbidden; use the token).
3.  **Never mix semantic meanings.** Green is for income only. Red is for expense only.
4.  **Ensure every financial component correctly implements both Light and Dark mode** using the appropriate tokens.
5.  **Verify that any component using semantic colors also includes a non-color signifier** (icon, label) for accessibility.

---

## 4. Shadow System

Shadows are a critical tool for communicating elevation. Light and Dark modes have entirely separate shadow systems.

### 4.1. Light Mode Shadows

Light mode shadows are softer and more diffuse, creating a sense of depth without being distracting. They are composed of multiple layers.

| Shadow Name | Usage | CSS `box-shadow` Value |
|-------------|-------|------------------------|
| `shadow-sm` | Small interactive elements, badges. | `0 1px 2px 0 rgb(0 0 0 / 0.05)` |
| `shadow-card` | **Default for all cards.** | `0 1px 2px 0 rgb(20 29 43 / 0.03), 0 2px 8px 0 rgb(20 29 43 / 0.06)` |
| `shadow-card-hover` | Hover state for cards. | `0 1px 2px 0 rgb(20 29 43 / 0.04), 0 4px 12px 0 rgb(20 29 43 / 0.08)` |
| `shadow-modal` | Modals, Popovers, Drawers. | `0 8px 32px -4px rgb(20 29 43 / 0.1), 0 4px 12px -2px rgb(20 29 43 / 0.05)` |
| `shadow-glass` | For Glassmorphism surfaces. | `0 4px 12px 0 rgb(20 29 43 / 0.08)` |
| `shadow-fab` | Floating Action Buttons. | `0 6px 20px -4px rgb(0 0 0 / 0.15)` |

### 4.2. Dark Mode Shadows

Dark mode shadows are often simpler, relying on glows and subtle highlights as much as dark shadows.

| Element | Shadow |
|---------|--------|
| **Root layout panel** | `shadow-2xl shadow-black` |
| **PortfolioCard** | `shadow-lg shadow-black/20` |
| **Modal** | `shadow-2xl shadow-black/70` |
| **BottomNav** | `shadow-nav` (custom token) |

---

## 5. Border & Radius System

### 5.1. Borders
Borders provide definition and structure, especially in Light Mode.

| Border Name | Usage | Light Mode | Dark Mode |
|-------------|-------|------------|-----------|
| **Divider** | Separating list items. | `1px solid var(--color-border-subtle)` | `1px solid var(--color-border-subtle)` |
| **Default** | Standard card and input borders. | `1px solid var(--color-border-default)` | `1px solid var(--color-border-default)` |
| **Interactive** | Hover state on cards/inputs. | `1px solid var(--color-border-strong)` | `1px solid var(--color-border-strong)` |
| **Focus** | Focused inputs or buttons. | `2px solid var(--color-brand)` | `2px solid var(--color-brand)` |

### 5.2. Border Radius
Consistent corner rounding is key to our visual identity.

| Element | Classes | Token / Value |
|---------|---------|---------------|
| **PortfolioCard** | `rounded-xl` | `--radius-xl` (1rem) |
| **Mini Cashflow cards** | `rounded-lg sm:rounded-xl` | `--radius-lg` (0.75rem) → `--radius-xl` (1rem) |
| **Buttons, Inputs** | `rounded-lg` | `--radius-lg` (0.75rem) |
| **Modals, Drawers** | `rounded-xl sm:rounded-2xl` | `--radius-xl` (1rem) → `--radius-2xl` (1.5rem) |
| **Badges, Pills** | `rounded-full` | `--radius-pill` (9999px) |

---
## 6. Typography & Spacing System

**Why**: Consistent typography and spacing are the twin pillars of a clean, readable, and professional user interface. They create rhythm, guide the user's eye, and eliminate the cognitive load of a chaotic layout. This section provides the single source of truth for all text styles and spatial arrangements.

### 6.1. Typography System

**Philosophy**: Our system is built on a foundation of semantic typography tokens. Instead of using dozens of individual utility classes (`text-sm`, `font-bold`, `tracking-tight`), developers MUST use a single semantic class (e.g., `text-heading-1`) that encapsulates all the necessary properties (font size, weight, line height, letter spacing). This ensures consistency and makes global updates trivial.

**Font Family**:
-   **Primary**: `--font-sans: "MesloLGL Nerd Font", sans-serif;`

**Semantic Type Scale**:
This is the single source of truth for all text styles. Using literal Tailwind classes like `text-lg` or `font-bold` is forbidden.

| Semantic Token | Font Size | Weight | Line Height | Letter Spacing | Usage |
|---|---|---|---|---|---|
| `text-display-lg` | `48px` | `800` (Extra Bold) | `1.2` | `-0.02em` | Primary marketing headlines (rarely used in-app). |
| `text-display-md` | `36px` | `800` (Extra Bold) | `1.2` | `-0.02em` | Screen titles on large displays. |
| `text-heading-1` | `27px` | `800` (Extra Bold) | `1.3` | `-0.01em` | **Portfolio Total.** The most prominent metric in the app. |
| `text-heading-2` | `22px` | `700` (Bold) | `1.3` | `-0.01em` | Major screen section titles. |
| `text-heading-3` | `18px` | `700` (Bold) | `1.4` | `normal` | Card titles, Modal titles. |
| `text-body-lg` | `16px` | `400` (Normal) | `1.5` | `normal` | Long-form text, descriptions. |
| `text-body-md` | `14px` | `400` (Normal) | `1.5` | `normal` | **Default body text.** List item titles, input text. |
| `text-body-sm` | `12px` | `400` (Normal) | `1.5` | `normal` | Secondary text, list item subtitles, captions. |
| `text-label-lg` | `14px` | `600` (Semi-bold) | `1.4` | `0.01em` | Form labels, button text. |
| `text-label-md` | `12px` | `600` (Semi-bold) | `1.4` | `0.02em` | Smaller labels, tab titles. |
| `text-label-sm` | `10px` | `700` (Bold) | `1.4` | `0.05em` | Badges, overline text, "eyebrow" text. Uppercased. |

**Implementation**: These tokens must be configured in `tailwind.config.js` to be available as utility classes. For example, `.text-heading-1` would apply all the properties defined in its row.

### 6.2. Spacing & Grid System

**Philosophy**: Our system is based on a **4px grid**. All spacing values (padding, margin, gaps) MUST be a multiple of 4px and MUST use the following semantic tokens. Using literal values like `p-3` or `m-5` is forbidden.

**Spacing Scale**:

| Token | Value | Usage |
|---|---|---|
| `spacing-xs` | `4px` | Micro-spacing, e.g., between an icon and its text. |
| `spacing-sm` | `8px` | Spacing for small, dense elements. |
| `spacing-md` | `12px` | Standard gap between list items or elements within a component. |
| `spacing-lg` | `16px` | **Default padding** for most components (cards, inputs). |
| `spacing-xl` | `24px` | Gap between major components or sections on a page. |
| `spacing-2xl` | `32px` | Padding for larger page sections. |
| `spacing-3xl` | `48px` | Spacing for large, spacious layouts. |

**Implementation**: These tokens must be configured in `tailwind.config.js` to be available for padding (`p-lg`), margin (`m-xl`), and gap (`gap-md`) utilities.

### 6.3. Layout Rules
-   **Page Padding**: All screen content MUST have a horizontal padding of `spacing-xl` (24px) to ensure a safe area from the screen edges.
-   **Card Padding**: All cards MUST use `spacing-lg` (16px) for their internal padding.
-   **List Item Spacing**: The vertical space between items in a list MUST be `spacing-md` (12px).
-   **Form Spacing**: The vertical gap between form fields MUST be `spacing-lg` (16px).

---

## 7. Component Specifications (v2.0 Redesign)

This section details the new design specifications, with a focus on the redesigned Light Mode experience.

### 7.1. PortfolioCard

**Goal:** Redesign to feel more premium and less empty, inspired by Apple Wallet.

**Light Mode:**
- **Background**: `var(--color-surface-raised)` (`#FFFFFF`).
- **Border**: `1px solid var(--color-border-default)`.
- **Shadow**: `shadow-card`, transitioning to `shadow-card-hover` on hover.
- **Layout**: A subtle gradient can be applied to the top portion of the card using the user's chosen brand accent color, fading to white. This adds a touch of personality.
- **Wallet Items**: Each wallet is a distinct sub-card with a `bg-[var(--color-surface-subtle)]` and a `border-[var(--color-border-subtle)]`. On hover, the sub-card lifts with a `shadow-sm`.

```
// PortfolioCard Light Mode Example
┌──────────────────────────────────────────┐
│ 🔵 Total Kekayaan                   👁  │  <- Subtle brand gradient here
│  Rp 50.000.000                          │
│  ─────────────────────────────────────  │
│  ▸ Wallet (3)                           │
│    ┌─ Dompet Cash ─────── Rp 2.000.000 ┐  │  <- Sub-card with own bg/border
│    └─ Bank BCA ────────── Rp 8.000.000 ┘  │
└──────────────────────────────────────────┘
```

### 7.2. Mini Cashflow (Income / Expense)

**Goal:** Use semantic backgrounds instead of plain white cards for better at-a-glance information.

**Light Mode:**
- **Income Card**:
    - **Background**: `var(--color-income-bg)` (e.g., `green-50`).
    - **Border**: `1px solid var(--color-income-border)` (e.g., `green-200`).
    - **Text**: `var(--color-income-text)` (e.g., `green-600`).
    - **Icon**: `green-500`.
- **Expense Card**:
    - **Background**: `var(--color-expense-bg)` (e.g., `red-50`).
    - **Border**: `1px solid var(--color-expense-border)` (e.g., `red-200`).
    - **Text**: `var(--color-expense-text)` (e.g., `red-600`).
    - **Icon**: `red-500`.

```
// Mini Cashflow Light Mode Example
┌──────────┐  ┌──────────┐
│  📈      │  │  📉      │
│  INCOME  │  │  EXPENSE  │
│ +Rp 5jt  │  │ -Rp 3jt  │
└──────────┘  └──────────┘
(Income card has light green bg, Expense has light red bg)
```

### 7.3. Calendar

**Goal:** Improve contrast and clarity for all states.

**Light Mode:**
| State | Background | Border | Text |
|-------|------------|--------|------|
| **Default** | `var(--color-surface-muted)` | `transparent` | `var(--color-text-secondary)` |
| **Hover** | `var(--color-surface-muted)` | `1px solid var(--color-border-strong)` | `var(--color-text-primary)` |
| **Today** | `var(--color-surface-raised)` | `1px solid var(--color-brand)` | `var(--color-brand)` |
| **Selected** | `var(--color-brand)` | `1px solid var(--color-brand)` | `white` |
| **Outside Month** | `var(--color-surface-base)` | `transparent` | `var(--color-text-disabled)` |

### 7.4. GlobalHeader & BottomNav

**Goal:** Create premium, floating glass navigation that provides context.

**Light Mode & Dark Mode:**
- **Implementation**: Apply the `Glassmorphism` specification from Section 2.2.
- **Behavior**: The header and bottom navigation are fixed. As content scrolls underneath, the blur effect provides readability for the navigation items while hinting at the content below.
- **Shadow**: Use `shadow-glass` to lift the navigation off the page.
- **Border**: A subtle, semi-transparent border adds a finished edge to the glass effect.

---

## 8. Component States & Interaction

A component is not a static entity; it is a collection of states. Defining these states with precision is what makes an interface feel responsive, predictable, and alive. This section provides a detailed matrix for the appearance and behavior of our core components across their various states.

**Why**: Without clear state definitions, user interactions feel dead. Users get no feedback on whether their tap was registered, if they can interact with an element, or if the system is working. This leads to confusion and a poor user experience.

**How**: States are communicated through a combination of color, shadow, scale, opacity, and motion. All transitions between states must use the tokens defined in the **Motion System**.

### 8.1. General State Definitions

-   **Default**: The component's resting state.
-   **Hover**: The state when a pointer is over the component. It signals interactivity.
-   **Pressed**: The moment a user clicks or taps the component. It provides immediate feedback that the input was received.
-   **Focused**: The state when a component is highlighted for input, usually via keyboard navigation (e.g., `Tab` key). Crucial for accessibility.
-   **Selected**: A persistent state indicating the component is active, chosen, or part of a selection (e.g., an active tab).
-   **Disabled**: The component is not interactive. It must be visually distinct and not respond to user input.
-   **Loading**: The component is waiting for an asynchronous action to complete. It should provide feedback to the user that the system is working.
-   **Skeleton**: A placeholder representation of the component used before data has loaded, preventing layout shifts and providing a sense of progress.
-   **Error**: The component is in an error state, often due to failed validation or a system issue.
-   **Success**: The component is in a success state, confirming a user action was completed successfully.

### 8.2. State Matrix: Buttons

Buttons are the primary interactive elements. We have three main variants: **Primary**, **Secondary**, and **Tertiary (Ghost)**.

#### Primary Button (High Emphasis)
*Used for the main call to action on a screen.*

| State | Visual Treatment (Light Mode) | Visual Treatment (Dark Mode) | Behavior / Animation |
|---|---|---|---|
| **Default** | `bg-[var(--color-brand)]`, `text-white`, `shadow-sm` | `bg-[var(--color-brand)]`, `text-white`, subtle glow | Rests at elevation. |
| **Hover** | `bg-[var(--color-brand-hover)]`, `shadow-md` | `bg-[var(--color-brand-hover)]` | Lifts slightly. `transform: translateY(-2px)`, `duration-200`, `ease-out`. |
| **Pressed** | `bg-[var(--color-brand-pressed)]`, `shadow-sm` | `bg-[var(--color-brand-pressed)]` | Scales down. `transform: scale(0.97)`, `duration-100`, `ease-in`. |
| **Focused** | Default state + `ring-2 ring-offset-2 ring-[var(--color-brand)]` | Default state + `ring-2 ring-offset-2 ring-[var(--color-brand)]` | Focus ring appears instantly. |
| **Disabled** | `bg-gray-200`, `text-gray-400`, no shadow | `bg-gray-700`, `text-gray-500`, no shadow | `cursor-not-allowed`, `opacity-50`. |
| **Loading** | Default state with a centered spinner icon replacing the text/icon. | Default state with a centered spinner icon. | Spinner rotates. Button maintains width to prevent layout shift. |

#### Secondary Button (Medium Emphasis)
*Used for secondary actions, like "Cancel" or "View Details".*

| State | Visual Treatment (Light Mode) | Visual Treatment (Dark Mode) | Behavior / Animation |
|---|---|---|---|
| **Default** | `bg-white`, `text-[var(--color-text-primary)]`, `border border-[var(--color-border-default)]` | `bg-gray-800`, `text-white`, `border border-gray-600` | Rests at elevation. |
| **Hover** | `bg-[var(--color-surface-muted)]` | `bg-gray-700` | No transform. Simple background fade. `duration-150`. |
| **Pressed** | `bg-gray-200` | `bg-gray-600` | No transform. Simple background fade. `duration-100`. |
| **Focused** | Default state + `ring-2 ring-offset-2 ring-[var(--color-brand)]` | Default state + `ring-2 ring-offset-2 ring-[var(--color-brand)]` | Focus ring appears instantly. |
| **Disabled** | `bg-white`, `text-gray-400`, `border-gray-200` | `bg-gray-800`, `text-gray-500`, `border-gray-700` | `cursor-not-allowed`, `opacity-50`. |

#### Tertiary / Ghost Button (Low Emphasis)
*Used for low-priority actions, often within cards or alongside other content.*

| State | Visual Treatment (Light Mode) | Visual Treatment (Dark Mode) | Behavior / Animation |
|---|---|---|---|
| **Default** | `bg-transparent`, `text-[var(--color-text-secondary)]` | `bg-transparent`, `text-gray-400` | No elevation. |
| **Hover** | `bg-[var(--color-surface-muted)]`, `text-[var(--color-text-primary)]` | `bg-white/10`, `text-white` | Background fades in. `duration-150`. |
| **Pressed** | `bg-gray-200` | `bg-white/20` | Background fades in. `duration-100`. |
| **Focused** | Default state + `ring-2 ring-[var(--color-brand)]` | Default state + `ring-2 ring-[var(--color-brand)]` | Focus ring appears instantly. |
| **Disabled** | `text-gray-400` | `text-gray-600` | `cursor-not-allowed`, `opacity-50`. |

### 8.3. State Matrix: Inputs

*Standard text inputs, textareas, and select fields.*

| State | Visual Treatment (Light Mode) | Visual Treatment (Dark Mode) | Behavior / Animation |
|---|---|---|---|
| **Default** | `bg-white`, `border border-[var(--color-border-default)]`, placeholder text is `var(--color-text-muted)` | `bg-gray-800`, `border border-gray-600`, placeholder text is `gray-500` | Rests at elevation. |
| **Hover** | `border-[var(--color-border-strong)]` | `border-gray-500` | Border color transition. `duration-200`. |
| **Focused** | `border-[var(--color-brand)]`, `ring-2 ring-[var(--color-brand)]/30` | `border-[var(--color-brand)]`, `ring-2 ring-[var(--color-brand)]/30` | Border and ring fade in. `duration-200`. |
| **Disabled** | `bg-gray-100`, `text-gray-400`, `border-gray-200` | `bg-gray-900`, `text-gray-600`, `border-gray-800` | `cursor-not-allowed`. |
| **Error** | `border-red-500`, `ring-2 ring-red-500/30` | `border-red-400`, `ring-2 ring-red-400/30` | Error state overrides all others except disabled. |
| **Success** | `border-green-500`, `ring-2 ring-green-500/30` | `border-green-400`, `ring-2 ring-green-400/30` | Success state is usually temporary, shown after validation. |

---

## 9. Motion System

Motion is a critical aspect of our user interface, serving not as decoration, but as a powerful tool for communication. Our motion system is designed to be fluid, responsive, and meaningful, guiding the user's attention and providing clear feedback.

### 9.1. Motion Philosophy

Every animation must have a purpose. Motion should be used to:

-   **Communicate Hierarchy**: Animations on page load establish the importance and relationship of elements. For example, the main portfolio card animates in first, followed by secondary elements.
-   **Indicate Cause & Effect**: When a user interacts with an element, motion provides immediate feedback, confirming the action and showing its result (e.g., a button press causing a modal to appear).
-   **Guide Focus**: Motion directs the user's eye to what is important, such as a new element appearing on screen or an error message that requires attention.
-   **Provide Feedback**: Animations confirm that the system has received a user's input, such as a pull-to-refresh gesture or a successful data submission.

**We never use motion for purely decorative purposes.** Gratuitous or distracting animations are to be avoided.

### 9.2. Core Principles: Duration, Easing, and Physics

#### Standard Durations
To maintain consistency, all animations should use one of the standard duration tokens.

| Token | Value | Usage |
|---|---|---|
| `--duration-100` | `100ms` | Quick feedback on micro-interactions (e.g., button press scale). |
| `--duration-150` | `150ms` | Fades, very short transitions. |
| `--duration-200` | `200ms` | **Default.** Used for most state changes, hovers, and fades. |
| `--duration-300` | `300ms` | Larger UI element transitions (e.g., modals, bottom sheets, accordions). |
| `--duration-400` | `400ms` | Complex or large-scale transitions. |
| `--duration-500` | `500ms` | Full-screen page transitions. |

#### Easing Curves
Easing adds personality and physical realism to motion.

| Token | Value | Usage |
|---|---|---|
| `--ease-in-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | **Default.** For elements that move across the screen or change size. |
| `--ease-out` | `cubic-bezier(0, 0, 0.2, 1)` | For elements entering the screen (e.g., modals, drawers). They start fast and slow down. |
| `--ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | For elements leaving the screen. They start slow and accelerate away. |
| `--ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | A bouncy, overshooting curve for playful or attention-grabbing moments. Use sparingly. |

### 9.3. Animation Specifications

This section defines the specific animations for common UI patterns.

| Pattern | Animation Details |
|---|---|
| **Page Transition** | **Why**: To create a smooth flow between screens. **How**: The outgoing screen fades out (`opacity: 0`, `duration-200`, `ease-in`). The incoming screen fades in and slides up slightly (`opacity: 1`, `transform: translateY(0)`, `duration-300`, `ease-out`). |
| **Modal / Bottom Sheet** | **Why**: To show a clear relationship between the trigger and the new surface. **How**: A scrim fades in (`opacity: 0.7`, `duration-300`). The modal scales up and fades in from the center (`transform: scale(0.95)`, `opacity: 0` to `scale(1)`, `opacity: 1`, `duration-300`, `ease-out`). The Bottom Sheet slides up from the bottom (`transform: translateY(100%)` to `translateY(0)`, `duration-300`, `ease-out`). |
| **FAB (Floating Action Button)** | **Why**: To feel responsive and physical. **How**: On hover, it lifts slightly (`transform: translateY(-4px)`, `duration-200`). On press, it scales down (`transform: scale(0.9)`, `duration-100`). If it reveals other options, they should animate out in an arc (`staggered`, `duration-300`, `ease-spring`). |
| **Navigation (Tabs)** | **Why**: To provide a clear visual indicator of the active state. **How**: The active indicator (a colored bar or pill) slides smoothly from the old tab to the new tab. The content of the tabs should cross-fade (`duration-200`). |
| **Accordion / Collapse** | **Why**: To smoothly reveal or hide content without abrupt jumps. **How**: Animate `grid-template-rows` from `0fr` to `1fr` (`duration-300`, `ease-in-out`). The chevron icon should rotate `90deg` with the same duration and easing. |
| **Calendar Transition** | **Why**: To provide a clear directional cue when changing months. **How**: When navigating to the next month, the current month's grid slides out to the left, and the new month's grid slides in from the right. The reverse happens for the previous month. Use `transform: translateX()`, `duration-400`, `ease-in-out`. |
| **Loading (Skeletons)** | **Why**: To indicate that content is loading without using a jarring spinner. **How**: Skeleton placeholders should have a soft, pulsing shimmer effect. This is achieved with a CSS animation that moves a gradient across the placeholder element (`duration-1500ms`, `infinite`, `ease-in-out`). |
| **Pull To Refresh** | **Why**: To provide satisfying physical feedback. **How**: As the user pulls down, the refresh icon should scale and rotate in sync with the pull distance. When the threshold is met, the icon animates into a spinner. |

---

## 10. Loading & Progressive Rendering System

The loading experience is not a technical afterthought; it is a core component of the user experience. A well-designed loading system transforms moments of waiting into moments of anticipation and clarity. It is the invisible hand that guides the user, maintains context, and makes the application feel fast and responsive, even when network conditions are suboptimal. This chapter provides the official guidelines for all loading patterns in Bendaharaku, ensuring a consistent, premium, and predictable experience.

### 10.1. Loading Philosophy

**Why**: Loading is an unavoidable part of any data-driven application. Our philosophy is to treat loading not as an interruption, but as an integral part of the interface's narrative. Every loading state must serve a purpose, reinforcing the user's confidence in the system.

**How**: We achieve this through a set of core principles:
-   **Reduce Uncertainty**: The user should always know what the system is doing. A loading indicator is a promise that content is on its way.
-   **Maintain Layout Stability**: The interface structure must never change during a loading process. This is the cardinal rule. A loading state that causes content to jump or reflow (Cumulative Layout Shift, or CLS) is a critical bug.
-   **Prevent Visual Jumps**: Avoid jarring transitions from a loading state to the final content. The transition should be seamless, often achieved by matching the skeleton's geometry to the final component's layout.
-   **Preserve User Context**: Loading a new piece of data should not cause the user to lose their place or orientation within the application. Partial and progressive loading are key to this.
-   **Improve Perceived Performance**: A user's perception of speed is more important than the actual load time. A well-orchestrated loading sequence can make a 2-second wait feel instantaneous, while a poorly managed 500ms wait can feel sluggish.
-   **Prefer Progressive Rendering**: The screen should fill with content as it becomes available. Never block the entire UI for a single slow request. The user should be able to interact with loaded content while other parts of the page are still fetching data.
-   **Avoid Blank Pages and Spinner-Only Screens**: These are the hallmarks of a lazy loading strategy. They are disorienting and communicate nothing about what is to come. A skeleton is always preferable.

### 10.2. Loading Decision Matrix

**Why**: To eliminate subjective implementation decisions. This matrix provides a clear, time-based framework for choosing the correct loading indicator. The thresholds are based on human perception and established UX research.

| Latency | User Perception | Required Action | Implementation Details |
|---|---|---|---|
| **< 100ms** | **Instant** | **Do Nothing.** The response feels immediate. | **Why**: Introducing any loading indicator would create a "flash" that is more jarring than the brief wait itself. The UI should simply update. |
| **100ms – 300ms** | **Immediate** | **Small Inline Indicator.** | **Why**: The user notices a very slight delay. A small, localized spinner (e.g., inside a button after a click) or a subtle opacity change confirms the action was registered without blocking the UI. |
| **300ms – 700ms** | **Fast** | **Skeleton Loader.** | **Why**: The delay is noticeable. A skeleton loader is now required to maintain layout stability and manage user expectation. The skeleton should appear instantly. |
| **700ms – 3s** | **Normal** | **Skeleton + Placeholder Content.** | **Why**: The user is actively waiting. The skeleton should be enhanced with placeholder content (e.g., generic icons, muted text lines) to make the loading state more informative and less abstract. |
| **> 3s** | **Slow** | **Progress Indicator or Message.** | **Why**: The user's attention may be wavering. If the total duration is known (e.g., file upload), use a progress bar. If not, provide a message: "Fetching a large amount of data..." |
| **Long Request** | **Background Task** | **Persistent Loading Message.** | **Why**: For tasks like generating a report. The UI should be fully interactive. A message in a non-modal toast or a status area should inform the user: "Your report is being generated. We'll notify you when it's ready." |
| **Failure** | **Error** | **Error State with Retry.** | **Why**: The promise of content was broken. The UI must clearly communicate the failure and provide a clear, actionable path to recovery (e.g., a "Retry" button). |
| **Offline** | **Disconnected** | **Offline Placeholder.** | **Why**: The request cannot be made. The UI must immediately inform the user of their offline status and, if possible, show cached data or an offline-specific empty state. |

### 10.3. Skeleton System

**Why**: Skeletons are the cornerstone of our loading strategy. They solve the critical problem of layout shift by rendering a low-fidelity, static preview of the UI before the data is available.

**How**: A skeleton is a component that visually mimics the structure of the final UI element it represents. It must always be a 1:1 match in terms of dimensions and spacing to prevent any layout shift when the real content loads.

| Property | Light Mode | Dark Mode | Token / Notes |
|---|---|---|---|
| **Shape & Radius** | Matches the final component exactly. | Matches the final component exactly. | Use the same border-radius tokens (`--radius-lg`, `--radius-xl`, etc.). |
| **Spacing** | Matches the final component's margins and padding. | Matches the final component's margins and padding. | Skeletons must live within the same grid and flexbox containers as the final content. |
| **Surface** | `var(--color-surface-muted)` | `var(--color-surface-overlay)` | The skeleton's background should be subtly different from the base surface to be visible. |
| **Border** | `1px solid var(--color-border-subtle)` | `1px solid var(--color-border-default)` | Provides a faint outline, helping to define the shape, especially on similar-colored backgrounds. |
| **Opacity** | `1.0` | `1.0` | The skeleton itself is not transparent. The shimmer animation provides the sense of activity. |
| **Animation** | Shimmer | Shimmer | See Section 10.7 for shimmer specifications. |

**Best Practices**:
-   **Never let a skeleton change the layout.** The skeleton's box model (width, height, padding, margin) must be identical to the final content's box model.
-   **Avoid oversized placeholders.** A skeleton for a line of text should have the same height as the text, not a giant block. Be precise.
-   Create dedicated, reusable Skeleton Components (e.g., `PortfolioCardSkeleton`, `TransactionItemSkeleton`).

**Bad Practices**:
-   Showing a single, large skeleton for an entire page.
-   Using a spinner inside a skeleton block.
-   The skeleton's dimensions being different from the final content, causing a jump.

### 10.4. Skeleton Component Library

This section is a partial specification. Every component in the app that loads data asynchronously must have a corresponding skeleton variant.

| Component | Skeleton Specification |
|---|---|
| **Portfolio Card** | A single card matching the exact `rounded-xl` dimensions. Inside, a short, wide block for the title, a taller, wider block for the total amount, and a thin line for the divider. No wallet details are shown. |
| **Wallet Card** | A simplified version of the wallet item. An icon shape on the left, and two lines of text of different widths on the right. |
| **Mini Cashflow** | Two cards with the correct `rounded-lg` and spacing. Each contains a square for the icon and two short text blocks. The semantic background color is NOT used in the skeleton. |
| **Calendar** | The calendar grid is shown with all date cells rendered as faint squares. The current day/selected states are not shown. |
| **Transaction List** | A container matching the list's dimensions, containing 5-7 `TransactionItemSkeleton` components. |
| **Transaction Item** | An icon shape on the left. On the right, two text blocks (a wider one for the name, a shorter one for the category) and a third, shorter block aligned to the far right for the amount. |
| **Chart** | A container with the correct aspect ratio. Inside, show a simplified version of the axes (thin lines) and a flat line or a few representative bars instead of the full dataset. |

### 10.5. Progressive Rendering

**Why**: To make the application feel alive and immediately useful. Users can start consuming information from the fastest-loading components while slower ones catch up. This is critical for the main dashboard.

**How**: The dashboard is orchestrated to render in a specific, top-to-bottom, most-important-to-least-important order. Each component should fetch its own data and replace its skeleton independently.

**Dashboard Rendering Order**:
1.  **Shell & Navigation**: The `Header` and `BottomNav` render instantly. They are static UI.
2.  **Portfolio Card**: This is the most important component. Its skeleton is visible immediately. It should be the first component to fetch data and transition to content.
3.  **Mini Cashflow Cards**: These are secondary but high-value. They load concurrently with the Portfolio Card but may resolve after.
4.  **Calendar**: The calendar can be complex. It loads after the main financial summaries.
5.  **Statistics / Charts**: These often require more complex queries. They load next.
6.  **Transaction List**: This can be a large dataset. The initial view loads last on the main screen.
7.  **Insights & Recommendations**: These are the least critical and can be lazy-loaded on scroll.

**Developer Note**: Never create a chain of dependent requests on the dashboard. All components should fetch their data in parallel. The rendering order is a perceived effect managed by component placement and data resolution speed.

### 10.6. Partial Loading

**Why**: To preserve user context and minimize data transfer. Refreshing one part of the UI should not force a full-page reload.

**How**: Component data fetching should be granular. When a user performs an action, only the affected components should enter a loading state.

**Rules for Partial Refresh**:
-   **Action**: User adds a new transaction from a modal.
    -   **Result**: The modal's "Save" button enters a loading state (spinner). Upon success, the modal closes. The `Transaction List` and `Portfolio Card` components re-fetch their data and transition from content -> skeleton -> new content. The `Header`, `Calendar`, and `Mini Cashflow` cards are unaffected.
-   **Action**: User pulls-to-refresh the transaction list.
    -   **Result**: Only the `Transaction List` shows a refresh indicator and re-fetches its data. All other dashboard components remain static.
-   **Action**: User renames a wallet.
    -   **Result**: Optimistic UI (see 10.9). The name updates instantly. A background request is sent. Only if it fails does the UI revert and show an error. No other components are reloaded.

### 10.7. Shimmer Animation

**Why**: The shimmer provides a subtle sense of activity to the static skeleton, communicating that the system is working. It's more elegant than a pulsing opacity.

**How**: A soft, angled gradient sweeps across the skeleton component.

| Property | Value | Notes |
|---|---|---|
| **Direction** | `-45deg` (Top-left to bottom-right). | Provides a natural, dynamic feel. |
| **Gradient** | A transparent-to-white-to-transparent gradient. | `transparent, rgba(255,255,255,0.5), transparent` for Dark Mode. `transparent, rgba(255,255,255,0.8), transparent` for Light Mode. |
| **Speed / Duration** | `1.5s` | Fast enough to be noticeable, slow enough not to be distracting. |
| **Loop** | `infinite` | The animation repeats until the content is loaded. |
| **Easing** | `linear` | The shimmer should move at a constant speed. |
| **Reduced Motion** | The shimmer animation MUST be disabled. The skeleton remains, but is static. | Check for `(prefers-reduced-motion: reduce)`. |

**Bad Practice**: Using the shimmer on actual content or as a standalone loading indicator without a skeleton.

### 10.8. Spinner Rules

**Why**: Spinners are a powerful but easily abused loading indicator. They are visually loud and block content. In Bendaharaku, spinners are used sparingly and with clear purpose. Their use implies a short, blocking, user-initiated action.

**When to Use a Spinner**:
-   **Button Action**: A user clicks a button (e.g., "Save", "Login"), and the action takes 100-1000ms. The spinner replaces the button's text/icon, providing immediate feedback on that specific interactive element. The button MUST maintain its width.
-   **Small Inline Loading**: A tiny piece of data within a larger component is being fetched (e.g., checking a username's availability). A very small spinner can appear next to the input.
-   **System-level Blocking Actions**: For actions that genuinely block interaction until completion, such as:
    -   File Upload / Export
    -   Initial App Sync / Authentication
    -   Applying a theme or language change.

**When NOT to Use a Spinner**:
-   **Never use a spinner for page or dashboard loading.** Use skeletons.
-   **Never place a spinner over the top of existing UI (z-index abuse).**
-   **Never use a large, full-screen spinner after the initial app load.**

### 10.9. Optimistic UI

**Why**: For very common and typically successful actions, waiting for server confirmation feels slow. Optimistic UI improves perceived performance by updating the interface *before* the server has responded.

**How**: When the user performs an action, the UI updates instantly. A request is sent to the server in the background. If the request succeeds, nothing more happens. If it fails, the UI "rolls back" to its previous state and an error is displayed.

**Candidates for Optimistic UI**:
-   **Transaction Creation**: Appears in the list immediately, possibly with a subtle "pending" indicator.
-   **Toggles**: Settings switches, marking a transaction as "favorite," etc.
-   **Simple Edits**: Renaming a wallet or category.
-   **Archiving/Deleting**: The item is removed from the list immediately.

**Rollback Behavior**:
-   If an optimistic update fails, the change must be reverted smoothly.
-   A toast notification should appear explaining the failure (e.g., "Could not delete transaction. Please try again.").
-   For a failed deletion, the item should fade back into the list.
-   For a failed creation, the pending item should be removed with a small animation and an error shown.

### 10.10. Refresh Patterns

| Pattern | Implementation |
|---|---|
| **Pull To Refresh** | Used in scrollable lists (e.g., Transactions). A custom, branded refresh indicator should be used, integrating with the Motion system. |
| **Manual Refresh** | A dedicated refresh button. Should only be used on pages where data can become stale and pull-to-refresh is not available (e.g., a complex dashboard on desktop). |
| **Auto Refresh** | Data should not auto-refresh while the user is interacting with it. If implemented, it should be silent and only update non-critical information in the background. |
| **Background Refresh** | The app may silently fetch new data when it is brought to the foreground after being in the background for a period of time (e.g., > 5 minutes). |

### 10.11. Infinite Scroll

| Behavior | Implementation |
|---|---|
| **Load Trigger** | New items are fetched when the user scrolls to within 1.5 viewport heights of the end of the list. |
| **Loading Indicator** | A `TransactionItemSkeleton` is appended to the bottom of the list while the next page is loading. Do not use a spinner. |
| **End of List** | A respectful, centered message: "You've reached the end." No more requests are made. |
| **Error** | A small error component is appended to the bottom of the list with a "Retry" button. |

### 10.12. Lazy Loading

**Why**: To improve initial page load time by deferring the loading of non-critical, "below-the-fold" content.

**How**: Use `IntersectionObserver` to trigger the loading of components only when they are about to enter the viewport.

**Candidates for Lazy Loading**:
-   **Charts & Analytics**: These are often heavy and can be loaded when the user scrolls to them.
-   **Images & Avatars**: All images should be lazy-loaded by default.
-   **Historical Data**: Older transactions or reports that are not part of the initial view.
-   **Heavy Components**: Any component that is not essential for the initial interactive experience.

### 10.13. Error Loading

**Why**: A robust app anticipates failure. Every loading state must have a corresponding error state.

**How**: Errors must be contextual, informative, and actionable.

| Error Type | UI Response |
|---|---|
| **Timeout / Network Error** | In-component error message with a "Retry" button. Example: "Could not load transactions. Tap to retry." |
| **Unauthorized (401)** | The user's session has expired. Immediately redirect to the login screen with a message: "Your session has expired. Please log in again." |
| **Server Error (5xx)** | A generic error message within the component: "A server error occurred. Please try again later." A "Retry" button should be available. |
| **Offline** | A global, non-blocking banner at the top or bottom of the screen: "You are currently offline." Components should use cached data if available, or show a specific offline state. |

### 10.14. Empty State System

**Why**: An empty state is an opportunity to guide the user, not a dead end. It should be encouraging and actionable.

**How**: Every module that can be empty must have a defined empty state.

**Standard Empty State Layout**:
1.  **Illustration**: A subtle, on-brand illustration from our asset library.
2.  **Headline**: A clear, concise message (e.g., "No transactions yet").
3.  **Description**: A brief, helpful explanation (e.g., "Add your first transaction to get started.").
4.  **Primary Button**: A clear call to action (e.g., "+ Add Transaction").

### 10.15. Accessibility

**Why**: Loading states can be disorienting for users of assistive technologies. We must ensure our loading experience is accessible to everyone.

**How**:
-   **`aria-busy="true"`**: Set this attribute on any container that is currently loading (e.g., the container that holds the skeleton). This informs screen readers that the section is updating.
-   **`aria-live="polite"`**: Use this on regions where content will dynamically load. When the loading is complete, the new content will be announced by the screen reader without interrupting them.
-   **Reduced Motion**: All animations, including the shimmer and spinners, MUST be disabled or replaced with a simple fade if the user has `prefers-reduced-motion` enabled.
-   **Focus Management**: After content loads, ensure focus is managed logically. Do not trap the user in a loading state.

### 10.16. Motion Integration

**Why**: The transition from a loading state to content is a key moment. It should be smooth and delightful, not jarring.

**How**: We use the Motion System tokens to orchestrate the reveal of content.

-   **Content Reveal**: When data is ready, the skeleton component should fade out (`opacity: 0`, `duration-200`) while the content component fades in (`opacity: 1`, `duration-300`). This cross-fade prevents a harsh "pop-in".
-   **Staggered Appearance**: For lists, items should fade in and slide up one by one, with a very short delay (`50ms`) between each. This creates an elegant "cascade" effect.

### 10.17. Performance Budget

**Why**: Loading animations can be computationally expensive. We must ensure they are performant and do not degrade the user experience.

**Rules**:
-   **CLS Near Zero**: The Cumulative Layout Shift score for any loading transition must be as close to 0 as possible. This is non-negotiable.
-   **GPU-Friendly Animations**: All animations must use properties that can be hardware-accelerated: `transform` and `opacity`. Never animate `width`, `height`, `margin`, or `top/left`.
-   **Avoid Expensive Blurs**: While `backdrop-filter` is used in our glassmorphism, avoid applying filter animations during loading states as they are very performance-intensive.

### 10.18. AI Implementation Rules

**Why**: To ensure that AI coding agents adhere strictly to these guidelines without deviation.

**Mandatory Rules**:
1.  **Always use a Skeleton for component-level loading.** Never use a Spinner.
2.  **Never block the entire page after initial load.** Implement progressive and partial loading.
3.  **The layout MUST be preserved.** A Skeleton's dimensions must exactly match the final component's dimensions.
4.  **Always reuse shared Skeleton components.** Do not create one-off skeleton styles.
5.  **All loading patterns must support both Light and Dark Mode using design tokens.**
6.  **All animations must respect `prefers-reduced-motion`.**
7.  **Always use design tokens for colors, spacing, and animation.** Never hardcode values.

### 10.19. UX Review Checklist

**Why**: A final quality gate before any loading-related feature is merged.

**Checklist**:
-   [ ] **Layout Stability**: Does the layout ever jump or shift during loading? (Must be NO)
-   [ ] **CLS Score**: Is the CLS score near zero? (Verify with browser tools)
-   [ ] **Skeleton Fidelity**: Does the skeleton accurately represent the final content's structure?
-   [ ] **Progressive Feel**: Does the page load in a logical, top-down order?
-   [ ] **Partial Refresh**: Do actions only reload the necessary components?
-   [ ] **State Handling**: Do `retry`, `offline`, and `error` states work as expected?
-   [ ] **Accessibility**: Is `aria-busy` used correctly? Is it navigable by keyboard? Does it respect reduced motion?
-   [ ] **Theme Support**: Does it work flawlessly in both Light and Dark Mode?
-   [ ] **Performance**: Are animations smooth? Is there any jank? (Profile if necessary)

---

## 11. Data Visualization System (Charts)

**Why**: Data visualization is the heart of Bendaharaku. Charts are not just illustrations; they are the primary interface for understanding financial health. A consistent, clear, and accessible chart system is critical for user trust and insight. This section provides the definitive rules for all chart implementations.

### 11.1. Charting Philosophy
-   **Clarity over Clutter**: Charts should be easy to read at a glance. Avoid unnecessary labels, gridlines, or decorations.
-   **Data-Driven**: The visual representation must accurately reflect the underlying data.
-   **Interactive**: Charts are not static images. Users should be able to hover, tap, and explore the data points.
-   **Accessible**: Charts must be understandable for all users, including those with color vision deficiencies or who rely on screen readers.
-   **Consistent**: All charts, regardless of type, must share a common set of visual and interactive patterns (tooltips, legends, colors).

### 11.2. Chart Component Rules

#### Unified Tooltip Specification
**Rule**: All charts MUST use a single, unified tooltip configuration. Falling back to the default, unstyled Chart.js tooltip is a critical bug.

| Property | Light Mode | Dark Mode | Token / Notes |
|---|---|---|---|
| **Background** | `var(--color-surface-overlay)` | `var(--color-surface-overlay)` | The tooltip is an overlay surface. |
| **Border Radius** | `var(--radius-lg)` | `var(--radius-lg)` | Consistent with other UI elements. |
| **Padding** | `12px` (`p-3`) | `12px` (`p-3`) | Provides adequate internal spacing. |
| **Shadow** | `var(--shadow-modal)` | `var(--shadow-modal)` | Lifts the tooltip off the chart content. |
| **Title Font** | `text-xs`, `font-bold`, `var(--color-text-secondary)` | `text-xs`, `font-bold`, `var(--color-text-secondary)` | `text-label-xs-bold` semantic token. |
| **Body Font** | `text-sm`, `var(--color-text-primary)` | `text-sm`, `var(--color-text-primary)` | `text-body-sm` semantic token. |
| **Color Swatch** | A `12x12px` square using the semantic color of the dataset. | A `12x12px` square using the semantic color of the dataset. | Must be included for clarity. |

**Implementation**: A global `getChartTooltipOptions()` function should be created that returns a token-based Chart.js configuration object. This function must be used when initializing every chart instance.

#### Unified Legend Specification
**Rule**: Manual, detached HTML legends are forbidden. Legends must be data-driven and programmatically linked to the chart.

-   **Default Behavior**: For most charts, the default Chart.js legend should be used, styled with the system's typography and color tokens.
-   **Custom Legends**: If a custom HTML legend is required (e.g., for complex interactivity), it MUST be generated from the chart's `datasets` property. A reusable Vue component (`<ChartLegend>`) that takes the chart data as a prop is the required pattern.
-   **Interactivity**: Clicking a legend item must toggle the visibility of the corresponding dataset in the chart. The item's style must update to reflect its toggled state (e.g., reduced opacity).

### 11.3. Chart Types & Specific Rules

#### Line Chart (e.g., Cumulative Balance)
-   **Y-Axis**: The Y-axis MUST be visible to provide context. Hiding axes for stylistic reasons is forbidden. The axis labels and lines must use `var(--color-text-muted)` and `var(--color-border-subtle)`.
-   **Gridlines**: Y-axis gridlines should be present but subtle, using `var(--color-border-subtle)`. X-axis gridlines should be disabled by default.
-   **Line Style**: The primary dataset line should use `var(--color-brand)`. The line should have a `tension` of `0.4` for a smooth curve.
-   **Area Fill**: A subtle gradient fill under the line is required, fading from `var(--color-brand)` with `20%` opacity to `0%`.

#### Bar Chart (e.g., Cash Flow)
-   **Bar Colors**: Bars MUST use the appropriate semantic colors. `Income` bars are green (`var(--color-income-chart)`), `Expense` bars are red (`var(--color-expense-chart)`).
-   **Bar Radius**: Bars must have a top-left and top-right radius of `4px` (`--radius-sm`).
-   **Hover State**: On hover, the bar's color should lighten by 10% or use a dedicated hover token if available.

#### Doughnut Chart (e.g., Category Breakdown)
-   **Color Source**: The `datasets` colors MUST be derived from the semantic color palettes (e.g., `var(--color-palette-expense-1)`, `var(--color-palette-expense-2)`). Hardcoded hex values are forbidden.
-   **Center Text**: The center of the doughnut chart should display the total value.
-   **Spacing**: A `spacing` of `2` and `borderWidth` of `0` should be used to create a clean look.

### 11.4. Chart Accessibility (A11y)
-   **`aria-label`**: Every chart canvas element must have a descriptive `aria-label` (e.g., "Bar chart showing monthly income and expenses").
-   **Color & Pattern**: For charts with multiple competing categories (e.g., >3 segments in a pie chart), patterns (stripes, dots) should be used in addition to color to ensure distinguishability.
-   **Keyboard Navigation**: Users must be able to tab to the chart canvas and interact with data points, triggering tooltips.
-   **Data Table Fallback**: A link to a tabular view of the data should be provided for complex charts, allowing screen reader users to access the raw data easily.

---

## 12. Screen Specifications

**Why**: While the preceding sections define the atoms and molecules of our design system (colors, components, states), this section defines the organisms: the screens themselves. A screen specification is the authoritative blueprint for constructing a specific page in the application, ensuring that layout, component composition, and behavior are consistent and intentional.

**How**: Each screen specification provides a comprehensive guide covering its purpose, layout, component mapping, and behavioral patterns. It leaves no room for ambiguity, enabling developers and AI agents to build pixel-perfect, functionally correct screens every time.

### 12.1. Analytics Screen

#### 1. Purpose & Primary Goal
-   **Purpose**: To provide users with a comprehensive, visual overview of their financial trends and behaviors over time.
-   **Primary Goal**: Enable users to answer key financial questions ("Where is my money going?", "How is my net worth changing?") through clear, interactive data visualizations.

#### 2. Information & Visual Hierarchy
The screen is designed to present information in a top-down hierarchy of importance.
1.  **Screen Title**: "Analytics". Clearly states the user's location.
2.  **Primary Filters**: Date range and data type toggles. These are the primary controls for the entire screen.
3.  **Cumulative Balance Chart**: The most important, high-level indicator of overall financial health. It occupies the most prominent position.
4.  **Cash Flow Chart**: A secondary, but critical, view of income vs. expense over the selected period.
5.  **Category Breakdown Chart**: A tertiary view providing a detailed breakdown of spending or income.

#### 3. Layout & Spacing
-   **Layout Structure**: A single-column vertical layout on mobile, transitioning to a more complex grid on desktop.
-   **Grid & Spacing**:
    -   All components MUST adhere to the global spacing scale.
    -   Padding within chart cards MUST be `var(--spacing-lg)` (16px).
    -   The vertical gap between chart cards MUST be `var(--spacing-xl)` (24px).
-   **Responsive Behavior**:
    -   **Mobile**: A simple vertical stack. `Cumulative Balance` -> `Cash Flow` -> `Category Breakdown`.
    -   **Desktop (`lg` breakpoint and up)**: A two-column grid. The `Cumulative Balance` chart takes the full width at the top. `Cash Flow` and `Category Breakdown` sit side-by-side below it.

#### 4. Component Mapping
-   **Header**: `GlobalHeader` with the title "Analytics".
-   **Content Body**:
    -   **Required Components**:
        1.  `FilterBar` (containing date range picker and view toggles).
        2.  `ChartCard` wrapping the `CumulativeBalanceChart` (Line Chart).
        3.  `ChartCard` wrapping the `CashFlowChart` (Bar Chart).
        4.  `ChartCard` wrapping the `CategoryBreakdownChart` (Doughnut Chart).
    -   **Component Order**: The order listed above is mandatory and must not be changed.

#### 5. Interaction & Motion
-   **View Toggles ("Harian", "Mingguan", etc.)**:
    -   These are implemented as a `ButtonGroup` component.
    -   **Default State**: Tertiary/Ghost button style.
    -   **Selected State**: MUST use the `Primary Button` style (`bg-[var(--color-brand)]`, `text-white`). Hardcoded gradients or colors are forbidden.
-   **Chart Hover**: Hovering over a data point on any chart MUST trigger the unified tooltip.
-   **Entrance Animation**: On initial load, chart cards MUST animate in using a staggered `fade-in-up` animation. The stagger delay is `75ms`. The animation MUST use `var(--duration-300)` and `var(--ease-out)`.

#### 6. Loading, Empty & Error States
-   **Loading State**:
    -   **Rule**: The Analytics screen MUST display skeleton loaders for all chart components while data is being fetched. A single page-level spinner is forbidden.
    -   **Implementation**: Each `ChartCard` must contain its corresponding `ChartSkeleton` component. The skeletons must be displayed until the data for all charts is resolved.
-   **Empty State**:
    -   **Rule**: If there is no data for the selected period, each chart must be replaced by a standard `EmptyState` component.
    -   **Content**: The empty state should contain an appropriate icon, a headline ("No Data Available"), and a description ("There are no transactions for the selected period.").
-   **Error State**:
    -   **Rule**: If a chart's data fails to load, it must be replaced by a standard `ErrorState` component.
    -   **Content**: The error state must contain an error icon, a headline ("Could Not Load Chart"), and a "Retry" button that re-triggers the data fetch for that specific chart.

#### 7. AI Implementation Checklist
-   [ ] **Use Tokens**: All colors, spacing, and typography MUST use design tokens.
-   [ ] **Chart Rules**: All charts MUST adhere to the specifications in `Section 11: Data Visualization System`.
-   [ ] **Stateful Components**: Ensure `Loading`, `Empty`, and `Error` states are implemented for each chart individually.
-   [ ] **No Hardcoded Values**: Verify no hardcoded colors (e.g., `text-green-400`), spacing (`p-3`), or font sizes (`text-sm`) exist in the final implementation.
-   [ ] **Component Reusability**: Use the shared `ChartCard`, `ChartSkeleton`, `EmptyState`, and `ErrorState` components. Do not create one-off versions.

### 12.2. Dashboard Screen

#### 1. Purpose & Primary Goal
-   **Purpose**: To provide users with an immediate, at-a-glance summary of their current financial status and quick access to common tasks.
-   **Primary Goal**: Answer the user's most pressing questions: "How much money do I have?", "What's my recent activity?", and "What do I need to do next?".

#### 2. Information & Visual Hierarchy
The dashboard is orchestrated to present the most critical information first, following a clear top-to-bottom flow.
1.  **Portfolio Card**: The hero component. Shows the user's total net worth.
2.  **Mini Cashflow Cards**: Quick summary of income vs. expense for the current period.
3.  **Quick Actions**: A set of primary actions like "Add Transaction".
4.  **Recent Transactions**: A feed of the user's latest financial activity.
5.  **Calendar / Financial Insights**: Secondary modules that provide additional context or insights.

#### 3. Layout & Spacing
-   **Layout Structure**: A single-column vertical layout on mobile. Desktop may introduce a multi-column layout for secondary information.
-   **Grid & Spacing**:
    -   All components MUST adhere to the semantic spacing scale.
    -   The vertical gap between all major dashboard components (e.g., Portfolio Card and Mini Cashflow cards) MUST be `spacing-xl` (24px).
    -   Page horizontal padding MUST be `spacing-xl` (24px).

#### 4. Component Mapping
-   **Header**: `GlobalHeader` with the app logo and user avatar/notifications.
-   **Content Body**:
    -   **Required Components**:
        1.  `PortfolioCard`
        2.  `MiniCashflowCard` (Income)
        3.  `MiniCashflowCard` (Expense)
        4.  `QuickActionsGroup`
        5.  `TransactionList` (showing the 5 most recent transactions).
    -   **Optional Components**:
        -   `CalendarWidget`
        -   `FinancialInsightCard`
        -   `GoalSummaryCard`
    -   **Component Order**: The order of required components is mandatory. Optional components can be placed below the `TransactionList`.

#### 5. Interaction & Motion
-   **Portfolio Card**: Tapping the card should navigate to the detailed "Wallets" screen. The card should scale down slightly on press (`transform: scale(0.98)`).
-   **Transaction List "View All"**: Tapping this button navigates to the full "Transactions" screen.
-   **Entrance Animation**: The dashboard MUST use the progressive rendering loading sequence defined in `Section 10.5`. Components animate in with a staggered `fade-in-up` effect as their data becomes available.

#### 6. Loading, Empty & Error States
-   **Loading State**:
    -   **Rule**: The dashboard MUST use the progressive rendering skeleton system. The entire page is never blocked by a single spinner.
    -   **Implementation**: On initial load, the screen displays `PortfolioCardSkeleton`, `MiniCashflowSkeleton`, and `TransactionListSkeleton`. Each component replaces its skeleton independently as its data resolves. See `Section 10.4` for skeleton specifications.
-   **Empty State (First-Time User)**:
    -   **Rule**: If the user has zero transactions and zero wallets, a specialized `EmptyDashboard` state is shown.
    -   **Content**: This state includes a welcoming message, an illustration, and a prominent primary action button: "+ Add Your First Wallet". It guides the user through the onboarding process.
-   **Error State**:
    -   If a specific component fails to load (e.g., `TransactionList`), only that component should show an `ErrorState` with a "Retry" button. The rest of the dashboard remains interactive.

#### 7. AI Implementation Checklist
-   [ ] **Progressive Loading**: Implement the staggered loading sequence. Do not wait for all data to be ready before rendering the first component.
-   [ ] **Use Skeletons**: All data-driven components MUST have a corresponding skeleton state.
-   [ ] **Semantic Spacing**: All layout MUST use the semantic spacing tokens (`spacing-xl`, etc.).
-   [ ] **Handle Empty State**: Implement the specific "First-Time User" empty state.
-   [ ] **Component Reusability**: Use shared components for all dashboard elements. Do not create one-off styles.

### 12.3. Authentication Pages

#### 1. Purpose & Primary Goal
-   **Purpose**: To provide secure entry points for user authentication (Login, Register, Password Reset, Email Verification).
-   **Primary Goal**: Simple, focused forms that guide users through authentication without distraction.

#### 2. Layout Philosophy
**Rule**: Authentication pages do NOT use card-based, narrow layouts. They follow the same responsive container system as other pages in the application.

**Why**: 
-   Consistency: Auth pages should feel like part of the app, not isolated modals.
-   Responsive: Auth forms should adapt naturally to all screen sizes.
-   Accessibility: Wider containers provide better readability and form usability.

#### 3. Layout & Spacing Structure
-   **Wrapper Container**: 
    -   MUST use `w-full min-w-0 flex-1` instead of `max-w-md`.
    -   MUST use responsive padding: `px-4 sm:px-6 lg:px-8`.
    -   The wrapper spans the full viewport width and height.
-   **Content Container** (form and content):
    -   MUST be centered with `max-w-md mx-auto`.
    -   This constrains the form itself, not the page wrapper.
-   **Spacing**:
    -   Vertical spacing between form elements: `space-y-4` or `space-y-5`.
    -   Page padding: `py-10` minimum for vertical breathing room.

#### 4. Component Mapping
All authentication pages share a common structure:
1.  **Application Logo**: Centered, `w-20 h-20`, with `mx-auto mb-5`.
2.  **Page Title**: `text-3xl font-bold`, centered.
3.  **Subtitle/Description**: `text-2xs uppercase tracking-widest`, muted color.
4.  **Form**: All inputs use standard input styling with proper focus states.
5.  **Primary Action Button**: Full-width, uses brand colors.
6.  **Secondary Links**: Centered, small text with brand color for links.

#### 5. Responsive Behavior
-   **Mobile**: Full-width container with standard padding.
-   **Tablet & Desktop**: Container remains full-width, but form content is centered and constrained to `max-w-md`.

#### 6. Forbidden Patterns
-   ❌ **NEVER** use `max-w-md` on the root wrapper/container.
-   ❌ **NEVER** use `mx-auto` on the root wrapper (it's full-width).
-   ❌ **NEVER** make auth pages look like floating cards in the center of the screen.
-   ✅ **ALWAYS** apply width constraints to the inner form content, not the page wrapper.

#### 7. Implementation Rules
```vue
<!-- WRONG: Constraining the wrapper -->
<div class="w-full max-w-md mx-auto min-h-screen">
  <form>...</form>
</div>

<!-- CORRECT: Full-width wrapper, constrained content -->
<div class="w-full min-w-0 flex-1 min-h-screen px-4 sm:px-6 lg:px-8 py-10">
  <div class="w-full max-w-md mx-auto">
    <form>...</form>
  </div>
</div>
```

#### 8. AI Implementation Checklist
-   [ ] **Wrapper Layout**: Verify wrapper uses `w-full min-w-0 flex-1`, NOT `max-w-md`.
-   [ ] **Content Constraint**: Form content is wrapped in `max-w-md mx-auto`.
-   [ ] **Responsive Padding**: Uses `px-4 sm:px-6 lg:px-8` pattern.
-   [ ] **No Card Look**: Auth page does not appear as a floating card.
-   [ ] **Runtime Verification**: Inspect DOM, ensure no `max-w-md` on outer wrapper.

---

## Picker Design Guidelines

### Filosofi
- **Picker**: Untuk eksplorasi data visual (Icon, Image, Color). Wajib memanfaatkan ruang desktop seluas-luasnya (`adaptive` atau size besar).
- **Dialog**: Untuk aksi konfirmasi atau input sederhana (Delete, Status toggle). Tetap kecil dan fokus (`sm` atau `md`).
- **Sheet**: Untuk aksi kontekstual cepat dari bottom mobile. Jika di desktop, harus menggunakan `BaseModal` dengan alignment yang sesuai.

### Picker Size Rules
| Konteks | Size `BaseModal` | Catatan |
|---------|------------------|---------|
| Confirmation | `sm` / `md` | Dialog kecil, fokus pada aksi |
| Form Editor | `adaptive` | Form mengikuti viewport |
| Search Explorer | `4xl` / `5xl` | Membutuhkan ruang hasil pencarian |
| Icon Picker | `adaptive` | Grid ikon mengisi layar |
| Image Crop | `adaptive` | Area crop butuh ruang |
| AI Explorer | `6xl` / `7xl` | Chat butuh ruang vertikal & horizontal |
| Dashboard Explorer | `7xl` | Data table, grafik, widget |

### Responsive Grid Rules
Untuk picker visual (Icon, Emoji, Color):
- **Mobile**: 4 kolom (`grid-cols-4`)
- **Tablet**: 6-8 kolom (`sm:grid-cols-6 md:grid-cols-8`)
- **Desktop**: 8-10 kolom (`lg:grid-cols-10`)
- **Ultra Wide**: 10-12 kolom (`xl:grid-cols-12`)

Gunakan `auto-fit/minmax` jika item bersifat dinamis.

### Component Rules
- **WAJIB `BaseModal`**: Semula `IconPicker`, `ImageCropModal`, `DateModal`, `TransactionDetailModal`, dan semua picker lain.
- **Custom**: Hanya untuk overlay non-modal (GlobalSearchOverlay) yang membutuhkan animasi full-screen khusus.
- **Dilarang**: Hardcode `max-w-sm`, `max-w-md`, `max-w-lg` pada komponen explorer/picker.

### Do
- Gunakan prop `max-width="adaptive"` atau size token (`xs` s.d `7xl`).
- Gunakan container responsif `PageContainer` untuk layout halaman.
- Pisahkan `#header`, `#default` (body), `#footer` di `BaseModal`.

### Don't
- Jangan hardcode `max-w-*` di dalam komponen picker.
- Jangan membuat struktur `Teleport` + `Transition` manual jika `BaseModal` sudah tersedia.
- Jangan gunakan layout mobile (4 kolom) di desktop.
