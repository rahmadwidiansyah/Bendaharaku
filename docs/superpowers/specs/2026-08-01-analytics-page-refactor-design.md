# Analytics Page Refactor Design Specification

**Date:** 2026-08-01  
**Component:** `/analytic` page (`resources/js/Pages/Analytics/Index.vue`)  
**Author:** AI Assistant  
**Status:** Approved

---

## Overview

This specification details improvements to the Analytics page to enhance usability, readability, and adherence to the Bendaharaku design system. The refactor addresses four key areas: spacing consistency, chart scrollability, label collision prevention, and toggle button text optimization.

---

## Problem Statement

The current Analytics page has several UX and visual issues:

1. **Spacing inconsistencies**: Some components may not fully adhere to design tokens
2. **Bar chart cramping**: Cash flow chart bars are cramped and difficult to read when data is dense
3. **Label collisions**: Bar chart labels overlap when bars have similar heights
4. **Toggle text overflow**: Category toggle buttons have long text that wraps on mobile devices

---

## Design Goals

1. Ensure all spacing uses design tokens from the Bendaharaku design system
2. Enable horizontal scrolling for the bar chart to make bars wider and more readable
3. Implement intelligent label positioning to prevent overlaps
4. Use abbreviated text in category toggles to prevent wrapping

---

## Detailed Design

### 1. Spacing & Layout Audit

**Current State:**
The page uses various spacing values throughout. Most are already token-compliant (p-3, p-4, p-5, gap-3, mb-5).

**Changes:**
- Verify all spacing values map to design tokens (--spacing-md, --spacing-lg, --spacing-xl)
- No major changes needed; spacing is largely compliant
- Ensure mobile-first responsive scaling follows design system guidelines

**Token Mapping:**
- `p-3` → `--spacing-md` (12px) ✓
- `p-4` → `--spacing-lg` (16px) ✓
- `p-5` → `--spacing-xl` (20px) ✓
- `gap-3` → `--spacing-md` (12px) ✓

---

### 2. Bar Chart Horizontal Scrolling

**Current Implementation:**
```vue
<div class="w-full" style="height: 220px;">
    <canvas ref="barChartRef" :key="barChartKey"></canvas>
</div>
```

**New Implementation:**

**HTML Structure:**
```vue
<div class="overflow-x-auto scrollbar-custom">
    <div :style="{ minWidth: chartMinWidth + 'px', height: '220px' }">
        <canvas ref="barChartRef" :key="barChartKey"></canvas>
    </div>
</div>
```

**Width Calculation Logic:**
```javascript
const chartMinWidth = computed(() => {
    const { labels } = buildBarData(barView.value);
    const minWidthPerLabel = 80; // 80px per time period
    return Math.max(labels.length * minWidthPerLabel, 300); // minimum 300px
});
```

**Chart.js Configuration Updates:**
```javascript
options: {
    responsive: true,
    maintainAspectRatio: false, // Allow fixed height
    // ... other options
}
```

**CSS Additions:**
```css
.scrollbar-custom {
    scrollbar-width: thin;
    scrollbar-color: rgba(139, 92, 246, 0.3) rgba(31, 41, 55, 0.5);
}

.scrollbar-custom::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}
```

**Behavior:**
- Chart width adjusts based on number of data points
- Smooth horizontal scrolling on all devices
- Custom scrollbar matches dark theme
- Touch-friendly swipe gestures on mobile

---

### 3. Smart Label Positioning Algorithm

**Current Implementation (lines 283-300):**
```javascript
plugins: [{
    id: 'barLabels',
    afterDraw(chart) {
        const ctx2 = chart.ctx;
        chart.data.datasets.forEach((ds, dsIndex) => {
            const meta = chart.getDatasetMeta(dsIndex);
            meta.data.forEach((el, i) => {
                const val = Number(ds.data[i]);
                if (!val || val <= 0) return;
                ctx2.fillStyle = '#D1D5DB';
                ctx2.font = 'bold 10px sans-serif';
                ctx2.textAlign = 'center';
                ctx2.textBaseline = 'bottom';
                ctx2.fillText(formatCompact(val), el.x, el.y - 4);
            });
        });
    }
}]
```

**New Implementation with Collision Detection:**

