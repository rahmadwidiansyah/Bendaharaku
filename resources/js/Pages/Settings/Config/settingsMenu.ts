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
        description: 'Timezone & date format',
        route: 'settings.account.preferences',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // Application (⚙️)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'application',
    label: 'Application',
    icon: 'Cog',
    description: 'App appearance & behavior',
    submenu: [
      {
        id: 'appearance',
        label: 'Appearance',
        icon: 'Palette',
        description: 'Theme, colors, density',
        route: 'settings.application.appearance',
      },
      {
        id: 'language',
        label: 'Language & Region',
        icon: 'Globe',
        description: 'Language, date format, currency',
        route: 'settings.application.language',
      },
      {
        id: 'notifications',
        label: 'Notifications',
        icon: 'Bell',
        description: 'Email, push, quiet hours',
        route: 'settings.application.notifications',
      },
    ],
  },

  // ═══════════════════════════════════════════════════════════
  // Finance (💰)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'finance',
    label: 'Finance',
    icon: 'Wallet',
    description: 'Wallets, categories, budget',
    submenu: [
      {
        id: 'defaults',
        label: 'Defaults',
        icon: 'Zap',
        description: 'Default wallet & currency',
        route: 'settings.finance.defaults',
      },
      {
        id: 'categories',
        label: 'Categories',
        icon: 'Tag',
        description: 'Manage transaction categories',
        route: 'settings.finance.categories',
      },
      {
        id: 'wallets',
        label: 'Wallets',
        icon: 'CreditCard',
        description: 'Manage wallets',
        route: 'settings.finance.wallets',
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
  // Keuangan (💰)
  // ═══════════════════════════════════════════════════════════
  {
    id: 'keuangan',
    label: 'Keuangan',
    icon: 'Wallet',
    description: 'Allow negative balance',
    submenu: [
      {
        id: 'allow-negative-balance',
        label: 'Allow Negative Balance',
        icon: 'ToggleLeft',
        description: 'Allow wallet balance to go negative',
        route: 'settings.keuangan.index',
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
