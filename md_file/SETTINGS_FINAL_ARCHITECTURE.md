# SETTINGS FINAL INFORMATION ARCHITECTURE

**Status**: Phase 3 - Professional UX Architecture  
**Approach**: Mental Model-Based (not category-based)  
**Principle**: "What is user trying to do?" not "Where does this belong?"  

**Revision**: Enhanced from component audit with expert feedback

---

## 🎯 CORE ARCHITECTURAL PRINCIPLE

**Settings** = User CONFIGURES application behavior  
**Features** = User PERFORMS actions on master data (CRUD)  
**Analytics** = User READS/MONITORS data (read-only)  
**Administration** = User MANAGES team/workspace (future)  

---

## 📊 WHAT STAYS IN SETTINGS vs WHAT MOVES

### ❌ MOVING OUT OF SETTINGS

#### 1. Master Data Management (→ Sidebar)
```
MOVING OUT:
- Wallets
- Categories
- Recurring Transactions
- Budget (?)

WHY: These are CRUD operations on domain entities
- User creates, reads, updates, deletes these
- Not "preferences" or "configuration"
- These are daily-use features
- Belongs in main navigation
- Like Notion: Workspace → Database (not Settings)

NEW LOCATION:
Sidebar Main Menu
├── Dashboard
├── Transactions
├── Wallets ← (moved from Finance settings)
├── Categories ← (moved from Finance settings)
├── Budget (optional - could stay or move)
├── Reports
└── Settings
```

#### 2. Analytics & Reporting (→ Dashboard)
```
MOVING OUT:
- AI Analytics (usage, performance, metrics)
- Cost tracking
- Usage statistics
- Performance reports

WHY: These are READ-ONLY monitoring
- Not actionable settings
- User monitors, not configures
- Should be in Dashboard/Analytics section

NEW LOCATION:
Dashboard Section
├── AI Analytics
│   ├── Token Usage
│   ├── Performance Metrics
│   ├── Cost Analysis
│   └── Recent Logs
├── Finance Reports
│   ├── Monthly Summary
│   ├── Category Breakdown
│   ├── Trends
│   └── Forecasts
└── System Health
    ├── Diagnostics
    ├── Performance
    └── Backup Status
```

---

## 🏗️ FINAL HIERARCHICAL STRUCTURE

### 📌 SIDEBAR MAIN MENU (Application Features)
```
Bendaharaku

├── 📊 Dashboard
│   ├── Overview
│   ├── Quick Actions
│   └── Recent Activity
│
├── 💰 Transactions
│   ├── All Transactions
│   ├── Income
│   ├── Expenses
│   └── Transfers
│
├── 🏦 Wallets ← MOVED from Settings
│   ├── Wallet List
│   ├── Create Wallet
│   ├── Wallet Settings (icon, color, archive)
│   └── Merge/Transfer
│
├── 📂 Categories ← MOVED from Settings
│   ├── All Categories
│   ├── Create Category
│   ├── Edit Hierarchy
│   └── Manage Rules
│
├── 📈 Budget ← OPTIONAL: Could stay or move
│   ├── Budget Plans
│   ├── Spending vs Budget
│   ├── Alerts
│   └── Automation
│
├── 📊 Reports
│   ├── Monthly Reports
│   ├── Category Analysis
│   ├── Trend Analysis
│   └── Export
│
└── ⚙️ Settings
    └── (see below)
```

---

### ⚙️ SETTINGS SECTION (Configuration Only)

