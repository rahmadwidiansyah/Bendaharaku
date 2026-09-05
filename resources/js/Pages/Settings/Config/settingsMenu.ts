/**
 * Settings Menu Configuration
 * 
 * Struktur hierarki untuk menu Settings.
 * Scalable: cukup tambah item, tidak perlu refactor komponen.
 */

export interface SettingsMenuItem {
  id: string;
  label: string;
  icon?: string;
  description?: string;
  route?: string;
  badge?: string | number;
  submenu?: SettingsMenuItem[];
}

export interface SettingsCategory extends SettingsMenuItem {
  submenu?: SettingsMenuItem[];
}

const settingsMenuTree: SettingsCategory[] = [
  // ═══════════════════════════════════════════════════════════
  // Account (👤)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'account',
    label: 'Account',
    icon: 'User',
    description: 'Manage your account',
    submenu: [
      {
        id: 'profile',
        label: 'Profile',
        icon: 'UserCircle',
        description: 'Personal information',
        route: 'settings.account.profile',
      },
      {
        id: 'security',
        label: 'Security',
        icon: 'Lock',
        description: 'Password & authentication',
        route: 'settings.account.security',
      },
      {
        id: 'preferences',
        label: 'Preferences',
        icon: 'Settings',
        description: 'Language, timezone & date format',
        route: 'settings.account.preferences',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // Appearance (🎨)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'finance',
    label: 'Finance',
    icon: 'Wallet',
    description: 'Wallets, categories, budget',
    submenu: [
      {
        id: 'transaction-logic',
        label: 'Transaction Logic',
        icon: 'ToggleLeft',
        description: 'Allow negative balance',
        route: 'settings.finance.logic',
      },
      {
        id: 'budget',
        label: 'Budget',
        icon: 'TrendingUp',
        description: 'Budget limits & alerts',
        route: 'settings.finance.budget',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // Artificial Intelligence (🤖)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'ai',
    label: 'Artificial Intelligence',
    icon: 'Zap',
    description: 'AI settings & integrations',
    submenu: [
      {
        id: 'models',
        label: 'Providers & Models',
        icon: 'Cpu',
        description: 'AI provider, model settings & configuration',
        route: 'settings.ai.models',
      },
      {
        id: 'bot',
        label: 'Bot Profile',
        icon: 'SmilePlus',
        description: 'Bot name, avatar, personality',
        route: 'settings.ai.bot',
      },
      {
        id: 'memory',
        label: 'Memory',
        icon: 'Database',
        description: 'Conversation and knowledge memory settings',
        route: 'settings.ai.memory',
      },
      {
        id: 'integration',
        label: 'Integrations',
        icon: 'Share2',
        description: 'Messaging, Automation & External Services',
        route: 'settings.ai.integrations',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // Privacy & Data (🔒)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'privacy',
    label: 'Privacy & Data',
    icon: 'Shield',
    description: 'Data & privacy management',
    submenu: [
      {
        id: 'privacy-settings',
        label: 'Privacy',
        icon: 'Eye',
        description: 'Data collection & analytics',
        route: 'settings.privacy.settings',
      },
      {
        id: 'data-management',
        label: 'Data Management',
        icon: 'Database',
        description: 'Export, import, backup',
        route: 'settings.privacy.data',
      },
      {
        id: 'activity-logs',
        label: 'Activity Logs',
        icon: 'Clock',
        description: 'Riwayat aktivitas & log 7 hari',
        route: 'settings.privacy.logs',
      },
      {
        id: 'danger-zone',
        label: 'Danger Zone',
        icon: 'AlertTriangle',
        description: 'Irreversible actions',
        route: 'settings.privacy.danger',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // System (🔧)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'system',
    label: 'System',
    icon: 'Settings',
    description: 'About & system',
    submenu: [
      {
        id: 'about',
        label: 'About',
        icon: 'Info',
        description: 'Version, license, credits',
        route: 'settings.system.about',
      },
    ],
  },
];

export default settingsMenuTree;
