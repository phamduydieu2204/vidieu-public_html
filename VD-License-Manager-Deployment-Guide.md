# VD License Manager - Deployment Guide

## 1. System Requirements

### 1.1 Server Requirements
- **Operating System:** Linux (Ubuntu 20.04+ or CentOS 8+ recommended)
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **PHP Version:** 7.4+ (PHP 8.0+ recommended)
- **MySQL Version:** 5.7+ or MariaDB 10.3+
- **WordPress Version:** 5.8+ (Latest stable recommended)
- **Memory:** Minimum 512MB RAM (2GB+ recommended for production)
- **Disk Space:** 100MB for plugin files + database storage
- **SSL Certificate:** Required for production environments

### 1.2 PHP Extensions Required
```bash
# Check required PHP extensions
php -m | grep -E "(openssl|curl|json|mysqli|zip|gd)"

# Required extensions:
- OpenSSL (for encryption)
- cURL (for HTTP requests)
- JSON (for data processing)
- MySQLi (for database connection)
- ZIP (for plugin updates)
- GD (for captcha/image processing - optional)
```

### 1.3 Database Requirements
```sql
-- Minimum MySQL configuration
mysql --version  # Should be 5.7 or higher

-- Check required privileges
SHOW GRANTS FOR 'wp_user'@'localhost';
-- Required: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
```

## 2. Pre-Installation Checklist

### 2.1 Environment Verification
```bash
#!/bin/bash
# deployment-check.sh

echo "=== VD License Manager Deployment Check ==="

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

# Check WordPress installation
if [ -f "wp-config.php" ]; then
    echo "✓ WordPress installation found"
else
    echo "✗ WordPress not found in current directory"
    exit 1
fi

# Check write permissions
if [ -w "wp-content/plugins/" ]; then
    echo "✓ Plugin directory writable"
else
    echo "✗ Plugin directory not writable"
    exit 1
fi

# Check database connection
php -r "
require_once 'wp-config.php';
try {
    new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    echo '✓ Database connection successful\n';
} catch (Exception \$e) {
    echo '✗ Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo "=== All checks passed! Ready for installation ==="
```

### 2.2 Security Configuration
```apache
# .htaccess security rules for plugin directory
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>

# Allow only specific files
<Files "index.php">
    Order Allow,Deny
    Allow from all
</Files>

# Protect sensitive configuration files
<Files "config.json">
    Order Allow,Deny
    Deny from all
</Files>
```

## 3. Installation Methods

### 3.1 WordPress Admin Installation (Recommended)
```php
// Installation via WordPress admin dashboard
1. Navigate to: Plugins > Add New
2. Click "Upload Plugin"
3. Select vd-license-manager.zip file
4. Click "Install Now"
5. Activate the plugin
6. Go to License Manager > Settings for configuration
```

### 3.2 Manual Installation
```bash
#!/bin/bash
# manual-install.sh

# Download and extract plugin
cd /path/to/wordpress/wp-content/plugins/
wget https://releases.vidieu.vn/vd-license-manager-latest.zip
unzip vd-license-manager-latest.zip
rm vd-license-manager-latest.zip

# Set correct permissions
chown -R www-data:www-data vd-license-manager/
chmod -R 755 vd-license-manager/
chmod -R 644 vd-license-manager/*.php

# Activate via WP-CLI (optional)
wp plugin activate vd-license-manager
```

### 3.3 Composer Installation
```json
{
    "name": "vidieu/license-manager-site",
    "require": {
        "vidieu/vd-license-manager": "^1.0"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://composer.vidieu.vn"
        }
    ]
}
```

```bash
# Install via Composer
composer install
wp plugin activate vd-license-manager
```

## 4. Database Setup

### 4.1 Automatic Database Creation
The plugin automatically creates required tables on activation:

