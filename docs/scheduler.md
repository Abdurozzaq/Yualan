# Panduan Scheduler (Automated Tasks)

Yualan Community Edition menggunakan **Laravel Scheduler** untuk menjalankan tugas-tugas rutin di latar belakang. Pengaturan ini sangat krusial untuk menjaga performa dan fungsionalitas otomatis aplikasi.

---

## Daftar Tugas Otomatis

### 1. Sinkronisasi Status Langganan
**Perintah**: `tenant:update-subscription-status`
**Jadwal**: Harian (Daily)
**Tujuan**: Memeriksa masa berlaku instalasi dan mengupdate status akses jika diperlukan.

---

## Cara Konfigurasi (Server Linux)

Untuk mengaktifkan penjadwalan otomatis, Anda perlu menambahkan satu entri ke dalam sistem **crontab** server Anda.

1. Buka konfigurasi crontab:
   ```bash
   crontab -e
   ```

2. Tambahkan baris berikut di bagian paling bawah:
   ```cron
   * * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> /dev/null 2>&1
   ```
   *Ganti `/path-ke-proyek-anda` dengan lokasi folder instalasi Yualan di server Anda.*

3. Simpan dan keluar. Sistem sekarang akan memicu scheduler Laravel setiap menit.

---

## Pemantauan (Monitoring)

### Memeriksa Log
Aktivitas scheduler dicatat dalam log Laravel standar. Anda dapat memantaunya dengan:
```bash
tail -f storage/logs/laravel.log | grep "schedule"
```

### Menjalankan Manual
Jika Anda ingin menjalankan semua tugas yang dijadwalkan saat ini juga untuk pengujian:
```bash
php artisan schedule:run
```

### Daftar Jadwal
Untuk melihat daftar tugas yang sedang aktif dan kapan jadwal berikutnya:
```bash
php artisan schedule:list
```

---

## Praktik Terbaik (Best Practices)

1. **User Permission**: Pastikan crontab dijalankan oleh user yang memiliki izin menulis ke folder `storage`, biasanya user `www-data`.
2. **Output Logging**: Jika Anda ingin mencatat hasil scheduler ke file terpisah untuk debugging:
   ```cron
   * * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
   ```

---

**Butuh Pemrosesan Latar Belakang Lebih Cepat?**
Gunakan **Redis** sebagai antrian (*queue*) untuk performa yang lebih responsif. Konfigurasi ini sangat disarankan untuk penggunaan skala enterprise.

**Created Under PT. Nusavasoft Digital Solutions**
