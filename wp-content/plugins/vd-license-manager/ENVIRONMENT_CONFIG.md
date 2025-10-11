# ENVIRONMENT CONFIGURATION - COMPLETE

**✅ Updated with actual values from wp-config.php**  
**Date:** 2025-01-06  
**Status:** READY FOR DEVELOPMENT

---

## 📋 SUMMARY

```
✅ Database: vidieu_db (password obtained)
✅ Table Prefix: bz_
✅ Encryption Key: Configured
✅ Auth Keys: All 8 salts configured
✅ Debug Mode: Enabled (WP_DEBUG=true)
✅ Email: admin@vidieu.vn configured
✅ WP Cache: Enabled
```

---

## 🗄️ DATABASE CONFIGURATION

### **From wp-config.php:**

```php
define( 'DB_NAME', 'vidieu_db' );
define( 'DB_USER', 'vidieu' );
define( 'DB_PASSWORD', 'Vidieu0204@#&6' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );  // Empty = default collation
$table_prefix = 'bz_';
```

### **Connection String:**
```ini
Host: localhost
Port: 3306 (default)
Database: vidieu_db
User: vidieu
Password: Vidieu0204@#&6
Charset: utf8mb4
Collation: Default (utf8mb4_unicode_ci)
```

### **VD Plugin Tables:**
```
All tables use prefix: bz_vd_

Tables to create:
1.  bz_vd_provider_accounts
2.  bz_vd_product_pools
3.  bz_vd_pool_accounts
4.  bz_vd_cookie_assignments
5.  bz_vd_product_share_configs
6.  bz_vd_device_fingerprints
7.  bz_vd_license_devices
8.  bz_vd_license_device_limits
9.  bz_vd_account_fetch_log
10. bz_vd_license_access_log
11. bz_vd_license_rate_limits
```

### **LMFWC Tables (Already exists):**
```
bz_lmfwc_licenses
bz_lmfwc_activations
bz_lmfwc_api_keys
bz_lmfwc_generators
bz_lmfwc_licenses_meta
```

---

## 🔐 SECURITY CONFIGURATION

### **Encryption Key (From wp-config.php):**

```php
// VD License Manager - Encryption Key
define( 'VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=' );
```

**Status:** ✅ Configured  
**Algorithm:** AES-256-CBC  
**Note:** This is DEVELOPMENT key. For PRODUCTION, generate new key.

### **Device Fingerprint Salt:**

**Status:** ⚠️ NEEDS TO BE ADDED

**Generate with:**
```bash
php -r "echo base64_encode(random_bytes(32));"
```

**Then add to wp-config.php:**
```php
define( 'VD_DEVICE_FINGERPRINT_SALT', 'your_generated_salt_here' );
```

### **WordPress Authentication Keys (From wp-config.php):**

