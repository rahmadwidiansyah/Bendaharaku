<?php

declare(strict_types=1);

/**
 * Translation keys untuk Settings — English.
 *
 * Naming convention:
 *   settings.<section>.<page>.<key>
 */
return [

    // ──────────────────────────────────────────────────────────────
    // GENERAL
    // ──────────────────────────────────────────────────────────────
    'title' => 'Settings',
    'subtitle' => 'Manage Settings',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'loading' => 'Loading...',
    'success' => 'Saved successfully!',
    'error' => 'An error occurred',

    // ──────────────────────────────────────────────────────────────
    // ACCOUNT SECTION
    // ──────────────────────────────────────────────────────────────
    'account' => [
        'title' => 'Account',
        'description' => 'Manage your account',

        'profile' => [
            'title' => 'Profile',
            'description' => 'Your personal information',
            'email' => 'Email',
            'name' => 'Name',
            'help_text' => 'To edit your profile, visit the Profile page',
        ],

        'security' => [
            'title' => 'Security',
            'description' => 'Manage account security and authentication',
            'password' => [
                'title' => 'Password',
                'description' => 'Change your password',
                'change_button' => 'Change Password',
            ],
            '2fa' => [
                'title' => 'Two-Factor Authentication',
                'description' => 'Add an extra layer of security',
                'coming_soon' => 'Coming Soon',
                'enable' => 'Enable 2FA',
            ],
            'login_activity' => [
                'title' => 'Login Activity',
                'description' => 'View your recent login attempts',
                'tracking_soon' => 'Login activity tracking coming soon',
            ],
        ],

        'sessions' => [
            'title' => 'Active Sessions',
            'description' => 'Manage your active sessions',
            'current' => 'Current Session',
            'current_browser' => 'This Browser',
            'last_active' => 'Last active just now',
            'active' => 'Active',
            'other_sessions' => 'Other Sessions',
            'no_other_sessions' => 'No other active sessions',
        ],

        'preferences' => [
            'title' => 'Preferences',
            'description' => 'Set your timezone and date format preferences',
            'timezone' => [
                'title' => 'Timezone',
                'description' => 'Select your timezone',
            ],
            'date_format' => [
                'title' => 'Date Format',
                'description' => 'Choose your preferred date format',
                'ddmmyyyy' => 'DD/MM/YYYY',
                'mmddyyyy' => 'MM/DD/YYYY',
                'yyyymmdd' => 'YYYY-MM-DD',
            ],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // APPLICATION SECTION
    // ──────────────────────────────────────────────────────────────
    'application' => [
        'title' => 'Application',
        'description' => 'App appearance and behavior',

        'appearance' => [
            'title' => 'Appearance',
            'description' => 'Customize how the application looks',
            'theme' => [
                'title' => 'Theme',
                'description' => 'Choose your preferred theme',
                'light' => 'Light',
                'dark' => 'Dark',
                'system' => 'System',
            ],
            'accent_color' => [
                'title' => 'Accent Color',
                'description' => 'Choose your accent color',
            ],
        ],

        'language' => [
            'title' => 'Language & Region',
            'description' => 'Set your language, currency, and date format',
            'language' => [
                'title' => 'Language',
                'description' => 'Choose your language',
                'id' => 'Bahasa Indonesia',
                'en' => 'English',
                'auto' => 'Auto (Device)',
                'autoDesc' => 'Follow device language settings',
                'current' => 'Current',
            ],
            'currency' => [
                'title' => 'Currency',
                'description' => 'Default currency for transactions',
                'idr' => 'IDR - Indonesian Rupiah',
                'usd' => 'USD - US Dollar',
                'eur' => 'EUR - Euro',
            ],
        ],

        'notifications' => [
            'title' => 'Notifications',
            'description' => 'Control how you receive notifications',
            'email' => [
                'title' => 'Email Notifications',
                'description' => 'Receive updates via email',
                'label' => 'Send me email notifications',
            ],
            'push' => [
                'title' => 'Push Notifications',
                'description' => 'Receive desktop notifications',
                'label' => 'Send me push notifications',
            ],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // FINANCE SECTION
    // ──────────────────────────────────────────────────────────────
    'finance' => [
        'title' => 'Finance',
        'description' => 'Wallets, categories, budgets',

        'defaults' => [
            'title' => 'Defaults',
            'description' => 'Set your default wallet and currency',
            'wallet' => [
                'title' => 'Default Wallet',
                'description' => 'Choose which wallet to use by default',
            ],
            'currency' => [
                'title' => 'Default Currency',
                'description' => 'Currency for new transactions',
            ],
            'transaction_logic' => [
                'title' => 'Transaction Logic',
                'description' => 'Allow negative balance on transactions',
                'label' => 'Allow transactions below zero balance',
                'on' => 'Enabled',
                'off' => 'Disabled',
            ],
        ],

        'categories' => [
            'title' => 'Categories',
            'description' => 'Manage your transaction categories',
            'manage' => 'Categories are managed from the main Categories page',
            'go_to' => 'Go to Categories',
        ],

        'wallets' => [
            'title' => 'Wallets',
            'description' => 'Manage your wallets',
            'manage' => 'Wallets are managed from the main Wallets page',
            'go_to' => 'Go to Wallets',
        ],

        'budget' => [
            'title' => 'Budget',
            'description' => 'Set budget limits and alerts',
            'coming_soon' => 'Budget management features are coming soon',
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // AI SECTION
    // ──────────────────────────────────────────────────────────────
    'ai' => [
        'title' => 'Artificial Intelligence',
        'description' => 'AI settings and integrations',

        'models' => [
            'title' => 'Models & Configuration',
            'description' => 'Select and configure your AI provider and models',
            'provider' => [
                'label' => 'AI Provider',
                'description' => 'Choose your AI provider (GPT, Claude, Gemini, etc.)',
            ],
            'model' => [
                'label' => 'Model Selection',
                'description' => 'Select the AI model to use',
                'hint' => 'Different models have different capabilities and costs',
                'description' => 'Choose your preferred model version',
            ],
            'token_limit' => [
                'label' => 'Token Limit',
                'description' => 'Set maximum tokens per request',
                'hint' => 'Higher limits allow longer responses but cost more',
            ],
            'api_key' => [
                'label' => 'API Key',
                'description' => 'Your API key for the selected provider',
                'warning' => 'Keep your API key secret. Never share it publicly.',
            ],
            'status' => 'Status',
            'test_button' => 'Test Connection',
            'testing' => 'Testing...',
            'test_success' => 'Connection successful!',
            'test_failed' => 'Connection failed',
            'select_model' => 'Select a model',
            'help_text' => 'Configure your AI provider and model settings here. Test the connection to ensure everything is working properly.',
        ],

        'bot' => [
            'title' => 'Bot Profile',
            'description' => 'Customize your AI assistant',
            'avatar' => [
                'label' => 'Bot Avatar',
                'description' => 'Upload a profile picture for your bot',
                'upload_button' => 'Choose Image',
                'hint' => 'Supports JPG, PNG (max 5MB)',
            ],
            'name' => [
                'label' => 'Bot Name',
                'description' => 'Give your bot a name',
                'placeholder' => 'e.g., Ken-Chan',
                'hint' => 'This name will appear in conversations',
            ],
            'personality' => [
                'label' => 'Personality & Tone',
                'description' => 'Customize how your bot communicates',
                'coming_soon' => 'Coming Soon',
                'coming_soon_description' => 'Personality settings will be available soon',
            ],
            'help_text' => 'Personalize your AI assistant here. Your bot\'s name and avatar will be displayed in conversations.',
        ],

        'memory' => [
            'title' => 'Memory Management',
            'description' => 'Configure conversation history and learning',
            'retention' => [
                'label' => 'Retention Policy',
                'description' => 'How long to keep conversation history',
                'hint' => 'Longer retention improves AI learning but uses more storage',
                'unlimited' => 'Unlimited',
                'last_7_days' => 'Last 7 days',
                'last_30_days' => 'Last 30 days',
                'last_90_days' => 'Last 90 days',
                'custom' => 'Custom',
                'custom_days' => 'Days to retain',
            ],
            'size_limit' => [
                'label' => 'Memory Size Limit',
                'description' => 'Maximum size of stored memories',
                'hint' => 'Set a storage limit for conversation memory',
            ],
            'conversation_history' => [
                'label' => 'Conversation History',
                'description' => 'Store conversations for context',
                'enable' => 'Enable conversation history',
            ],
            'learning' => [
                'label' => 'Knowledge Learning',
                'description' => 'Allow AI to learn from interactions',
                'enable' => 'Enable learning',
                'warning' => 'This may affect AI behavior over time',
            ],
            'privacy' => [
                'label' => 'Privacy Mode',
                'description' => 'Do not use data for model training',
                'enable' => 'Enable privacy mode',
                'hint' => 'When enabled, your conversations will not be used to train the AI model',
            ],
            'help_text' => 'Configure how your AI retains and learns from conversations.',
        ],

        'integration' => [
            'title' => 'Integrations',
            'description' => 'Connect AI to your platforms',
            'telegram' => [
                'title' => 'Telegram Bot',
                'description' => 'Connect your Telegram bot',
                'info' => 'Telegram integration allows you to use AI features through Telegram',
                'configure' => 'Configure Telegram',
                'status' => 'Status',
            ],
            'webhooks' => [
                'title' => 'Webhooks',
                'description' => 'Setup webhooks for AI events',
                'coming_soon' => 'Coming Soon',
            ],
        ],

        'advanced' => [
            'title' => 'Advanced Settings',
            'description' => 'Developer and experimental options',
            'developer_mode' => [
                'label' => 'Developer Mode',
                'description' => 'Enable advanced developer features',
                'enable' => 'Enable developer mode',
                'warning' => 'Developer mode shows technical details and debugging options',
            ],
            'prompt_debugging' => [
                'label' => 'Prompt Debugger',
                'description' => 'Debug prompts and responses',
                'enable' => 'Enable prompt debugging',
            ],
            'raw_responses' => [
                'label' => 'Raw Responses',
                'description' => 'View raw API responses',
                'enable' => 'Show raw responses',
            ],
            'system_prompt' => [
                'label' => 'System Prompt',
                'description' => 'Customize the system prompt',
                'placeholder' => 'Enter your custom system prompt...',
                'hint' => 'Advanced: Modify how the AI behaves at a system level',
            ],
            'templates' => [
                'label' => 'Prompt Templates',
                'description' => 'Save and reuse prompts',
                'coming_soon' => 'Coming Soon',
            ],
            'experimental' => [
                'label' => 'Experimental Features',
                'description' => 'Try new beta features',
                'enable' => 'Enable experimental features',
                'warning' => 'Experimental features may be unstable',
            ],
            'help_text' => 'Advanced options for power users and developers.',
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // PRIVACY & DATA SECTION
    // ──────────────────────────────────────────────────────────────
    'privacy' => [
        'title' => 'Privacy & Data',
        'description' => 'Data and privacy management',

        'settings' => [
            'title' => 'Privacy',
            'description' => 'Control your privacy settings',
            'data_collection' => [
                'title' => 'Data Collection',
                'description' => 'Allow us to collect usage data',
                'label' => 'Allow usage data collection',
            ],
            'analytics' => [
                'title' => 'Analytics',
                'description' => 'Allow analytics tracking',
                'label' => 'Send analytics',
            ],
        ],

        'data' => [
            'title' => 'Data Management',
            'description' => 'Export, import, and backup your data',
            'export' => [
                'title' => 'Export Data',
                'description' => 'Download your data in JSON format',
                'button' => 'Export as JSON',
            ],
            'import' => [
                'title' => 'Import Data',
                'description' => 'Import previously exported data',
                'button' => 'Import (Coming Soon)',
            ],
            'backup' => [
                'title' => 'Backup',
                'description' => 'Create an automatic backup',
                'button' => 'Backup Now (Coming Soon)',
            ],
        ],

        'danger' => [
            'title' => 'Danger Zone',
            'description' => 'Irreversible actions - use with caution',
            'clear_cache' => [
                'title' => 'Clear Cache',
                'description' => 'Remove all cached data',
                'button' => 'Clear Cache',
            ],
            'delete_account' => [
                'title' => 'Delete Account',
                'description' => 'Permanently delete your account and all data',
                'warning' => 'This action cannot be undone. All your data will be permanently deleted',
                'button' => 'Delete Account',
            ],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // SYSTEM SECTION
    // ──────────────────────────────────────────────────────────────
    'system' => [
        'title' => 'System',
        'description' => 'About and diagnostics',

        'about' => [
            'title' => 'About',
            'description' => 'Application information and credits',
            'app_name' => 'Bendaharaku',
            'app_description' => 'Personal finance manager application designed to help you manage expenses and assets easily',
            'version' => 'Version',
            'license' => [
                'title' => 'License',
                'description' => 'Open Source',
                'type' => 'MIT License',
            ],
            'credits' => [
                'title' => 'Credits',
                'description' => 'Built with',
                'laravel' => 'Laravel - Backend framework',
                'vue' => 'Vue 3 - Frontend framework',
                'inertia' => 'Inertia.js - Server-driven UI',
                'tailwind' => 'Tailwind CSS - Styling',
            ],
        ],

        'diagnostics' => [
            'title' => 'Diagnostics',
            'description' => 'System status and logs',
            'system_status' => [
                'title' => 'System Status',
                'description' => 'Overall system health',
                'api' => 'API Status',
                'database' => 'Database',
                'healthy' => 'Healthy',
                'connected' => 'Connected',
            ],
            'logs' => [
                'title' => 'Logs',
                'description' => 'Recent system logs',
                'no_logs' => 'No recent logs',
            ],
        ],
    ],
];
