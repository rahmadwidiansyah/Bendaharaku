/**
 * i18n/locales/id.js
 * Bahasa Indonesia — default locale Bendaharaku
 *
 * GLOSSARY (istilah baku yang digunakan seluruh aplikasi):
 *   Income      → Pemasukan
 *   Expense     → Pengeluaran
 *   Transfer    → Transfer
 *   Debt        → Hutang
 *   Receivable  → Piutang
 *   Wallet      → Dompet
 *   Asset       → Aset
 *   Category    → Kategori
 *   Label       → Label
 *   Savings     → Tabungan
 *   Investment  → Investasi
 *   Liquid      → Liquid
 *   Statistics  → Statistik
 *   Analytics   → Analitik
 *   Report      → Laporan
 *   Dashboard   → Dashboard
 *   Settings    → Pengaturan
 *   Export      → Ekspor
 *   Import      → Impor
 *   Draft       → Draft
 *   Confirmed   → Terkonfirmasi
 */

export default {

    // ────────────────────────────────────────────────────────────────
    // COMMON — string yang dipakai di banyak tempat
    // ────────────────────────────────────────────────────────────────
    common: {
        save:           'Simpan',
        saving:         'Menyimpan...',
        cancel:         'Batal',
        delete:         'Hapus',
        deleting:       'Menghapus...',
        edit:           'Edit',
        add:            'Tambah',
        create:         'Buat',
        confirm:        'Konfirmasi',
        close:          'Tutup',
        back:           'Kembali',
        loading:        'Memuat...',
        search:         'Cari...',
        filter:         'Filter',
        all:            'Semua',
        none:           'Tidak Ada',
        yes:            'Ya',
        no:             'Tidak',
        ok:             'OK',
        success:        'Berhasil',
        error:          'Gagal',
        warning:        'Peringatan',
        info:           'Info',
        required:       'Wajib diisi',
        optional:       'Opsional',
        total:          'Total',
        balance:        'Saldo',
        amount:         'Nominal',
        date:           'Tanggal',
        time:           'Waktu',
        note:           'Catatan',
        name:           'Nama',
        type:           'Tipe',
        status:         'Status',
        active:         'Aktif',
        inactive:       'Tidak Aktif',
        empty:          'Kosong',
        days:           'Hari',
        currency:       'Rp',
        dataEmpty:      'Data Kosong',
        noData:         'Tidak ada data',
        seeAll:         'Lihat Semua',
        goTo:           'Pergi ke',
        skipToContent:  'Langsung ke konten',
        processing:     'Memproses...',
        period:         'Periode',
    },

    // ────────────────────────────────────────────────────────────────
    // TRANSACTION TYPES — glossary baku
    // ────────────────────────────────────────────────────────────────
    types: {
        income:         'Pemasukan',
        expense:        'Pengeluaran',
        transfer:       'Transfer',
        debt:           'Hutang',
        receivable:     'Piutang',
        other:          'Lainnya',
        all:            'Semua Tipe',
        incomeDesc:     'Dapat uang',
        expenseDesc:    'Bayar sesuatu',
        transferDesc:   'Pindah antar dompet',
        debtDesc:       'Pinjam / bayar',
        receivableDesc: 'Kasih / terima',
    },

    // ────────────────────────────────────────────────────────────────
    // NAVIGATION
    // ────────────────────────────────────────────────────────────────
    nav: {
        home:       'Home',
        asset:      'Aset',
        record:     'Catat',
        analytics:  'Grafik',
        label:      'Label',
        loan:       'Tanggungan',
        settings:   'Pengaturan',
        telegram:   'Telegram',
        newRecord:  'Catat Baru',
        mainNav:    'Navigasi utama',
    },

    // ────────────────────────────────────────────────────────────────
    // HEADER
    // ────────────────────────────────────────────────────────────────
    header: {
        toggleBalance:  'Toggle visibilitas saldo',
        openSettings:   'Buka pengaturan',
        actions:        'Tombol aksi header',
    },

    // ────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ────────────────────────────────────────────────────────────────
    dashboard: {
        title:              'Dashboard',
        totalWealth:        'Total Kekayaan',
        mainWallets:        'Dompet Utama',
        unpinFromDashboard: 'Unpin dari Dashboard',
        transactionHistory: 'Histori Transaksi',
        filterType:         'Filter Tipe',
        searchPlaceholder:  'Cari catatan atau ID...',
        noTransactions:     'Tidak ada transaksi',
        calendarTab:        'Kalender',
        detailTab:          'Detail',
        income:             'Pemasukan',
        expense:            'Pengeluaran',
        cashflow:           'Arus Kas',

        // Hari-hari kalender (singkatan)
        calendar: {
            sun: 'Min',
            mon: 'Sen',
            tue: 'Sel',
            wed: 'Rab',
            thu: 'Kam',
            fri: 'Jum',
            sat: 'Sab',
        },

        // Filter kalender
        calendarFilter: {
            income:  'Pemasukan',
            total:   'Total',
            expense: 'Pengeluaran',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // PORTFOLIO / ASSET OVERVIEW
    // ────────────────────────────────────────────────────────────────
    portfolio: {
        title:      'Total Kekayaan',
        liquid:     'Liquid',
        investment: 'Investasi',
        subtitle:   'Pergerakan aset bulan ini',
    },

    // ────────────────────────────────────────────────────────────────
    // TRANSACTION
    // ────────────────────────────────────────────────────────────────
    transaction: {
        title:          'Catat Transaksi',
        titleEdit:      'Edit Transaksi',
        amount:         'Nominal',
        amountHint:     'Masukkan nominal',
        category:       'Kategori',
        chooseCategory: 'Pilih Kategori',
        sourceWallet:   'Dompet Sumber',
        destWallet:     'Dompet Tujuan',
        chooseWallet:   'Pilih Dompet',
        date:           'Tanggal',
        note:           'Catatan',
        notePlaceholder:'Tambah catatan... (opsional)',
        draft:          'Draft',
        confirmed:      'Terkonfirmasi',
        cancelled:      'Batal',
        confirmDraft:   'Konfirmasi Transaksi',
        confirmDraftQ:  'Konfirmasi transaksi draft ini?',
        confirmDraftMsg:'Transaksi akan dicatat secara resmi.',
        deleteTitle:    'Hapus Transaksi',
        deleteMsg:      'Transaksi ini akan dihapus permanen.',
        saveDraft:      'Simpan sebagai Draft',
        saveConfirm:    'Simpan & Konfirmasi',
        dueDate:        'Jatuh Tempo',
        dueDateHint:    'Kapan hutang/piutang ini harus diselesaikan?',
        loanSubject:    'Nama Pihak',
        loanSubjectHint:'Nama pemberi/penerima hutang',
        selectType:     'Pilih Tipe',
        typeRequired:   'Pilih tipe transaksi',

        // Validasi
        validation: {
            amountRequired:   'Nominal wajib diisi',
            amountInvalid:    'Nominal tidak valid',
            amountPositive:   'Nominal harus lebih dari 0',
            categoryRequired: 'Pilih kategori',
            sourceRequired:   'Pilih dompet sumber',
            destRequired:     'Pilih dompet tujuan',
            dateRequired:     'Pilih tanggal',
            dateFuture:       'Tanggal tidak boleh di masa depan',
            subjectRequired:  'Isi nama pihak',
        },

        // Detail modal
        detail: {
            title:       'Detail Transaksi',
            from:        'Dari',
            to:          'Ke',
            wallet:      'Dompet',
            party:       'Pelaku',
            category:    'Kategori',
            transactionId: 'ID Transaksi',
            date:        'Tanggal',
            note:        'Catatan',
            noNote:      'Tidak ada catatan.',
            dueDate:     'Jatuh Tempo',
            loanSubject: 'Nama Pihak',
            editBtn:     'Edit',
            deleteBtn:   'Hapus',
        },

        confirmDraftDetail: 'Apakah data transaksi ini sudah benar?',
        confirmDraftWarn:   'Status akan berubah dari Draft menjadi Terkonfirmasi dan saldo dompet akan dimutasi.',
        deleteWarn:         'Data yang dihapus tidak bisa dikembalikan.',
        processing:         'Memproses...',
        yesConfirm:         'Ya, Konfirmasi',

        cancelTitle:         'Transaksi Dibatalkan',

        // Sub-tabs Hutang / Piutang
        debt: {
            receive: 'Dapat Hutang',
            pay:     'Bayar Hutang',
        },
        receivable: {
            give:    'Beri Piutang',
            collect: 'Terima Piutang',
        },
    },

    chatTransaction: {
        aiParsed:      'AI Parsed',
        copy:          'Salin',
        copied:        'Tersalin',
        copyMessage:   'Salin pesan',
        regenerate:    'Generate ulang',
        regenerateAnswer: 'Generate ulang jawaban',
        retry:         'Coba lagi',
        retrySend:     'Coba kirim ulang',
        walletLoadFailed: 'Gagal memuat dompet.',
        confirmDelete: 'Hapus transaksi ini?',
        recordedFrom:  'Dicatat dari',
        processedBy:   'Diproses oleh',
        processingDuration: 'Durasi proses',
        aiConfidence:  'Confidence AI',
        transactionTime: 'Waktu transaksi',
        rawPrompt:     'Prompt Asli',
        seconds:       'detik',
        confidence: {
            high:   'Tinggi',
            medium: 'Sedang',
            low:    'Rendah',
        },
        intent: {
            label:   'Intent',
            single:  'Transaksi Tunggal',
            multi:   'Multi Transaksi',
            command: 'Perintah',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // WALLET / ASSET
    // ────────────────────────────────────────────────────────────────
    wallet: {
        title:          'Aset & Dompet',
        titleCreate:    'Tambah Dompet',
        titleEdit:      'Edit Dompet',
        totalWealth:    'Total Kekayaan',
        liquid:         'Liquid',
        investment:     'Investasi',
        addNew:         'Tambah Dompet / Aset',
        addNewBtn:      'Tambah Baru',
        name:           'Nama Dompet',
        namePlaceholder:'Contoh: BCA, GoPay, Emas',
        icon:           'Ikon',
        iconHint:       'Emoji atau URL gambar',
        keyword:        'Keyword',
        keywordHint:    'Kata kunci untuk AI (contoh: bca, gopay)',
        groupType:      'Tipe Grup',
        balance:        'Saldo Awal',
        balancePlaceholder: '0',
        deleteTitle:    'Hapus Dompet',
        deleteMsg:      'Dompet ini akan dihapus permanen beserta semua transaksinya.',
        deleteConfirm:  'Yakin ingin menghapus dompet ini?',
        totalDebt:      'Total Hutang',
        totalReceivable:'Total Piutang',

        // Group types
        groupTypes: {
            liquid:     'Liquid (Tunai/Digital)',
            asset:      'Aset / Investasi',
        },

        // Show page
        recentMutation: 'Mutasi Terakhir',
        emptyMutation:  'Belum ada mutasi',

        // Empty states
        empty:          'Belum ada dompet.',
        emptyLiquid:    'Belum ada dompet liquid.',
        emptyAsset:     'Belum ada aset.',
    },

    // ────────────────────────────────────────────────────────────────
    // CATEGORY / LABEL
    // ────────────────────────────────────────────────────────────────
    category: {
        title:          'Vault Kategori',
        titleCreate:    'Buat Kategori Baru',
        titleEdit:      'Edit Kategori',
        name:           'Nama Kategori',
        namePlaceholder:'Contoh: Makan, Transport',
        icon:           'Ikon',
        iconHint:       'Emoji atau URL gambar',
        keyword:        'Keyword',
        keywordHint:    'Kata kunci untuk AI',
        type:           'Tipe Transaksi',
        addNew:         'Buat Kategori Baru',
        deleteTitle:    'Hapus Kategori',
        deleteMsg:      'Kategori ini akan dihapus permanen.',
        deleteConfirm:  'Yakin ingin menghapus kategori ini?',
        totalLabel:     'Total',
        collection:     'Collection',
        transaction:    'transaksi',

        // Type labels (untuk grouping)
        typeHeaders: {
            Income:     'Pemasukan',
            Expense:    'Pengeluaran',
            Transfer:   'Transfer',
            Debt:       'Kategori Hutang',
            Receivable: 'Kategori Piutang',
        },

        // Show page
        show: {
            back:           'Kembali',
            transactions:   'Transaksi',
            noTransactions: 'Belum ada transaksi.',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // LOAN (Hutang & Piutang)
    // ────────────────────────────────────────────────────────────────
    loan: {
        title:          'Tanggungan',
        titleDebt:      'Hutang',
        titleReceivable:'Piutang',
        totalDebt:      'Total Hutang Aktif',
        totalReceivable:'Total Piutang Aktif',
        fromWhom:       'Dari Siapa',
        toWhom:         'Kepada Siapa',
        since:          'Aktif sejak',
        remaining:      'Sisa',
        days:           'Hari',
        clean:          'Bersih!',
        cleanMsg:       'Tidak ada hutang aktif saat ini.',
        cleanMsgRcv:    'Tidak ada piutang aktif saat ini.',

        // Plural: "{n} pemberi hutang aktif"
        activeDebtors:  '{n} pemberi hutang aktif',
        activeCreditors:'{n} orang berutang aktif',
    },

    // ────────────────────────────────────────────────────────────────
    // ANALYTICS / STATISTICS
    // ────────────────────────────────────────────────────────────────
    analytics: {
        title:          'Analitik',
        subtitle:       'Laporan',
        showingData:    'Menampilkan data',
        cumulativeBalance: 'Saldo Kumulatif',
        cumulativeDesc:    'Pergerakan total kekayaan',
        cashflow:       'Arus Kas',
        categoryBreakdown: 'Rincian Kategori',
        noData:         'Tidak Ada Data',
        totalIncome:    'Total Pemasukan',
        totalExpense:   'Total Pengeluaran',
        totalDebt:      'Total Hutang',
        totalReceivable:'Total Piutang',

        // Tampilan bar chart
        view: {
            daily:   'Hari',
            weekly:  'Pekan',
            monthly: 'Bulan',
        },

        // Tab kategori
        categoryTab: {
            expense:    'Keluar',
            income:     'Masuk',
            debt:       'Hutang',
            receivable: 'Piutang',
        },

        // Chart.js dataset labels
        chartLabels: {
            income:     'Masuk',
            expense:    'Keluar',
            debt:       'Hutang',
            receivable: 'Piutang',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // SETTINGS
    // ────────────────────────────────────────────────────────────────
    settings: {
        title:          'Pengaturan',
        subtitle:       'Preferences',

        // Sections
        account:        'Akun',
        transaction:    'Transaksi',
        appearance:     'Tampilan',
        language:       'Bahasa',
        ai:             'AI & Otomasi',
        danger:         'Zona Berbahaya',

        // Profile
        profile: {
            title: 'Profil & Keamanan',
            desc:  'Nama, avatar, dan password akun',
        },

        // Transaction logic
        negativeBalance: {
            title: 'Izinkan Saldo Minus',
            desc:  'Pengeluaran bisa dicatat meski saldo tidak mencukupi. Cocok untuk pencatatan harian yang direkap belakangan.',
        },

        // Appearance
        theme: {
            dark:           'Gelap',
            light:          'Terang (Segera)',
            lightSoon:      'Segera hadir',
            title:          'Tema Warna',
            desc:           'Saat ini hanya dark mode yang tersedia.',
        },

        // Layout
        layout: {
            title:          'Tata Letak Layar Lebar',
            desc:           'Mode tampilan untuk layar desktop.',
            desktop:        'Desktop',
            mobile:         'Mobile',
        },

        // Telegram
        telegram: {
            title:          'Telegram Bot',
            desc:           'Catat transaksi via chat natural language',
            status:         'Aktif',
        },

        // Data
        data: {
            section:        'Data',
            title:          'Ekspor & Pencadangan',
            desc:           'Unduh seluruh rekam jejak finansial ke format CSV.',
            exportBtn:      'Ekspor CSV',
        },

        // Status negativeBalance
        negativeBalanceOn:  '✓ Aktif — saldo negatif diperbolehkan',
        negativeBalanceOff: '✗ Nonaktif — transaksi ditolak bila saldo tidak mencukupi',

        // Language
        lang: {
            title:          '🌐 Bahasa / Language',
            auto:           'Ikuti Bahasa Perangkat',
            autoDesc:       'Menyesuaikan bahasa browser/perangkat Anda',
            id:             'Bahasa Indonesia',
            en:             'English',
            current:        'Aktif',
        },

        // AI
        aiSettings: {
            title: 'Pengaturan AI',
            desc:  'Kelola model, kredensial, dan preferensi AI',
        },
        aiAnalytics: {
            title: 'Analitik AI',
            desc:  'Performa dan statistik penggunaan AI',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // PROFILE
    // ────────────────────────────────────────────────────────────────
    profile: {
        title:          'Profil',
        name:           'Nama',
        namePlaceholder:'Nama lengkap kamu',
        email:          'Email',
        avatar:         'Foto Profil',
        changeAvatar:   'Ganti Foto',
        choosePhoto:    'Pilih Foto',
        removeAvatar:   'Hapus Foto',
        password:       'Password',
        currentPassword:'Password Saat Ini',
        newPassword:    'Password Baru',
        confirmPassword:'Konfirmasi Password Baru',
        updateProfile:  'Perbarui Profil',
        updatePassword: 'Perbarui Password',
        deleteAccount:  'Hapus Akun',
        deleteAccountDesc: 'Hapus akun secara permanen beserta semua data.',
        deleteAccountConfirm: 'YAKIN HAPUS PERMANEN? Semua data keuangan kamu akan hilang.',
        passwordUpdated: 'Password berhasil diperbarui.',

        // Google OAuth
        google: {
            connect:        'Hubungkan Akun Google',
            connected:      'Terkoneksi dengan Google',
        },

        // Logout
        logout:         'Keluar dari Aplikasi',

        // Danger zone
        dangerZone: {
            show:           'Tampilkan Zona Berbahaya',
            hide:           'Sembunyikan Zona Berbahaya',
            title:          'Hapus Akun Permanen',
            desc:           'Setelah dihapus, semua data keuangan, histori, dan pengaturan kamu akan musnah dan tidak dapat dipulihkan.',
            confirmBtn:     'Ya, Hapus Akun Saya',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // UPCOMING DEBTS (Dashboard widget)
    // ────────────────────────────────────────────────────────────────
    upcomingDebts: {
        title:          'Jatuh Tempo',
        subtitle:       'Tanggungan yang segera jatuh tempo',
        empty:          'Tidak ada tanggungan yang jatuh tempo.',
        debt:           'Hutang',
        receivable:     'Piutang',
        dueIn:          'Jatuh tempo dalam',
        overdue:        'Terlambat',
        days:           'hari',
    },

    // ────────────────────────────────────────────────────────────────
    // INSIGHT BANNER (Dashboard)
    // ────────────────────────────────────────────────────────────────
    insight: {
        good:       'Keuangan kamu bulan ini sehat! 💪',
        warning:    'Pengeluaran mendekati pemasukan. Hati-hati! ⚠️',
        bad:        'Pengeluaran melebihi pemasukan bulan ini. 😟',
        neutral:    'Belum ada transaksi bulan ini.',
    },

    // ────────────────────────────────────────────────────────────────
    // AI
    // ────────────────────────────────────────────────────────────────
    ai: {
        title:          'Pengaturan AI',
        subtitle:       'AI & Otomasi',
        provider:       'Provider AI',
        model:          'Model',
        apiKey:         'API Key',
        apiKeyPlaceholder: 'Masukkan API Key...',
        testConnection: 'Tes Koneksi',
        testing:        'Menguji...',
        save:           'Simpan Pengaturan',
        connectionOk:   'Koneksi berhasil!',
        connectionFail: 'Koneksi gagal.',
        enabled:        'AI Diaktifkan',
        disabled:       'AI Dinonaktifkan',

        // Analytics
        analyticsTitle: 'Analitik AI',
        analyticsSubtitle: 'Laporan',
        overview:       'Ringkasan',
        performance:    'Performa',
        learning:       'Pembelajaran',
        requests:       'Permintaan',
        success:        'Berhasil',
        drafts:         'Draft',
        tokens:         'Token',
        finalConfidence:'Kepercayaan Akhir',
        correctionRate: 'Tingkat Koreksi',
        estCost:        'Estimasi Biaya',

        // Chart titles
        charts: {
            trafficByProvider:  'Traffic per Provider',
            confidenceTrend:    'Tren Kepercayaan',
            confidenceTrendDesc:'Raw AI vs Sistem Pembobotan Bendaharaku',
            learnedKeywords:    'Keyword Terpelajari',
            correctedCategories:'Kategori Paling Dikoreksi',
        },

        // Ai.vue
        integration:        'Integrasi Kecerdasan Buatan',
        integrationDesc:    'Gunakan kunci API personal untuk mengaktifkan asisten finansial cerdas.',
        backupAi:           'Aktifkan sebagai AI Cadangan',
        backupAiDesc:       'AI lokal (Python) tetap berjalan pertama. Provider ini hanya digunakan jika Python tidak yakin atau sedang offline.',
        performanceTitle:   'Analitik Performa AI',
        tokenUsageTitle:    'Pemakaian Token per Provider',
        tokenUnit:          'token',
        tokenPrompt:        'Prompt',
        tokenCompletion:    'Completion',
        emptyTokenUsage:    'Belum ada pemakaian LLM tercatat.',
        emptyTokenUsageDesc:'Token hanya dihitung saat memakai Gemini/OpenAI/DeepSeek.',
        activityLogTitle:   'Riwayat Aktivitas AI',
        emptyActivityLog:   'Belum ada aktivitas AI yang tercatat.',
        confidenceLabel:    '% yakin',

        // AiAnalytics.vue
        last7Days:          '7 Hari Terakhir',
        last30Days:         '30 Hari Terakhir',
        last90Days:         '90 Hari Terakhir',
        emptyMemory:        'Belum ada memori yang terbentuk.',
        emptyCorrections:   'Belum ada log koreksi pengguna.',
        corrections:        'koreksi',
        categoryId:         'Kategori ID',
        hits:               'Hits',
        weight:             'Weight',
    },

    // ────────────────────────────────────────────────────────────────
    // ERRORS & VALIDATION
    // ────────────────────────────────────────────────────────────────
    errors: {
        generic:        'Terjadi kesalahan. Coba lagi.',
        network:        'Tidak dapat terhubung ke server.',
        unauthorized:   'Sesi kamu habis. Silakan login kembali.',
        notFound:       'Halaman tidak ditemukan.',
        serverError:    'Terjadi kesalahan pada server.',
        forbidden:      'Kamu tidak memiliki akses.',
    },

    validation: {
        required:       '{field} wajib diisi',
        minLength:      '{field} minimal {min} karakter',
        maxLength:      '{field} maksimal {max} karakter',
        email:          'Format email tidak valid',
        numeric:        '{field} harus berupa angka',
        positive:       '{field} harus lebih dari 0',
        future:         '{field} tidak boleh di masa depan',
        confirmed:      'Konfirmasi {field} tidak cocok',
    },

    // ────────────────────────────────────────────────────────────────
    // TOAST / NOTIFICATIONS
    // ────────────────────────────────────────────────────────────────
    toast: {
        saved:          'Berhasil disimpan.',
        deleted:        'Berhasil dihapus.',
        updated:        'Berhasil diperbarui.',
        error:          'Gagal. Coba lagi.',
        copied:         'Disalin ke clipboard.',
        languageChanged:'Bahasa berhasil diubah.',
    },

    // ────────────────────────────────────────────────────────────────
    // EMPTY STATES
    // ────────────────────────────────────────────────────────────────
    empty: {
        transaction:    'Belum ada transaksi.',
        transactionMsg: 'Catat transaksi pertama kamu!',
        wallet:         'Belum ada dompet.',
        walletMsg:      'Tambah dompet untuk mulai mencatat.',
        category:       'Belum ada kategori.',
        categoryMsg:    'Buat kategori untuk mengorganisir transaksi.',
        loan:           'Tidak ada hutang/piutang aktif.',
    },

    // ────────────────────────────────────────────────────────────────
    // CHAT BOT PROFILE
    // ────────────────────────────────────────────────────────────────
    chatBot: {
        title:          'Profil Bot',
        subtitle:       'Personalisasi asisten AI kamu',
        photoSection:   'Foto Bot',
        nameSection:    'Nama Bot',
        namePlaceholder:'Masukkan nama bot...',
        nameHint:       'Nama yang akan digunakan bot saat menyapa kamu.',
        saveBtn:        'Simpan Perubahan',
        uploadPhoto:    'Unggah Foto',
        removePhoto:    'Hapus Foto',
        presetNames:    'Nama Populer',
        head:           'Bot Profile',
    },

    // ────────────────────────────────────────────────────────────────
    // BUTTONS
    // ────────────────────────────────────────────────────────────────
    btn: {
        save:       'Simpan',
        saving:     'Menyimpan...',
        cancel:     'Batal',
        delete:     'Hapus',
        deleting:   'Menghapus...',
        edit:       'Edit',
        add:        'Tambah',
        create:     'Buat',
        confirm:    'Konfirmasi',
        back:       'Kembali',
        close:      'Tutup',
        next:       'Selanjutnya',
        prev:       'Sebelumnya',
        submit:     'Kirim',
        update:     'Perbarui',
        yes:        'Ya, Hapus',
        no:         'Tidak, Batal',
    },

}