```php
// ✅ All 8 salts configured in wp-config.php
define( 'AUTH_KEY',         'Q<E9R60MBokV(.J!%65lxs?Dy7l*N~#z[53|NRnoe5[u]q9I?5U]QI9zHX9L5J_}' );
define( 'SECURE_AUTH_KEY',  'Ug,2Q5vOEYS?{%}eH1FD|UiD{6{)rjf{*zrRhL=:D|/r3PUPKI7!aIQ!K#;q2@Mf' );
define( 'LOGGED_IN_KEY',    ':TL/bH<8.R3.x3_^#~-ijF7-+!,L~U%yO%G{#F$:jcs|[@yS{U+Von(;c|U08mT6' );
define( 'NONCE_KEY',        ' R=v#&8e/%>mG>M?,LVb)5~v+vIE+6vQh[8DCHO&2T#|M#/Y S{r1-C#E,E0ro^}' );
define( 'AUTH_SALT',        ':=WLf_o&_+whw;Cm-7</Em$WPrq2P{2N8Y(#ISQ(=<QLA*D/C))as#fqgLA4A0Jr' );
define( 'SECURE_AUTH_SALT', 'm|q%%z]9DY4[{2dfZ&x3cFO`S1vj-Xkb2isC1/>(OT,`8]`C%WvYiB]^~K0=pwMh' );
define( 'LOGGED_IN_SALT',   'QvfZ{X.%JB//}zvKIQ%LJL2u{u.s#tb-U;]eFn6N1!@Or$,+Ra*Yd|d()``@L1hM' );
define( 'NONCE_SALT',       'g^z$7OuG/<,rR}yRpX@nV&w-k,R#$}Qt*Mww7 *2XWwAAH(4KRfCcjB;n>$h5UKe' );
```

### **Rate Limiting:**
```ini
RATE_LIMIT_WINDOW=300              # 5 minutes
RATE_LIMIT_MAX_HITS=10             # 10 requests per window
DEFAULT_DEVICE_COOLDOWN=86400      # 24 hours
```

---

## 📧 EMAIL CONFIGURATION

### **From wp-config.php:**

```php
// WP Mail SMTP Configuration
define( 'WPMS_MAIL_FROM', 'admin@vidieu.vn' );
define( 'WPMS_MAIL_FROM_FORCE', true );
define( 'WPMS_MAIL_FROM_NAME', 'Vidieu.vn' );
define( 'WPMS_MAIL_FROM_NAME_FORCE', true );
```

**Status:** ✅ Email sender configured

### **Email Addresses:**
```ini
MAIL_FROM_EMAIL=admin@vidieu.vn
MAIL_FROM_NAME=Vidieu.vn
ADMIN_NOTIFICATION_EMAIL=admin@vidieu.vn
SUPPORT_EMAIL=admin@vidieu.vn (or create support@vidieu.vn)
NOREPLY_EMAIL=noreply@vidieu.vn (recommended to create)
```

### **SMTP Settings:**

**Status:** ⚠️ NEEDS CONFIGURATION

**You need to configure SMTP in WP Mail SMTP plugin:**
```
WordPress Admin → WP Mail SMTP → Settings

Options:
1. Gmail (with App Password)
2. SendGrid (recommended for production)
3. Mailgun
4. Amazon SES
5. Other SMTP

See HOW_TO_GET_MISSING_INFO.md section 7 for details
```

---

## 🐛 DEBUG SETTINGS

### **From wp-config.php:**

```php
// Debug Configuration
define('WP_DEBUG', true);           // ✅ Enabled
define('WP_DEBUG_LOG', true);       // ✅ Log to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false);  // ✅ Don't display errors on screen
```

**Status:** ✅ Properly configured for development

**Debug Log Location:**
```
/path/to/wordpress/wp-content/debug.log
```

### **VD Plugin Debug (To Add):**

**Add to wp-config.php:**
```php
// VD License Manager Debug
define( 'VD_DEBUG', true );                                    // Enable plugin debug
define( 'VD_DEBUG_LOG', WP_CONTENT_DIR . '/debug-vd.log' );   // Plugin log file
define( 'VD_LOG_LEVEL', 'debug' );                             // Log level: error, warning, info, debug
```

---

## 💾 CACHE CONFIGURATION

### **From wp-config.php:**

```php
define( 'WP_CACHE', true );  // ✅ Enabled
```

**Status:** ✅ WordPress Object Cache enabled

**Type:** Need to verify what caching plugin/system is used
- Check for Redis Object Cache plugin?
- Check for Memcached?
- Check for WP Rocket or W3 Total Cache?

**To check:**
```
WordPress Admin → Plugins
Look for: Redis Object Cache, W3 Total Cache, WP Rocket, etc.
```

---

## 🌐 WORDPRESS CONFIGURATION

### **Site Information:**

**To verify:**
```
WordPress Admin → Settings → General
- WordPress Address (URL): ?
- Site Address (URL): ?
```

**Expected:**
```ini
SITE_URL=https://vidieu.vn
HOME_URL=https://vidieu.vn
ADMIN_URL=https://vidieu.vn/wp-admin
```

### **WordPress Version:**
```
Required: 6.0+
Current: 6.8.2 (from original config)
```

### **PHP Version:**
```
Required: 7.4+
Current: 7.4.27 (from original config)
```

---

## 🔌 PLUGIN CONSTANTS TO ADD

### **Add these to wp-config.php:**

```php
// =============================================================================
// VD LICENSE MANAGER CONFIGURATION
// =============================================================================

// Plugin Version
define( 'VD_PLUGIN_VERSION', '1.0.0' );

