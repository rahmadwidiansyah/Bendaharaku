# SETTINGS INFORMATION ARCHITECTURE AUDIT

**Status**: Phase 3 - Complete Restructuring  
**Date**: 2026-07-19  
**Objective**: Full IA refactor based on professional SaaS standards

---

## 📊 CURRENT STATE ANALYSIS

### ✅ NEW PAGES CREATED (Phase 1-2)
```
Account/
├── Profile.vue (user info)
├── Security.vue (password, auth)
├── Sessions.vue (active sessions)
└── Preferences.vue (timezone, date format)

Application/
├── Appearance.vue (theme, colors)
├── Language.vue (language, currency)
└── Notifications.vue (notification prefs)

Finance/
├── Defaults.vue (default wallet/currency)
├── Categories.vue (category management)
├── Wallets.vue (wallet list)
└── Budget.vue (budget settings)

Privacy/
├── Settings.vue (privacy controls)
├── Data.vue (export/import/backup)
└── Danger.vue (delete account, reset)

System/
├── About.vue (version, credits)
└── Diagnostics.vue (system status, logs)

AI/
└── Integration.vue (Telegram webhook)
```

### ⚠️ OLD PAGES STILL IN USE (Need Refactoring)
```
Ai.vue ❌
  - Contains: AI Provider, Integration, Analytics (ALL MIXED)
  - Route: /settings/ai
  - Props: providerStatuses, availableProviders, modelsByProvider, usageStats, recentLogs
  - Issue: TOO MANY FEATURES IN ONE PAGE

AiAnalytics.vue ❌
  - Contains: Usage stats, performance charts
  - Not integrated into new structure
  - Should be: /settings/ai/analytics

ChatBotProfile.vue ❌
  - Contains: Bot name, avatar, personality (future)
  - Route: /settings/chat/bot-profile
  - Should move to: /settings/ai/bot or /settings/ai/profile
```

### 📋 ROUTES ANALYSIS

#### ✅ NEW ROUTES (Created)
```
/settings/account/profile
/settings/account/security
/settings/account/sessions
/settings/account/preferences
/settings/application/appearance
/settings/application/language
/settings/application/notifications
/settings/finance/defaults
/settings/finance/categories
/settings/finance/wallets
/settings/finance/budget
/settings/privacy/settings
/settings/privacy/data
/settings/privacy/danger
/settings/system/about
/settings/system/diagnostics
/settings/ai/integration
```

#### ⚠️ OLD ROUTES (Need Reorganization)
```
/settings (index) - OLD: flat list
/settings/ai - OLD: mixed provider/analytics/integration
/settings/ai/test - API endpoint
/settings/ai/api/dashboard - Analytics API
/settings/ai/api/feedback - Feedback API
/settings/chat/bot-profile - Chat bot settings
/settings/chat/bot-avatar - Bot avatar upload
```

---

## 🔍 DETAILED AUDIT: CURRENT LOCATION → RECOMMENDED LOCATION

### AI SECTION - MAJOR RESTRUCTURE NEEDED

| Current Page | Current Route | Content | Recommended Location | Recommended Route | Reason |
|---|---|---|---|---|---|
| Ai.vue | `/settings/ai` | AI Provider, Models, Test | `/settings/ai/models` | `settings.ai.models` | Provider selection is critical feature, deserves own page |
| Ai.vue | `/settings/ai` | Performance Analytics | `/settings/ai/analytics` | `settings.ai.analytics` | Analytics is separate concern from provider config |
| Ai.vue | `/settings/ai` | Integration setup | `/settings/ai/integrations` | `settings.ai.integrations` | Already exists as `/settings/ai/integration` - consolidate |
| AiAnalytics.vue | Not routed | Usage stats, charts | `/settings/ai/analytics` | `settings.ai.analytics` | Move to new hierarchy |
| ChatBotProfile.vue | `/settings/chat/bot-profile` | Bot name, avatar, personality | `/settings/ai/bot` | `settings.ai.bot` | Bot config is part of AI, not separate |
| (n/a) | (new) | AI Memory management | `/settings/ai/memory` | `settings.ai.memory` | Dedicated page for conversation memory |
| (n/a) | (new) | AI Behavior settings | `/settings/ai/behavior` | `settings.ai.behavior` | Dedicated page for AI personality/behavior |

**AI Structure After Refactor:**
```
AI (Artificial Intelligence)
├── Models
│   └── Provider selection, model config, token limits
├── Bot
│   └── Bot name, avatar, personality (from ChatBotProfile.vue)
├── Integrations
│   └── Telegram, Discord, WhatsApp (future)
├── Analytics
│   └── Usage stats, performance charts (from AiAnalytics.vue)
├── Memory
│   └── Conversation memory settings (new)
└── Behavior
    └── AI behavior tuning (new)
```

