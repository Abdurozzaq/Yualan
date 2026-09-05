# Troubleshooting (Panduan Solusi Masalah)

Dokumen ini membantu Anda mengatasi masalah umum yang mungkin muncul selama instalasi atau operasional **Yualan Community Edition (Dedicated Enterprise)**.

---

## Masalah Instalasi

### 1. Izin Folder (Permission Denied)
**Gejala**: Error 500 saat pertama kali dibuka atau log tidak dapat ditulis.
**Solusi**:
Jalankan perintah berikut di terminal server Anda untuk memberikan hak akses yang tepat kepada web server:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Database Connection Refused
**Gejala**: `Illuminate\Database\QueryException: Connection refused`
**Solusi**:
- Pastikan servis PostgreSQL sedang berjalan: `sudo systemctl status postgresql`.
- Periksa file `.env` Anda, pastikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sudah benar.
- Jika database berada di server berbeda, pastikan firewall mengizinkan koneksi ke port 5432.

### 3. Vite Manifest Not Found
**Gejala**: Error yang menyatakan file manifest aset frontend tidak ditemukan.
**Solusi**:
Anda perlu mengompilasi aset frontend untuk lingkungan produksi:
```bash
npm install
npm run build
```

---

## Masalah Operasional

### 1. Laporan Tidak Muncul (Kosong)
**Gejala**: Laporan Penjualan Detail atau Pembayaran tampil kosong padahal ada transaksi.
**Solusi**:
- Secara default, laporan di Community Edition diatur untuk menampilkan data "All Time". Jika masih kosong, periksa filter tanggal di bagian atas halaman laporan.
- Pastikan `APP_TIMEZONE` di `.env` sudah diatur ke `Asia/Jakarta` agar pencatatan waktu transaksi akurat.

### 2. Tugas Otomatis (Scheduler) Tidak Berjalan
**Gejala**: Status langganan tidak terupdate otomatis atau tugas latar belakang lainnya macet.
**Solusi**:
- Pastikan cron job sudah terpasang: `crontab -l`.
- Jika belum, ikuti panduan di [Scheduler Guide](scheduler.md).
- Periksa log untuk melihat apakah scheduler pernah dipicu: `tail -f storage/logs/laravel.log`.

---

## Debugging Tingkat Lanjut

Jika masalah masih berlanjut, Anda dapat mengaktifkan mode debug untuk melihat detail error yang lebih lengkap:

1. Ubah `.env`:
   ```env
   APP_DEBUG=true
   ```
2. Muat ulang halaman aplikasi.
3. **PENTING**: Segera kembalikan ke `false` setelah selesai debugging demi keamanan data Anda.

---

**Butuh Bantuan Lebih Lanjut?**
Jika Anda menemukan bug teknis yang belum ada solusinya di sini, silakan buka *issue* baru di repositori GitHub kami. Untuk dukungan komersial dan pemeliharaan server secara penuh, pertimbangkan untuk beralih ke **[Yualan Premium](https://yualan.web.id)**.

**Created Under PT. Nusavasoft Digital Solutions**
