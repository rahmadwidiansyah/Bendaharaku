<div align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D" alt="Vue">
  <img src="https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">

  <h1>🏦 Bendaharaku V4</h1>
  <p>Aplikasi manajemen keuangan pribadi cerdas yang memadukan pencatatan Web Dashboard dengan pemrosesan bahasa natural (NLP) via AI Telegram Bot.</p>
</div>

---

## 🌐 Live Demo

Ingin melihat dan mencoba Bendaharaku V4 secara langsung? Silakan kunjungi tautan berikut:  
👉 **[bendaharaku.widihhh.my.id](https://bendaharaku.widihhh.my.id)**

---

## 🔗 Arsitektur & Repositori

Sistem ini berjalan dengan arsitektur terpisah untuk memastikan performa yang maksimal:
- **Core Web & Backend (Repo Ini):** Menggunakan Laravel, Vue 3, Inertia.js, Tailwind CSS, dan PostgreSQL.
- **AI Microservice:** Menangani pemrosesan teks Telegram menggunakan Python FastAPI. Source code dapat diakses di repositori berikut:  
  👉 **[script_pencatat_keuangan](https://github.com/rahmadwidiansyah/script_pencatat_keuangan.git)**

## ✨ Fitur Utama

- 🤖 **AI Telegram Bot (Natural Language):** Catat transaksi hanya dengan *chat* santai. Sistem mencocokkan teks ke Kategori dan Dompet di *database* secara dinamis tanpa *hardcoded keyword*.
- 💼 **Smart Wallet Management:** Manajemen dompet yang terstruktur, memisahkan tab **Liquid** dan **Investment / Asset** secara otomatis.
- 🤝 **Debt & Receivable Tracker:** Sistem cerdas untuk mendeteksi dan menghitung hutang/piutang berdasarkan *hashtag* (contoh: *"Pinjam duit 100k bca #Budi"*).
- 🔐 **SSO Google Authentication:** Registrasi dan login yang cepat, aman, dan mulus terintegrasi langsung dengan akun Google.

---

## 🛠️ Prasyarat

Karena aplikasi ini sudah dikonfigurasi penuh menggunakan kontainer, Anda hanya membutuhkan:
- **Git** (Untuk kloning repositori)
- **Docker & Docker Compose** (Untuk menjalankan aplikasi, *database*, Node.js, dan Composer tanpa *install* di sistem lokal Anda)

---

## 🚀 Panduan Instalasi (Docker Setup)

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi di lingkungan lokal Anda menggunakan Docker:

**1. Clone the repository**
```bash
git clone [https://github.com/ZackBrawn/Bendaharaku.git](https://github.com/ZackBrawn/Bendaharaku.git)
cd Bendaharaku
```

**2. Install Composer Dependencies (Initial)**
```bash
docker run --rm \
-u "$(id -u):$(id -g)" \
-v "$(pwd):/var/www/html" \
-w /var/www/html \
laravelsail/php83-composer:latest \
composer install --ignore-platform-reqs
```

**3. Setup Environment File**
```bash
cp .env.example .env
```
*(Lihat bagian [Konfigurasi Environment](#-konfigurasi-environment) di bawah untuk mengatur API Keys).*

**4. Start the Containers**
```bash
docker compose up -d
```

**5. Generate Application Key**
```bash
docker compose exec app php artisan key:generate
```

**6. Run Database Migrations**
```bash
docker compose exec app php artisan migrate
```

**7. Install & Build Frontend Assets**
```bash
docker compose exec app npm install
docker compose exec app npm run dev
```

---

## ⚙️ Konfigurasi Environment

Setelah melakukan *copy* file `.env` (Langkah 3), pastikan Anda melengkapi kredensial berikut di dalam file `.env` agar seluruh fitur aplikasi dapat berjalan normal:

```env
# Koneksi Database (Sudah diatur otomatis oleh Docker Compose)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=bendaharaku
DB_USERNAME=sail
DB_PASSWORD=password

# Google OAuth (Untuk Fitur Login)
GOOGLE_CLIENT_ID="your_google_client_id"
GOOGLE_CLIENT_SECRET="your_google_client_secret"
GOOGLE_REDIRECT_URI="http://localhost:8000/auth/google/callback"

# Integrasi Telegram & AI Python
TELEGRAM_BOT_TOKEN="your_telegram_bot_token"
PYTHON_AI_URL="http://ip_atau_domain_python_ai_kalian:8001"
PYTHON_AI_KEY="your_ai_secret_key"
```

> 💡 **Catatan untuk Testing Telegram:** Saat *development* di `localhost`, URL web Anda tidak bisa diakses langsung oleh Telegram. Gunakan Ngrok atau Cloudflare Tunnel, lalu setel webhook Telegram ke URL tersebut.

---

## 👨‍💻 Kontributor

Proyek ini dibangun dan dikembangkan secara kolaboratif oleh:
- **Rahmad Widiansyah** - [@rahmadwidiansyah](https://github.com/rahmadwidiansyah)
- **Frakhan** - [@ZackBrawn](https://github.com/ZackBrawn)