// License Source
define( 'VD_LICENSE_SOURCE', 'LMFWC_DB' );
define( 'VD_LMFWC_TABLE', 'bz_lmfwc_licenses' );

// REST API Configuration
define( 'VD_REST_BASE', '/wp-json/vd/v1' );
define( 'VD_ENDPOINT_RESOLVE', '/license/resolve-info' );
define( 'VD_ENDPOINT_DEVICE_VERIFY', '/device/verify' );
define( 'VD_ENDPOINT_ACCOUNT_INFO', '/account/info' );
define( 'VD_ENDPOINT_HISTORY', '/history' );
define( 'VD_ENDPOINT_DEVICE_ROTATE', '/device/rotate' );

// LMFWC API Credentials
define( 'VD_LMFWC_CONSUMER_KEY', 'ck_208d18a140490def109b29fcc14739765427d8cb' );
define( 'VD_LMFWC_CONSUMER_SECRET', 'cs_36f463fa7f9548f6aff9cf195a3143a064b159ed' );
define( 'VD_LMFWC_API_BASE', 'https://vidieu.vn/wp-json/lmfwc' );

// Product Share Types
define( 'VD_PRODUCT_8210', 'COOKIE' );       // Helium10
define( 'VD_PRODUCT_1357', 'USERPASS' );     // Other product
define( 'VD_PRODUCT_6456', 'USERPASS_2FA' ); // Other product

// Security
define( 'VD_DEVICE_FINGERPRINT_SALT', 'GENERATE_AND_ADD_HERE' );

// Session & Rate Limiting
define( 'VD_SESSION_TIMEOUT', 3600 );           // 1 hour
define( 'VD_RATE_LIMIT_WINDOW', 300 );          // 5 minutes
define( 'VD_RATE_LIMIT_MAX_HITS', 10 );         // 10 requests
define( 'VD_DEFAULT_DEVICE_COOLDOWN', 86400 );  // 24 hours

// Test License (for development)
define( 'VD_TEST_LICENSE', 'H10D-DIJD-14RC-SOLE-6KUV30' );
define( 'VD_TEST_PRODUCT', 8210 );

// Feature Flags
define( 'VD_FEATURE_VPS_DETECTION', true );
define( 'VD_FEATURE_RATE_LIMITING', true );
define( 'VD_FEATURE_DEVICE_TRACKING', true );
```

---

## 📊 CURRENT STATUS CHECKLIST

### **✅ COMPLETED:**

```
Database Configuration:
✅ DB_NAME: vidieu_db
✅ DB_USER: vidieu
✅ DB_PASSWORD: Vidieu0204@#&6
✅ DB_HOST: localhost
✅ DB_CHARSET: utf8mb4
✅ Table prefix: bz_

WordPress Auth Keys:
✅ All 8 salts configured

Encryption:
✅ VD_ENCRYPTION_KEY configured

Email:
✅ From email: admin@vidieu.vn
✅ From name: Vidieu.vn

Debug:
✅ WP_DEBUG enabled
✅ WP_DEBUG_LOG enabled
✅ WP_DEBUG_DISPLAY disabled

Cache:
✅ WP_CACHE enabled
```

### **⚠️ NEEDS ACTION:**

```
🔴 CRITICAL:
[ ] Generate Device Fingerprint Salt
    Command: php -r "echo base64_encode(random_bytes(32));"
    Add to wp-config.php

[ ] Add VD Plugin Constants to wp-config.php
    Copy from section "PLUGIN CONSTANTS TO ADD" above

🟡 IMPORTANT:
[ ] Configure SMTP in WP Mail SMTP plugin
    See HOW_TO_GET_MISSING_INFO.md section 7

