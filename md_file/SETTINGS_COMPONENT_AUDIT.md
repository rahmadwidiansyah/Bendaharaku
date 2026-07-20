# SETTINGS INFORMATION ARCHITECTURE - COMPONENT LEVEL AUDIT

**Status**: Phase 3 - Deep Restructuring  
**Approach**: Content Migration (not just page migration)  
**Focus**: Every card, form, section, switch must be analyzed individually  

---

## 🎯 CORE PRINCIPLE

**"Jangan hanya memindahkan halaman. Audit setiap card, section, form, switch, select, button, dan action di dalam setiap halaman. Setiap elemen harus berada pada kategori yang paling sesuai."**

---

## 📦 ANALYZING OLD Ai.vue CONTENT

**Current Route**: `/settings/ai`  
**Current File**: `Settings/Ai.vue`  
**Props**: providerStatuses, availableProviders, modelsByProvider, usageStats, recentLogs

### Components Inside Ai.vue (CARD-BY-CARD ANALYSIS)

#### Card 1: "AI Provider Selection"
```
Current: /settings/ai → "Provider"
Props: availableProviders, selectedProvider, availableModels
Components: Select dropdown, test button, status indicator

Analysis:
- This is CONFIGURATION (user chooses their AI provider)
- NOT a setting, it's a CRITICAL CHOICE
- User would look for: "AI → Models" or "AI → Provider"
- Recommended Location: /settings/ai/models

Migration:
✓ Move to: /settings/ai/models.vue
✓ Keep: Select dropdown, test button, status
✓ Title: "AI Models & Provider"
```

#### Card 2: "Integration"
```
Current: /settings/ai → "Integration"
Content: Telegram webhook setup
Props: Integration configuration form

Analysis:
- This is INTEGRATION (connects to external services)
- Multiple integrations possible (Telegram, Discord, WhatsApp)
- Recommended Location: /settings/ai/integrations (NOT just one card)

Migration:
✓ Move to: /settings/ai/integrations.vue
✓ Keep: Telegram webhook form
✓ Future: Add Discord, WhatsApp tabs
```

#### Card 3: "Performance Analytics" (Chart + Metrics)
```
Current: /settings/ai → "Performance"
Content: Line chart, performance metrics
Props: data.performance, overview metrics

Analysis:
- This is ANALYTICS/REPORTING (shows usage data)
- NOT a "setting" - user doesn't CONFIGURE anything here
- Settings = user CHANGES something
- Analytics = user READS data
- User would NOT look in Settings for analytics

Recommended Location: REMOVE from Settings
✓ Move to: DASHBOARD or separate AI Analytics page (NOT in /settings)
✓ Could be: /dashboard/ai or /ai/analytics (outside settings)

Why: Analytics is READ-ONLY, Settings should be ACTIONABLE
```

---

## 📦 ANALYZING OLD ChatBotProfile.vue CONTENT

**Current Route**: `/settings/chat/bot-profile`  
**Current File**: `Settings/ChatBotProfile.vue`  
**Props**: botName, botAvatar

### Components Inside ChatBotProfile.vue (CARD-BY-CARD)

#### Card 1: "Bot Avatar" (Image Upload)
```
Current: /settings/chat/bot-profile → "Photo"
Components: Avatar preview, upload button
Props: botAvatar

Analysis:
- This is BOT CUSTOMIZATION
- Related to: Bot appearance, identity
- Connected to: Personality (in future)
- Recommended Location: /settings/ai/bot

Migration:
✓ Move to: /settings/ai/bot.vue
✓ Rename: "Bot Profile" or "Bot Appearance"
```

#### Card 2: "Bot Name" (Text Input)
```
Current: /settings/chat/bot-profile → "Name"
Components: Text input, save button
Props: botName

Analysis:
- This is BOT IDENTITY
- Part of: Bot profile customization
- Recommended Location: /settings/ai/bot

Migration:
✓ Move to: /settings/ai/bot.vue
✓ Group with: Avatar, personality (future)
```