```
/settings

├── 👤 ACCOUNT
│   ├── Personal
│   │   ├── Profile (name, email, bio)
│   │   └── Avatar
│   │
│   ├── Preferences
│   │   ├── Timezone
│   │   ├── Date Format
│   │   ├── Language
│   │   └── Currency (moved from Finance)
│   │
│   ├── Security
│   │   ├── Password
│   │   ├── Two-Factor Authentication
│   │   ├── Recovery Codes
│   │   └── Login History
│   │
│   └── Identity
│       ├── Email Address
│       ├── Password
│       ├── OAuth Connections
│       │   ├── Google
│       │   ├── GitHub
│       │   └── Microsoft
│       └── Connected Devices
│
├── 🎨 APPLICATION
│   ├── Appearance
│   │   ├── Theme (Dark/Light/System)
│   │   ├── Accent Color
│   │   ├── Font Size
│   │   └── Compact Mode
│   │
│   ├── Notifications
│   │   ├── Email Notifications
│   │   ├── Browser Notifications
│   │   ├── Sound Alerts
│   │   └── Do Not Disturb
│   │
│   └── Accessibility
│       ├── High Contrast
│       ├── Reduce Motion
│       ├── Text Size
│       ├── Screen Reader Support
│       └── Keyboard Shortcuts
│
├── 💼 FINANCE DEFAULTS
│   ├── General
│   │   ├── Default Wallet ← LINK to Wallets
│   │   ├── Default Currency
│   │   └── Number Format
│   │
│   └── Preferences
│       ├── Auto-categorization
│       ├── Transaction Defaults
│       └── Rounding Behavior
│
│   ⚠️ NOTE: Actual wallet/category/budget management
│       is in Sidebar, not here
│
├── 🔒 PRIVACY & DATA
│   ├── Privacy Controls
│   │   ├── Profile Visibility
│   │   ├── Activity Visibility
│   │   └── Data Sharing
│   │
│   ├── Tracking & Analytics
│   │   ├── Analytics Consent
│   │   ├── Error Reporting
│   │   └── Usage Data
│   │
│   ├── Backup & Restore
│   │   ├── Cloud Backup Status
│   │   ├── Google Drive Backup
│   │   ├── Local Backup
│   │   ├── Schedule
│   │   └── Restore Options
│   │
│   ├── Migration
│   │   ├── Import (CSV/Excel/JSON)
│   │   ├── Export (CSV/Excel/JSON)
│   │   └── Data Format
│   │
│   └── Danger Zone
│       ├── Delete Account
│       ├── Reset All Data
│       └── Unlink Services
│
├── ⚙️ SYSTEM
│   ├── About
│   │   ├── Version
│   │   ├── Build Info
│   │   ├── Release Notes
│   │   └── Credits
│   │
│   ├── Diagnostics
│   │   ├── System Health
│   │   ├── Performance
│   │   ├── Storage Usage
│   │   └── Database Status
│   │
│   ├── Developer Tools
│   │   ├── API Keys & Tokens
│   │   ├── Webhook Management
│   │   ├── Debug Mode
│   │   └── API Documentation
│   │
│   └── Logs & History
│       ├── Activity Logs
│       ├── Error Logs
│       ├── Audit Trail
│       └── Export Logs
│
├── 🤖 ARTIFICIAL INTELLIGENCE
│   ├── Models & Configuration
│   │   ├── AI Provider
│   │   ├── Model Selection
│   │   ├── Token Limits
│   │   ├── API Configuration
│   │   └── Test Connection
│   │
│   ├── Assistant (Bot)
│   │   ├── Bot Profile (Name, Avatar)
│   │   ├── Personality & Tone
│   │   ├── Custom Instructions
│   │   └── Response Format
│   │
│   ├── Memory Management
│   │   ├── Retention Policy
│   │   ├── Conversation History
│   │   ├── Knowledge Base
│   │   ├── Learning Settings
│   │   └── Privacy & Data
│   │
│   ├── Integrations
│   │   ├── Telegram Bot
│   │   ├── Discord Bot
│   │   ├── WhatsApp (Future)
│   │   ├── Webhooks
│   │   └── Custom Integrations
│   │
│   └── Advanced
│       ├── Developer Mode
│       ├── Prompt Debugger
│       ├── Raw Responses
│       ├── Prompt Templates
│       ├── System Prompt
│       └── Experimental Features
│
└── 🏢 WORKSPACE (Future/Enterprise)
    ├── Workspace Settings
    │   ├── Workspace Name
    │   ├── Logo
    │   ├── Plan & Billing
    │   └── Integrations
    │
    ├── Members & Invitations
    │   ├── Members List
    │   ├── Pending Invitations
    │   ├── Member Roles
    │   └── Permissions
    │
    ├── Teams
    │   ├── Create Team
    │   ├── Team Members
    │   ├── Team Permissions
    │   └── Default Settings
    │
    ├── Organizations
    │   ├── Sub-organizations
    │   ├── Department Management
    │   └── Cost Allocation
    │
    ├── Roles & Permissions
    │   ├── Custom Roles
    │   ├── Permission Sets
    │   ├── Inheritance Rules
    │   └── Audit
    │
    └── Billing & Usage
        ├── Plan Details
        ├── Billing History
        ├── Usage Statistics
        └── Upgrade/Downgrade
```

