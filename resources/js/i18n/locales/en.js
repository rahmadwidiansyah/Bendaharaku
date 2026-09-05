/**
 * i18n/locales/en.js
 * English locale for Bendaharaku
 *
 * GLOSSARY (consistent finance terminology):
 *   Pemasukan   → Income
 *   Pengeluaran → Expense
 *   Transfer    → Transfer
 *   Hutang      → Debt
 *   Piutang     → Receivable
 *   Dompet      → Wallet
 *   Aset        → Asset
 *   Kategori    → Category
 *   Label       → Label
 *   Tabungan    → Savings
 *   Investasi   → Investment
 *   Liquid      → Liquid
 *   Statistik   → Statistics
 *   Analitik    → Analytics
 *   Laporan     → Report
 *   Dashboard   → Dashboard
 *   Pengaturan  → Settings
 *   Ekspor      → Export
 *   Impor       → Import
 *   Draft       → Draft
 *   Terkonfirmasi → Confirmed
 */

export default {

    // ────────────────────────────────────────────────────────────────
    // COMMON
    // ────────────────────────────────────────────────────────────────
    common: {
        save:           'Save',
        saving:         'Saving...',
        cancel:         'Cancel',
        delete:         'Delete',
        deleting:       'Deleting...',
        edit:           'Edit',
        add:            'Add',
        create:         'Create',
        confirm:        'Confirm',
        close:          'Close',
        back:           'Back',
        loading:        'Loading...',
        search:         'Search...',
        filter:         'Filter',
        all:            'All',
        none:           'None',
        never:          'Never',
        yes:            'Yes',
        no:             'No',
        ok:             'OK',
        success:        'Success',
        error:          'Error',
        warning:        'Warning',
        partial:        'Partial',
        info:           'Info',
        required:       'Required',
        optional:       'Optional',
        total:          'Total',
        balance:        'Balance',
        amount:         'Amount',
        date:           'Date',
        time:           'Time',
        note:           'Note',
        name:           'Name',
        type:           'Type',
        status:         'Status',
        active:         'Active',
        inactive:       'Inactive',
        empty:          'Empty',
        days:           'Days',
        currency:       'Rp',
        dataEmpty:      'No Data',
        noData:         'No data available',
        seeAll:         'See All',
        goTo:           'Go to',
        skipToContent:  'Skip to content',
        processing:     'Processing...',
        period:         'Period',
        today:          'Today',
        saveAndAddMore: 'Save & add another',
        open:           'Open',
        dateRange:      'Date Range',
        from:           'From',
        to:             'To',
        dateInvalidRange: 'End date must be the same or after the start date.',
        thisYear:       'This Year',
        thisMonth:      'This Month',
        lastMonth:      'Last Month',
        applyFilter:    'Apply Filter',
        applying:       'Applying...',
        // Generic error messages
        errors: {
            generic: 'Something went wrong. Please try again later.'
        },
    },

    // ────────────────────────────────────────────────────────────────
    // TRANSACTION TYPES
    // ────────────────────────────────────────────────────────────────
    types: {
        income:         'Income',
        expense:        'Expense',
        transfer:       'Transfer',
        debt:           'Debt',
        receivable:     'Receivable',
        other:          'Other',
        all:            'All Types',
        incomeDesc:     'Receive money',
        expenseDesc:    'Pay for something',
        transferDesc:   'Move between wallets',
        debtDesc:       'Borrow / repay',
        receivableDesc: 'Lend / collect',
    },

    // ────────────────────────────────────────────────────────────────
    // NAVIGATION
    // ────────────────────────────────────────────────────────────────
    nav: {
        home:       'Home',
        budgeting:  'Budgeting',
        record:     'Record',
        analytics:  'Analytics',
        label:      'Labels',
        loan:       'Liabilities',
        settings:   'Settings',
        telegram:   'Telegram',
        chat:       'Chat',
        newRecord:  'New Record',
        mainNav:    'Main navigation',
        profile:    'Profile',
        help:       'Help',
        homeDesc:      'Main dashboard',
        budgetingDesc: 'Manage your budgets',
        recordDesc:    'Transaction history',
        analyticsDesc: 'Financial reports',
        chatDesc:      'Chat with AI Assistant',
        settingsDesc:  'All application settings',
    },

    // ────────────────────────────────────────────────────────────────
    // BUDGETING
    // ────────────────────────────────────────────────────────────────
    budgeting: {
        title:            'Budgeting',
        subtitle:         'AI Assistant',
        monthlyTitle:     'Monthly Budget',
        categories:       'categories',
        emptyTitle:       'Automatic budget from {bot}',
        emptyDesc:        'based on your last 3 months of transactions',
        generate:         'Ask {bot} to generate budget',
        pastPeriod:       'AI budgets can only be created for the current month',
        pastPeriodHint:   'AI budgets can only be created for the current month. Manual editing is still available.',
        generating:       'Generating budget...',
        generated:        'Budget generated',
        saved:            'Budget saved',
        refresh:          'Refresh AI',
        refreshConfirmTitle: 'Replace this budget?',
        refreshConfirmMsg: 'Regenerating will replace the current budget — including your manual edits — with a brand new one.',
        refreshConfirmCta: 'Yes, regenerate',
        edit:             'Edit Manually',
        save:             'Save',
        cancel:           'Cancel',
        saving:           'Saving...',
        editingHint:      'Edit mode — adjust amounts then save',
        period:           'Period',
        totalBudget:      'Total Budget',
        totalSpent:       'Total Spent',
        totalRemaining:   'Remaining',
        byCategory:       'By Category',
        byType:           'By Expense Type',
        budget:           'Budget',
        spent:            'Spent',
        remaining:        'Remaining',
        overBudget:       'Over budget',
        aiNotes:          'AI Insights',
        aiNotesTitle:     'Budget explanation from {bot}',
        close:            'Close',
        loading:          'Loading budget data...',
        loadError:        'Failed to load budget data',
        aiError:          '{bot} is busy or not responding. Please try again in a moment.',
        retry:            'Retry',
        noBudgetYet:      'No budget for this period yet',
        budgetFor:        'Budget for',
        timeout:          'Budget generation took too long. Check your connection and try again.',
        createManual:     'Create Manual Budget',
        titleCreate:      'Create Manual Budget',
        categoryLabel:    'Category',
        groupLabel:       'Expense Type',
        amountLabel:      'Amount',
        amountPlaceholder: '0',
        selectCategory:   'Select category',
        selectGroup:      'Select type',
        pickerHint:       'Tap to select',
        noCategories:     'No expense categories yet. Create one in the Categories menu.',
        customGroup:      'Custom',
        customGroupPlaceholder: 'New type name (e.g. Installment)',
        addCustomGroup:   'Add',
        addRow:           'Add Category',
        removeRow:        'Remove',
        totalLabel:       'Total Budget',
        mergeHint:        'Your existing AI budget will be merged: categories you change use your amounts, the rest stay from AI. Rows with an empty type are excluded from the budget.',
        replaceAi:        'Delete AI result & replace everything',
        replaceHint:      'The existing budget will be fully replaced by the contents of this form.',
    },

    // ────────────────────────────────────────────────────────────────
    // HEADER
    // ────────────────────────────────────────────────────────────────
    header: {
        toggleBalance:  'Toggle balance visibility',
        openSettings:   'Open settings',
        actions:        'Header actions',
    },

    // ────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ────────────────────────────────────────────────────────────────
    dashboard: {
        title:              'Dashboard',
        totalWealth:        'Net Worth',
        mainWallets:        'Main Wallets',
        unpinFromDashboard: 'Unpin from Dashboard',
        transactionHistory: 'Transaction History',
        filterType:         'Filter by Type',
        searchPlaceholder:  'Search notes or ID...',
        noTransactions:     'No transactions found',
        calendarTab:        'Calendar',
        detailTab:          'Detail',
        income:             'Income',
        expense:            'Expense',
        cashflow:           'Cash Flow',

        calendar: {
            sun: 'Sun',
            mon: 'Mon',
            tue: 'Tue',
            wed: 'Wed',
            thu: 'Thu',
            fri: 'Fri',
            sat: 'Sat',
        },

        calendarFilter: {
            income:  'Income',
            total:   'Total',
            expense: 'Expense',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // PORTFOLIO / ASSET OVERVIEW
    // ────────────────────────────────────────────────────────────────
    portfolio: {
        title:      'Net Worth',
        liquid:     'Liquid',
        investment: 'Investment',
        subtitle:   'Asset movement this month',
    },

    // ────────────────────────────────────────────────────────────────
    // TRANSACTION
    // ────────────────────────────────────────────────────────────────
    transaction: {
        title:          'Record Transaction',
        titleEdit:      'Edit Transaction',
        amount:         'Amount',
        amountHint:     'Enter amount',
        category:       'Category',
        chooseCategory: 'Select Category',
        sourceWallet:   'Source Wallet',
        destWallet:     'Destination Wallet',
        chooseWallet:   'Select Wallet',
        date:           'Date',
        note:           'Note',
        notePlaceholder:'Add a note... (optional)',
        draft:          'Draft',
        confirmed:      'Confirmed',
        cancelled:      'Cancelled',
        confirmDraft:   'Confirm Transaction',
        confirmDraftQ:  'Confirm this draft transaction?',
        confirmDraftMsg:'This transaction will be officially recorded.',
        deleteTitle:    'Delete Transaction',
        deleteMsg:      'This transaction will be permanently deleted.',
        saveDraft:      'Save as Draft',
        saveConfirm:    'Save & Confirm',
        dueDate:        'Due Date',
        dueDateHint:    'When should this debt/receivable be settled?',
        loanSubject:    'Party Name',
        loanSubjectHint:'Name of the lender/borrower',
        selectType:     'Select Type',
        typeRequired:   'Select transaction type',
        stepOf:         'Step {step} of {total}',
        chooseTypeToContinue: 'Select type to continue',
        chooseSourceWallet: 'Select source wallet...',
        chooseDestWallet: 'Select destination wallet...',
        chooseDate:     'Select Date',
        nextNominal:    'Next → Enter Amount',
        today:          'Today',
        yesterday:      'Yesterday',
        wallet:         'Wallet',
        transferFunds:  'Transfer Funds',
        allBalance:     'All Balance',
        settle:         'Settle',
        collectAll:     'Collect All',
        relatedParty:   'Related Party',
        namePlaceholder:'Name...',
        hasDueDate:     'Has Due Date?',
        fixedDate:      'Fixed Date',
        everyMonth:     'Every Month',
        everyDay:       'Every Day',
        dayPlaceholder: 'Date (1-31)',
        cyclePlaceholder:'Cycle (days)',
        saveAndStay:    'Save & Add Another',
        addCategory:    'Add Category',
        noCategory:     'No category yet',
        created:        'Created',
        updated:        'Updated',

        validation: {
            amountRequired:   'Amount is required',
            amountInvalid:    'Invalid amount',
            amountPositive:   'Amount must be greater than 0',
            categoryRequired: 'Select a category',
            sourceRequired:   'Select source wallet',
            destRequired:     'Select destination wallet',
            sameWallet:       'Source and destination wallets cannot be the same',
            dateRequired:     'Select a date',
            dateFuture:       'Date cannot be in the future',
            subjectRequired:  'Party name is required',
        },

        detail: {
            title:       'Transaction Detail',
            from:        'From',
            to:          'To',
            wallet:      'Wallet',
            party:       'Party',
            category:    'Category',
            transactionId: 'Transaction ID',
            date:        'Date',
            note:        'Note',
            noNote:      'No notes.',
            dueDate:     'Due Date',
            loanSubject: 'Party Name',
            editBtn:     'Edit',
            deleteBtn:   'Delete',
        },

        confirmDraftDetail: 'Is this transaction data correct?',
        confirmDraftWarn:   'Status will change from Draft to Confirmed and wallet balance will be updated.',
        deleteWarn:         'Deleted data cannot be recovered.',
        processing:         'Processing...',
        yesConfirm:         'Yes, Confirm',

        cancelTitle:         'Transaction Cancelled',

        // Debt / Receivable sub-tabs
        debt: {
            receive: 'Receive Debt',
            pay:     'Repay Debt',
        },
        receivable: {
            give:    'Give Receivable',
            collect: 'Collect Receivable',
        },
    },

    chatTransaction: {
        aiParsed:      'AI Parsed',
        copy:          'Copy',
        copied:        'Copied',
        copyMessage:   'Copy message',
        regenerate:    'Regenerate',
        regenerateAnswer: 'Regenerate answer',
        retry:         'Retry',
        retrySend:     'Retry sending',
        walletLoadFailed: 'Failed to load wallets.',
        confirmDelete: 'Delete this transaction?',
        recordedFrom:  'Recorded from',
        processedBy:   'Processed by',
        processingDuration: 'Processing duration',
        aiConfidence:  'AI confidence',
        transactionTime: 'Transaction time',
        rawPrompt:     'Original prompt',
        seconds:       'seconds',
        confidence: {
            high:   'High',
            medium: 'Medium',
            low:    'Low',
        },
        intent: {
            label:   'Intent',
            single:  'Single Transaction',
            multi:   'Multi Transaction',
            command: 'Command',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // WALLET / ASSET
    // ────────────────────────────────────────────────────────────────
    wallet: {
        title:          'Assets & Wallets',
        titleCreate:    'Add Wallet',
        titleEdit:      'Edit Wallet',
        totalWealth:    'Net Worth',
        liquid:         'Liquid',
        investment:     'Investment',
        addNew:         'Add Wallet / Asset',
        addNewBtn:      'Add New',
        name:           'Wallet Name',
        namePlaceholder:'e.g. BCA, GoPay, Gold',
        icon:           'Icon',
        iconHint:       'Emoji or image URL',
        keyword:        'Keyword',
        keywordHint:    'AI keyword (e.g. bca, gopay)',
        groupType:      'Group Type',
        balance:        'Initial Balance',
        balancePlaceholder: '0',
        deleteTitle:    'Delete Wallet',
        deleteMsg:      'This wallet and all its transactions will be permanently deleted.',
        deleteConfirm:  'Are you sure you want to delete this wallet?',
        totalDebt:      'Total Debt',
        totalReceivable:'Total Receivable',
        viewDebtDetail: 'View Debt Details',
        viewReceivableDetail: 'View Receivable Details',

        groupTypes: {
            liquid:     'Liquid (Cash/Digital)',
            asset:      'Asset / Investment',
        },

        recentMutation: 'Recent Mutations',
        emptyMutation:  'No mutations yet',

        pinDashboard:       'Pin to Dashboard',
        pinDashboardDesc:   'Show this wallet on the home page',

        empty:          'No wallets yet.',
        emptyLiquid:    'No liquid wallets yet.',
        emptyAsset:     'No assets yet.',
    },

    // ────────────────────────────────────────────────────────────────
    // CATEGORY / LABEL
    // ────────────────────────────────────────────────────────────────
    category: {
        title:          'Category Vault',
        titleCreate:    'Create New Category',
        titleEdit:      'Edit Category',
        name:           'Category Name',
        namePlaceholder:'e.g. Food, Transport',
        icon:           'Icon',
        iconHint:       'Emoji or image URL',
        keyword:        'Keyword',
        keywordHint:    'AI keyword',
        type:           'Transaction Type',
        addNew:         'Create New Category',
        deleteTitle:    'Delete Category',
        deleteMsg:      'This category will be permanently deleted.',
        deleteConfirm:  'Are you sure you want to delete this category?',
        totalLabel:     'Total',
        collection:     'Collection',
        transaction:    'transaction',
        systemCategory: 'System Category',

        typeHeaders: {
            Income:     'Income',
            Expense:    'Expense',
            Transfer:   'Transfer',
            Debt:       'Debt Category',
            Receivable: 'Receivable Category',
        },

        show: {
            back:           'Back',
            transactions:   'Transactions',
            noTransactions: 'No transactions yet.',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // LOAN
    // ────────────────────────────────────────────────────────────────
    loan: {
        title:          'Liabilities',
        titleDebt:      'Debt',
        titleReceivable:'Receivable',
        totalDebt:      'Total Active Debt',
        totalReceivable:'Total Active Receivable',
        fromWhom:       'From Whom',
        toWhom:         'To Whom',
        since:          'Active since',
        remaining:      'Remaining',
        days:           'Days',
        clean:          'All Clear!',
        cleanMsg:       'No active debts right now.',
        cleanMsgRcv:    'No active receivables right now.',
        activeDebtors:  '{n} active lender | {n} active lenders',
        activeCreditors:'{n} active borrower | {n} active borrowers',
    },

    // ────────────────────────────────────────────────────────────────
    // ANALYTICS / STATISTICS
    // ────────────────────────────────────────────────────────────────
    analytics: {
        title:          'Analytics',
        subtitle:       'Report',
        showingData:    'Showing data',
        cumulativeBalance: 'Cumulative Balance',
        cumulativeDesc:    'Net worth movement',
        cashflow:       'Cash Flow',
        categoryBreakdown: 'Category Breakdown',
        noData:         'No Data',
        totalIncome:    'Total Income',
        totalExpense:   'Total Expense',
        totalDebt:      'Outstanding Debt',
        totalReceivable:'Outstanding Receivable',
        outstandingDebt:'Outstanding Debt',
        outstandingReceivable:'Outstanding Receivable',

        view: {
            daily:   'Day',
            weekly:  'Week',
            monthly: 'Month',
        },

        categoryTab: {
            expense:    'EX',
            income:     'IN',
            debt:       'DT',
            receivable: 'RC',
        },

        chartLabels: {
            income:     'Income',
            expense:    'Expense',
            debt:       'Debt',
            receivable: 'Receivable',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // SETTINGS
    // ────────────────────────────────────────────────────────────────
    settings: {
        title:          'Settings',
        subtitle:       'Preferences',
        save_button:     'Save',
        index: {
            section: {
                account:        'Account',
                appearance:     'Appearance',
                finance:        'Finance',
                ai:             'Artificial Intelligence',
                notifications:  'Notifications',
                privacy:        'Privacy',
                danger:         'Danger Zone',
            },
            item:           'item',
            items:          'items',
        },
        notifications: {
            title:          'Notifications',
            description:    'Email & push notification preferences',
            notifications: {
                title:          'Notification Settings',
                description:    'Configure email and push notifications',
            },
            save_success:   'Settings saved successfully.',
            save_failed:    'Failed to save settings.',
            unsaved_changes: 'You have unsaved changes.',
        },
        security: {
            title:          'Security',
            description:    'Password & security settings',
            password: {
                title:          'Change Password',
                description:    'Strengthen your account security',
            },
        },
        developer: {
            title:          'Developer',
            description:    'Developer tools & experimental options',
        },

        // ═══ ACCOUNT ═══
        account: {
            title:          'Account',
            
            profile: {
                title:          'Profile',
                description:    'Personal information',
                email:          'Email',
                name:           'Name',
                help_text:      'To change your email or password, visit the full profile page at',
            },
            
            security: {
                title:          'Security',
                description:    'Password & authentication',
                password: {
                    title:          'Change Password',
                    description:    'Strengthen your account security',
                    change_button:  'Change Password',
                },
                '2fa': {
                    title:          'Two-Factor Authentication',
                    description:    'Extra protection for your account',
                    coming_soon:    'This feature is under development',
                    enable:         'Enable 2FA',
                },
                login_activity: {
                    title:          'Login History',
                    description:    'Monitor login activity to your account',
                    current:        'This session',
                    tracking_soon:  'Activity tracking will be available soon',
                },
            },
            
            sessions: {
                title:          'Active Sessions',
                description:    'Manage your device sessions',
                current:        'Current Session',
                current_browser:'This Browser/Device',
                last_active:    'Last active just now',
                active:         'Active',
                other_sessions: 'Other Sessions',
                no_other_sessions: 'No other active sessions',
            },
            
            preferences: {
                title:          'Preferences',
                description:    'Language, timezone & date format',
                language: {
                    title:          'Language',
                    description:    'Choose app language',
                    id:             'Bahasa Indonesia',
                    en:             'English',
                },
                timezone: {
                    title:          'Timezone',
                    description:    'Choose your timezone',
                },
                date_format: {
                    title:          'Date Format',
                    description:    'Choose date display format',
                    ddmmyyyy:       'DD/MM/YYYY',
                    mmddyyyy:       'MM/DD/YYYY',
                    yyyymmdd:       'YYYY-MM-DD',
                },
            },
        },

        // ═══ APPLICATION ═══
        application: {
            title:          'Application',
            
            appearance: {
                title:          'Appearance',
                description:    'Theme, colors, density',
                theme: {
                    title:          'Theme',
                    description:    'Choose color theme',
                    light:          'Light',
                    dark:           'Dark',
                    system:         'Follow System',
                },
                category_icon_color: {
                    title:          'Category Icon Color',
                    description:    'Icon color by transaction type',
                    label:          'Colored Category Icons',
                    on:             'Icons match transaction type',
                    off:            'All icons white',
                },
                accent_color: {
                    title:          'Accent Color',
                    description:    'Choose primary accent color',
                    custom:         'Custom Color...',
                    setAccent:      'Set {name} as accent color',
                },
            },
            
            language: {
                title:          'Language & Region',
                description:    'Language, date format, currency',
                language: {
                    title:          'Language',
                    description:    'Choose display language',
                    id:             'Bahasa Indonesia',
                    en:             'English',
                    auto:           'Follow Device Language',
                    autoDesc:       'Matches your browser/device language',
                    current:        'Active',
                },
                currency: {
                    title:          'Currency',
                    description:    'Choose default currency',
                    idr:            'IDR (Rp)',
                    usd:            'USD ($)',
                    eur:            'EUR (€)',
                },
            },
            
            notifications: {
                title:          'Notifications',
                description:    'Email, push, quiet hours',
                email: {
                    title:          'Email Notifications',
                    description:    'Receive notifications via email',
                    label:          'Enable email notifications',
                },
                push: {
                    title:          'Push Notifications',
                    description:    'Receive browser push notifications',
                    label:          'Enable push notifications',
                    unsupported:    'This browser or device does not support push notifications.',
                    vapid_missing:  'Push notifications are not configured by the admin yet.',
                    denied:         'Notification permission was denied in the browser. Enable it via your browser site settings.',
                    granted:        'Notification permission is active in the browser.',
                    default:        'Click to request browser notification permission.',
                },
            },
        },

        // ═══ FINANCE ═══
        finance: {
            title:          'Finance',
            
            defaults: {
                title:          'Defaults',
                description:    'Default wallet & currency',
                wallet: {
                    title:          'Default Wallet',
                    description:    'Wallet used when creating new transactions',
                },
                currency: {
                    title:          'Default Currency',
                    description:    'Default currency for transactions',
                },
                transaction_logic: {
                    title:          'Transaction Logic',
                    description:    'Allow negative balance on transactions',
                    label:          'Allow negative balance',
                    on:             '✓ Active — negative balance allowed',
                    off:            '✗ Inactive — transaction rejected if balance insufficient',
                },
            },
            
            categories: {
                title:          'Categories',
                description:    'Manage transaction categories',
                manage:         'Manage your transaction categories',
                go_to:          'Open Categories Page',
            },
            
            wallets: {
                title:          'Wallets',
                description:    'Manage your wallets',
                manage:         'Manage your wallets and balances',
                go_to:          'Open Wallets Page',
            },
            
            budget: {
                title:          'Budget',
                description:    'Monthly budget planning & auto-generation',
                auto_title:     'Auto-generate every month',
                auto_description: 'Generate a new budget automatically on the 1st of every month',
                save_success:   'Settings saved',
                save_error:     'Failed to save settings',
            },
        },

        // ═══ AI ═══
        ai: {
            title:          'AI & Automation',
            
            models: {
                title:          'Providers & Models',
                description:    'AI provider settings & model selection',
                provider: {
                    label:          'AI Provider',
                    description:    'Select and configure the AI provider you want to use',
                },
                model: {
                    label:          'Default Model',
                    description:    'Select the default model for this provider',
                    hint:           'This model will be used for all requests unless overridden',
                },
                token_limit: {
                    label:          'Token Limit',
                    description:    'Set maximum token limit',
                    hint:           'Limits the response length to manage costs',
                },
                api_key: {
                    label:          'API Key',
                    description:    'Your API key for this provider',
                    placeholder:    'Leave empty to keep current key',
                    warning:        'API key is encrypted and stored securely on the server',
                },
                select_model:   'Select a model...',
                status: 'Status',
                test_button: 'Test Connection',
                testing: 'Testing...',
                test_success: 'Connection succeeded.',
                test_failed: 'Connection failed.',
                set_active: 'Set as Active Provider',
                set_active_desc: 'This provider will be used for all AI conversations',
                provider_toggle: 'Make this the active provider for all conversations',
                active: 'Active',
                help_text: 'Configure the AI provider and model to use. Press Save to apply your changes.',
            },
            
            bot: {
                title:          'Bot Profile',
                description:    'Bot name, avatar, personality',
                avatar: {
                    label:          'Bot Avatar',
                    description:    'Your bot\'s profile photo',
                    upload_button:  'Upload Avatar',
                    hint:           'Use a square image; .png or .jpg recommended',
                },
                name: {
                    label:          'Bot Name',
                    description:    'Name shown in conversations',
                    placeholder:    'Bot name',
                    suggestions:    'Suggested Names',
                    hint:           'E.g.: Bendahara Bot',
                },
                personality: {
                    label:          'Bot Personality',
                    description:    'Short description of bot style and behavior',
                    placeholder:    'e.g. Friendly, concise, and helpful.',
                    hint:           'Describe the desired communication style for the bot.',
                },
                help_text: 'Personalize how your AI assistant talks and looks in the chat interface.',
            },
            
            memory: {
                title:          'Memory',
                description:    'Conversation and knowledge memory settings',
                retention: {
                    label: 'Retention Policy',
                    description: 'How long to keep conversation history',
                    unlimited: 'Keep indefinitely',
                    last_7_days: 'Keep last 7 days',
                    last_30_days: 'Keep last 30 days',
                    last_90_days: 'Keep last 90 days',
                    custom: 'Custom...',
                    hint: 'Older conversations will be automatically deleted',
                    custom_days: 'Number of days to keep',
                },
                size_limit: {
                    label: 'Memory Size Limit',
                    description: 'Maximum local storage size for memory',
                    hint: 'Limits how much storage is used for history',
                },
                conversation_history: {
                    label: 'Conversation History',
                    description: 'Remember previous messages in chat',
                    enable: 'Enable conversation history',
                },
                learning: {
                    label: 'Knowledge Base Learning',
                    description: 'Allow AI to learn from corrections',
                    enable: 'Enable continuous learning',
                    warning: 'This will store user-corrected data to improve future predictions',
                },
                privacy: {
                    label: 'Privacy Mode',
                    description: 'Do not store sensitive numbers in memory',
                    enable: 'Enable privacy mode',
                    hint: 'Transaction amounts will be redacted in history logs',
                },
                help_text: 'Memory settings control how much context the AI retains across sessions.',
                manage_button: 'Manage AI Memory',
                manage: {
                    title: 'AI Memory',
                    description: 'Memories learned by AI from your transaction habits',
                    search_placeholder: 'Search by keyword...',
                    filter_all: 'All',
                    filter_active: 'Active',
                    filter_low: 'Low Weight',
                    filter_high: 'High Weight',
                    empty_title: 'No Memories Yet',
                    empty_description: 'AI will start learning from your transactions. The more you transact, the better AI recognizes your patterns.',
                    empty_cta: 'Start Transacting',
                    card: {
                        keyword: 'Keyword',
                        category: 'Category',
                        wallet: 'Wallet',
                        weight: 'Weight',
                        hit_count: 'Frequency',
                        last_used: 'Last Used',
                        view_detail: 'View Details',
                    },
                    weight_low: 'Low',
                    weight_medium: 'Medium',
                    weight_high: 'High',
                },
                detail: {
                    title: 'Memory Detail',
                    back: 'Back to List',
                    info: 'Memory Information',
                    raw_subject: 'Raw Subject',
                    normalized_subject: 'Normalized Subject',
                    keyword: 'Keyword',
                    category: 'Category',
                    wallet: 'Wallet',
                    current_weight: 'Current Weight',
                    hit_count: 'Frequency',
                    created_at: 'Created',
                    last_used: 'Last Used',
                    algorithm_version: 'Algorithm Version',
                    timeline: 'Memory Timeline',
                    timeline_empty: 'No history for this memory yet.',
                    action: {
                        CREATED: 'Created',
                        REWARDED: 'Strengthened',
                        DECAYED: 'Decayed',
                        PRUNED: 'Pruned',
                        UPDATED: 'Updated',
                        DELETED: 'Deleted',
                        CONFLICT: 'Conflict',
                        MERGE: 'Merged',
                    },
                    weight_change: 'Weight: {old} → {new}',
                    hit_change: 'Frequency: {old} → {new}',
                },
            },
            
            integrations: {
                title:          'Integrations',
                description:    'Messaging, automation & external services',
                telegram: {
                    title:          'Telegram Integration',
                    description:    'Connect a Telegram bot to receive and send messages',
                    info:           'Connect your Telegram bot to interact with Bendaharaku via chat.',
                    configure:      'Configure Telegram Bot',
                },
                webhooks: {
                    title:          'Webhooks',
                    description:    'Send events to external services via webhook URLs',
                    hint:           'Add webhook endpoints to receive real-time events from the app.',
                },
            },

            // Alias without 's' — used by Integration.vue
            integration: {
                title:          'Integrations',
                description:    'Messaging, automation & external services',
                telegram: {
                    title:          'Telegram Integration',
                    description:    'Connect a Telegram bot to receive and send messages',
                    info:           'Connect your Telegram bot to interact with Bendaharaku via chat.',
                    configure:      'Configure Telegram Bot',
                },
                webhooks: {
                    title:          'Webhooks',
                    description:    'Send events to external services via webhook URLs',
                    hint:           'Add webhook endpoints to receive real-time events from the app.',
                },
            },

            
            advanced: {
                title:          'Developer Tools',
                description:    'Developer & experimental options',
                developer_mode: {
                    label:          'Developer Mode',
                    description:    'Enable developer mode for debugging',
                    enable:         'Enable Developer Mode',
                    warning:        'Developer mode may expose sensitive debug information',
                },
                prompt_debugging: {
                    label:          'Prompt Debugging',
                    description:    'Show the exact prompt sent to the LLM',
                    enable:         'Enable prompt debugging',
                },
                raw_responses: {
                    label:          'Raw Responses',
                    description:    'Show raw JSON/text from the AI',
                    enable:         'Show raw responses',
                },
                system_prompt: {
                    label:          'Custom System Prompt',
                    description:    'Override default behavior completely',
                    placeholder:    'You are a helpful financial assistant...',
                    hint:           'Leave empty to use the built-in system prompt',
                },
                experimental: {
                    label:          'Experimental Features',
                    description:    'Enable early-access AI features',
                    enable:         'Enable experimental features',
                    warning:        'These features may be unstable or change without notice',
                },
                templates: {
                    label:          'Prompt Templates',
                    description:    'Create and reuse prompt templates for AI interactions',
                    use:            'Use',
                    title_placeholder: 'Template title',
                    content_placeholder: 'Template body...',
                },
                help_text: 'Developer tools are for advanced users to debug or customize the AI.',
            },
        },

        // ═══ PRIVACY & DATA ═══
        privacy: {
            title:          'Privacy & Data',
            
            settings: {
                title:          'Privacy',
                description:    'Data collection & analytics',
                data_collection: {
                    title:          'Data Collection',
                    description:    'Allow us to collect analytics data',
                    label:          'Enable data collection',
                },
                analytics: {
                    title:          'Analytics',
                    description:    'Help us improve with usage analytics',
                    label:          'Enable analytics',
                },
            },
            
            data: {
                title:          'Data Management',
                description:    'Export, import, backup',
                export: {
                    title:          'Export Data',
                    description:    'Download a copy of your account data as JSON',
                    button:         'Export Data',
                    success:        'Export complete. File is downloading.',
                },
                import: {
                    title:          'Import Data',
                    description:    'Import a backup file to restore your data',
                    button:         'Import (Soon)',
                },
                backup: {
                    title:          'Backup',
                    description:    'Manage backups and restore',
                    button:         'Backup (Soon)',
                },
            },
            
            logs: {
                title:          'Activity Log',
                description:    '7-day activity history & logs',
                page_title:     'Activity Log',
                page_desc:      'Full activity history — transactions, AI memory, settings changes, and chat.',
                filters: {
                    all:         'All',
                    transaction: 'Transactions',
                    memory:      'Memory',
                    settings:    'Settings',
                    ai:          'AI',
                    chat:        'Chat',
                },
                empty_title:    'No activity yet',
                empty_desc:     'Activity will appear here after you create transactions, change settings, or chat with AI.',
                empty_filter:   'Active filter:',
                show_all:       'show all',
                pagination: {
                    page:        'Page',
                    of:          'of',
                    activities:  'activities',
                },
                hint_title:     'Why is memory empty?',
                hint_desc:      'Memory only learns from Telegram / Web Chat / OCR / Draft (see LearnFromTransaction). Transactions created directly via /transactions/create (WEB) do not train memory & default subject "-" is ignored. Check the log above with the Memory filter for CREATED/REWARDED. If still 0, try via Chat or fill Subject (e.g. “Bakso Malang”) before saving.',
            },

            danger: {
                title:          'Danger Zone',
                description:    'Irreversible actions',
                clear_cache: {
                    title:          'Clear Cache',
                    description:    'Clear local cache data',
                    button:         'Clear Cache',
                    success:        'Cache cleared successfully',
                },
                delete_account: {
                    title:          'Delete Account Permanently',
                    description:    'Delete account and all related data',
                    warning:        'This action cannot be undone. All financial data will be lost.',
                    button:         'Delete My Account',
                    confirm_title:  'Confirm Account Deletion',
                    confirm_description: 'This action CANNOT be undone. All financial data, history, and settings will be permanently deleted.',
                    confirm_button: 'Yes, Delete Permanently',
                },
            },

        },

        // ═══ SYSTEM ═══
        system: {
            title:          'System',
            
            about: {
                title:          'About',
                description:    'Version, license, credits',
                app_name:       'About Bendaharaku',
                app_description:'Smart personal finance management application',
                version:        'Version',
                license: {
                    title:          'License',
                    description:    'License information',
                    type:           'Licensed under MIT License',
                },
                credits: {
                    title:          'Credits',
                    description:    'Technologies used',
                    laravel:        'Laravel - Backend Framework',
                    vue:            'Vue 3 - Frontend Framework',
                    inertia:        'Inertia.js - Server-driven UI',
                    tailwind:       'Tailwind CSS - Styling',
                },
            },
        },

        // ═══ LEGACY KEYS (compatibility) ═══
        transaction:    'Transactions',
        negativeBalance: {
            title: 'Allow Negative Balance',
            desc:  'Expenses can be recorded even if the balance is insufficient. Useful for daily logging that is reconciled later.',
        },
        negativeBalanceOn:  '✓ Active — negative balance allowed',
        negativeBalanceOff: '✗ Inactive — transaction rejected if balance is insufficient',
        lang: {
            title:          '🌐 Language',
            auto:           'Follow Device Language',
            autoDesc:       'Matches your browser/device language',
            id:             'Bahasa Indonesia',
            en:             'English',
            current:        'Active',
        },
        aiSettings: {
            title: 'AI Settings',
            desc:  'Manage models, credentials, and AI preferences',
        },
        aiAnalytics: {
            title: 'AI Analytics',
            desc:  'Performance and AI usage statistics',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // PROFILE
    // ────────────────────────────────────────────────────────────────
    profile: {
        title:          'Profile',
        name:           'Name',
        namePlaceholder:'Your full name',
        email:          'Email',
        avatar:         'Profile Photo',
        changeAvatar:   'Change Photo',
        choosePhoto:    'Choose Photo',
        removeAvatar:   'Remove Photo',
        password:       'Password',
        currentPassword:'Current Password',
        newPassword:    'New Password',
        confirmPassword:'Confirm New Password',
        updateProfile:  'Update Profile',
        updatePassword: 'Update Password',
        deleteAccount:  'Delete Account',
        deleteAccountDesc: 'Permanently delete account and all data.',
        deleteAccountConfirm: 'CONFIRM PERMANENT DELETION? All your financial data will be lost.',
        passwordUpdated: 'Password updated successfully.',
        newPhotoSelected: 'New photo selected — save to apply',
        socialConnections: 'Social & Messaging',
        socialConnectionsDesc: 'Connect messaging apps for AI integration',
        whatsapp: 'WhatsApp',
        telegram: 'Telegram',
        google: {
            label:          'Google',
            connect:        'Connect Google Account',
            connected:      'Connected with Google',
        },

        // Logout
        logout:         'Sign Out',

        // Danger zone
        dangerZone: {
            show:           'Show Danger Zone',
            hide:           'Hide Danger Zone',
            title:          'Permanently Delete Account',
            desc:           'Once deleted, all your financial data, history, and settings will be gone and cannot be recovered.',
            confirmBtn:     'Yes, Delete My Account',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // UPCOMING DEBTS
    // ────────────────────────────────────────────────────────────────
    upcomingDebts: {
        title:          'Due Soon',
        subtitle:       'Liabilities coming due',
        empty:          'No upcoming liabilities.',
        debt:           'Debt',
        receivable:     'Receivable',
        dueIn:          'Due in',
        overdue:        'Overdue',
        days:           'days',
    },

    // ────────────────────────────────────────────────────────────────
    // INSIGHT BANNER
    // ────────────────────────────────────────────────────────────────
    insight: {
        good:       'Your finances are healthy this month! 💪',
        warning:    'Expenses are approaching income. Be careful! ⚠️',
        bad:        'Expenses exceeded income this month. 😟',
        neutral:    'No transactions this month yet.',
    },

    // ────────────────────────────────────────────────────────────────
    // AI
    // ────────────────────────────────────────────────────────────────
    ai: {
        title:          'AI Settings',
        subtitle:       'AI & Automation',
        provider:       'AI Provider',
        model:          'Model',
        apiKey:         'API Key',
        apiKeyPlaceholder: 'Enter API Key...',
        testConnection: 'Test Connection',
        testing:        'Testing...',
        save:           'Save Settings',
        connectionOk:   'Connection successful!',
        connectionFail: 'Connection failed.',
        enabled:        'AI Enabled',
        disabled:       'AI Disabled',

        analyticsTitle: 'AI Analytics',
        analyticsSubtitle: 'Report',
        overview:       'Overview',
        performance:    'Performance',
        learning:       'Learning',
        requests:       'Requests',
        success:        'Success',
        drafts:         'Drafts',
        tokens:         'Tokens',
        finalConfidence:'Final Confidence',
        correctionRate: 'Correction Rate',
        estCost:        'Est. Cost',

        charts: {
            trafficByProvider:  'Traffic by Provider',
            confidenceTrend:    'Confidence Trend',
            confidenceTrendDesc:'Raw AI vs Bendaharaku Weighting System',
            learnedKeywords:    'Most Learned Keywords',
            correctedCategories:'Top Corrected Categories',
        },

        // Ai.vue
        integration:        'AI Integration',
        integrationDesc:    'Use your personal API key to activate the smart financial assistant.',
        backupAi:           'Enable as Backup AI',
        backupAiDesc:       'Local AI (Python) runs first. This provider is only used if Python is uncertain or offline.',
        performanceTitle:   'AI Performance Analytics',
        tokenUsageTitle:    'Token Usage per Provider',
        tokenUnit:          'tokens',
        tokenPrompt:        'Prompt',
        tokenCompletion:    'Completion',
        emptyTokenUsage:    'No LLM usage recorded yet.',
        emptyTokenUsageDesc:'Tokens are only counted when using Gemini/OpenAI/DeepSeek.',
        activityLogTitle:   'AI Activity Log',
        emptyActivityLog:   'No AI activity recorded yet.',
        confidenceLabel:    '% confident',

        // AiAnalytics.vue
        last7Days:          'Last 7 Days',
        last30Days:         'Last 30 Days',
        last90Days:         'Last 90 Days',
        emptyMemory:        'No memory formed yet.',
        emptyCorrections:   'No user correction logs yet.',
        corrections:        'corrections',
        categoryId:         'Category ID',
        hits:               'Hits',
        weight:             'Weight',
    },

    // ────────────────────────────────────────────────────────────────
    // ERRORS & VALIDATION
    // ────────────────────────────────────────────────────────────────
    errors: {
        generic:        'Something went wrong. Please try again.',
        network:        'Unable to connect to server.',
        unauthorized:   'Your session has expired. Please log in again.',
        notFound:       'Page not found.',
        serverError:    'A server error occurred.',
        forbidden:      'You do not have access.',
    },

    validation: {
        required:       '{field} is required',
        minLength:      '{field} must be at least {min} characters',
        maxLength:      '{field} must be at most {max} characters',
        email:          'Invalid email format',
        numeric:        '{field} must be a number',
        positive:       '{field} must be greater than 0',
        future:         '{field} cannot be in the future',
        confirmed:      '{field} confirmation does not match',
    },

    // ────────────────────────────────────────────────────────────────
    // TOAST / NOTIFICATIONS
    // ────────────────────────────────────────────────────────────────
    toast: {
        saved:          'Saved successfully.',
        deleted:        'Deleted successfully.',
        updated:        'Updated successfully.',
        error:          'Failed. Please try again.',
        copied:         'Copied to clipboard.',
        languageChanged:'Language changed successfully.',
    },

    // ────────────────────────────────────────────────────────────────
    // EMPTY STATES
    // ────────────────────────────────────────────────────────────────
    empty: {
        transaction:    'No transactions yet.',
        transactionMsg: 'Record your first transaction!',
        wallet:         'No wallets yet.',
        walletMsg:      'Add a wallet to start recording.',
        category:       'No categories yet.',
        categoryMsg:    'Create categories to organize transactions.',
        loan:           'No active debts or receivables.',
    },

    // ────────────────────────────────────────────────────────────────
    // CHAT BOT PROFILE
    // ────────────────────────────────────────────────────────────────
    chatBot: {
        title:          'Bot Profile',
        subtitle:       'Personalize your AI assistant',
        photoSection:   'Bot Photo',
        nameSection:    'Bot Name',
        namePlaceholder:'Enter bot name...',
        nameHint:       'The name your bot will use when greeting you.',
        saveBtn:        'Save Changes',
        uploadPhoto:    'Upload Photo',
        removePhoto:    'Remove Photo',
        presetNames:    'Popular Names',
        head:           'Bot Profile',
    },

    // ────────────────────────────────────────────────────────────────
    // CHAT & SEARCH
    // ────────────────────────────────────────────────────────────────
    chat: {
        source: {
            web: 'Web Chat',
            telegram: 'Telegram Bot',
            whatsapp: 'WhatsApp',
            discord: 'Discord',
            api: 'REST API',
            import: 'Import',
            manual: 'Manual Entry',
            dashboard: 'Web Dashboard',
        },
        command: {
            balance_title: 'Current Balance',
            category_title: 'Categories',
            asset_title: '**Assets**',
            category_section_income: 'Income',
            category_section_expense: 'Expense',
            category_section_transfer: 'Transfer',
            category_section_debt: 'Debt',
            category_section_receivable: 'Receivable',
            wallet_title: '**Wallets & Assets**',
            wallet_type_asset: 'Asset',
            wallet_type_liquid: 'Liquid',
            wallet_type_system: 'System',
            asset_count: 'Assets',
        },
        status: {
            queued: 'Queued',
            pending: 'Pending',
            processing: 'Processing',
            uploading: 'Uploading',
            uploaded: 'Uploaded',
            parsed: 'Parsed',
            classified: 'Classified',
            ocrCompleted: 'OCR Complete',
            ready: 'Ready',
            completed: 'Completed',
            failed: 'Failed',
            resolved: 'Resolved',
        },
        evidenceStatus: {
            pending: 'Pending',
            uploading: 'Uploading...',
            processing: 'Processing',
            ready: 'Ready for review',
            committed: 'Saved',
            failed: 'Failed',
        },
        retry: 'Retry',
        retryEvidence: 'Retry upload',
        replyReady: '{bot} replied to your message',
        replyFailed: 'AI failed to respond. Open chat to see the details.',
        timeout: 'The bot is taking too long to respond. Try sending your message again.',
        history: 'Chat history',
        placeholder: 'Ask me anything...',
        typing: 'typing...',
        multi: {
            result: 'Multi Transaction Result',
        },
        assistant: 'AI Financial Assistant',
        loadMore: 'Show Older History',
        loadingMore: 'Loading history...',
        emptyState: 'Tell me about your transactions in natural language, or use the commands below.',
        gettingStarted: 'Getting started',
        commandButton: 'Open command menu',
        commandTitle: 'Commands (/)',
        sendButton: 'Send message',
        attachmentButton: 'Attach evidence image',
        attachmentTitle: 'Upload Evidence',
        desktopHint: 'Enter to send · Shift+Enter new line',
        uploadSheetTitle: 'Attach Transaction Evidence',
        uploadSheetDesc: 'Image will be OCR processed automatically',
        uploadSheetLabel: 'Choose image source',
        uploadCamera: 'Take Photo',
        uploadCameraDesc: 'Open camera to take a photo',
        uploadGallery: 'Choose from Gallery',
        uploadGalleryDesc: 'Select image from storage',
        evidenceUploading: 'Uploading evidence...',
        evidenceUploaded: 'Evidence uploaded, OCR processing...',
        evidenceSent: '📎 Transaction evidence sent',
        evidencePreview: 'Transaction evidence preview',
        openFullscreen: 'Open full image',
        evidence: 'Evidence',
        reviewEvidence: 'Review OCR result',
        removeEvidence: 'Remove attachment',
        sheetTitle: 'Quick Commands',
        sheetDesc: 'Select a command to insert into chat',
        sheetLabel: 'Command List',
        showMore: 'Show More',
        collapse: 'Collapse',
        scrollToBottom: 'Scroll to latest message',
        newMessages: 'New Messages',
        latest: 'Latest',
        suggestionExpense: 'Record expense',
        suggestionIncome: 'Record income',
        suggestionTransfer: 'Transfer between wallets',
        suggestionBalance: 'Check all wallet balances',
        suggestionSummary: 'Financial summary',
        suggestionReport: 'Monthly report (with AI)',
        suggestionStats: 'Monthly statistics',
        suggestionHelp: 'Usage guide',
        errorItem: 'Item #',
        buttonClose: 'Close',
        buttonSave: 'Save',
        evidenceLabel: 'Transaction Evidence',
        reviewBtn: 'Review',
        committed: 'Transaction saved',
        captionHint: 'Add caption (optional)',
        removeAttachment: 'Remove attachment',
        error: {
            sendFailed: 'Failed to send message. Check your connection.',
            evidenceFailed: 'Failed to send evidence. Check your connection.',
            connection: 'Failed to connect to server. Try again.',
            uploadFailed: 'Upload failed',
            loadDraftFailed: 'Failed to load draft data',
            saveFailed: 'Failed to save',
            commitFailed: 'Failed to create transaction',
        },
        evidenceReview: {
            title: 'Review Transaction',
            subtitle: 'Check and edit data before saving',
            confidence: 'Confidence',
            warnings: 'Warnings',
            amount: 'Amount',
            transactionType: 'Transaction Type',
            sourceWallet: 'Source Wallet',
            date: 'Date',
            description: 'Description',
            destinationName: 'Destination Name',
            destinationAccount: 'Destination Account',
            referenceNumber: 'Reference No.',
            selectWallet: 'Select wallet...',
            typeExpense: 'Expense',
            typeIncome: 'Income',
            typeTransfer: 'Transfer',
            cancel: 'Cancel',
            save: 'Save',
            saving: 'Saving...',
            commit: 'Save Transaction',
            committing: 'Saving...',
            retry: 'Retry',
            loading: 'Loading...',
        },
    },

    search: {
        placeholder: 'Search menus, settings, pages...',
        clear: 'Clear search',
        results: 'Search Results',
        shortcuts: 'Quick Shortcuts',
        noResults: 'No results for',
        navigation: 'Navigation',
        settings: 'Settings',
        hints: {
            navigate: 'navigate',
            select: 'select',
            close: 'close',
        }
    },

    // ────────────────────────────────────────────────────────────────
    // ICON PICKER
    // ────────────────────────────────────────────────────────────────
    iconPicker: {
        title:          'Choose Icon',
        search:         'Search icons...',
        upload:         'Upload',
        cropTitle:      'Crop Icon',
        notFound:       'Icon not found',
        tabs: {
            Finance:    'Finance',
            Lifestyle:  'Lifestyle',
            Places:     'Places',
            Tech:       'Tech',
            Animals:    'Animals',
            Misc:       'Misc',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // BUTTONS
    // ────────────────────────────────────────────────────────────────
    btn: {
        save:       'Save',
        saving:     'Saving...',
        cancel:     'Cancel',
        delete:     'Delete',
        deleting:   'Deleting...',
        edit:       'Edit',
        add:        'Add',
        create:     'Create',
        confirm:    'Confirm',
        back:       'Back',
        close:      'Close',
        next:       'Next',
        prev:       'Previous',
        submit:     'Submit',
        update:     'Update',
        yes:        'Yes, Delete',
        no:         'No, Cancel',
    },

}