[ ] Verify site URL (https://vidieu.vn)
    WordPress Admin → Settings → General

[ ] Verify test license exists in database
    Query: SELECT * FROM bz_lmfwc_licenses 
           WHERE license_key = 'H10D-DIJD-14RC-SOLE-6KUV30'

[ ] Test LMFWC API with curl command

[ ] Check SSL certificate installed

[ ] Setup backup solution

🟢 OPTIONAL:
[ ] Identify caching system in use
[ ] Configure CORS (if needed)
[ ] Setup Redis/Memcached (if available)
```

---

## 🔧 IMMEDIATE ACTIONS

### **1. Generate Device Fingerprint Salt**

**Run this command:**
```bash
php -r "echo base64_encode(random_bytes(32));"
```

**Example output:**
```
X9k2mN5pQ8rS3tU6vW7yA0bC1dE4fG7h
```

**Add to wp-config.php (after line 104):**
```php
// Device Fingerprint Salt
define( 'VD_DEVICE_FINGERPRINT_SALT', 'X9k2mN5pQ8rS3tU6vW7yA0bC1dE4fG7h' );
```

### **2. Add Plugin Constants**

**Copy entire "PLUGIN CONSTANTS TO ADD" section above**  
**Paste into wp-config.php (after line 104)**  
**Don't forget to replace 'GENERATE_AND_ADD_HERE' with actual salt from step 1**

### **3. Test Database Connection**

```bash
mysql -u vidieu -p vidieu_db
# Enter password: Vidieu0204@#&6

# In MySQL:
SHOW TABLES LIKE 'bz_lmfwc_licenses';
SELECT * FROM bz_lmfwc_licenses WHERE license_key = 'H10D-DIJD-14RC-SOLE-6KUV30';
```

### **4. Verify LMFWC API**

```bash
curl -X GET "https://vidieu.vn/wp-json/lmfwc/v2/licenses/H10D-DIJD-14RC-SOLE-6KUV30" \
  -u "ck_208d18a140490def109b29fcc14739765427d8cb:cs_36f463fa7f9548f6aff9cf195a3143a064b159ed"
```

---

## 📝 PRODUCTION NOTES

### **Before deploying to production:**

**1. Generate NEW encryption key:**
```bash
openssl rand -base64 32
```

**2. Update in wp-config.php:**
```php
// PRODUCTION - Use different key than development
define( 'VD_ENCRYPTION_KEY', 'base64:YOUR_NEW_PRODUCTION_KEY_HERE' );
```

**3. Disable debug:**
```php
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('VD_DEBUG', false);
```

**4. Change test license constant:**
```php
// Remove or comment out test license
// define( 'VD_TEST_LICENSE', 'H10D-DIJD-14RC-SOLE-6KUV30' );
```

---

## 🔒 SECURITY CHECKLIST

### **wp-config.php Security:**

```
✅ File permissions set to 600 or 640
   chmod 600 wp-config.php

✅ Not accessible via web
   Should return 403 Forbidden if accessed directly

✅ Database password is strong
   Current: Vidieu0204@#&6 (contains special chars, numbers)

⚠️ Encryption key will be changed for production
   Current key is in documentation (insecure for production)

⚠️ All sensitive constants added
   Need to add VD plugin constants
```

---

## 📞 SUMMARY FOR CLAUDE

### **What Claude needs to know:**

```php
// Database
DB: vidieu_db
User: vidieu
Pass: Vidieu0204@#&6
Prefix: bz_

// Tables to create (11 tables)
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

// Encryption
VD_ENCRYPTION_KEY is configured
Need to add VD_DEVICE_FINGERPRINT_SALT

// Email
From: admin@vidieu.vn
Name: Vidieu.vn

// Debug
Enabled: WP_DEBUG, WP_DEBUG_LOG
Display: false (logs to file)

// Test Data
License: H10D-DIJD-14RC-SOLE-6KUV30
Product: 8210 (COOKIE type)

// API
Base: /wp-json/vd/v1
LMFWC: Consumer key & secret provided
```

---

## ✅ READY TO START?

**After completing the 4 immediate actions above:**

```
1. ✅ Generate salt
2. ✅ Add plugin constants to wp-config.php
3. ✅ Test database connection
4. ✅ Test LMFWC API

Then you can:
→ Brief Claude with all documentation
→ Start Week 1, Day 1 development
→ Claude will create plugin structure
→ Claude will create database tables
→ Begin coding!
```

---

**Last Updated:** 2025-01-06  
**Status:** Ready for development (pending 4 immediate actions)  
**Environment:** Development  
**Database:** vidieu_db (configured ✅)  
**WordPress:** 6.8.2  
**PHP:** 7.4.27
