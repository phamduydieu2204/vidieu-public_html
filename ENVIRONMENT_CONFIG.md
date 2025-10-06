## 🎯 OVERVIEW

This document contains all environment-specific configuration for the VD License Manager plugin. **Update these values according to your environment before deployment.**

---

## 🔧 WORDPRESS CORE

```ini
# WordPress Version
WP_VERSION=6.8.2

# Database Table Prefix
TABLE_PREFIX=bz_

# PHP Version
PHP_VERSION=7.4.27

# Required PHP Extensions
PHP_EXTENSIONS=mysqli,curl,mbstring,json,openssl
```

---

## 🗄️ DATABASE CONFIGURATION

```ini
# Database Credentials
DB_NAME=vidieu_db
DB_USER=vidieu
DB_PASSWORD=[YOUR_DB_PASSWORD]
DB_HOST=localhost
DB_PORT=3306

# Database Charset & Collation
DB_CHARSET=utf8mb4
DB_COLLATE=utf8mb4_unicode_ci
```

### **VD License Manager Tables**
```
# Plugin Tables (created by plugin)
bz_vd_provider_accounts
bz_vd_product_pools
bz_vd_pool_accounts
bz_vd_cookie_assignments
bz_vd_product_share_configs
bz_vd_device_fingerprints
bz_vd_license_devices
bz_vd_license_device_limits
bz_vd_account_fetch_log
bz_vd_license_access_log
bz_vd_license_rate_limits
```

---

## 🔐 SECURITY & ENCRYPTION

```ini
# Encryption Key (base64 encoded, 32 bytes)
VD_ENCRYPTION_KEY=base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=

# Session Configuration
SESSION_TIMEOUT=3600              # 1 hour
DEVICE_FINGERPRINT_SALT=[RANDOM_SALT_HERE]

# Rate Limiting
RATE_LIMIT_WINDOW=300             # 5 minutes
RATE_LIMIT_MAX_HITS=10            # 10 requests per window

# Cooldown
DEFAULT_DEVICE_COOLDOWN=86400     # 24 hours
```

### **⚠️ IMPORTANT: Encryption Key**

Current encryption key (keep secure):
```
VD_ENCRYPTION_KEY=base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=
```

To generate a new key (for production):
```bash
# Generate new encryption key (Linux/Mac)
openssl rand -base64 32

# Or use PHP
php -r "echo 'base64:' . base64_encode(random_bytes(32));"
```

Add to `wp-config.php`:
```php
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');
```

---

## 🔌 REST API CONFIGURATION

### **Plugin REST API**
```ini
# Base URL
REST_BASE=/wp-json/vd/v1

# Main Endpoint
ENDPOINT_RESOLVE=/license/resolve-info

# Additional Endpoints
ENDPOINT_DEVICE_VERIFY=/device/verify
ENDPOINT_ACCOUNT_INFO=/account/info
ENDPOINT_HISTORY=/history
ENDPOINT_DEVICE_ROTATE=/device/rotate

# Full URLs (example)
# https://vidieu.vn/wp-json/vd/v1/license/resolve-info
# https://vidieu.vn/wp-json/vd/v1/device/verify
# https://vidieu.vn/wp-json/vd/v1/account/info
# https://vidieu.vn/wp-json/vd/v1/history
# https://vidieu.vn/wp-json/vd/v1/device/rotate
```

### **CORS Configuration (if needed)**
```php
// In wp-config.php
define('VD_CORS_ENABLED', true);
define('VD_CORS_ORIGINS', 'https://vidieu.vn,https://app.vidieu.vn');
```

---

## 📦 LICENSE SOURCE CONFIGURATION

### **License Manager for WooCommerce (LMFWC) Database**