#### Card 3: "Upcoming Features" (Future Personality Settings)
```
Current: /settings/chat/bot-profile → "Fitur Mendatang"
Components: Badge "Soon"
Props: (none yet)

Analysis:
- Placeholder for: Personality, tone, behavior
- When implemented: Should be separate page

Migration:
✓ Create new page: /settings/ai/behavior.vue
✓ Will have: Personality slider, tone selection, custom instructions
```

---

## 📦 ANALYZING OLD AiAnalytics.vue CONTENT

**Current File**: `Settings/AiAnalytics.vue`  
**Route**: None (Not routed)

### Components Inside AiAnalytics.vue (CARD-BY-CARD)

#### Card 1: "Overview Metrics" (4 KPIs)
```
Current: AiAnalytics.vue → "Overview"
Metrics:
- Total Requests
- Success Rate
- Draft Rate
- Correction Rate

Analysis:
- These are METRICS (read-only, reporting)
- NOT settings (user doesn't change these)
- User would look: Dashboard, Analytics page (NOT Settings)
- Recommended Location: OUTSIDE Settings

Migration:
✗ DO NOT INCLUDE in Settings
✓ Move to: Separate Analytics Dashboard (/dashboard/ai)
```

#### Card 2: "Performance Chart" (Line graph)
```
Current: AiAnalytics.vue → "Performance Over Time"
Content: Chart.js line chart, performance data
Props: performanceData

Analysis:
- This is VISUALIZATION of analytics data
- NOT a setting to change
- Recommended Location: Analytics dashboard (outside settings)

Migration:
✗ DO NOT INCLUDE in Settings
✓ Move to: /dashboard/ai or /ai/analytics (not /settings)
```

---

## 🏗️ NEW HIERARCHICAL STRUCTURE (PROFESSIONAL)

### ✅ ACCOUNT SETTINGS
```
/settings/account

├── Personal
│   ├── Profile (Name, email, bio)
│   └── Avatar (Profile picture upload)
│
├── Preferences
│   ├── Timezone
│   ├── Date Format
│   └── Language (MOVE from Application)
│
├── Security
│   ├── Password
│   ├── Two-Factor Authentication
│   └── Recovery Codes
│
└── Sessions & Devices
    ├── Active Sessions (device, location, last seen)
    └── Connected Accounts (Google, GitHub login)
```

**New Routes:**
- `/settings/account/profile` ✅ (exists)
- `/settings/account/preferences` ✅ (exists)
- `/settings/account/security` ✅ (exists)
- `/settings/account/sessions` ✅ (exists)

---

### ✅ APPLICATION SETTINGS
```
/settings/application

├── Appearance
│   ├── Theme (Dark/Light/System)
│   ├── Accent Color
│   └── Font Size
│
├── Notifications
│   ├── Email Notifications
│   ├── Browser Notifications
│   └── Sound Alerts
│
└── Accessibility
    ├── High Contrast
    ├── Reduce Motion
    └── Screen Reader Support
```

**Routes:**
- `/settings/application/appearance` ✅
- `/settings/application/notifications` ✅
- `/settings/application/accessibility` 🆕

**Move OUT of Application:**
- Language → Move to Account/Preferences

---

### 💰 FINANCE SETTINGS
```
/settings/finance

├── General
│   ├── Default Wallet
│   ├── Default Currency
│   └── Number Format (1,234.56 vs 1.234,56)
│
├── Accounts
│   ├── Wallets Management
│   └── Linked Bank Accounts
│
├── Organization
│   ├── Categories
│   ├── Recurring Transactions
│   └── Tags
│
├── Planning
│   ├── Budget Settings
│   ├── Financial Goals
│   └── Spending Alerts
│
├── Investments
│   ├── Portfolio Settings
│   ├── Asset Classes
│   └── Performance Tracking
│
└── Debt & Receivables
    ├── Debt Settings
    └── Receivables Tracking
```

**Routes:**
- `/settings/finance/defaults` ✅
- `/settings/finance/wallets` ✅
- `/settings/finance/categories` ✅
- `/settings/finance/budget` ✅
- `/settings/finance/investments` 🆕
- `/settings/finance/debt` 🆕

---

