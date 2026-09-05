# Panduan Developer (Development Guide)

Panduan ini ditujukan bagi pengembang yang ingin memodifikasi, menambah fitur, atau memahami arsitektur internal **Yualan Community Edition (Dedicated Enterprise)**.

---

## Filosofi Arsitektur

Yualan Community Edition menggunakan model **Dedicated Enterprise**. Meskipun secara teknis mendukung multi-tenancy dasar, versi ini dioptimalkan untuk berjalan sebagai satu instansi aplikasi mandiri bagi satu entitas bisnis.

### Teknologi Utama
- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Bahasa**: TypeScript (Frontend) & PHP (Backend)
- **Database**: PostgreSQL (Priority)
- **Styling**: Tailwind CSS & Vanilla CSS

---

## Standar Kode (Code Standards)

Kami mengikuti standar industri untuk menjaga kualitas dan keterbacaan kode.

### 1. PHP (Backend)
- Mengikuti standar **PSR-12**.
- Gunakan **Laravel Pint** untuk memformat kode secara otomatis:
  ```bash
  ./vendor/bin/pint
  ```
- Gunakan **Type Hinting** pada parameter fungsi dan return types.

### 2. Vue & TypeScript (Frontend)
- Gunakan **Composition API** dengan sintaks `<script setup lang="ts">`.
- Definisikan **Interfaces** untuk semua props dan data yang kompleks.
- Jalankan linting sebelum melakukan commit:
  ```bash
  npm run lint
  ```

---

## Struktur Proyek

### 1. Model & Database
Semua model menggunakan **UUID** sebagai primary key untuk keamanan dan skalabilitas.
```php
// app/Models/Product.php
protected $keyType = 'string';
public $incrementing = false;
```

### 2. Controllers & Routing
Gunakan **Resource Controllers** sesering mungkin untuk menjaga konsistensi alur data (CRUD).
- **Web Routes**: `routes/web.php` (Frontend via Inertia)
- **API Routes**: `routes/api.php` (Internal/External API)

### 3. Business Logic (Services)
Jangan menumpuk logika bisnis di Controller. Gunakan Service Classes di folder `app/Services` untuk logika yang kompleks seperti kalkulasi stok atau pemrosesan transaksi.

---

## Alur Pengembangan Fitur

1. **Database**: Buat migrasi baru menggunakan `php artisan make:migration`.
2. **Model**: Buat model terkait dengan factory: `php artisan make:model NamaModel -mf`.
3. **Frontend**: Buat komponen Vue di `resources/js/Pages`.
4. **Routing**: Daftarkan rute di `web.php`.
5. **Testing**: Buat test case untuk fitur tersebut di folder `tests/Feature`.

---

## Debugging & Monitoring

### Backend
- Gunakan `Log::info()` untuk mencatat data di `storage/logs/laravel.log`.
- Untuk debugging cepat, gunakan helper `dd()` atau `dump()`.

### Frontend
- Gunakan **Vue DevTools** di browser Anda.
- Pantau tab Network di Browser DevTools untuk melihat data yang dikirim/diterima via Inertia/XHR.

---

## Kontribusi

Kami sangat terbuka untuk kontribusi dari komunitas!
1. **Fork** repositori ini.
2. Buat branch fitur baru (`feature/fitur-keren`).
3. Kirimkan **Pull Request** dengan deskripsi perubahan yang jelas.

Pastikan kode Anda sudah melalui pengecekan tipe dan format:
```bash
npm run type-check
./vendor/bin/pint
```

---

**Butuh Fitur Lebih Lanjut?**
Beberapa fitur tingkat lanjut seperti analisis laba bersih mendalam dan manajemen multi-user lanjutan hanya tersedia di **[Yualan Premium](https://yualan.web.id)**.

**Created Under PT. Nusavasoft Digital Solutions**
