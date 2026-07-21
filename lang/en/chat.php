<?php

declare(strict_types=1);

/**
 * Translation keys for Chat Engine — English.
 *
 * Keys must mirror lang/id/chat.php exactly.
 * Only keys and structure here — no business logic.
 */
return [

    // ──────────────────────────────────────────────────────────────
    // GENERAL
    // ──────────────────────────────────────────────────────────────
    'general' => [
        'processing'      => '⏳ On it, processing with AI...',
        'unknown_error'   => '❌ A system error occurred. Please try again later.',
        'unauthorized'    => '❌ Your ID ({platform_id}) is not registered in Bendaharaku. Please register first!',
        'retry_later'     => 'Please try again in a few minutes.',
        'check_web'       => 'Check & complete on 👉 *Web Dashboard*.',
    ],

    // ──────────────────────────────────────────────────────────────
    // TRANSACTION — Single
    // ──────────────────────────────────────────────────────────────
    'transaction' => [
        'success'               => 'Transaction recorded successfully.',
        'draft_saved'           => '📝 Saved as draft. Complete it on the Web Dashboard.',
        'draft_header'          => '📝 *SAVED AS DRAFT (Review on Web)*',
        'draft_body'            => "AI could not recognise the category or wallet from: _{input}_\n\nPlease mention a registered wallet and category name.\nOr review and complete the draft on 👉 *Web Dashboard*.",
        'cleared'               => '✅ *TRANSACTION SUCCESSFUL*',
        'uncleared'             => '📝 *SAVED AS DRAFT (Review on Web)*',
        'label_ref'             => 'Ref ID',
        'label_amount'          => 'Amount',
        'label_category'        => 'Category',
        'label_source'          => 'Source',
        'label_destination'     => 'Destination',
        'label_subject'         => 'Party',
        'label_original_msg'    => 'Original Message',
        'label_ai_provider'     => 'Processed by',
        'label_ai_confidence'   => 'AI Confidence',
        'type_income'           => 'Income 🟢',
        'type_expense'          => 'Expense 🔴',
        'type_transfer'         => 'Transfer 🔵',
        'type_debt_receivable'  => 'Debt / Receivable 🤝',
        'type_default'          => 'Transaction ⚪',
    ],

    // ──────────────────────────────────────────────────────────────
    // TRANSACTION — Multi
    // ──────────────────────────────────────────────────────────────
    'multi' => [
        'all_success'     => '✅ *Successfully recorded :count transactions.*',
        'all_failed'      => '❌ *All :count transactions failed to process.*',
        'partial'         => '✅ *:success succeeded* · ❌ *:failed failed*',
        'ai_failed'       => '❌ AI failed to process multi-transactions: :reason',
    ],

    // ──────────────────────────────────────────────────────────────
    // VALIDATION
    // ──────────────────────────────────────────────────────────────
    'validation' => [
        'missing_amount'        => "🤔 *How much was it?*\nI couldn't find the amount in your message.",
        'missing_category'      => "🧐 *What category is this?*\nPlease mention the item or activity name.",
        'missing_debt_subject'  => "🤝 *Whose name is it?*\nFor Debt/Receivable transactions, you MUST use a hashtag.\n\n💡 *Example:* borrowed 50k dana #Budi",
        'invalid_amount'        => 'Invalid or zero amount.',
        'missing_category_ai'   => 'Category not detected by AI.',
        'same_wallet'           => 'Source and destination wallet cannot be the same.',
    ],

    // ──────────────────────────────────────────────────────────────
    // WALLET
    // ──────────────────────────────────────────────────────────────
    'wallet' => [
        'not_found'       => "Wallet ':name' not found.",
        'not_found_hint'  => 'Make sure the wallet name (e.g. *cash*, *dana*, *spay*) is registered on the Web.',
        'insufficient'    => "Insufficient balance in wallet ':name'.",
        'source_empty'    => 'Source wallet not detected.',
        'destination_empty' => 'Destination wallet not detected.',
        'missing_choose'  => 'The wallet is missing. Pick one of your frequently used wallets below.',
    ],

    // ──────────────────────────────────────────────────────────────
    // CATEGORY
    // ──────────────────────────────────────────────────────────────
    'category' => [
        'not_found'       => "Category ':name' not found.",
        'not_found_hint'  => 'Make sure the category name is registered on the Web.',
    ],

    // ──────────────────────────────────────────────────────────────
    // AI
    // ──────────────────────────────────────────────────────────────
    'ai' => [
        'not_configured'  => implode("\n", [
            "⚙️ *AI Not Configured*",
            "",
            "Python is offline and no fallback AI is active.",
            "",
            "👉 Go to *Web Dashboard → Settings → AI* and check *\"Enable as Fallback AI\"* for a provider with a valid API key.",
        ]),
        'rate_limit'      => implode("\n", [
            "⚠️ *:provider API Quota Exhausted*",
            "",
            "Your daily token/request limit has been reached.",
            "• Wait for the quota reset (usually midnight)",
            "• Or upgrade your plan at the :provider dashboard",
        ]),
        'token_limit'     => implode("\n", [
            "⚠️ *Token Limit Exceeded*",
            "",
            "Your message is too long for :provider AI (estimated: :tokens tokens).",
            "Possible solutions:",
            "• Try a shorter message",
            "• Or use a different AI provider from the Web Dashboard",
        ]),
        'timeout'         => "⏳ *:provider Server is Busy*\n\nPlease retry your message in 1-2 minutes.",
        'provider_error'  => "❌ *AI Provider Error*\n\n`:error`\n\nPlease try again later.",
        'parse_failed'    => '❌ AI failed to process: :reason',
        'parse_failed_default' => 'Unrecognised format.',
        'provider_python' => '🐍 Python NLP',
        'provider_gemini' => '✨ Gemini',
        'provider_openai' => '🤖 OpenAI',
        'provider_deepseek' => '🔍 DeepSeek',
        'provider_default' => '🤖 :provider',
        'confidence_label' => 'AI Confidence',
    ],

    // ──────────────────────────────────────────────────────────────
    // ERRORS
    // ──────────────────────────────────────────────────────────────
    'error' => [
        'data_not_found'  => implode("\n", [
            "🔍 *Data Not Found — All Transactions Cancelled*",
            "",
            ":message",
            "",
            "Make sure wallet names (e.g. *cash*, *dana*, *spay*) and categories are registered on the Web.",
            "All transactions in this message have been cancelled.",
        ]),
        'data_not_found_single' => implode("\n", [
            "🔍 *Data Not Found*",
            "",
            "The wallet or category does not exist in the system.",
            "Make sure the wallet name (e.g. *bca*, *dana*, *cash*) is registered on the Web.",
        ]),
        'runtime'         => "⚠️ *Failed to process:*\n:message",
        'system'          => 'A system error occurred. Please try again later.',
        'reason_prefix'   => 'Reason: ',
    ],

    // ──────────────────────────────────────────────────────────────
    // COMMANDS
    // ──────────────────────────────────────────────────────────────
    'command' => [
        'balance_title'   => '💳 *Current Balance Report:*',
        'balance_empty'   => "🏦 *No Wallets Found*\nYou haven't created any Asset/Liquid wallets yet.",
        'balance_line_raw'  => ':line',
        'balance_total'     => '💰 **Total: :total**',
        'wallet_title'      => '👛 **Wallets & Assets**',
        'asset_title'       => '📈 **Assets**',
        'asset_empty'       => '📈 No Asset wallet yet.',
        'category_title'    => '🏷️ **Categories**',
        'category_empty'    => '🏷️ No categories yet.',
        'transaction_today_title' => '📋 **Today\'s Transactions**',
        'transaction_today_empty' => '📋 No transactions today.',
        'income_title'      => '🟢 **Income This Month**',
        'expense_title'     => '🔴 **Expenses This Month**',
        'month_type_empty'  => 'No data for this month yet.',
        'month_type_total'  => ':count transactions, total :total.',
        'report_title'      => '📊 **This Month\'s Summary**',
        'report_title_period' => '📊 **:period Summary**',
        'report_empty'      => '📊 No transactions this month to summarize yet.',
        'report_empty_period' => '📊 No transactions for :period yet.',
        'report_saved'      => '💾 This report snapshot has been saved.',
        'report_period'     => 'Period: :period',
        'report_income'     => 'Income: :amount',
        'report_expense'    => 'Expense: :amount',
        'report_net'        => 'Net: :amount',
        'report_previous'   => "Previous month summary:\n:summary",
        'report_top_categories' => "Top expense categories:\n:categories",
        'report_comparison_title' => 'Comparison with Last Month',
        'report_comparison_income' => ':emoji Income: :amount (vs last month)',
        'report_comparison_expense' => ':emoji Expense: :amount (vs last month)',
        'report_gemini_unavailable' => 'Gemini is not ready, so I showed a local summary for now.',
        'not_yet_implemented' => '🚧 Command `:command` is not yet available in Web Chat. Check back later!',
        'web_link_msg'    => implode("\n", [
            "🌐 *Access Bendaharaku V4*",
            "",
            "Click the link below to open the Web Dashboard:",
            "",
            "👉 [Open Bendaharaku](:url)",
            "",
            "_Note: If it opens inside Telegram, tap the three dots in the top right and choose 'Open in Chrome/Browser'._",
        ]),
        'help_greeting'   => '👋 *Hello :name!*',
        'help_intro'      => "I'm your *Bendaharaku* assistant. I'll automatically record all your finances.",
        'help_guide'      => '📖 *HOW TO RECORD TRANSACTIONS:*',
        'help_example_intro' => 'Just type casually, for example:',
        'help_commands_title' => '📊 *BOT COMMANDS:*',
        'help_cmd_balance'  => '▫️ `/saldo` - Check your current balance.',
        'help_cmd_web'      => '▫️ `/web` - Open the web dashboard.',
        'help_cmd_help'     => '▫️ `/help` - Show this guide.',
        'help_example_expense'  => '💸 Expense: "Bought lunch 15k bca"',
        'help_example_income'   => '💰 Income: "Salary 5M mandiri"',
        'help_example_transfer' => '🔄 Transfer: "Transfer bca to dana 100k"',
        'help_example_debt'     => '🤝 Debt/Receivable: "Borrowed 100k bca #Budi"',
        'total_balance'     => 'Total Balance',
        'report_summary'    => 'Summary: :summary',
        'help_cmd_template' => '▫️ :icon `:command` - :description',
    ],

    'commands' => [
        'help' => [
            'description' => 'Show chatbot usage guide.',
            'hint'        => 'Use to view message format examples.',
        ],
        'start' => [
            'description' => 'Start conversation with chatbot.',
        ],
        'saldo' => [
            'description' => 'Check current wallet balance.',
            'hint'        => 'Example: /saldo',
        ],
        'wallet' => [
            'description' => 'List wallets and their respective balances.',
        ],
        'kategori' => [
            'description' => 'List financial transaction categories.',
        ],
        'aset' => [
            'description' => 'List financial assets.',
        ],
        'transaksi' => [
            'description' => 'List today\'s financial transactions.',
            'hint'        => 'Example: /transaksi',
        ],
        'pemasukan' => [
            'description' => 'List income this month.',
        ],
        'pengeluaran' => [
            'description' => 'List expenses this month.',
        ],
        'transfer' => [
            'description' => 'Record transfer between wallets.',
        ],
        'ringkasan' => [
            'description' => 'Monthly financial summary report.',
            'hint'        => 'Example: /ringkasan',
        ],
        'laporan' => [
            'description' => 'Detailed monthly financial report.',
        ],
        'statistik' => [
            'description' => 'Personal financial statistics.',
        ],
        'settings' => [
            'description' => 'Open chatbot settings.',
        ],
        'web' => [
            'description' => 'Open Bendaharaku Web dashboard.',
        ],
    ],

    'command_icon_saldo' => '💳',

    // ──────────────────────────────────────────────────────────────
    // SUGGESTIONS
    // ──────────────────────────────────────────────────────────────
    'suggestion' => [
        'add_wallet'      => 'Add wallet :name in the Web Dashboard.',
        'add_category'    => 'Add category :name in the Web Dashboard.',
        'check_spelling'  => 'Check the spelling of the wallet or category name.',
        'use_hashtag'     => 'Use #PersonName for debt/receivable transactions.',
    ],

];