```javascript
plugins: [{
    id: 'barLabels',
    afterDraw(chart) {
        const ctx2 = chart.ctx;
        const placedLabels = []; // Track placed labels to detect collisions
        const isMobile = window.innerWidth < 640;
        const fontSize = isMobile ? 9 : 10;
        
        ctx2.font = `bold ${fontSize}px sans-serif`;
        ctx2.fillStyle = '#D1D5DB';
        ctx2.textAlign = 'center';
        ctx2.shadowColor = 'rgba(0, 0, 0, 0.5)';
        ctx2.shadowBlur = 2;
        ctx2.shadowOffsetY = 1;
        
        chart.data.datasets.forEach((ds, dsIndex) => {
            const meta = chart.getDatasetMeta(dsIndex);
            meta.data.forEach((el, i) => {
                const val = Number(ds.data[i]);
                if (!val || val <= 0) return;
                
                const text = formatCompact(val);
                const textWidth = ctx2.measureText(text).width;
                const textHeight = fontSize;
                
                // Try multiple positions to avoid collisions
                const positions = [
                    { x: el.x, y: el.y - 4, baseline: 'bottom' },      // default
                    { x: el.x, y: el.y - 12, baseline: 'bottom' },     // higher
                    { x: el.x, y: el.y - 20, baseline: 'bottom' },     // even higher
                    { x: el.x, y: el.y + 12, baseline: 'top' }         // inside bar (if tall enough)
                ];
                
                // Check each position for collisions
                let placed = false;
                for (const pos of positions) {
                    const labelBounds = {
                        x: pos.x - textWidth / 2 - 2, // 2px padding
                        y: pos.baseline === 'bottom' ? pos.y - textHeight - 2 : pos.y,
                        width: textWidth + 4,
                        height: textHeight + 4
                    };
                    
                    // Check if this position collides with any placed label
                    const hasCollision = placedLabels.some(placed => {
                        return !(labelBounds.x + labelBounds.width < placed.x ||
                                labelBounds.x > placed.x + placed.width ||
                                labelBounds.y + labelBounds.height < placed.y ||
                                labelBounds.y > placed.y + placed.height);
                    });
                    
                    // If no collision and within chart bounds, place the label
                    if (!hasCollision && pos.y > 10 && pos.y < chart.height - 10) {
                        ctx2.textBaseline = pos.baseline;
                        ctx2.fillText(text, pos.x, pos.y);
                        placedLabels.push(labelBounds);
                        placed = true;
                        break;
                    }
                }
                
                // If no position worked, skip this label (rare case)
            });
        });
        
        // Reset shadow
        ctx2.shadowColor = 'transparent';
    }
}]
```

**Algorithm Logic:**

1. **Detection Phase:**
   - Calculate bounding box for each label (x, y, width, height)
   - Include 2px padding around text for spacing buffer

2. **Position Testing:**
   - Try default position (4px above bar)
   - If collision, try progressively higher positions
   - As last resort, try inside bar (if bar is tall enough)
   - Skip label only if all positions collide (rare)

3. **Collision Check:**
   - Compare new label bounds with all previously placed labels
   - Use rectangle intersection algorithm
   - Ensure label stays within chart bounds

4. **Visual Enhancements:**
   - Responsive font size: 9px (mobile) vs 10px (desktop)
   - Text shadow for better contrast: `0 1px 2px rgba(0,0,0,0.5)`
   - Maintain color: `#D1D5DB` (gray-300)

---

### 4. Category Toggle Text Abbreviation

**Current Implementation (lines 67-72, 567-575):**
```javascript
const categoryViews = [
    { key: 'expense', label: () => t('analytics.categoryTab.expense') },
    { key: 'income', label: () => t('analytics.categoryTab.income') },
    { key: 'debt', label: () => t('analytics.categoryTab.debt') },
    { key: 'receivable', label: () => t('analytics.categoryTab.receivable') },
];
```

**Translation Updates Required:**

Add to Indonesian translation file:
```json
{
    "analytics": {
        "categoryTab": {
            "expense": "Pengeluaran",
            "expenseShort": "Keluar",
            "income": "Pemasukan",
            "incomeShort": "Masuk",
            "debt": "Hutang",
            "debtShort": "Hutang",
            "receivable": "Piutang",
            "receivableShort": "Piutang"
        }
    }
}
```

