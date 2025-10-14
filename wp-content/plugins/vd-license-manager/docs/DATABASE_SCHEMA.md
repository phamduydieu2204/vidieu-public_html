# VD License Manager - Database Schema Documentation

> **Last Updated:** 2025-10-14
> **Database Prefix:** `bz_`
> **Total VD Tables:** 14
> **WordPress Version:** 6.8.2+
> **Plugin Version:** 1.0.0

---

## 📋 TABLE NAMING CONVENTION

**CRITICAL:** All VD plugin tables use prefix: `bz_vd_`

### Format: `{wordpress_prefix}vd_{table_name}`

**Example:**
- WordPress prefix: `bz_`
- Table name: `license_keys`
- **Full table name:** `bz_vd_license_keys`

### ⚠️ COMMON MISTAKES:
- ❌ `vd_licenses` (WRONG - missing full name)
- ❌ `bz_licenses` (WRONG - missing vd_ middle)
- ❌ `bz_bz_vd_license_keys` (WRONG - double prefix)
- ✅ `bz_vd_license_keys` (CORRECT)

---

## 📊 CORE TABLES (14 tables)

### 1. LICENSE MANAGEMENT

#### `bz_vd_license_keys` ⭐ CORE
**Purpose:** Main license records synced from LMfWC
**Current Rows:** 6
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_key` VARCHAR(255) UNIQUE NOT NULL (e.g., H10D-DIJD-14RC-SOLE-6KUV30)
- `lmfwc_license_id` BIGINT UNSIGNED (Foreign key to LMfWC plugin)
- `product_id` BIGINT UNSIGNED NOT NULL (WooCommerce product ID)
- `assigned_pool_id` BIGINT UNSIGNED NULL (Pool assignment)
- `assigned_account_id` BIGINT UNSIGNED NULL (Account assignment)
- `status` ENUM('active', 'expired', 'suspended', 'inactive') DEFAULT 'active'
- `max_devices` INT UNSIGNED DEFAULT 1
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_license_key` (`license_key`)
- INDEX `idx_product_id` (`product_id`)
- INDEX `idx_status` (`status`)
- INDEX `idx_assigned_pool` (`assigned_pool_id`)

**Related Flow:** FLOW 2 (Customer Purchase), FLOW 3 (Customer Access)

---

#### `bz_vd_license_devices` ⭐ CORE
**Purpose:** Track devices registered to each license
**Current Rows:** 4
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_license_keys)
- `device_combined_id` VARCHAR(255) NOT NULL (SHA256 hash: fingerprint + token)
- `device_fingerprint` TEXT (Browser fingerprint data)
- `device_name` VARCHAR(255) (User-friendly name, e.g., "Laptop - Chrome")
- `slot` TINYINT UNSIGNED (Device slot number: 1, 2, 3...)
- `status` ENUM('active', 'removed', 'blocked') DEFAULT 'active'
- `is_vps` BOOLEAN DEFAULT FALSE (VPS detection flag)
- `ip_address` VARCHAR(45) (Last known IP)
- `user_agent` TEXT (Last known user agent)
- `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `last_access_at` DATETIME NULL

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_license_device` (`license_id`, `device_combined_id`)
- INDEX `idx_device_combined_id` (`device_combined_id`)
- INDEX `idx_status` (`status`)

**Device Limits:** Controlled by product_share_configs.max_devices_per_license
**Related Flow:** FLOW 3 (Customer Access - Device Tracking)

---

