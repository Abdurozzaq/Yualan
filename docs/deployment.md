# Deployment Guide

## Overview

Panduan ini menjelaskan cara deploy Yualan POS ke production environment. Support deployment untuk VPS, shared hosting, dan cloud platforms seperti AWS, DigitalOcean, dan Vercel.

## Prerequisites

**Software Requirements:**
- **OS**: Ubuntu 20.04+ / CentOS 8+ / RHEL 8+
- **PHP**: 8.2 atau lebih tinggi
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite 3
- **Node.js**: 18.x atau 20.x
- **Composer**: 2.0+
- **SSL Certificate**: Required untuk production

---

## Deployment ke VPS (Ubuntu)

### 1. Server Setup

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y software-properties-common curl wget git unzip

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 dan extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-pgsql php8.2-sqlite3 \
    php8.2-curl php8.2-dom php8.2-gd php8.2-intl php8.2-json php8.2-mbstring \
    php8.2-openssl php8.2-pdo php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Nginx
sudo apt install -y nginx

# Install MySQL (opsional)
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis untuk caching (recommended)
sudo apt install -y redis-server
sudo systemctl enable redis-server
```

### 2. Database Setup

#### MySQL Setup

```bash
# Masuk ke MySQL
sudo mysql -u root -p

# Buat database dan user
CREATE DATABASE yualan_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'yualan'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON yualan_pos.* TO 'yualan'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### PostgreSQL Setup (Alternative)

```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Buat database dan user
sudo -u postgres createdb yualan_pos
sudo -u postgres createuser --interactive yualan
sudo -u postgres psql -c "ALTER USER yualan PASSWORD 'strong_password_here';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE yualan_pos TO yualan;"
```

### 3. Deploy Application

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/Abdurozzaq/Yualan.git yualan
cd yualan

# Set ownership
sudo chown -R www-data:www-data /var/www/yualan
sudo chmod -R 755 /var/www/yualan
sudo chmod -R 775 /var/www/yualan/storage
sudo chmod -R 775 /var/www/yualan/bootstrap/cache

# Install PHP dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
sudo -u www-data npm ci --only=production

# Copy environment file
sudo -u www-data cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate
```

### 4. Environment Configuration

Edit file `.env`:

```bash
sudo nano .env
```

```env
APP_NAME="Yualan POS"
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yualan_pos
DB_USERNAME=yualan
DB_PASSWORD=strong_password_here

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=yourdomain.com

CACHE_STORE=redis
CACHE_PREFIX=yualan

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Yualan POS"

# iPaymu Configuration (Production)
MOVED TO DATABASE
```

### 5. Database Migration

```bash
# Jalankan migrations
sudo -u www-data php artisan migrate --force

# Seed initial data (opsional)
sudo -u www-data php artisan db:seed --class=InitialSeeder
```

### 6. Build Assets

```bash
# Build production assets
sudo -u www-data npm run build

# Create storage link
sudo -u www-data php artisan storage:link

# Optimize application
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 7. Nginx Configuration