**Component Changes:**
```javascript
const categoryViews = [
    { key: 'expense', label: () => t('analytics.categoryTab.expenseShort') },
    { key: 'income', label: () => t('analytics.categoryTab.incomeShort') },
    { key: 'debt', label: () => t('analytics.categoryTab.debtShort') },
    { key: 'receivable', label: () => t('analytics.categoryTab.receivableShort') },
];
```

**Accessibility Enhancement:**
```vue
<button 
    v-for="c in categoryViews" 
    :key="c.key" 
    @click="switchCategory(c.key)"
    :aria-label="t('analytics.categoryTab.' + c.key)"
    :class="[...]">
    {{ c.label() }}
</button>
```

**Benefits:**
- Prevents text wrapping on small screens
- Cleaner, more compact UI
- Maintains accessibility with full text in aria-labels
- Faster visual scanning

---

## Implementation Checklist

### Files to Modify:
1. `resources/js/Pages/Analytics/Index.vue` - Main component
2. `lang/id/analytics.php` or equivalent - Add abbreviated translations

### Changes Breakdown:

**Analytics/Index.vue:**
- [ ] Add `chartMinWidth` computed property
- [ ] Update bar chart container with overflow-x-auto and dynamic width
- [ ] Replace `barLabels` plugin with collision-detection version
- [ ] Update `categoryViews` to use abbreviated translations
- [ ] Add `aria-label` attributes to category toggle buttons
- [ ] Add scrollbar custom styling to `<style>` section

**Translation file:**
- [ ] Add `expenseShort`, `incomeShort`, `debtShort`, `receivableShort` keys

---

## Testing Requirements

### Functional Testing:
- [ ] Verify bar chart scrolls horizontally on all devices
- [ ] Confirm bars are wider and more readable
- [ ] Check label positioning prevents overlaps in various data scenarios
- [ ] Verify abbreviated toggle text displays correctly
- [ ] Test keyboard navigation and screen reader compatibility

### Visual Testing:
- [ ] Test on mobile (320px - 640px width)
- [ ] Test on tablet (641px - 1024px width)
- [ ] Test on desktop (1025px+ width)
- [ ] Verify dark mode styling consistency
- [ ] Check custom scrollbar appearance

### Data Scenarios:
- [ ] Daily view with 5 days of data
- [ ] Weekly view with 4-5 weeks in current month
- [ ] Monthly view with 3 months of data
- [ ] Empty state (no data)
- [ ] Extreme values (very large/small numbers)

### Accessibility:
- [ ] Keyboard navigation works for all interactive elements
- [ ] Screen readers announce toggle states correctly
- [ ] Color contrast meets WCAG AA standards
- [ ] Touch targets meet minimum 44x44px size

---

## Success Criteria

1. ✓ All spacing values use design tokens
2. ✓ Bar chart is horizontally scrollable with wider, readable bars
3. ✓ Chart labels never overlap regardless of data density
4. ✓ Category toggles display abbreviated text without wrapping
5. ✓ All changes maintain dark mode consistency
6. ✓ Accessibility standards are met (keyboard nav, screen readers, ARIA)
7. ✓ Mobile experience is smooth with touch gestures

---

## Risks & Mitigation

**Risk:** Chart.js collision detection algorithm impacts performance
- **Mitigation:** Algorithm is O(n²) but n is small (typically 5-20 labels), negligible impact

**Risk:** Custom scrollbar styling inconsistent across browsers
- **Mitigation:** Provide both standard and webkit-specific styles; graceful degradation

**Risk:** Abbreviated text loses clarity for some users
- **Mitigation:** Full text preserved in aria-labels for accessibility; abbreviated terms are commonly understood

---

## Future Enhancements (Out of Scope)

- Interactive tooltips on hover for full category names
- Export chart as image functionality
- Comparison mode to show multiple date ranges
- Animation transitions when switching views

---

## Notes

- Design adheres to Bendaharaku design system (CLAUDE.md)
- Semantic color tokens maintained (income=green, expense=red, debt=amber, receivable=purple)
- Mobile-first approach prioritized
- All changes are backwards compatible