#### `bz_vd_license_access_log` ⭐ CORE
**Purpose:** Log every API access attempt
**Current Rows:** 38
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_id` BIGINT UNSIGNED NULL (FK to bz_vd_license_keys)
- `device_id` BIGINT UNSIGNED NULL (FK to bz_vd_license_devices)
- `license_key` VARCHAR(255) NOT NULL (For logging even invalid keys)
- `endpoint` VARCHAR(255) NOT NULL (API endpoint called)
- `http_method` VARCHAR(10) DEFAULT 'GET'
- `ip_address` VARCHAR(45) (Client IP - masked for privacy)
- `user_agent` TEXT
- `authentication_result` ENUM('success', 'expired', 'blocked', 'invalid', 'device_limit', 'rate_limit') NOT NULL
- `error_code` VARCHAR(50) NULL (Specific error codes for debugging)
- `response_status` SMALLINT UNSIGNED (HTTP status code)
- `execution_time` DECIMAL(8,3) NULL (Response time in milliseconds)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX `idx_license_key` (`license_key`)
- INDEX `idx_created_at` (`created_at`)
- INDEX `idx_authentication_result` (`authentication_result`)
- INDEX `idx_endpoint` (`endpoint`)

**Retention:** 90 days (configurable via wp-config.php)
**Related Flow:** FLOW 3 (Access Logging), FLOW 4 (Admin Analytics)

---

### 2. POOL & ACCOUNT MANAGEMENT

#### `bz_vd_pools` ⭐ CORE
**Purpose:** Pool definitions (grouping of accounts)
**Current Rows:** 2
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `name` VARCHAR(255) NOT NULL (Display name, e.g., "Netflix Premium Pool 1")
- `description` TEXT NULL
- `capacity` INT UNSIGNED NOT NULL DEFAULT 1 (Max licenses this pool can serve)
- `assigned_count` INT UNSIGNED DEFAULT 0 (Current licenses assigned)
- `priority` TINYINT UNSIGNED DEFAULT 1 (Assignment priority: 1=highest)
- `status` ENUM('active', 'inactive', 'full') DEFAULT 'active'
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX `idx_status` (`status`)
- INDEX `idx_priority` (`priority`)

**Business Logic:** Pool marked 'full' when assigned_count >= capacity
**Related Flow:** FLOW 1 (Admin Setup), FLOW 2 (Pool Assignment)

---

#### `bz_vd_provider_accounts` ⭐ CORE
**Purpose:** Provider account credentials (Netflix, Spotify, Helium10, etc.)
**Current Rows:** 2
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `provider` VARCHAR(100) NOT NULL (Netflix, Spotify, Helium10, etc.)
- `account_login` VARCHAR(255) NOT NULL (Login email/username)
- `display_name` VARCHAR(255) NULL (Admin display name)
- `login_password` TEXT NULL (Encrypted password)
- `cookie` LONGTEXT NULL (Session cookie if applicable)
- `custom_fields` JSON NULL (Additional provider-specific fields)
- `capacity` INT UNSIGNED DEFAULT 1 (How many licenses this account can serve)
- `current_usage` INT UNSIGNED DEFAULT 0 (Current active assignments)
- `status` ENUM('active', 'inactive', 'expired', 'blocked') DEFAULT 'active'
- `expires_at` DATETIME NULL (Account expiration)
- `last_verified_at` DATETIME NULL (Last successful login verification)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_provider_login` (`provider`, `account_login`)
- INDEX `idx_provider` (`provider`)
- INDEX `idx_status` (`status`)

**Encryption:** All sensitive fields encrypted with VD_ENCRYPTION_KEY
**Related Flow:** FLOW 1 (Admin Setup), FLOW 3 (Credential Response)

---

#### `bz_vd_pool_accounts`
**Purpose:** Many-to-many relationship (pools ↔ accounts)
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `pool_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_pools)
- `account_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_provider_accounts)
- `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_pool_account` (`pool_id`, `account_id`)

---

#### `bz_vd_product_pools`
**Purpose:** Link WooCommerce products to pools with priority
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `product_id` BIGINT UNSIGNED NOT NULL (WooCommerce product ID)
- `pool_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_pools)
- `priority` TINYINT UNSIGNED DEFAULT 1 (Assignment order)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX `idx_product_id` (`product_id`)
- INDEX `idx_priority` (`priority`)

---

### 3. CONFIGURATION

#### `bz_vd_product_share_configs`
**Purpose:** Per-product sharing rules and response configuration
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `product_id` BIGINT UNSIGNED NOT NULL UNIQUE (WooCommerce product ID)
- `max_devices_per_license` TINYINT UNSIGNED DEFAULT 2 (Device limit)
- `device_reset_days` SMALLINT UNSIGNED DEFAULT 7 (Auto-reset period)
- `max_requests_per_day` SMALLINT UNSIGNED DEFAULT 10 (Rate limit)
- `response_fields` JSON NOT NULL (Which credential fields to show customer)
- `pool_assignment_rule` ENUM('priority', 'round_robin', 'least_used') DEFAULT 'priority'
- `is_active` BOOLEAN DEFAULT TRUE
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP

**Example response_fields JSON:**
```json
{
  "fields": [
    {
      "key": "account_login",
      "label": "Email Address",
      "type": "email",
      "order": 1,
      "required": true
    },
    {
      "key": "login_password",
      "label": "Password",
      "type": "password",
      "order": 2,
      "required": true
    },
    {
      "key": "cookie",
      "label": "Session Cookie",
      "type": "textarea",
      "order": 3,
      "required": false
    }
  ]
}
```

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_product_id` (`product_id`)