---

## 📋 DETAILED BREAKDOWN BY SECTION

### 👤 ACCOUNT SETTINGS
**Principle**: User configuration of personal identity & preferences

#### Profile (Existing ✅)
- Name, email, bio
- Avatar upload
- Language selection
- Timezone

#### Preferences (Existing ✅)
- Timezone
- Date Format
- Currency (MOVED from Finance)
- Theme preference

#### Security (Existing ✅)
- Password change
- 2FA setup
- Recovery codes
- Login history

#### Identity (NEW - Enhanced)
- **Email Address** (from Profile)
- **Password** (from Security)
- **OAuth Connections** (NEW)
  - Connect Google
  - Connect GitHub
  - Connect Microsoft
  - Connect Apple (future)
- **Connected Devices** (from Sessions)

**Why separate "Identity"**: OAuth is future-proof. When you support multi-sign-on, this page becomes critical.

---

### 🎨 APPLICATION SETTINGS
**Principle**: Application UI/UX preferences

#### Appearance (Existing ✅)
- Theme (dark/light/system)
- Accent color
- Font size
- Compact mode

#### Notifications (Existing ✅)
- Email notifications
- Browser notifications
- Sound alerts
- Do Not Disturb schedule

#### Accessibility (NEW ✅)
- High contrast
- Reduce motion
- Text size override
- Screen reader support
- Keyboard shortcuts
- Focus indicators

**Future additions**: Color blindness modes, dyslexia-friendly fonts

---

### 💼 FINANCE DEFAULTS
**Principle**: Finance feature configuration (NOT management)

**IMPORTANT**: Wallet, Category, Budget management is in Sidebar, NOT Settings

#### General
- **Default Wallet** → Links to Wallets in Sidebar
- **Default Currency** → Moved from Language
- **Number Format** → 1,234.56 vs 1.234,56

#### Preferences
- Auto-categorization rules
- Transaction defaults
- Rounding behavior

---

### 🔒 PRIVACY & DATA
**Principle**: User data control & protection

#### Privacy Controls (ENHANCED)
- Profile visibility
- Activity visibility
- Data sharing with 3rd parties
- Ad targeting preferences

#### Tracking & Analytics
- Analytics consent
- Error reporting
- Usage data collection
- Crash reports

#### Backup & Restore (NEW - Split)
- Cloud backup status
- Google Drive backup
- Local backup download
- Schedule preferences
- Restore options
- Version history

#### Migration (NEW - Split)
- Import data (CSV/Excel/JSON)
- Export data (CSV/Excel/JSON)
- Data format options
- Batch operations

#### Danger Zone
- Delete account
- Reset all data
- Unlink services

---

### ⚙️ SYSTEM
**Principle**: Application health & developer tools

#### About
- Version number
- Build information
- Release notes
- Credits & licenses

#### Diagnostics
- System health check
- Performance metrics
- Storage usage
- Database status
- Network status

#### Developer Tools (NEW - Renamed from "Developer")
- API keys & tokens
- Webhook management
- Debug mode toggle
- API documentation links
- Rate limits

#### Logs & History
- Activity logs
- Error logs
- Audit trail
- Export logs

---

### 🤖 ARTIFICIAL INTELLIGENCE
**Principle**: AI feature configuration (not analytics)

#### Models & Configuration
- Select AI provider (GPT, Claude, Gemini, etc.)
- Choose model version
- Set token limits
- Configure API keys
- Test connection

#### Assistant (Bot)
- Bot profile name & avatar
- Personality & tone settings
- Custom instructions
- Response format

#### Memory Management
- Retention policy (how long to keep conversations)
- Conversation history settings
- Knowledge base configuration
- Learning settings
- Privacy controls for AI learning