### 🔒 PRIVACY & DATA SETTINGS
```
/settings/privacy

├── Privacy Controls
│   ├── Profile Visibility
│   ├── Activity Visibility
│   └── Sharing Permissions
│
├── Tracking & Analytics
│   ├── Analytics Consent
│   ├── Error Reporting
│   └── Usage Data
│
└── Data Management
    ├── Export Data
    ├── Import Data
    ├── Backup
    ├── Restore
    └── Danger Zone (Delete Account, Reset All)
```

**Routes:**
- `/settings/privacy/settings` ✅ (Privacy Controls)
- `/settings/privacy/data` ✅ (Data Management)
- `/settings/privacy/danger` ✅ (Danger Zone)

---

### ⚙️ SYSTEM SETTINGS
```
/settings/system

├── About
│   ├── Version
│   ├── Build Info
│   └── Credits
│
├── Diagnostics
│   ├── System Health
│   └── Performance Metrics
│
├── Developer Tools
│   ├── API Keys & Tokens
│   ├── Webhooks
│   └── Debug Mode
│
└── Logs
    ├── Activity Logs
    ├── Error Logs
    └── Audit Trail
```

**Routes:**
- `/settings/system/about` ✅
- `/settings/system/diagnostics` ✅
- `/settings/system/developer` 🆕
- `/settings/system/logs` 🆕

---

### 🤖 ARTIFICIAL INTELLIGENCE (NEW HIERARCHICAL STRUCTURE)

**IMPORTANT: AI is a BIG FEATURE, not just a setting**

```
/settings/ai

├── Models & Configuration
│   ├── AI Provider Selection
│   ├── Model Selection
│   ├── Token Limits
│   ├── API Configuration
│   └── Testing & Validation
│
├── Assistant (Bot)
│   ├── Bot Profile (Name, Avatar)
│   ├── Personality & Tone
│   ├── Custom Instructions
│   └── Response Format
│
├── Memory Management
│   ├── Conversation History
│   ├── Long-term Memory Settings
│   ├── Privacy & Data Retention
│   └── Memory Cleanup
│
├── Integrations
│   ├── Telegram Bot
│   ├── Discord Bot
│   ├── WhatsApp (Future)
│   └── Webhooks & Custom Integrations
│
└── Developer & Advanced
    ├── Prompt Debugging
    ├── System Prompts
    ├── Advanced Settings
    └── API Documentation
```

**Routes (NEW STRUCTURE):**
- `/settings/ai/models` 🆕 (from Ai.vue)
- `/settings/ai/bot` 🆕 (from ChatBotProfile.vue)
- `/settings/ai/memory` 🆕 (new feature)
- `/settings/ai/integrations` 🆕 (from Ai.vue + enhance)
- `/settings/ai/developer` 🆕 (new section)

**REMOVE from Settings:**
- ❌ AI Analytics (Usage charts, metrics) → Move to Dashboard
- ❌ Performance reporting → Move to Dashboard

---

## 📋 CONTENT MIGRATION TABLE (DETAILED)

### From Ai.vue
| Component | Content | Current Location | Recommended Location | Type | Action |
|---|---|---|---|---|---|
| Provider Dropdown | Select AI provider | /settings/ai | /settings/ai/models | CONFIG | ✅ Move |
| Model Selection | Choose model (GPT, Claude) | /settings/ai | /settings/ai/models | CONFIG | ✅ Move |
| Token Limit | Set token limits | /settings/ai | /settings/ai/models | CONFIG | ✅ Move |
| Test Button | Test AI connection | /settings/ai | /settings/ai/models | ACTION | ✅ Move |
| Status Indicator | Show provider status | /settings/ai | /settings/ai/models | INFO | ✅ Move |
| Telegram Webhook | Telegram integration form | /settings/ai | /settings/ai/integrations | CONFIG | ✅ Move |
| Analytics Chart | Performance metrics graph | /settings/ai | ❌ REMOVE | ANALYTICS | ✗ Delete (move to dashboard) |
| Usage Stats | Total requests, success rate | /settings/ai | ❌ REMOVE | ANALYTICS | ✗ Delete (move to dashboard) |
| Recent Logs | Recent API calls | /settings/ai | /settings/system/logs | LOGS | ✅ Move to system |

