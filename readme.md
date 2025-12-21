# 🚀 Yualan POS (Community Edition)

![Yualan POS Banner](https://github.com/user-attachments/assets/1057787c-9f71-444e-ab98-86a7fdea9b69)

**Open Source Point of Sale untuk Belajar, Eksperimen, dan Implementasi Dasar**

---

## 👋 Tentang Yualan POS Community Edition

**Yualan POS Community Edition (CE)** adalah versi **open source** dari Yualan POS yang dirilis untuk **komunitas, pembelajaran, riset, dan penggunaan internal skala kecil**.

Versi ini cocok untuk:
- Developer yang ingin belajar arsitektur POS modern
- Kampus / sekolah / lab praktikum
- UMKM untuk uji coba internal
- Proof of Concept (PoC) atau demo

⚠️ **Untuk penggunaan bisnis serius dan operasional harian, gunakan versi Premium:**  
👉 https://yualan.web.id

---

## ✨ Fitur Utama (Community Edition)

### 🛍️ Manajemen Penjualan Lengkap
- **Checkout Cepat** - Proses transaksi dalam hitungan detik
- **Multi-Metode Pembayaran** - Tunai, QRIS, E-Wallet (iPaymu & Midtrans)
- **Manajemen Produk** - Kelola SKU, kategori, harga, dan stok dengan mudah
- **Customer Management** - Bangun database pelanggan dan loyalitas

### 📊 Manajemen Inventaris Cerdas
- **Real-Time Stock Tracking** - Pantau stok secara live across semua outlet
- **Supplier Management** - Kelola rantai pasokan dengan efisien
- **Stock Adjustment** - Koreksi stok dengan antarmuka intuitif
- **Inventory Reports** - Laporan nilai stok dan pergerakan barang

### 👥 Multi-Level Access Control
- **Superadmin** - Kontrol penuh atas seluruh sistem dan tenant
- **Admin/Pemilik Toko** - Kelola operasional toko dan laporan keuangan
- **Kasir** - Akses terbatas untuk transaksi harian

### 🧱 Teknologi
- Laravel 12 + PHP 8.2+
- Vue 3 + Inertia
- Tailwind CSS
- Multi-tenant (basic)

---

## ⚠️ LIMITASI COMMUNITY EDITION

### 🔄 Update & Maintenance
- Update resmi **1x dalam 1 tahun**
- Tidak ada security patch prioritas
- Tidak ada SLA

### 🧑‍💻 Support
- Tidak ada support teknis
- Tidak ada onboarding
- Dokumentasi terbatas

---

## 📜 Lisensi GPL v2

Community Edition menggunakan **GNU GPL v2**, artinya:
- Bebas digunakan & dimodifikasi
- Wajib membuka source code jika didistribusikan
- Tidak ada garansi
- Tidak disarankan untuk SaaS komersial tertutup

---

## 🚀 Yualan POS Premium (RECOMMENDED)

Gunakan **Yualan POS Premium** untuk kebutuhan bisnis:
- Update rutin
- Security patch
- Fitur lengkap
- Support resmi
- Aman untuk komersial

🌐 https://yualan.web.id

---

## 🛠️ Instalasi

```bash
git clone https://github.com/Abdurozzaq/Yualan.git
cd Yualan
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

---

### TL;DR
Community Edition = belajar & eksperimen  
Premium = bisnis & produksi
