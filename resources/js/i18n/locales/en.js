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
        yes:            'Yes',
        no:             'No',
        ok:             'OK',
        success:        'Success',
        error:          'Error',
        warning:        'Warning',
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
        asset:      'Assets',
        record:     'Record',
        analytics:  'Analytics',
        label:      'Labels',
        loan:       'Liabilities',
        settings:   'Settings',
        telegram:   'Telegram',
        newRecord:  'New Record',
        mainNav:    'Main navigation',
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
        searchPlaceholder:  'Search notes...',
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

        validation: {
            amountRequired:   'Amount is required',
            amountInvalid:    'Invalid amount',
            amountPositive:   'Amount must be greater than 0',
            categoryRequired: 'Select a category',
            sourceRequired:   'Select source wallet',
            destRequired:     'Select destination wallet',
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

        groupTypes: {
            liquid:     'Liquid (Cash/Digital)',
            asset:      'Asset / Investment',
        },

        recentMutation: 'Recent Mutations',
        emptyMutation:  'No mutations yet',

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
        totalDebt:      'Total Debt',
        totalReceivable:'Total Receivable',

        view: {
            daily:   'Day',
            weekly:  'Week',
            monthly: 'Month',
        },

        categoryTab: {
            expense:    'Expense',
            income:     'Income',
            debt:       'Debt',
            receivable: 'Receivable',
        },

        chartLabels: {
            income:     'In',
            expense:    'Out',
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

        account:        'Account',
        transaction:    'Transactions',
        appearance:     'Appearance',
        language:       'Language',
        ai:             'AI & Automation',
        danger:         'Danger Zone',

        profile: {
            title: 'Profile & Security',
            desc:  'Name, avatar, and account password',
        },

        negativeBalance: {
            title: 'Allow Negative Balance',
            desc:  'Expenses can be recorded even if the balance is insufficient. Useful for daily logging that is reconciled later.',
        },

        theme: {
            dark:           'Dark',
            light:          'Light (Coming Soon)',
            lightSoon:      'Coming soon',
            title:          'Color Theme',
            desc:           'Only dark mode is currently available.',
        },

        // Layout
        layout: {
            title:          'Wide Screen Layout',
            desc:           'Display mode for desktop screens.',
            desktop:        'Desktop',
            mobile:         'Mobile',
        },

        // Telegram
        telegram: {
            title:          'Telegram Bot',
            desc:           'Record transactions via natural language chat',
            status:         'Active',
        },

        // Data
        data: {
            section:        'Data',
            title:          'Export & Backup',
            desc:           'Download your complete financial history in CSV format.',
            exportBtn:      'Export CSV',
        },

        // Status negativeBalance
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

        // Google OAuth
        google: {
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