### From ChatBotProfile.vue
| Component | Content | Current Location | Recommended Location | Type | Action |
|---|---|---|---|---|---|
| Avatar Upload | Bot profile picture | /settings/chat/bot-profile | /settings/ai/bot | CONFIG | ✅ Move |
| Bot Name Input | Bot display name | /settings/chat/bot-profile | /settings/ai/bot | CONFIG | ✅ Move |
| Personality Badge | (Future) Personality settings | /settings/chat/bot-profile | /settings/ai/behavior | PLACEHOLDER | ✅ Move |

### From AiAnalytics.vue
| Component | Content | Current Location | Recommended Location | Type | Action |
|---|---|---|---|---|---|
| KPI Metrics | 4 overview cards | Settings/AiAnalytics.vue | ❌ REMOVE | ANALYTICS | ✗ Delete (move to dashboard) |
| Performance Chart | Line graph over time | Settings/AiAnalytics.vue | ❌ REMOVE | ANALYTICS | ✗ Delete (move to dashboard) |

---

## 🗺️ ROUTES REFACTORING

### ✅ Routes to KEEP (No Change)
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
```

### 🆕 Routes to CREATE
```
/settings/account/connected-accounts (NEW - for OAuth accounts)
/settings/application/accessibility (NEW)
/settings/finance/investments (NEW)
/settings/finance/debt (NEW)
/settings/system/logs (NEW)
/settings/system/developer (NEW)
/settings/ai/models (NEW - from Ai.vue)
/settings/ai/bot (NEW - from ChatBotProfile.vue)
/settings/ai/memory (NEW)
/settings/ai/integrations (RENAME/ENHANCE - from Ai.vue integration)
/settings/ai/developer (NEW - advanced settings)
```

### 🔄 Routes to REDIRECT (Backward Compatibility)
```
/settings/ai
  → Redirect to /settings/ai/models
  → Status: 301 Moved Permanently

/settings/chat/bot-profile
  → Redirect to /settings/ai/bot
  → Status: 301 Moved Permanently

/settings (old index)
  → Redirect to /settings/account/profile
  → Status: 301 Moved Permanently
```

### ❌ Routes to DEPRECATE
```
/settings/ai (old endpoint - will redirect)
/settings/ai/test (move to API)
/settings/ai/api/dashboard (move to /dashboard/ai)
/settings/ai/api/feedback (move to /dashboard/ai)
/settings/chat/bot-avatar (consolidate to PATCH /settings/ai/bot)
```

---

## 📑 MENU CONFIGURATION (settingsMenu.ts)

**Before (Current):**
```typescript
// Only 6 categories, flat structure
Account → Profile, Security, Sessions, Preferences
Application → Appearance, Language, Notifications
Finance → Defaults, Categories, Wallets, Budget
Privacy → Settings, Data, Danger
System → About, Diagnostics
AI → Integration
```

**After (New):**
```typescript
// Hierarchical structure with subsections
Account
  ├── Personal
  │   ├── Profile
  │   └── Avatar
  ├── Preferences
  │   ├── Timezone
  │   ├── Date Format
  │   └── Language
  ├── Security
  │   ├── Password
  │   ├── Two-Factor
  │   └── Recovery
  └── Sessions
      ├── Active Sessions
      └── Connected Accounts

Application
  ├── Appearance
  ├── Notifications
  └── Accessibility

Finance
  ├── General
  │   ├── Defaults
  │   └── Accounts
  ├── Organization
  │   ├── Categories
  │   └── Recurring
  ├── Planning
  │   ├── Budget
  │   └── Goals
  ├── Investments
  └── Debt & Receivables

Privacy
  ├── Privacy Controls
  ├── Tracking
  └── Data Management
      ├── Export
      ├── Import
      ├── Backup
      └── Danger Zone

System
  ├── About
  ├── Diagnostics
  ├── Developer Tools
  └── Logs

AI (Artificial Intelligence)
  ├── Models & Configuration
  ├── Assistant (Bot)
  ├── Memory
  ├── Integrations
  └── Developer