#### Integrations
- Telegram bot setup
- Discord integration
- WhatsApp (future)
- Custom webhooks
- API integrations

#### Advanced
- Developer mode (for prompt engineers)
- Prompt debugger (view raw responses)
- Raw response viewing
- Prompt templates
- System prompt customization
- Experimental features flag

**Why separate "Advanced"?**: Not all users need this. Keeps main settings clean.

---

### 🏢 WORKSPACE (FUTURE/ENTERPRISE)
**Principle**: Team & organization management

#### Workspace Settings
- Workspace name & branding
- Logo upload
- Plan & billing
- Integrations
- API limits

#### Members & Invitations
- Members list
- Pending invitations
- Role assignment
- Permission review

#### Teams (Future)
- Create teams
- Team members
- Team-specific settings
- Team permissions

#### Organizations (Future)
- Sub-organizations
- Department management
- Cost allocation
- Hierarchy

#### Roles & Permissions
- Custom role creation
- Permission sets
- Inheritance rules
- Audit log

#### Billing & Usage
- Plan details
- Billing history
- Usage metrics
- Upgrade/downgrade

**Why include now?**: Future-proofs architecture. When you scale to teams/orgs, structure is ready.

---

## 🗺️ FINAL ROUTES STRUCTURE

### Settings Routes (28 routes)
```
/settings (dashboard)
/settings/account/profile
/settings/account/preferences
/settings/account/security
/settings/account/identity
/settings/application/appearance
/settings/application/notifications
/settings/application/accessibility
/settings/finance/defaults
/settings/privacy/settings
/settings/privacy/tracking
/settings/privacy/backup
/settings/privacy/migration
/settings/privacy/danger
/settings/system/about
/settings/system/diagnostics
/settings/system/developer
/settings/system/logs
/settings/ai/models
/settings/ai/bot
/settings/ai/memory
/settings/ai/integrations
/settings/ai/advanced
/settings/workspace/settings (future)
/settings/workspace/members (future)
/settings/workspace/billing (future)
```

### Feature Routes (Sidebar)
```
/wallets
/wallets/{id}
/categories
/categories/{id}
/budget
/budget/{id}
/transactions
/reports
/dashboard
```

### Analytics Routes (Dashboard)
```
/dashboard/ai
/dashboard/finance
/dashboard/system
```

---

## 🔄 MIGRATION PLAN (FROM OLD Ai.vue)

| Old Component | Old Location | New Location | Type | Action |
|---|---|---|---|---|
| Provider Dropdown | `/settings/ai` | `/settings/ai/models` | CONFIG | ✅ Move |
| Model Selection | `/settings/ai` | `/settings/ai/models` | CONFIG | ✅ Move |
| Token Limit | `/settings/ai` | `/settings/ai/models` | CONFIG | ✅ Move |
| Test Button | `/settings/ai` | `/settings/ai/models` | ACTION | ✅ Move |
| Telegram Setup | `/settings/ai` | `/settings/ai/integrations` | CONFIG | ✅ Move |
| Analytics Chart | `/settings/ai` | `/dashboard/ai` | ANALYTICS | ✅ Move |
| Usage Stats | `/settings/ai` | `/dashboard/ai` | ANALYTICS | ✅ Move |

---

## 📊 INFORMATION ARCHITECTURE STATISTICS

| Aspect | Before | After | Change |
|---|---|---|---|
| Settings Pages | 17 | 24 | +7 |
| Settings Routes | 23 | 28 | +5 |
| Top-level Categories | 6 | 7 | +1 |
| Pages with <5 items | 60% | 100% | ✅ |
| Analytics in Settings | ⚠️ Yes | ❌ No | Moved |
| Master Data in Settings | ⚠️ Yes | ❌ No | Moved |
| Future-proof Sections | 0 | 2 | ✅ |

---

## ✨ MENTAL MODEL ALIGNMENT

### Principle: Ask "What is the user trying to do?"

#### User: "I want to change my password"
```
Old: Settings → (where?)
New: Settings → Account → Security ✅
Mental Model: Account settings for authentication
```

#### User: "I want to connect Telegram to AI"
```
Old: Settings → AI → (might find it, might not)
New: Settings → AI → Integrations ✅
Mental Model: AI feature has integrations section
```