**Related Flow:** FLOW 1 (Admin Setup), FLOW 3 (Dynamic Response)

---

### 4. TRACKING & LIMITS

#### `bz_vd_license_rate_limits`
**Purpose:** Rate limiting tracking per license
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_license_keys)
- `request_count` SMALLINT UNSIGNED DEFAULT 0 (Requests today)
- `window_start` DATETIME NOT NULL (Rate limit window start)
- `last_request_at` DATETIME NULL (Last API call timestamp)
- `blocked_until` DATETIME NULL (If temporarily blocked)

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_license_rate` (`license_id`)
- INDEX `idx_window_start` (`window_start`)

---

#### `bz_vd_license_device_limits`
**Purpose:** Device slot management per license
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_license_keys)
- `max_devices` TINYINT UNSIGNED NOT NULL (Limit from product config)
- `current_active_devices` TINYINT UNSIGNED DEFAULT 0 (Active device count)
- `last_reset_at` DATETIME NULL (Last device reset)
- `next_reset_at` DATETIME NULL (Next scheduled reset)

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_license_limits` (`license_id`)

---

#### `bz_vd_device_fingerprints`
**Purpose:** Device fingerprint history and VPS detection
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `device_combined_id` VARCHAR(255) NOT NULL (SHA256 hash)
- `raw_fingerprint` JSON NOT NULL (Original fingerprint data)
- `is_vps_detected` BOOLEAN DEFAULT FALSE
- `detection_confidence` DECIMAL(5,2) DEFAULT 0.00 (VPS detection confidence)
- `first_seen_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `last_seen_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_device_combined_id` (`device_combined_id`)
- INDEX `idx_is_vps_detected` (`is_vps_detected`)

---

### 5. ANALYTICS & LOGGING

#### `bz_vd_account_fetch_log`
**Purpose:** Track when account credentials are fetched for licenses
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `license_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_license_keys)
- `account_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_provider_accounts)
- `pool_id` BIGINT UNSIGNED NOT NULL (FK to bz_vd_pools)
- `fetch_reason` ENUM('assignment', 'rotation', 'manual') DEFAULT 'assignment'
- `fetched_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX `idx_license_id` (`license_id`)
- INDEX `idx_account_id` (`account_id`)
- INDEX `idx_fetched_at` (`fetched_at`)

---

#### `bz_vd_page_sidebar_mappings`
**Purpose:** WordPress admin UI sidebar configuration
**Current Rows:** 1
**Key Columns:**
- `id` BIGINT UNSIGNED PRIMARY KEY
- `page_slug` VARCHAR(255) NOT NULL
- `sidebar_config` JSON NULL

---

## 🔍 QUICK REFERENCE

### In PHP Code:
```php
global $wpdb;

// ✅ CORRECT - Using $wpdb->prefix
$table = $wpdb->prefix . 'vd_license_keys';
// Result: bz_vd_license_keys

// ❌ WRONG - Hardcoding prefix
$table = 'bz_vd_license_keys';
// Breaks if site uses different prefix

// ✅ CORRECT - Query with prepared statement
$licenses = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vd_license_keys WHERE product_id = %d",
    $product_id
));
```

### In SQL Queries:
```sql
-- ✅ CORRECT (full table name)
SELECT * FROM bz_vd_license_keys WHERE status = 'active';

-- ❌ WRONG (missing bz_ prefix)
SELECT * FROM vd_license_keys WHERE status = 'active';

-- ❌ WRONG (missing vd_ middle part)
SELECT * FROM bz_license_keys WHERE status = 'active';

-- ❌ WRONG (wrong table name)
SELECT * FROM bz_vd_licenses WHERE status = 'active';
```

### In Test Scripts:
```php
// ✅ CORRECT (script adds bz_ prefix automatically)
$tables_to_check = [
    'vd_license_keys',        // Will check: bz_vd_license_keys
    'vd_license_devices',     // Will check: bz_vd_license_devices
    'vd_license_access_log'   // Will check: bz_vd_license_access_log
];

// ❌ WRONG (these tables don't exist)
$tables_to_check = [
    'vd_licenses',    // Table doesn't exist
    'vd_devices'      // Table doesn't exist
];
```