```ini
# License Source Type
LICENSE_SOURCE=LMFWC_DB

# LMFWC Main Table
LMFWC_TABLE=bz_lmfwc_licenses

# Available Columns in bz_lmfwc_licenses
LMFWC_COLUMNS=id,order_id,product_id,user_id,license_key,hash,expires_at,valid_for,source,status,times_activated,times_activated_max,created_at,created_by,updated_at,updated_by

# Fields Used by Plugin
LMFWC_FIELDS=license_key,product_id,status,expires_at

# Related Tables
LMFWC_ACTIVATIONS_TABLE=bz_lmfwc_activations
LMFWC_API_KEYS_TABLE=bz_lmfwc_api_keys
LMFWC_GENERATORS_TABLE=bz_lmfwc_generators
LMFWC_LICENSES_META_TABLE=bz_lmfwc_licenses_meta
```

### **Alternative: External API** *(for future implementation)*
```ini
# License Source Type
LICENSE_SOURCE=EXTERNAL_API

# External API Configuration
EXTERNAL_API_BASE_URL=https://api.example.com/v1
EXTERNAL_API_KEY=[YOUR_API_KEY]
EXTERNAL_API_SECRET=[YOUR_API_SECRET]
EXTERNAL_API_TIMEOUT=30
```

---

## 🔑 LMFWC REST API CONFIGURATION

### **API Credentials**
```ini
# WooCommerce REST API Keys (LMFWC)
LMFWC_CONSUMER_KEY=ck_208d18a140490def109b29fcc14739765427d8cb
LMFWC_CONSUMER_SECRET=cs_36f463fa7f9548f6aff9cf195a3143a064b159ed

# API Base URL
LMFWC_API_BASE=https://vidieu.vn/wp-json/lmfwc
```

### **Available LMFWC API Endpoints**

#### **Licenses**
```
GET    /lmfwc/v2/licenses                              # List all licenses
GET    /lmfwc/v2/licenses/{license_key}                # Get specific license
POST   /lmfwc/v2/licenses                              # Create license
PUT    /lmfwc/v2/licenses/{license_key}                # Update license
DELETE /lmfwc/v2/licenses/{license_key}                # Delete license
GET    /lmfwc/v2/licenses/activate/{license_key}      # Activate license
GET    /lmfwc/v2/licenses/deactivate/{activation_token} # Deactivate license
GET    /lmfwc/v2/licenses/validate/{license_key}      # Validate license
```

#### **Generators**
```
GET    /lmfwc/v2/generators                            # List generators
GET    /lmfwc/v2/generators/{id}                       # Get generator
POST   /lmfwc/v2/generators                            # Create generator
PUT    /lmfwc/v2/generators/{id}                       # Update generator
DELETE /lmfwc/v2/generators/{id}                       # Delete generator
```

### **Authentication Example**
```bash
# Using cURL with your credentials
curl -X GET "https://vidieu.vn/wp-json/lmfwc/v2/licenses/H10D-DIJD-14RC-SOLE-6KUV30" \
  -u "ck_208d18a140490def109b29fcc14739765427d8cb:cs_36f463fa7f9548f6aff9cf195a3143a064b159ed"
```

---

## 🛍️ PRODUCT → SHARE TYPE MAPPING

### **Current Configuration**
```ini
# Product ID → Share Type Mapping
# Format: PRODUCT_{PRODUCT_ID}={SHARE_TYPE}

# Product 8210 shares COOKIE only
PRODUCT_8210=COOKIE

# Product 1357 shares Email + Password
PRODUCT_1357=USERPASS

# Product 6456 shares Email + Password + 2FA
PRODUCT_6456=USERPASS_2FA
```

### **Available Share Types**
```
COOKIE          → Cookie only
USERPASS        → Email + Password
USERPASS_2FA    → Email + Password + TOTP Secret
FULL            → All fields (Cookie + Credentials + 2FA + Recovery)
```

### **Database Configuration**

For each product, insert into `bz_vd_product_share_configs`:

```sql
-- Product 8210 (COOKIE)
INSERT INTO bz_vd_product_share_configs (product_id, share_fields, instructions) 
VALUES (
    8210,
    '["cookie"]',
    'Import cookie into browser extension to use immediately'
);

-- Product 1357 (USERPASS)
INSERT INTO bz_vd_product_share_configs (product_id, share_fields, instructions) 
VALUES (
    1357,
    '["login_email","login_password"]',
    'Login with provided email and password'
);

-- Product 6456 (USERPASS_2FA)
INSERT INTO bz_vd_product_share_configs (product_id, share_fields, instructions) 
VALUES (
    6456,
    '["login_email","login_password","totp_secret"]',
    'Login with email, password. Use TOTP code if 2FA is required'
);
```

### **How to Add New Product**

1. **Via Database:**
```sql
INSERT INTO bz_vd_product_share_configs (product_id, share_fields, instructions) 
VALUES (
    YOUR_PRODUCT_ID,
    '["cookie"]',  -- or ["login_email","login_password"] etc
    'Your instructions here'
);
```

2. **Via Admin UI:**
```
WordPress Admin → VD License Manager → Product Configs
→ Select Product → Check fields → Save
```

---

## 🧪 TEST DATA

### **Test License Information**
```ini
# Test License Key
TEST_LICENSE=H10D-DIJD-14RC-SOLE-6KUV30

# Associated Product
TEST_LICENSE_PRODUCT=8210

# License Status
TEST_LICENSE_STATUS=active

# Share Type for this License
TEST_SHARE_TYPE=COOKIE
```

### **Test in LMFWC Database**
```sql
-- Verify test license exists
SELECT * FROM bz_lmfwc_licenses 
WHERE license_key = 'H10D-DIJD-14RC-SOLE-6KUV30';

-- Expected result:
-- id: [some_id]
-- product_id: 8210
-- license_key: H10D-DIJD-14RC-SOLE-6KUV30
-- status: active
-- expires_at: [future_date]
```

### **Test Provider Account** *(create for testing)*
```sql
INSERT INTO bz_vd_provider_accounts (
    provider, 
    account_login, 
    display_name, 
    capacity, 
    status,
    cookie,
    cookie_format
) VALUES (
    'helium10',
    'test@example.com',
    'Test Helium10 Account',
    5,
    'active',
    '{"session":"test_session_123","token":"test_token_xyz"}',
    'json'
);
```

### **Test Device Fingerprint**
```ini
# Test Device
TEST_DEVICE_FP=abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890
TEST_DEVICE_UA=Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0
TEST_DEVICE_OS=Windows 10
TEST_DEVICE_BROWSER=Chrome
```

### **Test API Calls**

```bash
# 1. Verify Device
curl -X POST "https://vidieu.vn/wp-json/vd/v1/device/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "fp": "abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890",
    "ua": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0",
    "os": "Windows 10"
  }'

# 2. Get Account Info
curl -X GET "https://vidieu.vn/wp-json/vd/v1/account/info" \
  -H "Authorization: Bearer H10D-DIJD-14RC-SOLE-6KUV30" \
  -H "X-Device-Fingerprint: abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890"

# 3. Resolve Info (Main Endpoint)
curl -X GET "https://vidieu.vn/wp-json/vd/v1/license/resolve-info?license_key=H10D-DIJD-14RC-SOLE-6KUV30"
```

---

## 🌍 GEOLOCATION API

### **IP Geolocation Service**
```ini
# Option 1: ip-api.com (Free, no key required)
GEO_API_PROVIDER=ip-api
GEO_API_URL=http://ip-api.com/json/{ip}
GEO_API_RATE_LIMIT=45                # requests per minute

# Option 2: ipapi.co (Free tier: 1000/day)
# GEO_API_PROVIDER=ipapi
# GEO_API_URL=https://ipapi.co/{ip}/json/

# Cache Settings
GEO_CACHE_DURATION=604800            # 7 days
```

---

## 📧 EMAIL NOTIFICATIONS