```

---

## 🎯 WHY EACH CHANGE

### AI Provider → /settings/ai/models (Not just "Models")
**Reason:** User thinks "AI Models" when they want to configure provider/model/tokens

### Chat Bot → AI Bot
**Reason:** Bot is part of AI, not separate. Consistency with feature structure.

### Analytics → Remove from Settings
**Reason:** Settings = configuration (user changes things). Analytics = reporting (user reads data). They're different concerns. Move to Dashboard.

### Language → Move to Account/Preferences
**Reason:** Language preference is personal preference, not application-wide. Belongs with timezone, date format.

### Finance Restructuring
**Reason:** Currently flat. User can't find "where do I manage investments?" Should be clear subsections.

### AI Gets Special Treatment
**Reason:** It's a major feature. Deserves hierarchical treatment like professional apps (GitHub, VS Code, Notion).

---

## ✅ IMPLEMENTATION ROADMAP

### Phase 3A: AI Complete Restructure (PRIORITY 1)
1. Analyze Ai.vue card-by-card ✅ (done above)
2. Create `/settings/ai/models` page
3. Move `/settings/chat/bot-profile` → `/settings/ai/bot`
4. Create `/settings/ai/memory` page
5. Create `/settings/ai/integrations` page (enhance existing)
6. Create `/settings/ai/developer` page
7. DELETE old Ai.vue, ChatBotProfile.vue, AiAnalytics.vue
8. Add redirects
9. Update settingsMenu.ts with hierarchy

### Phase 3B: New Settings Pages (PRIORITY 2)
1. Create `/settings/application/accessibility`
2. Create `/settings/account/connected-accounts` (future OAuth)
3. Create `/settings/finance/investments`
4. Create `/settings/finance/debt`
5. Create `/settings/system/logs`
6. Create `/settings/system/developer`
7. Update settingsMenu.ts

### Phase 3C: Account/Finance Optimization (PRIORITY 3)
1. Move Language from Application to Account
2. Reorganize Finance subsections
3. Update all breadcrumbs
4. Update all i18n keys

### Phase 3D: Final Polish (PRIORITY 4)
1. Test all routes
2. Verify analytics moved to dashboard (not settings)
3. Test redirects
4. Verify responsive design
5. Accessibility audit
6. i18n verification

---

## 📊 STATS

| Metric | Before | After | Change |
|---|---|---|---|
| Total Settings Pages | 17 | 23 | +6 |
| Settings Routes | 23 | 28 | +5 |
| Menu Subsections | 6 | 12+ | Hierarchical |
| Max Items per Page | 8-10 | 5-8 | ✅ Better |
| Pages with Single Purpose | 80% | 100% | ✅ Clearer |
| Analytics in Settings | ⚠️ Yes | ❌ No | ✅ Moved to Dashboard |

---

## 🎓 BEFORE vs AFTER

### Before: User Confusion
```
User: "Where do I set up Telegram integration?"
→ Checks: Settings → Application? No.
→ Checks: Settings → AI → (mixes provider + integration + analytics)
→ Confused why analytics is here
```

### After: Intuitive
```
User: "Where do I set up Telegram integration?"
→ Checks: Settings → AI → Integrations ✅
→ Finds Telegram, Discord, WhatsApp all together
→ Clear, organized, logical
```

### Before: Too Much in One Place
```
Settings → AI
  ├── Choose provider (CONFIGURATION)
  ├── View analytics (REPORTING)
  ├── Set webhook (INTEGRATION)
  ├── View performance (REPORTING)
  └── Set bot name (PROFILE)
```

### After: Separated by Purpose
```
Settings → AI → Models (CONFIGURATION)
Settings → AI → Bot (PROFILE)
Settings → AI → Integrations (INTEGRATION)
Settings → AI → Developer (ADVANCED)
Dashboard → AI Analytics (REPORTING)
```

---

## ✨ SUCCESS CRITERIA

- ✅ Every setting is in exactly ONE logical place
- ✅ No page has more than 5-8 items
- ✅ Analytics moved OUT of Settings to Dashboard
- ✅ AI properly hierarchical with subsections
- ✅ Finance properly organized by function
- ✅ All redirects work for backward compatibility
- ✅ User can intuitively find any setting
- ✅ Feels like GitHub/Google Cloud/VS Code Settings
- ✅ i18n works on all new pages
- ✅ Responsive design maintained
- ✅ All translations updated
- ✅ Accessibility standards met