---

## 🎯 TABLE NAME MAPPING

| Common Reference | Actual Table Name | Current Rows | Status |
|------------------|-------------------|--------------|--------|
| **Licenses** | `bz_vd_license_keys` | 6 | ✅ Active |
| **Devices** | `bz_vd_license_devices` | 4 | ✅ Active |
| **Access Log** | `bz_vd_license_access_log` | 38 | ✅ Active |
| **Pools** | `bz_vd_pools` | 2 | ✅ Active |
| **Accounts** | `bz_vd_provider_accounts` | 2 | ✅ Active |
| **Share Configs** | `bz_vd_product_share_configs` | 0 | ✅ Ready |
| **Product Pools** | `bz_vd_product_pools` | 0 | ✅ Ready |
| **Pool Accounts** | `bz_vd_pool_accounts` | 0 | ✅ Ready |
| **Rate Limits** | `bz_vd_license_rate_limits` | 0 | ✅ Ready |
| **Device Limits** | `bz_vd_license_device_limits` | 0 | ✅ Ready |
| **Fingerprints** | `bz_vd_device_fingerprints` | 0 | ✅ Ready |
| **Fetch Log** | `bz_vd_account_fetch_log` | 0 | ✅ Ready |
| **Sidebar Config** | `bz_vd_page_sidebar_mappings` | 1 | ✅ Active |

---

## 🔄 RELATIONSHIPS DIAGRAM

```
bz_vd_license_keys (6 rows)
├── bz_vd_license_devices (4 rows) [1:N]
├── bz_vd_license_access_log (38 rows) [1:N]
├── bz_vd_license_rate_limits [1:1]
└── bz_vd_license_device_limits [1:1]

bz_vd_pools (2 rows)
├── bz_vd_pool_accounts [N:M] ↔ bz_vd_provider_accounts (2 rows)
└── bz_vd_product_pools [N:M] ↔ WooCommerce Products

bz_vd_product_share_configs
└── [1:1] ↔ WooCommerce Products

bz_vd_account_fetch_log
├── → bz_vd_license_keys [N:1]
├── → bz_vd_provider_accounts [N:1]
└── → bz_vd_pools [N:1]
```

---

## ⚠️ MIGRATION NOTES

### Known Issues Fixed:

#### 1. Double Prefix Issue (Fixed 2025-10-14)
- **Problem:** Table `bz_bz_vd_product_pools` (double bz_ prefix)
- **Solution:** Migration script `includes/migrations/fix-double-prefix.php`
- **Action:** Automatically renames/merges to `bz_vd_product_pools`
- **Status:** ✅ Resolved

#### 2. Table Name Confusion (Fixed 2025-10-14)
- **Problem:** Test scripts looking for `vd_licenses`, `vd_devices` (don't exist)
- **Correct Names:** `vd_license_keys`, `vd_license_devices`
- **Solution:** Updated all test scripts and documentation
- **Status:** ✅ Resolved

### Schema Versions:
- **v1.0.0** - Initial release schema (2025-10-14)
- **Compatibility:** WordPress 6.0+, PHP 7.4+, MySQL 5.7+/MariaDB 10.3+

---

## 🛠️ MAINTENANCE COMMANDS

### Check Table Existence:
```sql
SHOW TABLES LIKE 'bz_vd_%';
```

### Check Table Sizes:
```sql
SELECT
    table_name as 'Table',
    table_rows as 'Rows',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
AND table_name LIKE 'bz_vd_%'
ORDER BY table_rows DESC;
```

### Clean Old Access Logs (90+ days):
```sql
DELETE FROM bz_vd_license_access_log
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 📚 REFERENCES

- **Business Flows:** See FLOW_1_Admin_Setup.xml, FLOW_2_Customer_Purchase.xml, FLOW_3_Customer_Access.xml
- **Configuration:** See ENVIRONMENT_CONFIG_COMPLETE.md
- **API Documentation:** See includes/class-vd-rest-api.php
- **Migration Scripts:** See includes/migrations/
- **Testing:** See tests/quick-check.php

---

**For developers:** Always reference this document when writing database queries or tests.
**For administrators:** Use this for understanding data relationships and troubleshooting.
**For testers:** Use correct table names from the mapping section.

> Last verified: 2025-10-14 with database containing 201 total tables, 14 VD plugin tables active