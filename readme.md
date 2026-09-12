# Yualan Community Edition (Dedicated Enterprise)

Solusi Point of Sale (POS) *self-hosted* dan *single-tenant* untuk instansi yang membutuhkan kendali penuh atas data dan infrastruktur server.

---

## Gambaran Umum

Yualan Community Edition dirancang untuk memberikan kedaulatan data secara mutlak dan akses database langsung tanpa ketergantungan pada pihak ketiga.

### Tangkapan Layar
* Dashboard Analytics: `public/dashboard.png`
* Antarmuka Kasir: `public/ordering.png`

---

## Fitur Utama

### POS & Penjualan
* **Transaksi Cepat** - Alur kerja kasir yang optimal dan responsif.
* **Manajemen Produk** - Dukungan SKU, kategori kompleks, dan satuan unit.
* **Promo & Voucher** - Pengaturan diskon dan promosi mandiri.
* **CRM Pelanggan** - Pengelolaan database pelanggan secara privat.

### Inventaris & Rantai Pasokan
* **Stock Card Real-Time** - Riwayat pergerakan barang secara mendetail.
* **Penerimaan & Penyesuaian** - Modul stok masuk dan penyesuaian akurat.
* **Direktori Supplier** - Pengelolaan data pemasok barang.

### Pelaporan
* **Laporan Penjualan** - Akses data transaksi mentah untuk audit.
* **Manajemen Keuangan** - Pemantauan arus kas dan piutang.

---

## Teknologi Inti
* **Backend**: Laravel 12 (PHP 8.3+)
* **Frontend**: Vue 3 + Inertia.js (SPA)
* **Database**: SQLite / PostgreSQL 15+
* **Styling**: Tailwind CSS

---

## Instalasi Server

Membutuhkan PHP 8.3+, Node.js 20+, dan opsional PostgreSQL 15+.

### Metode 1: Composer
```bash
composer create-project abdurozzaq/yualan --stability alpha
cd yualan
npm install && npm run build
```

### Metode 2: Docker
```bash
docker run -d -p 8627:80 --name yualan-app rozzaqnh/yualan-community-edition:latest
```
Akses melalui `http://localhost:8627`.

### Metode 3: Manual Git Clone
```bash
git clone https://github.com/Abdurozzaq/Yualan.git
cd Yualan
composer install --optimize-autoloader --no-dev
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed --force
npm run build
```

### Metode 4: Build Aplikasi Desktop (Windows)
Aplikasi ini juga dapat dibuild menjadi aplikasi desktop mandiri (installer `.exe`) menggunakan NativePHP. 
Pastikan Anda sudah menginstall dependensi PHP & Node.js, kemudian jalankan:
```bash
php artisan native:build win x64
```
Hasil build installer akan tersimpan di dalam direktori `nativephp/electron/dist/`.

---

## Alternatif Terkelola

Bagi yang menginginkan infrastruktur, SSL, pemeliharaan, serta fitur lanjutan (Laba Bersih & Manajemen Karyawan) tanpa repot kelola server, tersedia **Yualan Premium** di [yualan.web.id](https://yualan.web.id).

---

## Lisensi

Dilisensikan di bawah **GNU GPL v2**.

**PT. Nusavasoft Digital Solutions**