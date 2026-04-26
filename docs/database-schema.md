# Skema Database (Database Schema)

Yualan Community Edition menggunakan skema database yang dirancang untuk integritas data tingkat enterprise. Meskipun berjalan sebagai instansi tunggal, sistem ini mempertahankan struktur **Tenant Isolation** untuk memudahkan manajemen data dan potensi skalabilitas di masa depan.

---

## Tabel Utama (Core Tables)

### 1. tenants
Menyimpan informasi entitas bisnis utama.
- `id`: UUID (Primary Key)
- `name`: Nama Bisnis
- `slug`: URL Unique identifier
- `email`: Email Bisnis
- `business_type`: Tipe bisnis (Toko, Restoran, dll)
- `is_active`: Status aktifitas bisnis

### 2. users
Menyimpan data pengguna (Pemilik & Kasir).
- `id`: UUID (Primary Key)
- `tenant_id`: Relasi ke tabel tenants
- `name`: Nama Pengguna
- `email`: Email login
- `role`: Role akses (`admin`, `cashier`)

---

## Manajemen Produk

### 3. categories
Kategori produk untuk pengelompokan.
- `id`: UUID
- `tenant_id`: Relasi ke tenants
- `name`: Nama Kategori

### 4. products
Data master barang/produk.
- `id`: UUID
- `category_id`: Relasi ke kategori
- `name`: Nama Produk
- `sku`: Kode unik barang (Stock Keeping Unit)
- `price`: Harga jual
- `cost_price`: Harga modal (HPP)
- `stock`: Stok saat ini

### 5. suppliers
Data pemasok barang.
- `id`: UUID
- `name`: Nama Supplier
- `contact`: Informasi kontak

---

## Transaksi & Penjualan

### 6. sales
Header transaksi penjualan.
- `id`: UUID
- `invoice_number`: Nomor faktur unik
- `total_amount`: Total nilai transaksi
- `payment_method`: Metode pembayaran (Tunai/Bank)
- `status`: Status transaksi (`completed`, `cancelled`)

### 7. sale_items
Detail produk dalam satu transaksi penjualan.
- `sale_id`: Relasi ke header sales
- `product_id`: Relasi ke produk
- `quantity`: Jumlah yang dibeli
- `price`: Harga saat transaksi

### 8. payments
Catatan pembayaran transaksi.
- `sale_id`: Relasi ke sales
- `amount`: Jumlah bayar
- `status`: Status pembayaran

---

## Inventaris

### 9. inventories
Log pergerakan stok (Stock Card).
- `product_id`: Relasi ke produk
- `quantity_change`: Perubahan jumlah (+/-)
- `type`: Jenis pergerakan (`in`, `out`, `adjustment`)

---

## Aturan Integritas Data

1. **UUID**: Semua tabel utama wajib menggunakan UUID untuk menghindari tabrakan ID dan meningkatkan keamanan.
2. **Soft Deletes**: Produk, Kategori, dan User menggunakan *Soft Deletes* agar data historis transaksi tetap terjaga meskipun data master dihapus.
3. **Audit Trails**: Setiap transaksi mencatat waktu (`created_at`) dan pengguna yang melakukan aksi (`user_id`).

---

**Created Under PT. Nusavasoft Digital Solutions**