```php
// Triggered automatically on plugin activation
register_activation_hook(__FILE__, ['VD_License_Manager', 'activate']);

// Tables created:
// - wp_vd_provider_accounts
// - wp_vd_content_versions
// - wp_vd_licenses
// - wp_vd_license_assignments
// - wp_vd_device_records
// - wp_vd_usage_logs
// - wp_vd_rate_limits
// - wp_vd_failed_attempts
// - wp_vd_audit_logs
// - wp_vd_config
// - wp_vd_product_provider_mapping
```

### 4.2 Manual Database Setup (If Needed)
```bash
#!/bin/bash
# manual-db-setup.sh

# Import database schema
mysql -u root -p your_database < vd-license-manager-schema.sql

# Or via WP-CLI
wp eval "
require_once 'wp-content/plugins/vd-license-manager/includes/class-vd-database.php';
\$db = new VD_Database();
\$db->create_tables();
echo 'Database tables created successfully';
"
```

### 4.3 Database Migration (Upgrades)
```php
// Version check and migration
function vd_check_db_version() {
    $current_version = get_option('vd_license_manager_db_version', '0.0.0');
    $required_version = '1.0.0';

    if (version_compare($current_version, $required_version, '<')) {
        vd_migrate_database($current_version, $required_version);
    }
}

// Migration script
function vd_migrate_database($from_version, $to_version) {
    global $wpdb;

    if (version_compare($from_version, '1.0.0', '<')) {
        // Add new columns for v1.0.0
        $wpdb->query("ALTER TABLE {$wpdb->prefix}vd_licenses
                     ADD COLUMN max_devices INT UNSIGNED NULL");
    }

    update_option('vd_license_manager_db_version', $to_version);
}
```

## 5. Configuration

### 5.1 Basic Configuration
```php
// wp-config.php additions
define('VD_LICENSE_MANAGER_DEBUG', false);
define('VD_LICENSE_RATE_LIMIT', 100);
define('VD_LICENSE_RISK_THRESHOLD', 70);
define('VD_LICENSE_ENCRYPTION_KEY', 'your-32-character-encryption-key-here');

// Environment-specific settings
if (WP_ENV === 'production') {
    define('VD_LICENSE_MANAGER_DEBUG', false);
    define('VD_LICENSE_STRICT_MODE', true);
} else {
    define('VD_LICENSE_MANAGER_DEBUG', true);
    define('VD_LICENSE_STRICT_MODE', false);
}
```

### 5.2 Environment Variables
```bash
# .env file for deployment
VD_LICENSE_DEBUG=false
VD_LICENSE_RATE_LIMIT=100
VD_LICENSE_RISK_THRESHOLD=70
VD_LICENSE_ENCRYPTION_KEY=your-encryption-key-here
VD_LICENSE_API_TIMEOUT=30

# Database settings (if different from WP)
VD_DB_HOST=localhost
VD_DB_NAME=license_manager
VD_DB_USER=vd_user
VD_DB_PASS=secure_password
```

### 5.3 Initial Settings Configuration
```bash
#!/bin/bash
# configure-plugin.sh

# Set initial options via WP-CLI
wp option update vd_default_duration 365
wp option update vd_default_max_devices 5
wp option update vd_assignment_strategy "least_loaded"
wp option update vd_rate_limit_hour 100
wp option update vd_rate_limit_day 1000
wp option update vd_risk_threshold 70
wp option update vd_block_high_risk 1

echo "Plugin configured with default settings"
```

## 6. SSL and Security Setup

### 6.1 SSL Certificate Installation
```nginx
# Nginx SSL configuration
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Protect plugin files
    location ~* /wp-content/plugins/vd-license-manager/.*\.php$ {
        deny all;
    }
}
```

### 6.2 Firewall Rules
```bash
# UFW firewall setup
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw --force enable

# Fail2ban configuration for license API
cat > /etc/fail2ban/filter.d/vd-license.conf << EOF
[Definition]
failregex = .*VD License Manager.*Rate limit exceeded.*<HOST>
            .*VD License Manager.*Invalid license.*<HOST>
ignoreregex =
EOF

cat > /etc/fail2ban/jail.d/vd-license.conf << EOF
[vd-license]
enabled = true
filter = vd-license
logpath = /var/log/wordpress/error.log
maxretry = 10
bantime = 3600
EOF
```

