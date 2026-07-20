<?php

declare(strict_types=1);

/**
 * Translation keys untuk Settings — Bahasa Indonesia.
 *
 * Konvensi penamaan:
 *   settings.<section>.<page>.<key>
 */
return [

    // ──────────────────────────────────────────────────────────────
    // GENERAL
    // ──────────────────────────────────────────────────────────────
    'title' => 'Pengaturan',
    'subtitle' => 'Kelola Pengaturan',
    'save' => 'Simpan',
    'cancel' => 'Batal',
    'loading' => 'Memuat...',
    'success' => 'Berhasil disimpan!',
    'error' => 'Terjadi kesalahan',

    // ──────────────────────────────────────────────────────────────
    // ACCOUNT SECTION
    // ──────────────────────────────────────────────────────────────
    'account' => [
        'title' => 'Akun',
        'description' => 'Kelola akun Anda',

        'profile' => [
            'title' => 'Profil',
            'description' => 'Informasi pribadi Anda',
            'email' => 'Email',
            'name' => 'Nama',
            'help_text' => 'Untuk mengedit profil, kunjungi halaman Profil',
        ],

        'security' => [
            'title' => 'Keamanan',
            'description' => 'Kelola keamanan akun dan autentikasi',
            'password' => [
                'title' => 'Kata Sandi',
                'description' => 'Ubah kata sandi Anda',
                'change_button' => 'Ubah Kata Sandi',
            ],
            '2fa' => [
                'title' => 'Autentikasi Dua Faktor',
                'description' => 'Tambahkan lapisan keamanan ekstra',
                'coming_soon' => 'Segera hadir',
                'enable' => 'Aktifkan 2FA',
            ],
            'login_activity' => [
                'title' => 'Aktivitas Login',
                'description' => 'Lihat percobaan login terakhir Anda',
                'tracking_soon' => 'Pelacakan aktivitas login segera hadir',
            ],
        ],

        'sessions' => [
            'title' => 'Sesi Aktif',
            'description' => 'Kelola sesi aktif Anda',
            'current' => 'Sesi Saat Ini',
            'current_browser' => 'Browser Ini',
            'last_active' => 'Terakhir aktif baru saja',
            'active' => 'Aktif',
            'other_sessions' => 'Sesi Lainnya',
            'no_other_sessions' => 'Tidak ada sesi aktif lainnya',
        ],

        'preferences' => [
            'title' => 'Preferensi',
            'description' => 'Atur preferensi timezone dan format tanggal',
            'timezone' => [
                'title' => 'Zona Waktu',
                'description' => 'Pilih zona waktu Anda',
            ],
            'date_format' => [
                'title' => 'Format Tanggal',
                'description' => 'Pilih format tanggal yang diinginkan',
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
        'title' => 'Aplikasi',
        'description' => 'Tampilan dan perilaku aplikasi',

        'appearance' => [
            'title' => 'Tampilan',
            'description' => 'Sesuaikan tampilan aplikasi',
            'theme' => [
                'title' => 'Tema',
                'description' => 'Pilih tema pilihan Anda',
                'light' => 'Terang',
                'dark' => 'Gelap',
                'system' => 'Sistem',
            ],
            'accent_color' => [
                'title' => 'Warna Aksen',
                'description' => 'Pilih warna aksen Anda',
            ],
        ],

        'language' => [
            'title' => 'Bahasa & Region',
            'description' => 'Atur bahasa, mata uang, dan format tanggal',
            'language' => [
                'title' => 'Bahasa',
                'description' => 'Pilih bahasa Anda',
                'id' => 'Bahasa Indonesia',
                'en' => 'English',
                'auto' => 'Otomatis (Perangkat)',
                'autoDesc' => 'Ikuti pengaturan bahasa perangkat',
                'current' => 'Saat Ini',
            ],
            'currency' => [
                'title' => 'Mata Uang',
                'description' => 'Mata uang default untuk transaksi',
                'idr' => 'IDR - Rupiah Indonesia',
                'usd' => 'USD - Dolar AS',
                'eur' => 'EUR - Euro',
            ],
        ],

        'notifications' => [
            'title' => 'Notifikasi',
            'description' => 'Kontrol cara Anda menerima notifikasi',
            'email' => [
                'title' => 'Notifikasi Email',
                'description' => 'Terima update melalui email',
                'label' => 'Kirim notifikasi email kepada saya',
            ],
            'push' => [
                'title' => 'Notifikasi Push',
                'description' => 'Terima notifikasi desktop',
                'label' => 'Kirim notifikasi push kepada saya',
            ],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // FINANCE SECTION
    // ──────────────────────────────────────────────────────────────
    'finance' => [
        'title' => 'Keuangan',
        'description' => 'Dompet, kategori, anggaran',

        'defaults' => [
            'title' => 'Default',
            'description' => 'Atur dompet dan mata uang default',
            'wallet' => [
                'title' => 'Dompet Default',
                'description' => 'Pilih dompet mana yang digunakan secara default',
            ],
            'currency' => [
                'title' => 'Mata Uang Default',
                'description' => 'Mata uang untuk transaksi baru',
            ],
            'transaction_logic' => [
                'title' => 'Logika Transaksi',
                'description' => 'Izinkan saldo negatif pada transaksi',
                'label' => 'Izinkan transaksi di bawah saldo nol',
                'on' => 'Aktif',
                'off' => 'Mati',
            ],
        ],

        'categories' => [
            'title' => 'Kategori',
            'description' => 'Kelola kategori transaksi Anda',
            'manage' => 'Kategori dikelola dari halaman Kategori utama',
            'go_to' => 'Buka Kategori',
        ],

        'wallets' => [
            'title' => 'Dompet',
            'description' => 'Kelola dompet dan akun Anda',
            'manage' => 'Dompet dikelola dari halaman Dompet utama',
            'go_to' => 'Buka Dompet',
        ],

        'budget' => [
            'title' => 'Anggaran',
            'description' => 'Atur batas anggaran dan peringatan',
            'coming_soon' => 'Fitur manajemen anggaran segera hadir',
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // AI SECTION
    // ──────────────────────────────────────────────────────────────
    'ai' => [
        'title' => 'Kecerdasan Buatan',
        'description' => 'Pengaturan dan integrasi AI',

        'models' => [
            'title' => 'Model & Konfigurasi',
            'description' => 'Pilih dan konfigurasi penyedia dan model AI Anda',
            'provider' => [
                'label' => 'Penyedia AI',
                'description' => 'Pilih penyedia AI Anda (GPT, Claude, Gemini, dll)',
            ],
            'model' => [
                'label' => 'Pilihan Model',
                'description' => 'Pilih model AI yang akan digunakan',
                'hint' => 'Model berbeda memiliki kemampuan dan biaya yang berbeda',
                'description' => 'Pilih versi model pilihan Anda',
            ],
            'token_limit' => [
                'label' => 'Batas Token',
                'description' => 'Atur token maksimum per permintaan',
                'hint' => 'Batas yang lebih tinggi memungkinkan respons yang lebih panjang tetapi lebih mahal',
            ],
            'api_key' => [
                'label' => 'Kunci API',
                'description' => 'Kunci API Anda untuk penyedia yang dipilih',
                'warning' => 'Jaga kunci API Anda tetap rahasia. Jangan pernah membagikannya secara publik.',
            ],
            'status' => 'Status',
            'test_button' => 'Tes Koneksi',
            'testing' => 'Menguji...',
            'test_success' => 'Koneksi berhasil!',
            'test_failed' => 'Koneksi gagal',
            'select_model' => 'Pilih model',
            'help_text' => 'Konfigurasi pengaturan penyedia dan model AI Anda di sini. Uji koneksi untuk memastikan semuanya berfungsi dengan baik.',
        ],

        'bot' => [
            'title' => 'Profil Bot',
            'description' => 'Sesuaikan asisten AI Anda',
            'avatar' => [
                'label' => 'Avatar Bot',
                'description' => 'Unggah foto profil untuk bot Anda',
                'upload_button' => 'Pilih Gambar',
                'hint' => 'Mendukung JPG, PNG (maks 5MB)',
            ],
            'name' => [
                'label' => 'Nama Bot',
                'description' => 'Berikan nama untuk bot Anda',
                'placeholder' => 'mis. Ken-Chan',
                'hint' => 'Nama ini akan muncul dalam percakapan',
            ],
            'personality' => [
                'label' => 'Kepribadian & Nada',
                'description' => 'Sesuaikan cara bot berkomunikasi',
                'coming_soon' => 'Segera Hadir',
                'coming_soon_description' => 'Pengaturan kepribadian akan segera tersedia',
            ],
            'help_text' => 'Personalisasi asisten AI Anda di sini. Nama dan avatar bot Anda akan ditampilkan dalam percakapan.',
        ],

        'memory' => [
            'title' => 'Manajemen Memori',
            'description' => 'Konfigurasi riwayat percakapan dan pembelajaran',
            'retention' => [
                'label' => 'Kebijakan Retensi',
                'description' => 'Berapa lama menyimpan riwayat percakapan',
                'hint' => 'Retensi lebih lama meningkatkan pembelajaran AI tetapi menggunakan lebih banyak penyimpanan',
                'unlimited' => 'Tidak Terbatas',
                'last_7_days' => '7 hari terakhir',
                'last_30_days' => '30 hari terakhir',
                'last_90_days' => '90 hari terakhir',
                'custom' => 'Kustom',
                'custom_days' => 'Hari untuk disimpan',
            ],
            'size_limit' => [
                'label' => 'Batas Ukuran Memori',
                'description' => 'Ukuran maksimum memori yang disimpan',
                'hint' => 'Tetapkan batas penyimpanan untuk memori percakapan',
            ],
            'conversation_history' => [
                'label' => 'Riwayat Percakapan',
                'description' => 'Simpan percakapan untuk konteks',
                'enable' => 'Aktifkan riwayat percakapan',
            ],
            'learning' => [
                'label' => 'Pembelajaran Pengetahuan',
                'description' => 'Izinkan AI belajar dari interaksi',
                'enable' => 'Aktifkan pembelajaran',
                'warning' => 'Ini dapat mempengaruhi perilaku AI seiring waktu',
            ],
            'privacy' => [
                'label' => 'Mode Privasi',
                'description' => 'Jangan gunakan data untuk pelatihan model',
                'enable' => 'Aktifkan mode privasi',
                'hint' => 'Jika diaktifkan, percakapan Anda tidak akan digunakan untuk melatih model AI',
            ],
            'help_text' => 'Konfigurasi cara AI Anda menyimpan dan belajar dari percakapan.',
        ],

        'integration' => [
            'title' => 'Integrasi',
            'description' => 'Hubungkan AI ke platform Anda',
            'telegram' => [
                'title' => 'Bot Telegram',
                'description' => 'Hubungkan bot Telegram Anda',
                'info' => 'Integrasi Telegram memungkinkan Anda menggunakan fitur AI melalui Telegram',
                'configure' => 'Konfigurasi Telegram',
                'status' => 'Status',
            ],
            'webhooks' => [
                'title' => 'Webhook',
                'description' => 'Siapkan webhook untuk acara AI',
                'coming_soon' => 'Segera Hadir',
            ],
        ],

        'advanced' => [
            'title' => 'Pengaturan Lanjutan',
            'description' => 'Opsi developer dan eksperimental',
            'developer_mode' => [
                'label' => 'Mode Developer',
                'description' => 'Aktifkan fitur developer canggih',
                'enable' => 'Aktifkan mode developer',
                'warning' => 'Mode developer menunjukkan detail teknis dan opsi debug',
            ],
            'prompt_debugging' => [
                'label' => 'Debugger Prompt',
                'description' => 'Debug prompt dan respons',
                'enable' => 'Aktifkan debugging prompt',
            ],
            'raw_responses' => [
                'label' => 'Respons Mentah',
                'description' => 'Lihat respons API mentah',
                'enable' => 'Tampilkan respons mentah',
            ],
            'system_prompt' => [
                'label' => 'Prompt Sistem',
                'description' => 'Sesuaikan prompt sistem',
                'placeholder' => 'Masukkan prompt sistem kustom Anda...',
                'hint' => 'Lanjutan: Ubah cara AI berperilaku di tingkat sistem',
            ],
            'templates' => [
                'label' => 'Template Prompt',
                'description' => 'Simpan dan gunakan kembali prompt',
                'coming_soon' => 'Segera Hadir',
            ],
            'experimental' => [
                'label' => 'Fitur Eksperimental',
                'description' => 'Coba fitur beta baru',
                'enable' => 'Aktifkan fitur eksperimental',
                'warning' => 'Fitur eksperimental mungkin tidak stabil',
            ],
            'help_text' => 'Opsi lanjutan untuk pengguna power dan developer.',
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // PRIVACY & DATA SECTION
    // ──────────────────────────────────────────────────────────────
    'privacy' => [
        'title' => 'Privasi & Data',
        'description' => 'Manajemen data dan privasi',

        'settings' => [
            'title' => 'Privasi',
            'description' => 'Kontrol pengaturan privasi Anda',
            'data_collection' => [
                'title' => 'Pengumpulan Data',
                'description' => 'Izinkan kami mengumpulkan data penggunaan',
                'label' => 'Izinkan pengumpulan data penggunaan',
            ],
            'analytics' => [
                'title' => 'Analitik',
                'description' => 'Izinkan pelacakan analitik',
                'label' => 'Kirim analitik',
            ],
        ],

        'data' => [
            'title' => 'Manajemen Data',
            'description' => 'Ekspor, impor, dan cadangkan data Anda',
            'export' => [
                'title' => 'Ekspor Data',
                'description' => 'Unduh data Anda dalam format JSON',
                'button' => 'Ekspor sebagai JSON',
            ],
            'import' => [
                'title' => 'Impor Data',
                'description' => 'Impor data yang telah diekspor sebelumnya',
                'button' => 'Impor (Segera Hadir)',
            ],
            'backup' => [
                'title' => 'Cadangan',
                'description' => 'Buat cadangan otomatis',
                'button' => 'Cadangan Sekarang (Segera Hadir)',
            ],
        ],

        'danger' => [
            'title' => 'Zona Berbahaya',
            'description' => 'Tindakan yang tidak dapat dibalikkan - gunakan dengan hati-hati',
            'clear_cache' => [
                'title' => 'Hapus Cache',
                'description' => 'Hapus semua data cache',
                'button' => 'Hapus Cache',
            ],
            'delete_account' => [
                'title' => 'Hapus Akun',
                'description' => 'Hapus akun dan semua data Anda secara permanen',
                'warning' => 'Tindakan ini tidak dapat dibatalkan. Semua data Anda akan dihapus secara permanen',
                'button' => 'Hapus Akun',
            ],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // SYSTEM SECTION
    // ──────────────────────────────────────────────────────────────
    'system' => [
        'title' => 'Sistem',
        'description' => 'Tentang & diagnostik',

        'about' => [
            'title' => 'Tentang',
            'description' => 'Informasi aplikasi dan kredit',
            'app_name' => 'Bendaharaku',
            'app_description' => 'Aplikasi manajemen keuangan pribadi yang dirancang untuk membantu Anda mengelola pengeluaran dan aset dengan mudah',
            'version' => 'Versi',
            'license' => [
                'title' => 'Lisensi',
                'description' => 'Open Source',
                'type' => 'Lisensi MIT',
            ],
            'credits' => [
                'title' => 'Kredit',
                'description' => 'Dibangun dengan',
                'laravel' => 'Laravel - Backend framework',
                'vue' => 'Vue 3 - Frontend framework',
                'inertia' => 'Inertia.js - Server-driven UI',
                'tailwind' => 'Tailwind CSS - Styling',
            ],
        ],

        'diagnostics' => [
            'title' => 'Diagnostik',
            'description' => 'Status sistem dan log',
            'system_status' => [
                'title' => 'Status Sistem',
                'description' => 'Kesehatan sistem keseluruhan',
                'api' => 'Status API',
                'database' => 'Database',
                'healthy' => 'Sehat',
                'connected' => 'Terhubung',
            ],
            'logs' => [
                'title' => 'Log',
                'description' => 'Log sistem terbaru',
                'no_logs' => 'Tidak ada log terbaru',
            ],
        ],
    ],
];
