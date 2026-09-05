# Panduan Deployment (Dedicated Enterprise)

Panduan ini menjelaskan langkah-langkah untuk melakukan deployment **Yualan Community Edition (Dedicated Enterprise)** ke lingkungan produksi. Fokus utama panduan ini adalah penggunaan **VPS (Virtual Private Server)** atau **Dedicated Server** untuk menjamin performa tingkat enterprise.

---

## Prasyarat Server (Prerequisites)

Sebelum memulai, pastikan server Anda memiliki komponen berikut:
- **OS**: Ubuntu 22.04 LTS atau 24.04 LTS (Direkomendasikan).
- **PHP**: 8.2+ dengan ekstensi `pdo_pgsql`, `mbstring`, `intl`, `gd`, `zip`, `bcmath`.
- **Database**: PostgreSQL 15+.
- **Web Server**: Nginx (Pilihan Utama).
- **SSL**: Sertifikat SSL aktif (Let's Encrypt).
- **Node.js**: 20.x untuk kompilasi aset.

---

## Langkah Deployment di Ubuntu VPS

### 1. Persiapan Lingkungan
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip nginx postgresql postgresql-contrib supervisor redis-server
```

### 2. Konfigurasi Database PostgreSQL
```bash
# Masuk ke prompt postgres
sudo -u postgres psql

# Buat database dan user
CREATE DATABASE yualan_db;
CREATE USER yualan_user WITH PASSWORD 'PasswordKuatAnda';
GRANT ALL PRIVILEGES ON DATABASE yualan_db TO yualan_user;
\q
```

### 3. Instalasi Aplikasi
```bash
cd /var/www
sudo git clone https://github.com/Abdurozzaq/Yualan.git yualan
cd yualan

# Instalasi Dependensi
sudo composer install --optimize-autoloader --no-dev
sudo npm install
sudo npm run build

# Atur Izin File
sudo chown -R www-data:www-data /var/www/yualan
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Konfigurasi Environment (.env)
```bash
sudo cp .env.example .env
sudo nano .env
```
Sesuaikan parameter berikut:
```env
APP_NAME="Yualan POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=yualan_db
DB_USERNAME=yualan_user
DB_PASSWORD=PasswordKuatAnda

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```
Setelah simpan, jalankan:
```bash
php artisan key:generate
php artisan migrate --force --seed
```

### 5. Konfigurasi Nginx
Buat file konfigurasi: `/etc/nginx/sites-available/yualan`
```nginx
server {
    listen 80;
    server_name domain-anda.com;
    root /var/www/yualan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Aktifkan dan pasang SSL:
```bash
sudo ln -s /etc/nginx/sites-available/yualan /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl restart nginx
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d domain-anda.com
```

---

## Optimalisasi Performa Produksi

Untuk memastikan aplikasi berjalan secepat kilat di server Anda, jalankan perintah caching berikut:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Pemeliharaan (Maintenance)

### 1. Update Aplikasi
Jika ada pembaruan dari repositori resmi:
```bash
git pull origin main
composer install --no-dev
php artisan migrate --force
npm run build
php artisan optimize
```

### 2. Backup Database
Lakukan backup rutin PostgreSQL Anda:
```bash
pg_dump -U yualan_user yualan_db > backup_$(date +%F).sql
```

---

**Butuh Dukungan Teknis Profesional?**
Jika Anda mengelola bisnis skala besar dan membutuhkan dukungan deployment kustom atau SLA, silakan hubungi kami di **[yualan.web.id](https://yualan.web.id)** untuk opsi upgrade ke **Yualan Premium**.

**Created Under PT. Nusavasoft Digital Solutions**