---

### ACCOUNT SECTION - MOSTLY GOOD

| Current | Current Route | Content | Recommended | Recommended Route | Status |
|---|---|---|---|---|---|
| Profile.vue | `/settings/account/profile` | User info, email, name | Same | `settings.account.profile` | ✅ GOOD |
| Security.vue | `/settings/account/security` | Password, 2FA | Same | `settings.account.security` | ✅ GOOD |
| Sessions.vue | `/settings/account/sessions` | Active sessions | Same | `settings.account.sessions` | ✅ GOOD |
| Preferences.vue | `/settings/account/preferences` | Timezone, date format | Same | `settings.account.preferences` | ✅ GOOD |

---

### APPLICATION SECTION - GOOD

| Current | Current Route | Content | Recommended | Recommended Route | Status |
|---|---|---|---|---|---|
| Appearance.vue | `/settings/application/appearance` | Theme, colors, fonts | Same | `settings.application.appearance` | ✅ GOOD |
| Language.vue | `/settings/application/language` | Language, currency | Same | `settings.application.language` | ✅ GOOD |
| Notifications.vue | `/settings/application/notifications` | Notifications prefs | Same | `settings.application.notifications` | ✅ GOOD |
| (n/a) | (new) | Accessibility options | `/settings/application/accessibility` | `settings.application.accessibility` | 🆕 NEW |

---

### FINANCE SECTION - GOOD

| Current | Current Route | Content | Recommended | Recommended Route | Status |
|---|---|---|---|---|---|
| Defaults.vue | `/settings/finance/defaults` | Default wallet, currency | Same | `settings.finance.defaults` | ✅ GOOD |
| Categories.vue | `/settings/finance/categories` | Category management | Same | `settings.finance.categories` | ✅ GOOD |
| Wallets.vue | `/settings/finance/wallets` | Wallet list | Same | `settings.finance.wallets` | ✅ GOOD |
| Budget.vue | `/settings/finance/budget` | Budget settings | Same | `settings.finance.budget` | ✅ GOOD |
| (n/a) | (new) | Investment settings | `/settings/finance/investments` | `settings.finance.investments` | 🆕 NEW |
| (n/a) | (new) | Debt & Receivables | `/settings/finance/debt` | `settings.finance.debt` | 🆕 NEW |

---

### PRIVACY SECTION - GOOD

| Current | Current Route | Content | Recommended | Recommended Route | Status |
|---|---|---|---|---|---|
| Settings.vue | `/settings/privacy/settings` | Privacy controls | Same | `settings.privacy.settings` | ✅ GOOD |
| Data.vue | `/settings/privacy/data` | Export/Import/Backup | Same | `settings.privacy.data` | ✅ GOOD |
| Danger.vue | `/settings/privacy/danger` | Danger zone | Same | `settings.privacy.danger` | ✅ GOOD |

---

### SYSTEM SECTION - GOOD

| Current | Current Route | Content | Recommended | Recommended Route | Status |
|---|---|---|---|---|---|
| About.vue | `/settings/system/about` | Version, credits | Same | `settings.system.about` | ✅ GOOD |
| Diagnostics.vue | `/settings/system/diagnostics` | System status | Same | `settings.system.diagnostics` | ✅ GOOD |
| (n/a) | (new) | Logs & History | `/settings/system/logs` | `settings.system.logs` | 🆕 NEW |
| (n/a) | (new) | Developer/API | `/settings/system/developer` | `settings.system.developer` | 🆕 NEW |

---

## ✂️ CONSOLIDATION PLAN

### Pages to DELETE
- ❌ `Settings/Ai.vue` - Consolidate into new `/settings/ai/*` pages
- ❌ `Settings/AiAnalytics.vue` - Move to `/settings/ai/analytics`
- ❌ `Settings/ChatBotProfile.vue` - Move to `/settings/ai/bot`
- ❌ `Settings/Index.vue` - Replace with new dashboard or remove

### Pages to MOVE/RENAME
- `ChatBotProfile.vue` → `AI/Bot.vue`
- `AiAnalytics.vue` → `AI/Analytics.vue`
- `Ai.vue` → Split into `AI/Models.vue` + consolidate integration

### Pages to CREATE
- 🆕 `AI/Memory.vue` - Conversation memory settings
- 🆕 `AI/Behavior.vue` - AI behavior/personality settings
- 🆕 `Application/Accessibility.vue` - Accessibility options
- 🆕 `Finance/Investments.vue` - Investment tracking
- 🆕 `Finance/Debt.vue` - Debt & receivables
- 🆕 `System/Logs.vue` - System logs
- 🆕 `System/Developer.vue` - Developer/API settings