### 6.3 Security Headers
```php
// Add security headers
function vd_add_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
add_action('send_headers', 'vd_add_security_headers');
```

## 7. Performance Optimization

### 7.1 Caching Configuration
```php
// Redis cache setup (if available)
if (class_exists('Redis')) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    // Cache license data
    function vd_cache_license_data($license_key, $data) {
        global $redis;
        $redis->setex("vd_license_{$license_key}", 1800, serialize($data));
    }
}

// Object cache configuration
define('WP_CACHE', true);

// OpCache settings in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

### 7.2 Database Optimization
```sql
-- Create indexes for performance
CREATE INDEX idx_license_key ON wp_vd_licenses(license_key);
CREATE INDEX idx_device_fp ON wp_vd_device_records(device_fp);
CREATE INDEX idx_usage_timestamp ON wp_vd_usage_logs(timestamp);
CREATE INDEX idx_assignment_license ON wp_vd_license_assignments(license_key);

-- Optimize database tables
OPTIMIZE TABLE wp_vd_licenses;
OPTIMIZE TABLE wp_vd_usage_logs;
OPTIMIZE TABLE wp_vd_device_records;
```

### 7.3 CDN Configuration
```php
// CDN settings for static assets
define('VD_LICENSE_CDN_URL', 'https://cdn.yourdomain.com');

function vd_cdn_asset_url($asset_path) {
    if (defined('VD_LICENSE_CDN_URL')) {
        return VD_LICENSE_CDN_URL . '/' . ltrim($asset_path, '/');
    }
    return $asset_path;
}
```

## 8. Monitoring and Logging

### 8.1 Application Monitoring
```php
// Custom logging configuration
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/wordpress/vd-license-manager.log');

// Monitoring hooks
function vd_monitor_performance() {
    $start_time = microtime(true);

    // Your license resolution code here

    $execution_time = microtime(true) - $start_time;

    if ($execution_time > 1.0) { // Log slow queries
        error_log("VD License Manager: Slow query detected ({$execution_time}s)");
    }
}
```

### 8.2 Health Check Endpoint
```php
// Health check endpoint
add_action('rest_api_init', function() {
    register_rest_route('vd/v1', '/health', [
        'methods' => 'GET',
        'callback' => 'vd_health_check',
        'permission_callback' => '__return_true'
    ]);
});

function vd_health_check() {
    global $wpdb;

    $checks = [
        'database' => $wpdb->get_var("SELECT 1") === '1',
        'cache' => function_exists('wp_cache_get'),
        'ssl' => is_ssl(),
        'plugin_active' => is_plugin_active('vd-license-manager/vd-license-manager.php')
    ];

    $status = array_reduce($checks, function($carry, $check) {
        return $carry && $check;
    }, true);

    return [
        'status' => $status ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => current_time('mysql')
    ];
}
```

### 8.3 Log Rotation
```bash
# Logrotate configuration
cat > /etc/logrotate.d/vd-license-manager << EOF
/var/log/wordpress/vd-license-manager.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}
EOF
```

## 9. Backup and Recovery

### 9.1 Database Backup
```bash
#!/bin/bash
# backup-database.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups/vd-license-manager"
DB_NAME="your_wordpress_db"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup only VD License Manager tables
mysqldump -u root -p $DB_NAME \
    $(mysql -u root -p -e "SHOW TABLES FROM $DB_NAME LIKE 'wp_vd_%'" -s | tr '\n' ' ') \
    > $BACKUP_DIR/vd_license_manager_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/vd_license_manager_$DATE.sql

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: vd_license_manager_$DATE.sql.gz"
```

### 9.2 File Backup
```bash
#!/bin/bash
# backup-files.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups/vd-license-manager"
PLUGIN_DIR="/var/www/html/wp-content/plugins/vd-license-manager"