### **SMTP Configuration** *(optional, recommended for production)*
```ini
# SMTP Settings
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls                      # or 'ssl'
SMTP_AUTH=true
SMTP_USERNAME=noreply@vidieu.vn
SMTP_PASSWORD=[YOUR_SMTP_PASSWORD]

# From Email
MAIL_FROM_EMAIL=noreply@vidieu.vn
MAIL_FROM_NAME=VD License Manager

# Admin Notification Email
ADMIN_NOTIFICATION_EMAIL=admin@vidieu.vn
```

### **Notification Types**
```ini
# Enable/Disable Notifications
NOTIFY_DEVICE_ROTATION=true
NOTIFY_ACCOUNT_EXPIRING=true
NOTIFY_CAPACITY_HIGH=true
NOTIFY_SUSPICIOUS_ACTIVITY=true

# Thresholds
ACCOUNT_EXPIRY_WARNING_DAYS=7
CAPACITY_WARNING_PERCENT=90
```

---

## 🔄 CRON JOBS

### **Schedule Configuration**
```ini
# Cron Frequencies
CRON_DAILY_CLEANUP=daily             # 00:00 UTC
CRON_HOURLY_CHECK=hourly             # Every hour
CRON_ACCOUNT_CHECK=twicedaily        # 00:00 and 12:00 UTC

# Cleanup Settings
IP_ANONYMIZE_AFTER_DAYS=90
LOG_RETENTION_DAYS=365
TRANSIENT_CLEANUP_DAYS=30
```

### **Manual Cron Trigger** *(for testing)*
```bash
# Trigger WordPress cron manually
php wp-cron.php

# Or via URL
curl https://vidieu.vn/wp-cron.php
```

---

## 🎨 PORTAL CUSTOMIZATION

### **Portal Settings**
```ini
# Portal Page
PORTAL_PAGE_SLUG=license-portal
PORTAL_SHORTCODE=[vd_license_portal]

# UI Settings
PORTAL_THEME=default                 # default, dark, light
PORTAL_LANGUAGE=vi                   # vi, en
PORTAL_LOGO_URL=/wp-content/uploads/logo.png

# Features Toggle
PORTAL_SHOW_HISTORY=true
PORTAL_SHOW_DEVICES=true
PORTAL_SHOW_ROTATION_REQUEST=true
PORTAL_AUTO_REFRESH_TOTP=true
```

---

## 🐛 DEBUG & LOGGING

### **Debug Settings**
```php
// In wp-config.php

// Enable WordPress Debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// VD License Manager Debug
define('VD_DEBUG', true);
define('VD_DEBUG_LOG', WP_CONTENT_DIR . '/debug-vd.log');

// Log Levels: error, warning, info, debug
define('VD_LOG_LEVEL', 'debug');
```

### **Logging Configuration**
```ini
# Log Settings
LOG_ENABLED=true
LOG_LEVEL=info                       # error, warning, info, debug
LOG_FILE=/wp-content/debug-vd.log
LOG_MAX_SIZE=10485760               # 10MB
LOG_ROTATION=true
```

---

## 🚀 PERFORMANCE OPTIMIZATION

### **Cache Configuration**
```ini
# Object Cache (requires Redis/Memcached)
OBJECT_CACHE_ENABLED=false
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Transient Settings
USE_TRANSIENT_CACHE=true
TRANSIENT_DEFAULT_EXPIRATION=3600

# Query Cache
QUERY_CACHE_ENABLED=true
QUERY_CACHE_DURATION=300
```

---

## 📝 CONFIGURATION FILE TEMPLATE

### **wp-config.php additions:**