### Routes to CREATE
- `/settings/ai/models` - AI model selection & config
- `/settings/ai/bot` - Bot profile & customization
- `/settings/ai/analytics` - Usage analytics
- `/settings/ai/memory` - Conversation memory
- `/settings/ai/behavior` - AI behavior settings
- `/settings/application/accessibility` - Accessibility
- `/settings/finance/investments` - Investments
- `/settings/finance/debt` - Debt & receivables
- `/settings/system/logs` - System logs
- `/settings/system/developer` - Developer settings

### Routes to REDIRECT (Backward Compatibility)
- `/settings/ai` → `/settings/ai/models` (301 redirect)
- `/settings/chat/bot-profile` → `/settings/ai/bot` (301 redirect)
- OLD `/settings` → `/settings/account/profile` (301 redirect)

### Routes to DEPRECATE
- `/settings/ai/test` - Move to API v1
- `/settings/chat/bot-avatar` - Keep but as PATCH /settings/ai/bot

---

## 📐 FINAL STRUCTURE (COMPLETE)

```
Account Settings
├── Profile
├── Security
├── Sessions
└── Preferences

Application Settings
├── Appearance
├── Language
├── Notifications
└── Accessibility (NEW)

Finance Settings
├── Defaults
├── Wallets
├── Categories
├── Budget
├── Investments (NEW)
└── Debt & Receivables (NEW)

Privacy & Data
├── Settings
├── Data (Export/Import/Backup)
└── Danger Zone

System Settings
├── About
├── Diagnostics
├── Logs (NEW)
└── Developer (NEW)

Artificial Intelligence (NEW HIERARCHY)
├── Models (Provider/Selection)
├── Bot (Profile/Personality)
├── Integrations (Telegram, Discord, etc)
├── Analytics (Usage/Performance)
├── Memory (Conversation history)
└── Behavior (Personality tuning)
```

---

## 🔄 IMPLEMENTATION PHASES

### Phase 3A: AI Restructure (CRITICAL)
1. Create `/settings/ai/models` page (from Ai.vue)
2. Move `/settings/chat/bot-profile` → `/settings/ai/bot`
3. Move `AiAnalytics.vue` → `/settings/ai/analytics`
4. Create `/settings/ai/memory` page (new)
5. Create `/settings/ai/behavior` page (new)
6. Delete old `Ai.vue` file
7. Update routes with redirects
8. Update `settingsMenu.ts` config

### Phase 3B: Complete Settings (Additional Features)
1. Create `/settings/application/accessibility` (new)
2. Create `/settings/finance/investments` (new)
3. Create `/settings/finance/debt` (new)
4. Create `/settings/system/logs` (new)
5. Create `/settings/system/developer` (new)
6. Update menu config

### Phase 3C: Polish & Verify
1. Test all routes
2. Verify redirects work
3. Test i18n on new pages
4. Verify backward compatibility
5. Test responsive design
6. Accessibility audit

---

## 📝 IMPLEMENTATION CHECKLIST

### Phase 3A: AI Restructure
- [ ] Analyze Ai.vue components (what goes where)
- [ ] Create `/settings/ai/models/Models.vue`
- [ ] Move `/settings/chat/bot-profile` → `/settings/ai/bot/Bot.vue`
- [ ] Move `AiAnalytics.vue` → `/settings/ai/analytics/Analytics.vue`
- [ ] Create `/settings/ai/memory/Memory.vue`
- [ ] Create `/settings/ai/behavior/Behavior.vue`
- [ ] Create `/settings/ai/integrations/Integrations.vue` (enhance existing)
- [ ] Update routes (add 5 new routes, 3 redirects)
- [ ] Update `settingsMenu.ts`
- [ ] Create translations (new keys for new pages)
- [ ] Delete `Settings/Ai.vue`
- [ ] Delete `Settings/AiAnalytics.vue`
- [ ] Delete `Settings/ChatBotProfile.vue`
- [ ] Test all AI routes
- [ ] Test i18n on new AI pages
- [ ] Test navigation & breadcrumbs

### Phase 3B: New Settings Pages
- [ ] Create remaining new pages (5 pages)
- [ ] Update routes
- [ ] Update menu config
- [ ] Create translations

### Phase 3C: Cleanup
- [ ] Verify all redirects
- [ ] Test backward compat
- [ ] Responsive testing
- [ ] Accessibility audit
- [ ] Update documentation

---

## 🎯 SUCCESS CRITERIA

- ✅ All pages properly organized by logical hierarchy
- ✅ No page has more than 5-8 settings (rule respected)
- ✅ AI section fully restructured (6 sub-pages)
- ✅ Old pages deleted or consolidated
- ✅ All routes working
- ✅ Backward compatible redirects in place
- ✅ All translations updated
- ✅ Menu reflects new structure
- ✅ Responsive design works
- ✅ i18n works on all pages
- ✅ No accessibility issues