# Create backup
tar -czf $BACKUP_DIR/vd_license_manager_files_$DATE.tar.gz \
    -C $(dirname $PLUGIN_DIR) \
    $(basename $PLUGIN_DIR)

echo "File backup completed: vd_license_manager_files_$DATE.tar.gz"
```

### 9.3 Automated Backup Cron
```bash
# Add to crontab (crontab -e)
# Daily database backup at 2 AM
0 2 * * * /path/to/backup-database.sh >> /var/log/vd-backup.log 2>&1

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /path/to/backup-files.sh >> /var/log/vd-backup.log 2>&1
```

## 10. Deployment Environments

### 10.1 Development Environment
```bash
# Docker setup for development
cat > docker-compose.yml << EOF
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wp_dev
      WORDPRESS_DB_USER: wp_user
      WORDPRESS_DB_PASSWORD: wp_pass
      WORDPRESS_DEBUG: 1
    volumes:
      - ./vd-license-manager:/var/www/html/wp-content/plugins/vd-license-manager

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wp_dev
      MYSQL_USER: wp_user
      MYSQL_PASSWORD: wp_pass
      MYSQL_ROOT_PASSWORD: root_pass
    ports:
      - "3306:3306"
EOF

# Start development environment
docker-compose up -d
```

### 10.2 Staging Environment
```bash
# Staging deployment script
#!/bin/bash
# deploy-staging.sh

# Pull latest code
git pull origin develop

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
wp eval "VD_License_Manager::migrate_database();"

# Clear cache
wp cache flush

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm

echo "Staging deployment completed"
```

### 10.3 Production Deployment
```bash
#!/bin/bash
# deploy-production.sh

set -e  # Exit on any error

echo "Starting production deployment..."

# Backup before deployment
./backup-database.sh
./backup-files.sh

# Switch to maintenance mode
wp maintenance-mode activate

# Deploy new version
git checkout main
git pull origin main

# Install production dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
wp eval "VD_License_Manager::migrate_database();"

# Clear all caches
wp cache flush
sudo systemctl reload php7.4-fpm

# Run health check
curl -f http://localhost/wp-json/vd/v1/health || exit 1

# Disable maintenance mode
wp maintenance-mode deactivate

echo "Production deployment completed successfully"
```

## 11. Troubleshooting

### 11.1 Common Issues
```bash
# Check plugin activation
wp plugin list | grep vd-license-manager

# Check database tables
mysql -e "SHOW TABLES LIKE 'wp_vd_%';" your_database

# Check file permissions
ls -la wp-content/plugins/vd-license-manager/

# Check PHP errors
tail -f /var/log/php_errors.log | grep "VD License"

# Test database connection
wp db check

# Check WordPress debug log
tail -f wp-content/debug.log
```

### 11.2 Performance Debugging
```php
// Enable query debugging
define('SAVEQUERIES', true);

// Add to functions.php for debugging
function vd_debug_queries() {
    global $wpdb;
    echo "<pre>";
    print_r($wpdb->queries);
    echo "</pre>";
}
add_action('wp_footer', 'vd_debug_queries');
```

### 11.3 Recovery Procedures
```bash
# Restore database backup
gunzip -c /backups/vd-license-manager/vd_license_manager_20240115_020000.sql.gz |
mysql -u root -p your_database

# Restore file backup
tar -xzf /backups/vd-license-manager/vd_license_manager_files_20240115_030000.tar.gz -C /var/www/html/wp-content/plugins/

# Reset plugin to defaults
wp option delete vd_license_manager_options
wp plugin deactivate vd-license-manager
wp plugin activate vd-license-manager
```

This deployment guide provides comprehensive instructions for installing, configuring, and maintaining the VD License Manager plugin in various environments with proper security, performance optimization, and monitoring practices.