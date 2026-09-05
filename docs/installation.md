# Panduan Instalasi Yualan Community Edition

Panduan ini akan membimbing Anda melalui proses instalasi **Yualan Community Edition (Dedicated Enterprise)** di lingkungan lokal (development) maupun server produksi.

## Prasyarat Utama

Pastikan sistem Anda memenuhi spesifikasi minimum berikut:

### Sistem & Runtime
- **PHP:** 8.2 atau lebih tinggi (Sangat disarankan 8.3)
- **Composer:** 2.6 atau lebih tinggi
- **Node.js:** 20.x (LTS)
- **NPM:** Versi terbaru
- **Database:** **PostgreSQL 15+** (Sangat disarankan untuk stabilitas enterprise)
- **Web Server:** Nginx (Direkomendasikan) atau Apache

### PHP Extensions
Pastikan ekstensi PHP berikut telah terpasang dan aktif:
`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_pgsql`, `tokenizer`, `xml`, `zip`.

```bash
# Contoh instalasi di Ubuntu 22.04/24.04
sudo apt install php8.2-curl php8.2-dom php8.2-gd php8.2-intl php8.2-mbstring php8.2-pgsql php8.2-xml php8.2-zip
```

---

## Langkah Instalasi

### 1. Persiapan Source Code
```bash
git clone https://github.com/Abdurozzaq/Yualan.git
cd Yualan
```

### 2. Instalasi Dependensi Backend
```bash
composer install --no-dev # Gunakan --no-dev hanya untuk production
```

### 3. Instalasi Dependensi Frontend
```bash
npm install
```

### 4. Konfigurasi Lingkungan (.env)
```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan koneksi database Anda:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=yualan_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 5. Migrasi & Seeding Database
Langkah ini akan membuat struktur tabel dan mengisi data awal yang diperlukan (seperti role dan settings dasar).
```bash
php artisan migrate --seed
```

### 6. Kompilasi Aset Frontend
```bash
# Untuk Development
npm run dev

# Untuk Production (Wajib dijalankan saat deploy)
npm run build
```

### 7. Pengaturan Storage
```bash
php artisan storage:link
```

---

## Konfigurasi Produksi (Wajib)

### 1. Scheduler (Cron Job)
Aplikasi ini sangat bergantung pada Task Scheduler untuk proses otomatis. Tambahkan baris berikut ke crontab server Anda:
```bash
* * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Queue Worker (Supervisor)
Gunakan Supervisor untuk menjalankan queue worker agar pemrosesan latar belakang tetap berjalan.
```ini
[program:yualan-worker]
command=php /path-ke-proyek-anda/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path-ke-proyek-anda/storage/logs/worker.log
```

---

## Verifikasi Instalasi
Akses aplikasi melalui browser (default development: `http://localhost:8000`).
1. **Halaman Welcome**: Pastikan muncul branding "Yualan Community Edition".
2. **Registrasi**: Daftarkan akun bisnis baru (Role: Admin).
3. **Login**: Masuk ke dashboard dan pastikan semua menu dapat diakses.

---

## Troubleshooting Singkat

**Masalah Izin File (Permission):**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Vite Manifest Not Found:**
Pastikan Anda sudah menjalankan `npm run build` jika berada di lingkungan produksi.

---

## Langkah Selanjutnya
- [Panduan Developer](development-guide.md)
- [Kebutuhan Server](server-requirement.md)
- [Strategi Deployment](deployment.md)

**Created Under PT. Nusavasoft Digital Solutions**
