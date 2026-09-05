## 1. Di Server Linux/Unix (Produksi)

Buka crontab:
```bash
crontab -e
```

Tambahkan baris ini:
```bash
* * * * * cd /path/to/yualan && php artisan schedule:run >> /dev/null 2>&1
```

Ganti `/path/to/yualan` dengan path absolut ke project Anda di server.

## 2. Di Lingkungan Windows (Development)

Untuk Windows, Anda dapat menggunakan Task Scheduler atau PowerShell script untuk menjalankan scheduler setiap menit guna mensimulasikan lingkungan produksi.

## 3. Verifikasi
Test secara manual untuk memastikan scheduler terhubung ke database dengan benar:
```bash
php artisan schedule:run
```

Lihat daftar tugas yang dijadwalkan:
```bash
php artisan schedule:list
```

## 4. Penting
- Pastikan versi PHP CLI sama dengan versi PHP yang digunakan oleh web server.
- Pastikan user yang menjalankan cron memiliki izin tulis ke folder `storage`.

**Created Under PT. Nusavasoft Digital Solutions**
