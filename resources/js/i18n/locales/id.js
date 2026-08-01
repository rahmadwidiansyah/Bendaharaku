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
        never:          'Tidak Pernah',
        yes:            'Ya',
        no:             'Tidak',
        ok:             'OK',
        success:        'Berhasil',
        error:          'Gagal',
        warning:        'Peringatan',
        partial:        'Sebagian',
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
        today:          'Hari Ini',
        saveAndAddMore: 'Simpan & tambah lagi',
        open:           'Buka',
        dateRange:      'Rentang Waktu',
        from:           'Dari',
        to:             'Sampai',
        dateInvalidRange: 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        thisYear:       'Tahun Ini',
        thisMonth:      'Bulan Ini',
        lastMonth:      'Bulan Lalu',
        applyFilter:    'Terapkan Filter',
        applying:       'Menerapkan...',
        // Generic error messages
        errors: {
            generic: 'Terjadi kesalahan. Silakan coba lagi nanti.'
        },
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
        home:       'Beranda',
        budgeting:  'Anggaran',
        record:     'Catat',
        analytics:  'Analitik',
        label:      'Label',
        loan:       'Tanggungan',
        settings:   'Pengaturan',
        telegram:   'Telegram',
        newRecord:  'Catat Baru',
        mainNav:    'Navigasi utama',
        profile:    'Profil',
        help:       'Bantuan',
        homeDesc:      'Halaman utama',
        budgetingDesc: 'Kelola anggaran Anda',
        recordDesc:    'Riwayat transaksi',
        analyticsDesc: 'Laporan keuangan',
        chatDesc:      'Chat dengan AI Assistant',
        settingsDesc:  'Semua pengaturan aplikasi',
    },

    // ────────────────────────────────────────────────────────────────
    // BUDGETING
    // ────────────────────────────────────────────────────────────────
    budgeting: {
        title:            'Budgeting',
        subtitle:         'Asisten AI',
        monthlyTitle:     'Budget Bulan Ini',
        categories:       'kategori',
        emptyTitle:       'Budget otomatis dari {bot}',
        emptyDesc:        'berdasarkan transaksi 3 bulan terakhir kamu',
        generate:         'Minta {bot} generate budgeting',
        pastPeriod:       'Budget AI hanya bisa dibuat untuk bulan berjalan ini',
        pastPeriodHint:   'Budget AI hanya bisa dibuat untuk bulan berjalan. Edit manual tetap tersedia.',
        generating:       'Generate budget...',
        generated:        'Budget berhasil di-generate',
        saved:            'Budget berhasil disimpan',
        refresh:          'Refresh AI',
        refreshConfirmTitle: 'Ganti budget ini?',
        refreshConfirmMsg: 'Generate ulang akan menggantikan budget saat ini — termasuk edit manual kamu — dengan yang baru.',
        refreshConfirmCta: 'Ya, generate ulang',
        edit:             'Edit Manual',
        save:             'Simpan',
        cancel:           'Batal',
        saving:           'Menyimpan...',
        editingHint:      'Mode edit — ubah nominal lalu simpan',
        period:           'Periode',
        totalBudget:      'Total Budget',
        totalSpent:       'Total Terpakai',
        totalRemaining:   'Sisa Budget',
        byCategory:       'Per Kategori',
        byType:           'Per Tipe Pengeluaran',
        budget:           'Budget',
        spent:            'Terpakai',
        remaining:        'Sisa',
        overBudget:       'Melebihi budget',
        aiNotes:          'Penjelasan AI',
        aiNotesTitle:     'Penjelasan budget dari {bot}',
        close:            'Tutup',
        loading:          'Memuat data budget...',
        loadError:        'Gagal memuat data budget',
        aiError:          '{bot} sedang sibuk atau tidak merespons. Coba lagi beberapa saat lagi.',
        retry:            'Coba lagi',
        noBudgetYet:      'Belum ada budget untuk periode ini',
        budgetFor:        'Budget untuk',
        timeout:          'Generate budget memakan waktu terlalu lama. Periksa koneksi atau coba lagi.',
        createManual:     'Buat Budget Manual',
        titleCreate:      'Buat Budget Manual',
        categoryLabel:    'Kategori',
        groupLabel:       'Tipe Pengeluaran',
        amountLabel:      'Nominal',
        amountPlaceholder: '0',
        selectCategory:   'Pilih kategori',
        selectGroup:      'Pilih tipe',
        pickerHint:       'Ketuk untuk memilih',
        noCategories:     'Belum ada kategori pengeluaran. Buat dulu di menu Kategori.',
        customGroup:      'Custom',
        customGroupPlaceholder: 'Nama tipe baru (mis. Cicilan)',
        addCustomGroup:   'Tambahkan',
        addRow:           'Tambah Kategori',
        removeRow:        'Hapus',
        totalLabel:       'Total Budget',
        mergeHint:        'Budget AI yang ada akan digabung: kategori yang kamu ubah memakai nominalmu, sisanya tetap dari AI. Baris dengan tipe dikosongkan tidak masuk budget.',
        replaceAi:        'Hapus hasil AI & ganti seluruhnya',
        replaceHint:      'Budget yang ada akan diganti seluruhnya dengan isi form ini.',
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
        stepOf:         'Langkah {step} dari {total}',
        chooseTypeToContinue: 'Pilih jenis untuk melanjutkan',
        chooseSourceWallet: 'Pilih dompet asal...',
        chooseDestWallet: 'Pilih dompet tujuan...',
        chooseDate:     'Pilih Tanggal',
        nextNominal:    'Lanjut → Isi Nominal',
        today:          'Hari Ini',
        yesterday:      'Kemarin',
        wallet:         'Dompet',
        transferFunds:  'Pindah Dana',
        allBalance:     'Semua Saldo',
        settle:         'Lunasi',
        collectAll:     'Tagih Semua',
        relatedParty:   'Pihak Terkait',
        namePlaceholder:'Nama...',
        hasDueDate:     'Ada Jatuh Tempo?',
        fixedDate:      'Tgl Pasti',
        everyMonth:     'Tiap Bulan',
        everyDay:       'Per Hari',
        dayPlaceholder: 'Tgl (1-31)',
        cyclePlaceholder:'Siklus (hari)',
        saveAndStay:    'Simpan & tambah lagi',
        addCategory:    'Tambah Kategori',
        noCategory:     'Belum ada kategori',
        created:        'Dibuat',
        updated:        'Diperbarui',

        // Validasi
        validation: {
            amountRequired:   'Nominal wajib diisi',
            amountInvalid:    'Nominal tidak valid',
            amountPositive:   'Nominal harus lebih dari 0',
            categoryRequired: 'Pilih kategori',
            sourceRequired:   'Pilih dompet sumber',
            destRequired:     'Pilih dompet tujuan',
            sameWallet:       'Dompet asal dan tujuan tidak boleh sama',
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
        aiParsed:      'Hasil Parsing AI',
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
        seconds:       'Detik',
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
        keyword:        'Kata Kunci',
        keywordHint:    'Kata kunci untuk AI (contoh: bca, gopay)',
        groupType:      'Tipe Grup',
        balance:        'Saldo Awal',
        balancePlaceholder: '0',
        deleteTitle:    'Hapus Dompet',
        deleteMsg:      'Dompet ini akan dihapus permanen beserta semua transaksinya.',
        deleteConfirm:  'Yakin ingin menghapus dompet ini?',
        totalDebt:      'Total Hutang',
        totalReceivable:'Total Piutang',
        viewDebtDetail: 'Lihat Detail Hutang',
        viewReceivableDetail: 'Lihat Detail Piutang',

        // Group types
        groupTypes: {
            liquid:     'Liquid (Tunai/Digital)',
            asset:      'Aset / Investasi',
        },

        // Show page
        recentMutation: 'Mutasi Terakhir',
        emptyMutation:  'Belum ada mutasi',

        pinDashboard:       'Pin ke Dashboard',
        pinDashboardDesc:   'Tampilkan dompet ini di halaman utama',

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
        keyword:        'Kata Kunci',
        keywordHint:    'Kata kunci untuk AI',
        type:           'Tipe Transaksi',
        addNew:         'Buat Kategori Baru',
        deleteTitle:    'Hapus Kategori',
        deleteMsg:      'Kategori ini akan dihapus permanen.',
        deleteConfirm:  'Yakin ingin menghapus kategori ini?',
        totalLabel:     'Total',
        collection:     'Koleksi',
        transaction:    'Transaksi',
        systemCategory: 'Kategori Sistem',

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
        totalDebt:      'Sisa Hutang',
        totalReceivable:'Sisa Piutang',
        outstandingDebt:'Sisa Hutang',
        outstandingReceivable:'Sisa Piutang',

        // Tampilan bar chart
        view: {
            daily:   'Hari',
            weekly:  'Pekan',
            monthly: 'Bulan',
        },

// Tab kategori
categoryTab: {
    expense:    'Pengeluaran',
    income:     'Pemasukan',
    debt:       'Hutang',
    receivable: 'Piutang',
    expenseShort: 'Keluar',
    incomeShort:  'Masuk',
    debtShort:    'Hutang',
    receivableShort: 'Piutang'
},

        // Chart.js dataset labels
        chartLabels: {
            income:     'Pemasukan',
            expense:    'Pengeluaran',
            debt:       'Hutang',
            receivable: 'Piutang',
        },
    },

    // ────────────────────────────────────────────────────────────────
    // SETTINGS
    // ────────────────────────────────────────────────────────────────
    settings: {
        title:          'Pengaturan',
        subtitle:       'Preferensi',
        save_button:     'Simpan',
        index: {
            section: {
                account:        'Akun',
                appearance:     'Tampilan',
                finance:        'Keuangan',
                ai:             'Kecerdasan Buatan',
                notifications:  'Notifikasi',
                privacy:        'Privasi',
                danger:         'Zona Berbahaya',
            },
            item:           'item',
            items:          'item',
        },
        notifications: {
            title:          'Notifikasi',
            description:    'Preferensi notifikasi email & push',
            notifications: {
                title:          'Pengaturan Notifikasi',
                description:    'Atur notifikasi email dan push',
            },
            save_success:   'Pengaturan berhasil disimpan.',
            save_failed:    'Gagal menyimpan pengaturan.',
            unsaved_changes: 'Anda memiliki perubahan yang belum disimpan.',
        },
        security: {
            title:          'Keamanan',
            description:    'Pengaturan kata sandi & keamanan',
            password: {
                title:          'Ubah Password',
                description:    'Tingkatkan keamanan akun kamu',
            },
        },
        developer: {
            title:          'Developer',
            description:    'Alat developer & opsi eksperimental',
        },

        // ═══ ACCOUNT ═══
        account: {
            title:          'Akun',
            
            profile: {
                title:          'Profil',
                description:    'Informasi pribadi',
                email:          'Email',
                name:           'Nama',
                help_text:      'Untuk mengubah email atau password, kunjungi halaman profil lengkap di',
            },
            
            security: {
                title:          'Keamanan',
                description:    'Password & autentikasi',
                password: {
                    title:          'Ubah Password',
                    description:    'Tingkatkan keamanan akun kamu',
                    change_button:  'Ubah Password',
                },
                '2fa': {
                    title:          'Autentikasi Dua Faktor',
                    description:    'Perlindungan ekstra untuk akun kamu',
                    coming_soon:    'Fitur ini sedang dikembangkan',
                    enable:         'Aktifkan 2FA',
                },
                login_activity: {
                    title:          'Riwayat Login',
                    description:    'Pantau aktivitas masuk ke akun kamu',
                    current:        'Sesi ini',
                    tracking_soon:  'Fitur pelacakan akan segera tersedia',
                },
            },
            
            sessions: {
                title:          'Sesi Aktif',
                description:    'Kelola sesi perangkat kamu',
                current:        'Sesi Saat Ini',
                current_browser:'Browser/Perangkat Ini',
                last_active:    'Terakhir aktif baru saja',
                active:         'Aktif',
                other_sessions: 'Sesi Lainnya',
                no_other_sessions: 'Tidak ada sesi lain yang aktif',
            },
            
            preferences: {
                title:          'Preferensi',
                description:    'Bahasa, zona waktu & format tanggal',
                language: {
                    title:          'Bahasa',
                    description:    'Pilih bahasa aplikasi',
                    id:             'Bahasa Indonesia',
                    en:             'English',
                },
                timezone: {
                    title:          'Zona Waktu',
                    description:    'Pilih zona waktu kamu',
                },
                date_format: {
                    title:          'Format Tanggal',
                    description:    'Pilih format tampilan tanggal',
                    ddmmyyyy:       'DD/MM/YYYY',
                    mmddyyyy:       'MM/DD/YYYY',
                    yyyymmdd:       'YYYY-MM-DD',
                },
            },
        },

        // ═══ APPLICATION ═══
        application: {
            title:          'Aplikasi',
            
            appearance: {
                title:          'Tampilan',
                description:    'Tema, warna, kepadatan',
                theme: {
                    title:          'Tema',
                    description:    'Pilih tema warna',
                    light:          'Terang',
                    dark:           'Gelap',
                    system:         'Mengikuti Sistem',
                },
                category_icon_color: {
                    title:          'Warna Ikon Kategori',
                    description:    'Warna ikon berdasarkan tipe transaksi',
                    label:          'Ikon Kategori Berwarna',
                    on:             'Ikon sesuai tipe transaksi',
                    off:            'Semua ikon putih',
                },
                accent_color: {
                    title:          'Warna Aksen',
                    description:    'Pilih warna aksen utama',
                    custom:         'Warna Kustom...',
                    setAccent:      'Set {name} sebagai warna aksen',
                },
            },
            
            language: {
                title:          'Bahasa & Wilayah',
                description:    'Bahasa, format tanggal, mata uang',
                language: {
                    title:          'Bahasa',
                    description:    'Pilih bahasa tampilan',
                    id:             'Bahasa Indonesia',
                    en:             'English',
                    auto:           'Ikuti Bahasa Perangkat',
                    autoDesc:       'Menyesuaikan dengan bahasa browser/perangkat kamu',
                    current:        'Aktif',
                },
                currency: {
                    title:          'Mata Uang',
                    description:    'Pilih mata uang default',
                    idr:            'IDR (Rp)',
                    usd:            'USD ($)',
                    eur:            'EUR (€)',
                },
            },
            
            notifications: {
                title:          'Notifikasi',
                description:    'Email, push, jam hening',
                email: {
                    title:          'Notifikasi Email',
                    description:    'Terima notifikasi via email',
                    label:          'Aktifkan notifikasi email',
                },
                push: {
                    title:          'Notifikasi Push',
                    description:    'Terima notifikasi push browser',
                    label:          'Aktifkan notifikasi push',
                    unsupported:    'Browser atau perangkat ini tidak mendukung notifikasi push.',
                    vapid_missing:  'Notifikasi push belum dikonfigurasi oleh admin.',
                    denied:         'Izin notifikasi ditolak di browser. Izinkan lewat pengaturan situs di browser kamu.',
                    granted:        'Izin notifikasi aktif di browser.',
                    default:        'Klik untuk meminta izin notifikasi browser.',
                },
            },
        },

        // ═══ FINANCE ═══
        finance: {
            title:          'Keuangan',
            
            defaults: {
                title:          'Default',
                description:    'Dompet & mata uang default',
                wallet: {
                    title:          'Dompet Default',
                    description:    'Dompet yang digunakan saat membuat transaksi baru',
                },
                currency: {
                    title:          'Mata Uang Default',
                    description:    'Mata uang default untuk transaksi',
                },
                transaction_logic: {
                    title:          'Logika Transaksi',
                    description:    'Izinkan saldo negatif saat transaksi',
                    label:          'Izinkan saldo minus',
                    on:             '✓ Aktif — saldo minus diizinkan',
                    off:            '✗ Tidak Aktif — transaksi ditolak jika saldo tidak cukup',
                },
            },
            
            categories: {
                title:          'Kategori',
                description:    'Kelola kategori transaksi',
                manage:         'Kelola kategori transaksi kamu',
                go_to:          'Buka Halaman Kategori',
            },
            
            wallets: {
                title:          'Dompet',
                description:    'Kelola dompet kamu',
                manage:         'Kelola dompet dan saldo kamu',
                go_to:          'Buka Halaman Dompet',
            },
            
            budget: {
                title:          'Anggaran',
                description:    'Perencanaan anggaran bulanan & auto-generate',
                auto_title:     'Auto-generate setiap bulan',
                auto_description: 'Generate anggaran baru otomatis setiap tanggal 1',
                save_success:   'Pengaturan tersimpan',
                save_error:     'Gagal menyimpan pengaturan',
            },
        },

        // ═══ AI ═══
        ai: {
            title:          'AI & Otomasi',
            
            models: {
                title:          'Provider & Model',
                description:    'Pengaturan provider AI & model',
                provider: {
                    label:          'Provider AI',
                    description:    'Pilih dan konfigurasi provider AI yang ingin digunakan',
                },
                model: {
                    label:          'Model Default',
                    description:    'Pilih model default untuk provider ini',
                    hint:           'Model ini akan digunakan untuk semua permintaan kecuali diganti',
                },
                token_limit: {
                    label:          'Batas Token',
                    description:    'Atur batas maksimal token',
                    hint:           'Membatasi panjang respon untuk menghemat biaya',
                },
                api_key: {
                    label:          'API Key',
                    description:    'API key Anda untuk provider ini',
                    placeholder:    'Kosongkan jika tidak ingin mengubah key',
                    warning:        'API key dienkripsi dan disimpan secara aman di server',
                },
                select_model:   'Pilih model...',
                status: 'Status',
                test_button: 'Uji Koneksi',
                testing: 'Mengujicoba...',
                test_success: 'Koneksi berhasil.',
                test_failed: 'Koneksi gagal.',
                set_active: 'Jadikan Provider Aktif',
                set_active_desc: 'Provider ini akan digunakan untuk semua percakapan AI',
                provider_toggle: 'Jadikan provider aktif untuk semua percakapan',
                active: 'Aktif',
                help_text: 'Konfigurasi provider dan model AI yang akan digunakan. Tekan Simpan untuk menyimpan perubahan.',
            },
            
            bot: {
                title:          'Profil Bot',
                description:    'Nama, avatar, kepribadian bot',
                avatar: {
                    label:          'Avatar Bot',
                    description:    'Foto profil bot kamu',
                    upload_button:  'Unggah Avatar',
                    hint:           'Gunakan gambar persegi; format .png atau .jpg',
                },
                name: {
                    label:          'Nama Bot',
                    description:    'Nama yang akan tampil pada percakapan',
                    placeholder:    'Nama bot',
                    suggestions:    'Nama Saran',
                    hint:           'Contoh: Bendahara Bot',
                },
                personality: {
                    label:          'Kepribadian Bot',
                    description:    'Deskripsi singkat tentang gaya dan perilaku bot',
                    placeholder:    'Contoh: Ramah, informatif, dan ringkas.',
                    hint:           'Jelaskan gaya komunikasi yang diinginkan untuk bot.',
                },
                help_text: 'Personalisasi bagaimana asisten AI Anda berbicara dan tampil di antarmuka chat.',
            },
            
            memory: {
                title:          'Memori',
                description:    'Pengaturan memori percakapan & pengetahuan',
                retention: {
                    label: 'Kebijakan Penyimpanan',
                    description: 'Berapa lama riwayat percakapan disimpan',
                    unlimited: 'Simpan selamanya',
                    last_7_days: 'Simpan 7 hari terakhir',
                    last_30_days: 'Simpan 30 hari terakhir',
                    last_90_days: 'Simpan 90 hari terakhir',
                    custom: 'Kustom...',
                    hint: 'Percakapan yang lebih lama akan dihapus otomatis',
                    custom_days: 'Jumlah hari untuk disimpan',
                },
                size_limit: {
                    label: 'Batas Ukuran Memori',
                    description: 'Ukuran penyimpanan lokal maksimal untuk memori',
                    hint: 'Membatasi berapa banyak penyimpanan yang digunakan untuk riwayat',
                },
                conversation_history: {
                    label: 'Riwayat Percakapan',
                    description: 'Ingat pesan sebelumnya dalam chat',
                    enable: 'Aktifkan riwayat percakapan',
                },
                learning: {
                    label: 'Pembelajaran Knowledge Base',
                    description: 'Izinkan AI belajar dari koreksi',
                    enable: 'Aktifkan pembelajaran berkelanjutan',
                    warning: 'Ini akan menyimpan data yang dikoreksi pengguna untuk meningkatkan prediksi ke depan',
                },
                privacy: {
                    label: 'Mode Privasi',
                    description: 'Jangan simpan angka sensitif dalam memori',
                    enable: 'Aktifkan mode privasi',
                    hint: 'Nominal transaksi akan disensor dalam log riwayat',
                },
                help_text: 'Pengaturan memori mengontrol seberapa banyak konteks yang dipertahankan AI antar sesi.',
                manage_button: 'Kelola Memori AI',
                manage: {
                    title: 'Memori AI',
                    description: 'Memori yang dipelajari AI dari kebiasaan transaksimu',
                    search_placeholder: 'Cari berdasarkan keyword...',
                    filter_all: 'Semua',
                    filter_active: 'Aktif',
                    filter_low: 'Berat Rendah',
                    filter_high: 'Berat Tinggi',
                    empty_title: 'Belum Ada Memori',
                    empty_description: 'AI akan mulai belajar dari transaksi yang kamu lakukan. Semakin sering kamu bertransaksi, semakin baik AI mengenali polamu.',
                    empty_cta: 'Mulai Transaksi',
                    card: {
                        keyword: 'Keyword',
                        category: 'Kategori',
                        wallet: 'Dompet',
                        weight: 'Bobot',
                        hit_count: 'Frekuensi',
                        last_used: 'Terakhir Dipakai',
                        view_detail: 'Lihat Detail',
                    },
                    weight_low: 'Rendah',
                    weight_medium: 'Sedang',
                    weight_high: 'Tinggi',
                },
                detail: {
                    title: 'Detail Memori',
                    back: 'Kembali ke Daftar',
                    info: 'Informasi Memori',
                    raw_subject: 'Subjek Asli',
                    normalized_subject: 'Subjek Normalisasi',
                    keyword: 'Keyword',
                    category: 'Kategori',
                    wallet: 'Dompet',
                    current_weight: 'Bobot Saat Ini',
                    hit_count: 'Frekuensi',
                    created_at: 'Dibuat',
                    last_used: 'Terakhir Dipakai',
                    algorithm_version: 'Versi Algoritma',
                    timeline: 'Kronologi Memori',
                    timeline_empty: 'Belum ada riwayat untuk memori ini.',
                    action: {
                        CREATED: 'Dibuat',
                        REWARDED: 'Diperkuat',
                        DECAYED: 'Melemah',
                        PRUNED: 'Dihapus',
                        UPDATED: 'Diperbarui',
                        DELETED: 'Dihapus',
                        CONFLICT: 'Konflik',
                        MERGE: 'Digabung',
                    },
                    weight_change: 'Berat: {old} → {new}',
                    hit_change: 'Frekuensi: {old} → {new}',
                },
            },
            
            integrations: {
                title:          'Integrasi',
                description:    'Messaging, otomasi & layanan eksternal',
                telegram: {
                    title:          'Integrasi Telegram',
                    description:    'Hubungkan bot Telegram untuk menerima dan mengirim pesan',
                    info:           'Hubungkan bot Telegram Anda untuk berinteraksi dengan Bendaharaku melalui chat.',
                    configure:      'Konfigurasi Bot Telegram',
                },
                webhooks: {
                    title:          'Webhooks',
                    description:    'Kirim event ke layanan eksternal melalui webhook',
                    hint:           'Tambahkan endpoint webhook untuk menerima event secara realtime dari aplikasi.',
                },
            },

            // Alias tanpa 's' — dipakai oleh Integration.vue
            integration: {
                title:          'Integrasi',
                description:    'Messaging, otomasi & layanan eksternal',
                telegram: {
                    title:          'Integrasi Telegram',
                    description:    'Hubungkan bot Telegram untuk menerima dan mengirim pesan',
                    info:           'Hubungkan bot Telegram Anda untuk berinteraksi dengan Bendaharaku melalui chat.',
                    configure:      'Konfigurasi Bot Telegram',
                },
                webhooks: {
                    title:          'Webhooks',
                    description:    'Kirim event ke layanan eksternal melalui webhook',
                    hint:           'Tambahkan endpoint webhook untuk menerima event secara realtime dari aplikasi.',
                },
            },


            advanced: {
                title:          'Developer Tools',
                description:    'Opsi developer & eksperimental',
                developer_mode: {
                    label:          'Mode Developer',
                    description:    'Aktifkan mode developer untuk debugging',
                    enable:         'Aktifkan Mode Developer',
                    warning:        'Mode developer mungkin mengekspos informasi debug yang sensitif',
                },
                prompt_debugging: {
                    label:          'Debugging Prompt',
                    description:    'Tampilkan prompt persis yang dikirim ke LLM',
                    enable:         'Aktifkan debugging prompt',
                },
                raw_responses: {
                    label:          'Respon Mentah',
                    description:    'Tampilkan JSON/teks mentah dari AI',
                    enable:         'Tampilkan respon mentah',
                },
                system_prompt: {
                    label:          'System Prompt Kustom',
                    description:    'Timpa perilaku default sepenuhnya',
                    placeholder:    'Anda adalah asisten keuangan yang membantu...',
                    hint:           'Biarkan kosong untuk menggunakan system prompt bawaan',
                },
                experimental: {
                    label:          'Fitur Eksperimental',
                    description:    'Aktifkan fitur AI akses awal',
                    enable:         'Aktifkan fitur eksperimental',
                    warning:        'Fitur-fitur ini mungkin tidak stabil atau dapat berubah sewaktu-waktu',
                },
                templates: {
                    label:          'Template Prompt',
                    description:    'Buat dan gunakan kembali template prompt untuk interaksi AI',
                    use:            'Gunakan',
                    title_placeholder: 'Judul template',
                    content_placeholder: 'Isi template...',
                },
                help_text: 'Developer tools adalah untuk pengguna tingkat lanjut untuk men-debug atau menyesuaikan AI.',
            },
        },

        // ═══ PRIVACY & DATA ═══
        privacy: {
            title:          'Privasi & Data',
            
            settings: {
                title:          'Privasi',
                description:    'Pengumpulan & analitik data',
                data_collection: {
                    title:          'Pengumpulan Data',
                    description:    'Izinkan kami mengumpulkan data analitik',
                    label:          'Aktifkan pengumpulan data',
                },
                analytics: {
                    title:          'Analitik',
                    description:    'Bantu kami meningkatkan dengan analitik penggunaan',
                    label:          'Aktifkan analitik',
                },
            },
            
            data: {
                title:          'Manajemen Data',
                description:    'Ekspor, impor, cadangan',
                export: {
                    title:          'Ekspor Data',
                    description:    'Unduh salinan data akun Anda sebagai file JSON',
                    button:         'Ekspor Data',
                    success:        'Ekspor selesai. File sedang diunduh.',
                },
                import: {
                    title:          'Impor Data',
                    description:    'Impor file cadangan untuk memulihkan data',
                    button:         'Impor (Segera)',
                },
                backup: {
                    title:          'Cadangan',
                    description:    'Kelola cadangan dan restore',
                    button:         'Cadangkan (Segera)',
                },
            },
            
            danger: {
                title:          'Zona Berbahaya',
                description:    'Tindakan yang tidak dapat dibatalkan',
                clear_cache: {
                    title:          'Hapus Cache',
                    description:    'Hapus data cache lokal',
                    button:         'Hapus Cache',
                    success:        'Cache berhasil dihapus',
                },
                delete_account: {
                    title:          'Hapus Akun Permanen',
                    description:    'Hapus akun dan semua data terkait',
                    warning:        'Tindakan ini tidak dapat dibatalkan. Semua data keuangan akan hilang.',
                    button:         'Hapus Akun Saya',
                    confirm_title:  'Konfirmasi Penghapusan Akun',
                    confirm_description: 'Tindakan ini TIDAK DAPAT DIBATALKAN. Semua data keuangan, riwayat, dan pengaturan Anda akan dihapus secara permanen.',
                    confirm_button: 'Ya, Hapus Selamanya',
                },
            },

        },

        // ═══ SYSTEM ═══
        system: {
            title:          'Sistem',
            
            about: {
                title:          'Tentang',
                description:    'Versi, lisensi, kredit',
                app_name:       'Tentang Bendaharaku',
                app_description:'Aplikasi manajemen keuangan pribadi yang cerdas',
                version:        'Versi',
                license: {
                    title:          'Lisensi',
                    description:    'Informasi lisensi',
                    type:           'Dilisensikan di bawah MIT License',
                },
                credits: {
                    title:          'Kredit',
                    description:    'Teknologi yang digunakan',
                    laravel:        'Laravel - Backend Framework',
                    vue:            'Vue 3 - Frontend Framework',
                    inertia:        'Inertia.js - Server-driven UI',
                    tailwind:       'Tailwind CSS - Styling',
                },
            },
            
        },

        // ═══ LEGACY KEYS (compatibility) ═══
        transaction:    'Transaksi',
        negativeBalance: {
            title: 'Izinkan Saldo Minus',
            desc:  'Pengeluaran bisa dicatat meski saldo tidak mencukupi. Cocok untuk pencatatan harian yang direkap belakangan.',
        },
        negativeBalanceOn:  '✓ Aktif — saldo negatif diperbolehkan',
        negativeBalanceOff: '✗ Nonaktif — transaksi ditolak bila saldo tidak mencukupi',
        lang: {
            title:          '🌐 Bahasa / Language',
            auto:           'Ikuti Bahasa Perangkat',
            autoDesc:       'Menyesuaikan bahasa browser/perangkat Anda',
            id:             'Bahasa Indonesia',
            en:             'English',
            current:        'Aktif',
        },
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
        newPhotoSelected: 'Foto baru terpilih — simpan untuk menerapkan',
        socialConnections: 'Koneksi Sosial & Pesan',
        socialConnectionsDesc: 'Hubungkan aplikasi perpesanan untuk integrasi AI',
        whatsapp: 'WhatsApp',
        telegram: 'Telegram',
        google: {
            label:          'Google',
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
        days:           'Hari',
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
        tokenUnit:          'Token',
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
        corrections:        'Koreksi',
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
        head:           'Profil Bot',
    },

    // ────────────────────────────────────────────────────────────────
    // CHAT & SEARCH
    // ────────────────────────────────────────────────────────────────
    chat: {
        source: {
            web: 'Web Chat',
            telegram: 'Bot Telegram',
            whatsapp: 'WhatsApp',
            discord: 'Discord',
            api: 'REST API',
            import: 'Impor',
            manual: 'Entri Manual',
            dashboard: 'Dashboard Web',
        },
        command: {
            balance_title: 'Saldo Saat Ini',
            category_title: 'Kategori',
            asset_title: '**Daftar Aset**',
            category_section_income: 'Pemasukan',
            category_section_expense: 'Pengeluaran',
            category_section_transfer: 'Transfer',
            category_section_debt: 'Hutang',
            category_section_receivable: 'Piutang',
            wallet_title: '**Daftar Dompet & Aset**',
            wallet_type_asset: 'Aset',
            wallet_type_liquid: 'Likuid',
            wallet_type_system: 'Sistem',
            asset_count: 'Aset',
        },
        status: {
            queued: 'Antrean',
            pending: 'Menunggu',
            processing: 'Memproses',
            uploading: 'Mengunggah',
            uploaded: 'Terunggah',
            parsed: 'Terurai',
            classified: 'Terklasifikasi',
            ocrCompleted: 'OCR Selesai',
            ready: 'Siap',
            completed: 'Selesai',
            failed: 'Gagal',
            resolved: 'Terselesaikan',
        },
        retry: 'Coba lagi',
        retryEvidence: 'Coba unggah ulang',
        replyReady: '{bot} menjawab pesan kamu',
        replyFailed: 'AI gagal merespons. Buka chat untuk melihat detail.',
        timeout: 'Bot tidak merespons terlalu lama. Coba kirim ulang pesanmu.',
        history: 'Riwayat percakapan',
        placeholder: 'Tanya saya apa saja...',
        typing: 'Sedang mengetik...',
        multi: {
            result: 'Hasil Multi Transaksi',
        },
        assistant: 'Asisten Keuangan AI',
        loadMore: 'Tampilkan Riwayat Sebelumnya',
        loadingMore: 'Memuat riwayat...',
        emptyState: 'Ceritakan transaksimu dengan bahasa alami, atau gunakan perintah di bawah.',
        gettingStarted: 'Mulai dari sini',
        commandButton: 'Buka menu perintah',
        commandTitle: 'Perintah (/)',
        sendButton: 'Kirim pesan',
        attachmentButton: 'Lampirkan gambar bukti',
        attachmentTitle: 'Upload Bukti',
        desktopHint: 'Enter kirim · Shift+Enter baris baru',
        uploadSheetTitle: 'Lampirkan Bukti Transaksi',
        uploadSheetDesc: 'Gambar akan diproses OCR secara otomatis',
        uploadSheetLabel: 'Pilih sumber gambar',
        uploadCamera: 'Ambil Foto',
        uploadCameraDesc: 'Buka kamera untuk foto langsung',
        uploadGallery: 'Pilih dari Galeri',
        uploadGalleryDesc: 'Pilih gambar dari penyimpanan',
        evidenceUploading: 'Mengunggah bukti...',
        evidenceUploaded: 'Bukti berhasil diunggah, sedang diproses OCR...',
        evidenceSent: '📎 Bukti transaksi dikirim',
        evidencePreview: 'Preview bukti transaksi',
        openFullscreen: 'Buka gambar penuh',
        evidence: 'Bukti Transaksi',
        reviewEvidence: 'Review hasil OCR',
        removeEvidence: 'Hapus lampiran',
        sheetTitle: 'Perintah Cepat',
        sheetDesc: 'Pilih perintah untuk memasukkannya ke chat',
        sheetLabel: 'Daftar Perintah',
        showMore: 'Lihat Selengkapnya',
        collapse: 'Tutup',
        scrollToBottom: 'Scroll ke pesan terbaru',
        newMessages: 'Pesan Baru',
        latest: 'Terbaru',
        suggestionExpense: 'Catat pengeluaran',
        suggestionIncome: 'Catat pemasukan',
        suggestionTransfer: 'Transfer antar dompet',
        suggestionBalance: 'Lihat saldo semua dompet',
        suggestionSummary: 'Ringkasan keuangan',
        suggestionReport: 'Laporan bulanan (dengan AI)',
        suggestionStats: 'Statistik ringkas bulan ini',
        suggestionHelp: 'Panduan penggunaan',
        errorItem: 'Item #',
        buttonClose: 'Tutup',
        buttonSave: 'Simpan',
        evidenceLabel: 'Bukti Transaksi',
        reviewBtn: 'Review',
        committed: 'Transaksi tersimpan',
    },

    search: {
        placeholder: 'Cari menu, pengaturan, halaman...',
        clear: 'Hapus pencarian',
        results: 'Hasil Pencarian',
        shortcuts: 'Pintasan Cepat',
        noResults: 'Tidak ada hasil untuk',
        navigation: 'Navigasi',
        settings: 'Pengaturan',
        hints: {
            navigate: 'Navigasi',
            select: 'Pilih',
            close: 'Tutup',
        }
    },

    // ────────────────────────────────────────────────────────────────
    // ICON PICKER
    // ────────────────────────────────────────────────────────────────
    iconPicker: {
        title:          'Pilih Icon',
        search:         'Cari icon...',
        upload:         'Upload',
        cropTitle:      'Potong Icon',
        notFound:       'Icon tidak ditemukan',
        tabs: {
            Finance:    'Keuangan',
            Lifestyle:  'Gaya Hidup',
            Places:     'Tempat',
            Tech:       'Teknologi',
            Animals:    'Hewan',
            Misc:       'Lainnya',
        },
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
