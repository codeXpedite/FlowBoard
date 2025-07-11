# FlowBoard Deployment Guide

This guide covers different deployment options for FlowBoard in production environments.

## 🚀 Quick Deployment Options

### Option 1: Traditional Server Deployment

#### Prerequisites
- Linux server (Ubuntu 20.04+ recommended)
- PHP 8.2+
- Composer
- Node.js 18+
- Web server (Nginx/Apache)
- Database (MySQL 8.0+ or PostgreSQL 13+)
- Redis (optional, for caching and queues)

#### Step-by-Step Deployment

1. **Prepare the Server**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install PHP and extensions
   sudo apt install php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-gd php8.2-zip php8.2-redis
   
   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   
   # Install Node.js
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt install nodejs
   ```

2. **Clone and Setup Application**
   ```bash
   # Clone repository
   cd /var/www
   sudo git clone https://github.com/CodeXpedite/flowboard.git
   cd flowboard
   
   # Install dependencies
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   
   # Set permissions
   sudo chown -R www-data:www-data /var/www/flowboard
   sudo chmod -R 755 /var/www/flowboard/storage
   sudo chmod -R 755 /var/www/flowboard/bootstrap/cache
   ```

3. **Environment Configuration**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   
   # Edit environment variables
   nano .env
   ```

4. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE flowboard;
   CREATE USER 'flowboard'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON flowboard.* TO 'flowboard'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   # Run migrations
   php artisan migrate --force
   php artisan db:seed --force
   ```

5. **Nginx Configuration**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /var/www/flowboard/public;
   
       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";
   
       index index.php;
   
       charset utf-8;
   
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
   }
   ```

6. **Process Management (Supervisor)**
   ```bash
   # Install Supervisor
   sudo apt install supervisor
   
   # Create queue worker configuration
   sudo nano /etc/supervisor/conf.d/flowboard-worker.conf
   ```
   
   ```ini
   [program:flowboard-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/flowboard/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=8
   redirect_stderr=true
   stdout_logfile=/var/www/flowboard/storage/logs/worker.log
   ```

7. **SSL Certificate (Let's Encrypt)**
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d your-domain.com
   ```

### Option 2: Docker Deployment

1. **Using Docker Compose**
   ```bash
   # Clone repository
   git clone https://github.com/CodeXpedite/flowboard.git
   cd flowboard
   
   # Copy environment file
   cp .env.example .env
   
   # Edit environment variables for Docker
   nano .env
   
   # Build and start containers
   docker-compose up -d
   
   # Run migrations
   docker-compose exec app php artisan migrate --force
   docker-compose exec app php artisan db:seed --force
   ```

2. **Environment Variables for Docker**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=database
   DB_PORT=3306
   DB_DATABASE=flowboard
   DB_USERNAME=flowboard
   DB_PASSWORD=secret
   
   REDIS_HOST=redis
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```

### Option 3: Cloud Platform Deployment

#### Laravel Forge
1. Connect your server to Laravel Forge
2. Create a new site pointing to your repository
3. Configure environment variables
4. Enable queue workers
5. Set up SSL certificate

#### DigitalOcean App Platform
1. Connect your GitHub repository
2. Configure build and run commands
3. Set environment variables
4. Add database and Redis components

#### AWS/Azure/GCP
1. Use container services (ECS, Container Instances, Cloud Run)
2. Set up managed databases
3. Configure load balancers
4. Implement auto-scaling

## 🔧 Production Configuration

### Required Environment Variables

```env
# Application
APP_NAME="FlowBoard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=flowboard
DB_USERNAME=flowboard_user
DB_PASSWORD=secure_password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=noreply@your-domain.com

# GitHub Integration
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_WEBHOOK_SECRET=your_webhook_secret

# Push Notifications
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@your-domain.com
```

### Performance Optimization

1. **Enable Opcache**
   ```php
   ; /etc/php/8.2/fpm/conf.d/10-opcache.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=4000
   opcache.revalidate_freq=2
   opcache.fast_shutdown=1
   ```

2. **Configure PHP-FPM**
   ```ini
   ; /etc/php/8.2/fpm/pool.d/www.conf
   pm = dynamic
   pm.max_children = 50
   pm.start_servers = 20
   pm.min_spare_servers = 10
   pm.max_spare_servers = 30
   ```

3. **Database Optimization**
   ```sql
   -- Enable query cache
   SET GLOBAL query_cache_size = 268435456;
   SET GLOBAL query_cache_type = ON;
   
   -- Optimize buffer sizes
   SET GLOBAL innodb_buffer_pool_size = 1073741824;
   ```

### Security Hardening

1. **File Permissions**
   ```bash
   find /var/www/flowboard -type f -exec chmod 644 {} \;
   find /var/www/flowboard -type d -exec chmod 755 {} \;
   chmod -R 775 /var/www/flowboard/storage
   chmod -R 775 /var/www/flowboard/bootstrap/cache
   ```

2. **Hide Server Information**
   ```nginx
   # Add to nginx.conf
   server_tokens off;
   ```

3. **Rate Limiting**
   ```nginx
   # Add to nginx server block
   limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
   
   location /login {
       limit_req zone=login burst=5 nodelay;
   }
   ```

## 📊 Monitoring & Maintenance

### Health Checks
```bash
# Create health check endpoint
php artisan make:command HealthCheck
```

### Log Management
```bash
# Rotate logs daily
sudo nano /etc/logrotate.d/flowboard
```

```
/var/www/flowboard/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### Backup Strategy
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/flowboard"

# Database backup
mysqldump -u flowboard -p flowboard > $BACKUP_DIR/db_$DATE.sql

# File backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/flowboard

# Cleanup old backups (keep 30 days)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### Performance Monitoring

1. **Application Performance**
   ```bash
   # Install monitoring tools
   composer require laravel/telescope --dev
   php artisan telescope:install
   php artisan migrate
   ```

2. **Server Monitoring**
   - CPU and memory usage
   - Disk space monitoring
   - Network traffic analysis
   - Database performance metrics

## 🚨 Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   sudo chown -R www-data:www-data /var/www/flowboard
   sudo chmod -R 775 storage bootstrap/cache
   ```

2. **Queue Workers Not Processing**
   ```bash
   # Restart queue workers
   sudo supervisorctl restart flowboard-worker:*
   ```

3. **Database Connection Issues**
   ```bash
   # Test database connection
   php artisan tinker
   DB::connection()->getPdo();
   ```

4. **Cache Issues**
   ```bash
   # Clear all caches
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Getting Help

- Check application logs: `/var/www/flowboard/storage/logs/`
- Monitor queue jobs: `php artisan queue:monitor`
- Database queries: `php artisan db:monitor`
- Security logs: `/var/www/flowboard/storage/logs/security.log`

For additional support, please visit our [GitHub Issues](https://github.com/CodeXpedite/flowboard/issues) page.