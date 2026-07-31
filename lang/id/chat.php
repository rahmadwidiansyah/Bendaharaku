<?php

declare(strict_types=1);

/**
 * Translation keys untuk Chat Engine — Bahasa Indonesia.
 *
 * Konvensi penamaan:
 *   chat.<domain>.<key>
 *
 * Formatter TIDAK boleh menulis kalimat. Formatter hanya memanggil:
 *   __('chat.transaction.success', ['count' => 3])
 *
 * Business logic TIDAK menyentuh file ini.
 * Hanya Formatter dan Adapter yang boleh memanggil trans().
 */
return [

    // ──────────────────────────────────────────────────────────────
    // GENERAL
    // ──────────────────────────────────────────────────────────────
    'general' => [
        'processing' => '⏳ Siap, lagi dicerna AI...',
        'unknown_error' => '❌ Terjadi kesalahan sistem. Coba lagi nanti.',
        'unauthorized' => '❌ ID kamu ({platform_id}) belum terdaftar di Bendaharaku. Daftarkan dulu ya!',
        'retry_later' => 'Coba kirim ulang dalam beberapa menit.',
        'check_web' => 'Cek & lengkapi di 👉 *Dashboard Web*.',
    ],

    // ──────────────────────────────────────────────────────────────
    // TRANSACTION — Single
    // ──────────────────────────────────────────────────────────────
    'transaction' => [
        'success' => 'Transaksi berhasil dicatat.',
        'draft_saved' => '📝 Masuk draft. Lengkapi di Web Dashboard.',
        'draft_header' => '📝 *MASUK DRAFT (Butuh Cek Web)*',
        'draft_body' => "AI tidak dapat mengenali kategori atau dompet dari: _{input}_\n\nCoba sebutkan nama dompet dan kategori yang sudah terdaftar.\nAtau cek & lengkapi transaksi draft-nya di 👉 *Dashboard Web*.",
        'cleared' => '✅ *TRANSAKSI BERHASIL*',
        'uncleared' => '📝 *MASUK DRAFT (Butuh Cek Web)*',
        'label_ref' => 'Ref ID',
        'label_amount' => 'Nominal',
        'label_category' => 'Kategori',
        'label_source' => 'Sumber',
        'label_destination' => 'Tujuan',
        'label_subject' => 'Pihak',
        'label_original_msg' => 'Pesan Asli',
        'label_ai_provider' => 'Diproses oleh',
        'label_ai_confidence' => 'Keyakinan AI',
        'type_income' => 'Pemasukan 🟢',
        'type_expense' => 'Pengeluaran 🔴',
        'type_transfer' => 'Transfer 🔵',
        'type_debt_receivable' => 'Hutang / Piutang 🤝',
        'type_default' => 'Transaksi ⚪',
    ],

    // ──────────────────────────────────────────────────────────────
    // TRANSACTION — Multi
    // ──────────────────────────────────────────────────────────────
    'multi' => [
        'all_success' => '✅ *Berhasil memproses :count transaksi.*',
        'all_failed' => '❌ *Semua :count transaksi gagal diproses.*',
        'partial' => '✅ *:success berhasil* · ❌ *:failed gagal*',
        'ai_failed' => '❌ AI Gagal memproses multi-transaksi: :reason',
    ],

    // ──────────────────────────────────────────────────────────────
    // VALIDATION
    // ──────────────────────────────────────────────────────────────
    'validation' => [
        'missing_amount' => "🤔 *Nominalnya berapa Bos?*\nAku bingung nih, kamu belum nyebutin jumlah uangnya.",
        'missing_category' => "🧐 *Masuk kategori apa nih?*\nSebutkan nama barang atau kegiatannya ya.",
        'missing_debt_subject' => "🤝 *Nama orangnya siapa Bos?*\nKarena ini transaksi Hutang/Piutang, kamu WAJIB pakai hashtag.\n\n💡 *Contoh:* pinjam uang 50k dana #Budi",
        'invalid_amount' => 'Nominal tidak valid atau nol.',
        'missing_category_ai' => 'Kategori tidak terdeteksi oleh AI.',
        'same_wallet' => 'Dompet asal dan tujuan tidak boleh sama.',
    ],

    // ──────────────────────────────────────────────────────────────
    // WALLET
    // ──────────────────────────────────────────────────────────────
    'wallet' => [
        'not_found' => "Dompet ':name' tidak ditemukan.",
        'not_found_hint' => 'Pastikan nama dompet (contoh: *cash*, *dana*, *spay*) sudah terdaftar di Web.',
        'insufficient' => "Saldo dompet ':name' tidak mencukupi.",
        'source_empty' => 'Dompet asal tidak terdeteksi.',
        'destination_empty' => 'Dompet tujuan tidak terdeteksi.',
        'missing_choose' => 'Eh, wallet-nya belum ada nih. Pilih salah satu dompet yang sering kamu pakai di bawah ya.',
        'missing_source' => 'Wallet asalnya belum ada nih. Pilih salah satu dompet yang sering kamu pakai di bawah ya.',
        'missing_destination' => 'Wallet tujuannya belum ada nih. Pilih salah satu dompet yang sering kamu pakai di bawah ya.',
    ],

    // ──────────────────────────────────────────────────────────────
    // CATEGORY
    // ──────────────────────────────────────────────────────────────
    'category' => [
        'not_found' => "Kategori ':name' tidak ditemukan.",
        'not_found_hint' => 'Pastikan nama kategori sudah terdaftar di Web.',
    ],

    // ──────────────────────────────────────────────────────────────
    // AI
    // ──────────────────────────────────────────────────────────────
    'ai' => [
        'not_configured' => implode("\n", [
            '⚙️ *AI Belum Dikonfigurasi*',
            '',
            'Python sedang offline dan belum ada AI cadangan yang aktif.',
            '',
            '👉 Buka *Dashboard Web → Settings → AI* lalu centang *"Aktifkan sebagai AI Cadangan"* pada provider yang sudah kamu isi API key-nya.',
        ]),
        'rate_limit' => implode("\n", [
            '⚠️ *Kuota API :provider Habis*',
            '',
            'Limit token/request harian kamu sudah tercapai.',
            '• Tunggu reset kuota (biasanya tengah malam)',
            '• Atau topup/upgrade plan API kamu di dashboard :provider',
        ]),
        'token_limit' => implode("\n", [
            '⚠️ *Token Limit Terlampaui*',
            '',
            'Pesan kamu terlalu panjang untuk AI :provider (estimasi: :tokens token).',
            'Beberapa kemungkinan:',
            '• Coba pesan yang lebih singkat',
            '• Atau gunakan AI provider lain di Dashboard Web',
        ]),
        'timeout' => "⏳ *Server :provider Sedang Sibuk*\n\nCoba kirim ulang pesanmu dalam 1-2 menit ya Bos.",
        'provider_error' => "❌ *Terjadi Error pada AI*\n\n`:error`\n\nCoba lagi nanti.",
        'parse_failed' => '❌ AI Gagal memproses: :reason',
        'parse_failed_default' => 'Format tidak dikenali.',
        'provider_python' => '🐍 Python NLP',
        'provider_gemini' => '✨ Gemini',
        'provider_openai' => '🤖 OpenAI',
        'provider_deepseek' => '🔍 DeepSeek',
        'provider_default' => '🤖 :provider',
        'confidence_label' => 'Keyakinan AI',
    ],

    // ──────────────────────────────────────────────────────────────
    // ERRORS — kode terstruktur
    // ──────────────────────────────────────────────────────────────
    'error' => [
        'data_not_found' => implode("\n", [
            '🔍 *Data Tidak Ditemukan — Semua Transaksi Dibatalkan*',
            '',
            ':message',
            '',
            'Pastikan nama dompet (contoh: *cash*, *dana*, *spay*) dan kategori sudah terdaftar di Web.',
            'Semua transaksi dalam pesan ini dibatalkan.',
        ]),
        'data_not_found_single' => implode("\n", [
            '🔍 *Data Tidak Ditemukan*',
            '',
            'Dompet atau kategori yang dimaksud tidak ada di sistem.',
            'Pastikan nama dompet (contoh: *bca*, *dana*, *cash*) sudah kamu daftarkan di Web.',
        ]),
        'runtime' => "⚠️ *Gagal diproses:*\n:message",
        'system' => 'Waduh, ada error sistem internal Bos. Coba lagi nanti ya.',
        'reason_prefix' => 'Alasan: ',
    ],

    // ──────────────────────────────────────────────────────────────
    // COMMANDS
    // ──────────────────────────────────────────────────────────────
    'command' => [
        'balance_title' => 'Saldo Saat Ini',
        'balance_total_label' => 'Total Saldo',
        'balance_wallet_count' => ':count Dompet',
        'balance_list' => '',
        'balance_empty' => "🏦 *Belum Ada Dompet*\nKamu belum membuat dompet Asset/Liquid apa pun di web.",
        'balance_line_raw' => ':line',
        'balance_total' => '💰 **Total: :total**',
        'wallet_title' => '**Daftar Dompet & Aset**',
        'asset_title' => '**Daftar Aset**',
        'asset_empty' => '📈 Belum ada wallet bertipe Asset.',
        'category_title' => 'Kategori',
        'category_empty' => '🏷️ Belum ada kategori.',
        'category_section_income' => 'Pemasukan',
        'category_section_expense' => 'Pengeluaran',
        'category_section_transfer' => 'Transfer',
        'category_section_debt' => 'Hutang',
        'category_section_receivable' => 'Piutang',
        'wallet_type_asset' => 'Aset',
        'wallet_type_liquid' => 'Likuid',
        'wallet_type_system' => 'Sistem',
        'transaction_today_title' => '**Transaksi Hari Ini**',
        'transaction_today_empty' => '📋 Belum ada transaksi hari ini.',
        'income_title' => '**Pemasukan Bulan Ini**',
        'expense_title' => '**Pengeluaran Bulan Ini**',
        'month_type_empty' => 'Belum ada data untuk bulan ini.',
        'month_type_total' => ':count transaksi, total :total.',
        'report_title' => '📊 **Ringkasan Bulan Ini**',
        'report_title_period' => '📊 **Ringkasan :period**',
        'report_empty' => '📊 Belum ada transaksi bulan ini untuk diringkas.',
        'report_empty_period' => '📊 Belum ada transaksi untuk :period.',
        'report_saved' => '💾 Snapshot laporan ini sudah disimpan.',
        'report_period' => 'Periode: :period',
        'report_income' => 'Pemasukan: :amount',
        'report_expense' => 'Pengeluaran: :amount',
        'report_net' => 'Selisih: :amount',
        'report_previous' => "Pembanding bulan sebelumnya:\n:summary",
        'report_top_categories' => "Top kategori pengeluaran:\n:categories",
        'report_comparison_title' => 'Perbandingan dengan Bulan Lalu',
        'report_comparison_income' => ':emoji Pendapatan: :amount (vs bulan lalu)',
        'report_comparison_expense' => ':emoji Pengeluaran: :amount (vs bulan lalu)',
        'report_gemini_unavailable' => 'Gemini belum siap dipakai, jadi aku tampilkan ringkasan lokal dulu.',
        'not_yet_implemented' => '🚧 Perintah `:command` belum tersedia di Web Chat. Coba lagi nanti!',
        'web_link_msg' => implode("\n", [
            '🌐 *Akses Bendaharaku V4*',
            '',
            'Silakan klik tombol/link di bawah ini untuk membuka Web Dashboard:',
            '',
            '👉 [Buka Bendaharaku](:url)',
            '',
            "_Catatan: Jika terbuka di dalam Telegram, klik titik tiga di pojok kanan atas lalu pilih 'Buka di Chrome/Browser'._",
        ]),
        'help_greeting' => '👋 *Halo Bos :name!*',
        'help_intro' => 'Saya adalah asisten *Bendaharaku*. Saya akan mencatat semua keuanganmu secara otomatis.',
        'help_guide' => '📖 *PANDUAN CATAT TRANSAKSI:*',
        'help_example_intro' => 'Cukup ketik kalimat santai, contoh:',
        'help_commands_title' => '📊 *PERINTAH BOT:*',
        'help_cmd_balance' => '▫️ `/saldo` - Cek sisa uangmu saat ini.',
        'help_cmd_web' => '▫️ `/web` - Buka dashboard web.',
        'help_cmd_help' => '▫️ `/help` - Tampilkan panduan ini.',
        'help_example_expense' => '💸 Pengeluaran: "Beli nasi goreng 15k bca"',
        'help_example_income' => '💰 Pemasukan: "Gajian 5jt mandiri"',
        'help_example_transfer' => '🔄 Transfer: "Transfer bca ke dana 100k"',
        'help_example_debt' => '🤝 Hutang/Piutang: "Pinjam duit 100k bca #Budi"',
        'total_balance' => 'Total Saldo',
        'report_summary' => 'Ringkasan: :summary',
        'help_cmd_template' => '▫️ :icon `:command` - :description',
    ],

    'commands' => [
        'help' => [
            'description' => 'Tampilkan panduan penggunaan chatbot.',
            'hint' => 'Gunakan untuk melihat contoh format pesan.',
        ],
        'start' => [
            'description' => 'Mulai percakapan dengan chatbot.',
        ],
        'saldo' => [
            'description' => 'Cek saldo dompet saat ini.',
            'hint' => 'Contoh: /saldo',
        ],
        'wallet' => [
            'description' => 'Daftar dompet dan saldo masing-masing.',
        ],
        'kategori' => [
            'description' => 'Daftar kategori transaksi keuangan.',
        ],
        'aset' => [
            'description' => 'Daftar aset keuangan.',
        ],
        'transaksi' => [
            'description' => 'Daftar transaksi keuangan hari ini.',
            'hint' => 'Contoh: /transaksi',
        ],
        'pemasukan' => [
            'description' => 'Daftar pemasukan bulan ini.',
        ],
        'pengeluaran' => [
            'description' => 'Daftar pengeluaran bulan ini.',
        ],
        'transfer' => [
            'description' => 'Catat transfer antar dompet.',
        ],
        'ringkasan' => [
            'description' => 'Laporan ringkasan keuangan bulanan.',
            'hint' => 'Contoh: /ringkasan',
        ],
        'laporan' => [
            'description' => 'Laporan keuangan detail bulanan.',
        ],
        'statistik' => [
            'description' => 'Statistik keuangan pribadi.',
        ],
        'settings' => [
            'description' => 'Buka pengaturan chatbot.',
        ],
        'web' => [
            'description' => 'Buka dashboard Web Bendaharaku.',
        ],
    ],

    'command_icon_saldo' => '💳',

    // ──────────────────────────────────────────────────────────────
    // SUGGESTIONS
    // ──────────────────────────────────────────────────────────────
    'suggestion' => [
        'add_wallet' => 'Tambah dompet :name di Web Dashboard.',
        'add_category' => 'Tambah kategori :name di Web Dashboard.',
        'check_spelling' => 'Periksa ejaan nama dompet atau kategori.',
        'use_hashtag' => 'Gunakan #NamaOrang untuk transaksi hutang/piutang.',
    ],

];
