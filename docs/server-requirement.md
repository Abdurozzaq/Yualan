# Kebutuhan Server (Server Requirements)

Dokumen ini merinci spesifikasi teknis minimum dan yang direkomendasikan untuk menjalankan **Yualan Community Edition (Dedicated Enterprise)** secara optimal.

---

## Spesifikasi Perangkat Keras (Hardware)

Karena model Dedicated Enterprise berarti Anda mengelola satu instansi aplikasi secara eksklusif, spesifikasi berikut disesuaikan untuk performa maksimal pada satu entitas bisnis.

### 1. Lingkungan Pengembangan (Development)
- **RAM**: 4GB minimum (8GB direkomendasikan jika menjalankan Docker/Virtualization)
- **Penyimpanan**: 5GB ruang kosong (SSD sangat disarankan)
- **CPU**: Dual-core processor 2.0GHz+
- **Koneksi**: Diperlukan untuk instalasi dependensi via Composer dan NPM.

### 2. Lingkungan Produksi (Production)

**Skala Kecil (< 100 Transaksi/Hari)**
- **RAM**: 2GB (Minimal)
- **Penyimpanan**: 20GB SSD
- **CPU**: 1 vCPU
- **Bandwidth**: 100GB/bulan

**Skala Menengah (100 - 1000 Transaksi/Hari)**
- **RAM**: 4GB
- **Penyimpanan**: 50GB SSD
- **CPU**: 2 vCPU
- **Bandwidth**: 500GB/bulan

**Skala Besar (> 1000 Transaksi/Hari)**
- **RAM**: 8GB+
- **Penyimpanan**: 100GB+ SSD (NVMe direkomendasikan)
- **CPU**: 4 vCPU+
- **Bandwidth**: 1TB+/bulan
- **Arsitektur**: Disarankan menggunakan server database terpisah.

---

## Perangkat Lunak (Software)

### 1. PHP
**Versi**: PHP 8.2 atau lebih tinggi (Direkomendasikan PHP 8.3).

**Ekstensi PHP yang Wajib:**
- `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_pgsql` (Wajib untuk PostgreSQL), `tokenizer`, `xml`, `zip`.

**Konfigurasi PHP yang Direkomendasikan (php.ini):**
```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
display_errors = Off (Wajib Off di Produksi)
```

### 2. Database
**PostgreSQL 15+ (Sangat Direkomendasikan)**
Yualan Community Edition dioptimalkan untuk PostgreSQL guna menjamin integritas data tingkat enterprise.

**Alternatif:**
- **SQLite 3.35+**: Hanya disarankan untuk pengujian internal atau pengembangan lokal.
- **MySQL 8.0+**: Masih didukung namun PostgreSQL adalah prioritas pengembangan kami.

### 3. Web Server
- **Nginx 1.20+** (Pilihan Utama): Performa tinggi untuk aplikasi Laravel.
- **Apache 2.4+**: Didukung melalui modul `mod_rewrite`.

---

## Layanan Tambahan (Opsional tapi Direkomendasikan)

### 1. Redis 6.0+
Sangat disarankan untuk:
- **Caching**: Mempercepat pemuatan data produk dan kategori.
- **Session**: Manajemen sesi pengguna yang lebih cepat.
- **Queue**: Pemrosesan latar belakang untuk laporan dan notifikasi.

### 2. Supervisor (Linux)
Digunakan untuk memantau proses `queue:work` agar selalu berjalan di latar belakang. Jika worker mati, Supervisor akan otomatis menjalankannya kembali.

### 3. SSL (HTTPS)
Wajib digunakan di lingkungan produksi untuk keamanan data transaksi. Anda dapat menggunakan **Let's Encrypt** (Gratis) atau sertifikat SSL komersial.

---

## Keamanan & Firewall

Pastikan port berikut terbuka:
- **Port 80/443**: Untuk akses web (HTTP/HTTPS).
- **Port 22**: Untuk akses SSH (Gunakan kunci SSH dan port kustom jika memungkinkan).
- **Port 5432**: (Hanya jika server database terpisah) Pastikan hanya dapat diakses oleh IP server aplikasi.

---

**Butuh Solusi Tanpa Repot Konfigurasi Server?**
Jika Anda merasa spesifikasi di atas terlalu teknis, pertimbangkan untuk menggunakan **[Yualan Premium](https://yualan.web.id)** yang sudah termasuk hosting, maintenance, dan support penuh.

**Created Under PT. Nusavasoft Digital Solutions**