#### User: "I want to see how much AI I've used"
```
Old: Settings → AI → (analytics mixed with settings)
New: Dashboard → AI Analytics ✅
Mental Model: Dashboards show metrics, settings show configuration
```

#### User: "I need to create a new wallet"
```
Old: Settings → Finance → (wallet is not here)
New: Sidebar → Wallets ✅
Mental Model: Wallets are main app features, not settings
```

#### User: "I need to set my default wallet"
```
New: Settings → Finance Defaults → Default Wallet ✅
Mental Model: Settings has preferences/defaults
```

---

## 🎓 DESIGN PRINCIPLES APPLIED

### 1. Separation of Concerns
- **Settings**: Configuration (user chooses behavior)
- **Features**: CRUD operations (user manages data)
- **Dashboard**: Monitoring (user reads metrics)
- **Admin**: Team management (organization control)

### 2. Mental Models
- User thinks about what they want to do
- Architecture reflects use cases
- Navigation feels intuitive

### 3. Information Scent
- Each section name clearly indicates content
- No surprises when clicking
- Related items grouped logically

### 4. Progressive Disclosure
- Basic settings visible
- Advanced settings hidden in "Advanced"
- Future features in "Workspace"

### 5. Scalability
- Adding OAuth → Identity section ready
- Adding teams → Workspace section ready
- Adding new integrations → Integrations page ready

---

## ✅ IMPLEMENTATION PHASES

### Phase 3A: Critical Restructuring (Week 1)
- [ ] Create `/settings/ai/models` (from Ai.vue)
- [ ] Create `/settings/ai/bot` (from ChatBotProfile)
- [ ] Create `/settings/ai/integrations` (enhance)
- [ ] Create `/settings/ai/memory` (new)
- [ ] Create `/settings/ai/advanced` (renamed from developer)
- [ ] Move `/settings/finance/` to Sidebar `/wallets`, `/categories`
- [ ] Move Analytics to `/dashboard/ai`
- [ ] Add 301 redirects for old routes
- [ ] Update `settingsMenu.ts`

### Phase 3B: New Settings Pages (Week 2)
- [ ] Create `/settings/account/identity` (new)
- [ ] Create `/settings/application/accessibility`
- [ ] Create `/settings/privacy/backup`
- [ ] Create `/settings/privacy/migration`
- [ ] Create `/settings/system/logs`
- [ ] Create `/settings/system/developer`

### Phase 3C: Feature Enhancement (Week 3)
- [ ] Enhance Wallets feature (move from settings)
- [ ] Enhance Categories feature (move from settings)
- [ ] Create Sidebar navigation with Features
- [ ] Update all breadcrumbs
- [ ] Update all translations

### Phase 3D: Polish & Verify (Week 4)
- [ ] Test all routes & redirects
- [ ] Verify Analytics in Dashboard
- [ ] Responsive testing
- [ ] Accessibility audit
- [ ] i18n verification
- [ ] Performance check

---

## 🎯 SUCCESS CRITERIA

✅ Every setting is configuration-only (user changes behavior)  
✅ Master data management in Sidebar (not Settings)  
✅ Analytics in Dashboard (not Settings)  
✅ No page exceeds 5-8 items  
✅ Mental model alignment (intuitive navigation)  
✅ Future-proof structure (Workspace ready)  
✅ All redirects functional  
✅ Backward compatible  
✅ Professional SaaS appearance  
✅ All translations updated  
✅ Responsive design  
✅ Accessibility standards met  

---

## 📝 NOTES

- **Wallets/Categories/Budget**: Moved to Sidebar features (not Settings)
  because they're daily-use CRUD operations, not preferences
  
- **Analytics**: Moved to Dashboard (not Settings)
  because they're read-only monitoring, not configuration

- **Advanced AI Settings**: Separate from main settings
  to keep options clean for non-developers

- **Workspace**: Included in structure for future scalability
  when enterprise/team features are added

- **Identity**: Renamed from "Connected Accounts"
  to be future-proof for multi-signin options

- **Currency**: Moved to Account/Preferences (personal)
  not Application (which is for UI/UX only)