```php
<?php
// =============================================================================
// VD LICENSE MANAGER CONFIGURATION
// =============================================================================

// Database Configuration
define('DB_NAME', 'vidieu_db');
define('DB_USER', 'vidieu');
define('DB_PASSWORD', 'your_secure_password_here');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Table Prefix
$table_prefix = 'bz_';

// Encryption Key
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');

// License Source
define('VD_LICENSE_SOURCE', 'LMFWC_DB');
define('VD_LMFWC_TABLE', 'bz_lmfwc_licenses');

// REST API
define('VD_REST_BASE', '/wp-json/vd/v1');
define('VD_ENDPOINT_RESOLVE', '/license/resolve-info');

// LMFWC API Credentials
define('VD_LMFWC_CONSUMER_KEY', 'ck_208d18a140490def109b29fcc14739765427d8cb');
define('VD_LMFWC_CONSUMER_SECRET', 'cs_36f463fa7f9548f6aff9cf195a3143a064b159ed');

// Debug Settings (disable in production)
define('VD_DEBUG', false);
define('VD_LOG_LEVEL', 'error');

// Product Share Types
define('VD_PRODUCT_8210', 'COOKIE');
define('VD_PRODUCT_1357', 'USERPASS');
define('VD_PRODUCT_6456', 'USERPASS_2FA');
```

---

## ✅ DEPLOYMENT CHECKLIST

**Before going to production:**

```
Environment Setup:
□ PHP 7.4.27 installed with mysqli, curl, mbstring extensions
□ WordPress 6.8.2 installed
□ Database vidieu_db created with user vidieu
□ Table prefix set to bz_

Plugin Configuration:
□ VD_ENCRYPTION_KEY configured in wp-config.php
□ LMFWC_CONSUMER_KEY and LMFWC_CONSUMER_SECRET verified
□ Product share types configured (8210, 1357, 6456)
□ Test license H10D-DIJD-14RC-SOLE-6KUV30 verified in database

Security:
□ Change DB_PASSWORD to strong password
□ Disable VD_DEBUG in production
□ Set LOG_LEVEL to 'error'
□ SSL certificate installed (HTTPS)
□ Firewall rules configured

Testing:
□ Test license verification with H10D-DIJD-14RC-SOLE-6KUV30
□ Test /license/resolve-info endpoint
□ Test device verification
□ Test account info retrieval for product 8210 (COOKIE)
□ Test rate limiting
□ Verify LMFWC integration

Monitoring:
□ SMTP configured for notifications
□ Admin notification email set
□ Cron jobs scheduled
□ Error logs monitored
□ Database backups configured
```

---

## 🔄 ENVIRONMENT SUMMARY

```
┌─────────────────────────────────────────────────────────┐
│  CURRENT ENVIRONMENT CONFIGURATION                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  WordPress:        6.8.2                                │
│  PHP:              7.4.27                               │
│  Database:         vidieu_db                            │
│  Table Prefix:     bz_                                  │
│                                                         │
│  License Source:   LMFWC_DB                             │
│  LMFWC Table:      bz_lmfwc_licenses                    │
│                                                         │
│  API Base:         /wp-json/vd/v1                       │
│  Main Endpoint:    /license/resolve-info                │
│                                                         │
│  Products Configured:                                   │
│    8210  → COOKIE                                       │
│    1357  → USERPASS                                     │
│    6456  → USERPASS_2FA                                 │
│                                                         │
│  Test License:     H10D-DIJD-14RC-SOLE-6KUV30           │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 📞 SUPPORT & TROUBLESHOOTING

### **Common Issues:**

**Issue 1: REST API not working**
```
Solution: Check permalink settings
WordPress Admin → Settings → Permalinks → Save Changes
```

**Issue 2: Test license not found**
```
Solution: Verify in database
mysql -u vidieu -p vidieu_db
SELECT * FROM bz_lmfwc_licenses WHERE license_key = 'H10D-DIJD-14RC-SOLE-6KUV30';
```

**Issue 3: Product share type not working**
```
Solution: Insert into bz_vd_product_share_configs
See "Product → Share Type Mapping" section above
```

**Issue 4: LMFWC API authentication failed**
```
Solution: Verify consumer key and secret
Test: curl with provided credentials
```

---

**Last Updated:** 2025-01-06  
**Plugin Version:** 1.0.0  
**Environment:** Development/Staging  
**WordPress Version:** 6.8.2  
**PHP Version:** 7.4.27