```bash
# Buat konfigurasi Nginx
sudo nano /etc/nginx/sites-available/yualan
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/yualan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Security headers
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';";

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Increase upload limits
    client_max_body_size 20M;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/yualan /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### 8. SSL Certificate dengan Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 9. Process Management (Supervisor)

#### Setup Queue Workers

```bash
# Install supervisor
sudo apt install -y supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/yualan-worker.conf
```

```ini
[program:yualan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yualan/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/yualan/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yualan-worker:*

# Check status
sudo supervisorctl status
```

#### Setup Scheduler

```bash
# Add cron job untuk Laravel scheduler
sudo crontab -e -u www-data

# Tambahkan baris berikut:
* * * * * cd /var/www/yualan && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Security Hardening

#### Firewall Setup

```bash
# Install UFW
sudo apt install -y ufw

# Basic rules
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH, HTTP, HTTPS
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

#### Additional Security

```bash
# Disable unused PHP functions
sudo nano /etc/php/8.2/fpm/php.ini

# Find dan edit:
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## Deployment ke Shared Hosting

### 1. Persiapan

```bash
# Build assets di lokal
npm run build

# Optimize untuk production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Upload Files

Upload semua files kecuali:
- `node_modules/`
- `.git/`
- `.env` (upload manual)
- `storage/logs/` (buat folder kosong)

### 3. Shared Hosting Configuration

Buat file `.htaccess` di root domain:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Edit `public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
```

### 4. Database Setup

Gunakan database yang disediakan hosting dan update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost  # atau IP yang diberikan hosting
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

---

## Deployment ke Cloud Platforms

### AWS EC2

#### 1. Launch EC2 Instance

```bash
# Amazon Linux 2 / Ubuntu 20.04
# t3.micro untuk testing, t3.small+ untuk production

# Security Group:
# - SSH (22) dari IP Anda
# - HTTP (80) dari 0.0.0.0/0
# - HTTPS (443) dari 0.0.0.0/0
```

#### 2. Setup sama seperti VPS Ubuntu

#### 3. Load Balancer (untuk multiple instances)

```bash
# Create Application Load Balancer
# Target group untuk EC2 instances
# Health check: /api/health
```

#### 4. RDS Database

```bash
# Create RDS MySQL/PostgreSQL instance
# Update .env dengan RDS endpoint
```

#### 5. S3 untuk File Storage

```env
# .env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### DigitalOcean

#### 1. Droplet Setup

```bash
# Ubuntu 20.04 droplet
# 1GB RAM minimum, 2GB recommended

# Follow VPS Ubuntu deployment steps
```

#### 2. DigitalOcean Spaces (optional)

```env
# .env untuk DO Spaces
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_spaces_key
AWS_SECRET_ACCESS_KEY=your_spaces_secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=your-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
```

#### 3. Managed Database

```env
# .env untuk DO Managed Database
DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=25060
DB_DATABASE=yualan_pos
DB_USERNAME=doadmin
DB_PASSWORD=your_db_password
DB_OPTIONS='{"sslmode":"require"}'
```

---

## CI/CD Setup

### GitHub Actions

Buat file `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '20'
        
    - name: Install PHP dependencies
      run: composer install --optimize-autoloader --no-dev
      
    - name: Install Node dependencies
      run: npm ci
      
    - name: Build assets
      run: npm run build
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/yualan
          git pull origin main
          composer install --optimize-autoloader --no-dev
          npm ci
          npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo supervisorctl restart yualan-worker:*
```

### GitLab CI

Buat file `.gitlab-ci.yml`:

```yaml
stages:
  - build
  - deploy

build:
  stage: build
  image: node:20
  script:
    - npm ci
    - npm run build
  artifacts:
    paths:
      - public/build/
    expire_in: 1 hour

deploy:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache rsync openssh
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
  script:
    - rsync -avz --delete --exclude-from='.rsyncignore' . user@server:/var/www/yualan/
    - ssh user@server "cd /var/www/yualan && php artisan migrate --force && php artisan config:cache"
  only:
    - main
```

---

## Monitoring & Maintenance

### Log Monitoring

```bash
# Setup log rotation
sudo nano /etc/logrotate.d/yualan

# Isi:
/var/www/yualan/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
}
```

### Health Checks

Buat endpoint health check:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::store()->getStore() instanceof \Illuminate\Contracts\Cache\Store ? 'connected' : 'disconnected',
    ]);
});
```

### Performance Monitoring

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor processes
htop

# Monitor disk I/O
sudo iotop

# Monitor network
sudo nethogs
```

### Backup Strategy

```bash
# Database backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/yualan"
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u yualan -p'password' yualan_pos | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/yualan/storage/app/public

# Keep only last 7 days
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

# Tambahkan ke crontab
# 0 2 * * * /path/to/backup.sh
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/yualan
sudo chmod -R 755 /var/www/yualan
sudo chmod -R 775 /var/www/yualan/storage /var/www/yualan/bootstrap/cache
```

#### 2. Storage Link Issues

```bash
# Remove existing link
rm /var/www/yualan/public/storage

# Create new link
sudo -u www-data php artisan storage:link
```

#### 3. Queue Not Working

```bash
# Restart queue workers
sudo supervisorctl restart yualan-worker:*

# Check worker logs
tail -f /var/www/yualan/storage/logs/worker.log
```

#### 4. SSL Certificate Issues

```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew

# Check Nginx config
sudo nginx -t
```

### Performance Issues

#### 1. High Memory Usage

```bash
# Optimize PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Adjust:
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
```

#### 2. Slow Database Queries

```bash
# Enable slow query log
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add:
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

#### 3. High Disk Usage

```bash
# Clean old logs
php artisan log:clear

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Security Updates

### Regular Updates

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Composer dependencies
composer update --with-dependencies

# Update Node.js dependencies
npm update
```

### Security Monitoring

```bash
# Install fail2ban
sudo apt install -y fail2ban

# Configure fail2ban
sudo nano /etc/fail2ban/jail.local

# Isi:
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true

[nginx-http-auth]
enabled = true
```

Panduan deployment ini mencakup setup production-ready untuk berbagai platform. Sesuaikan konfigurasi sesuai kebutuhan dan skala aplikasi Anda.
