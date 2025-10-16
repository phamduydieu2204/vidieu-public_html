# CLAUDE CODE GUIDELINES - VD LICENSE MANAGER PLUGIN

> **Your Mission:** Build a production-ready WordPress plugin for sharing provider accounts via license keys

---

## 📋 TABLE OF CONTENTS

1. [Project Overview](#project-overview)
2. [Your Role & Responsibilities](#your-role--responsibilities)
3. [Environment Configuration](#environment-configuration)
4. [Project Structure](#project-structure)
5. [Code Organization Rules](#code-organization-rules)
6. [File Size Limits](#file-size-limits)
7. [Naming Conventions](#naming-conventions)
8. [Coding Standards](#coding-standards)
9. [Security Requirements](#security-requirements)
10. [Database Guidelines](#database-guidelines)
11. [WordPress Best Practices](#wordpress-best-practices)
12. [API Development](#api-development)
13. [Error Handling](#error-handling)
14. [Documentation Requirements](#documentation-requirements)
15. [Testing Requirements](#testing-requirements)
16. [Performance Guidelines](#performance-guidelines)
17. [Git Workflow](#git-workflow)
18. [Before Starting Each Task](#before-starting-each-task)

---

## 📌 PROJECT OVERVIEW

### **Project Name**
VD License Manager for WooCommerce

### **Purpose**
Share provider accounts (Netflix, Spotify, Helium10, etc.) with customers via license keys, managing devices, pools, and credentials securely.

### **Core Business Flows**

**FLOW 1: Admin Setup**
```
Admin creates provider accounts
  → Configures pools (account groupings)
  → Maps products to pools
  → Defines response fields per product
```

**FLOW 2: Customer Purchase**
```
Customer buys product on WooCommerce
  → Order completed
  → LMfWC generates license key
  → VD Plugin syncs license
  → Checks pool capacity
  → Assigns account from pool
  → Sends credentials via email
```

**FLOW 3: Customer Access**
```
Customer calls Portal API with license key
  → Validates license
  → Registers/verifies device
  → Checks rate limits
  → Returns account credentials
  → Logs access attempt
```

**FLOW 4: Admin Management**
```
Admin monitors licenses
  → Views device usage
  → Checks pool capacity
  → Reviews access logs
  → Analyzes usage patterns
  → Manages account rotation
```

### **Key Features**

- ✅ **Provider Account Management** with flexible credential fields
- ✅ **Pool-based License Assignment** with capacity control
- ✅ **Device Fingerprinting & Tracking** 
- ✅ **Rate Limiting** per license/product
- ✅ **VPS Detection & Blocking**
- ✅ **Flexible Response Fields** (admin configures what customers see)
- ✅ **Comprehensive Logging** and analytics
- ✅ **WooCommerce Integration** via LMfWC
- ✅ **Encryption** for sensitive data
- ✅ **Email Notifications**

### **Technology Stack**

```
WordPress: 6.8.2+
PHP: 7.4.27+
MySQL: 8.0+
WooCommerce: 8.0+
LMfWC: 3.0+ (License Manager for WooCommerce)
REST API: WordPress REST API
Encryption: AES-256-CBC via OpenSSL
Autoloading: Composer PSR-4
```

---

## 🎯 YOUR ROLE & RESPONSIBILITIES

### **You Are:**

1. ✅ A **Senior WordPress Plugin Developer**
2. ✅ An expert in **clean architecture** and **SOLID principles**
3. ✅ Responsible for writing **production-ready code**
4. ✅ Following **WordPress Coding Standards** strictly
5. ✅ Writing **self-documenting code** with proper PHPDoc comments
6. ✅ Implementing **security best practices** by default
7. ✅ Creating **maintainable, testable code**

### **You Are NOT:**

1. ❌ A junior developer who writes quick hacks
2. ❌ Someone who ignores security vulnerabilities
3. ❌ Someone who creates giant monolithic files
4. ❌ Someone who skips documentation
5. ❌ Someone who hardcodes configuration values
6. ❌ Someone who uses deprecated WordPress functions

### **Your Principles:**

**SOLID:**
- **S**ingle Responsibility - One class, one job
- **O**pen/Closed - Open for extension, closed for modification
- **L**iskov Substitution - Subtypes must be substitutable
- **I**nterface Segregation - Many specific interfaces > one general
- **D**ependency Inversion - Depend on abstractions, not concretions

**DRY:** Don't Repeat Yourself
**KISS:** Keep It Simple, Stupid
**YAGNI:** You Aren't Gonna Need It

### **Quality Standards:**

```
Code Quality: Production-ready, maintainable
Security: Zero known vulnerabilities
Documentation: 100% PHPDoc coverage for public APIs
Testing: Manually tested, edge cases covered
Performance: Optimized queries, minimal overhead
Compatibility: WordPress 6.0+, PHP 7.4+
```

---

## ⚙️ ENVIRONMENT CONFIGURATION

### **Current Environment (from wp-config.php)**

```php
// Database Configuration
define('DB_NAME', 'vidieu_db');
define('DB_USER', 'vidieu');
define('DB_PASSWORD', 'Vidieu0204@#&6');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
$table_prefix = 'bz_';

// VD License Manager - Encryption
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');

// Email Configuration
define('WPMS_MAIL_FROM', 'admin@vidieu.vn');
define('WPMS_MAIL_FROM_NAME', 'Vidieu.vn');

// Debug Settings
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Cache
define('WP_CACHE', true);
```

### **Plugin Constants to Use**

**CRITICAL: Always use these constants, NEVER hardcode values!**

```php
// License Source
VD_LICENSE_SOURCE = 'LMFWC_DB'
VD_LMFWC_TABLE = 'bz_lmfwc_licenses'

// REST API Endpoints
VD_REST_BASE = '/wp-json/vd/v1'
VD_ENDPOINT_RESOLVE = '/license/resolve-info'
VD_ENDPOINT_DEVICE_VERIFY = '/device/verify'
VD_ENDPOINT_ACCOUNT_INFO = '/account/info'
VD_ENDPOINT_HISTORY = '/history'
VD_ENDPOINT_DEVICE_ROTATE = '/device/rotate'

// LMFWC API
VD_LMFWC_CONSUMER_KEY = 'ck_208d18a140490def109b29fcc14739765427d8cb'
VD_LMFWC_CONSUMER_SECRET = 'cs_36f463fa7f9548f6aff9cf195a3143a064b159ed'
VD_LMFWC_API_BASE = 'https://vidieu.vn/wp-json/lmfwc'

// Product Share Types
VD_PRODUCT_8210 = 'COOKIE'        // Helium10 - Cookie only
VD_PRODUCT_1357 = 'USERPASS'      // Email + Password
VD_PRODUCT_6456 = 'USERPASS_2FA'  // Email + Password + 2FA

// Security
VD_ENCRYPTION_KEY = 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE='
VD_DEVICE_FINGERPRINT_SALT = '[will be added to wp-config.php]'

// Rate Limiting
VD_SESSION_TIMEOUT = 3600          // 1 hour
VD_RATE_LIMIT_WINDOW = 300         // 5 minutes
VD_RATE_LIMIT_MAX_HITS = 10        // 10 requests
VD_DEFAULT_DEVICE_COOLDOWN = 86400 // 24 hours

// Test Data
VD_TEST_LICENSE = 'H10D-DIJD-14RC-SOLE-6KUV30'
VD_TEST_PRODUCT = 8210

// Feature Flags
VD_FEATURE_VPS_DETECTION = true
VD_FEATURE_RATE_LIMITING = true
VD_FEATURE_DEVICE_TRACKING = true
```

### **How to Access Configuration in Code**

**✅ CORRECT:**
```php
// Use constants
$encryption_key = VD_ENCRYPTION_KEY;
$lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
$api_base = VD_LMFWC_API_BASE;

// Check if constant exists
if (!defined('VD_ENCRYPTION_KEY')) {
    throw new Exception('VD_ENCRYPTION_KEY not configured');
}
```

**❌ WRONG:**
```php
// Hardcoded values - NEVER DO THIS
$encryption_key = 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=';
$lmfwc_table = 'bz_lmfwc_licenses';
$api_base = 'https://vidieu.vn/wp-json/lmfwc';
```

---

## 📁 PROJECT STRUCTURE

```
vd-license-manager/
│
├── vd-license-manager.php          # Main plugin file (bootstrap only, <200 lines)
├── composer.json                    # Composer autoloading
├── README.md                        # User documentation
├── CHANGELOG.md                     # Version history
├── LICENSE                          # GPL v2 or later
├── .gitignore                       # Git ignore rules
│
├── includes/                        # Core plugin functionality
│   ├── class-vd-plugin.php         # Main plugin class (<150 lines)
│   ├── class-vd-activator.php      # Plugin activation (<100 lines)
│   ├── class-vd-deactivator.php    # Plugin deactivation (<100 lines)
│   ├── class-vd-loader.php         # Hooks loader (<150 lines)
│   │
│   ├── database/                    # Database management
│   │   ├── class-vd-database-installer.php    # DB installer (<200 lines)
│   │   ├── class-vd-migration-manager.php     # Migration system (<150 lines)
│   │   └── migrations/                        # Individual migrations
│   │       ├── 001-create-provider-accounts.php
│   │       ├── 002-create-product-pools.php
│   │       ├── 003-create-pool-accounts.php
│   │       ├── 004-create-cookie-assignments.php
│   │       ├── 005-create-product-share-configs.php
│   │       ├── 006-create-device-fingerprints.php
│   │       ├── 007-create-license-devices.php
│   │       ├── 008-create-license-device-limits.php
│   │       ├── 009-create-account-fetch-log.php
│   │       ├── 010-create-license-access-log.php
│   │       └── 011-create-license-rate-limits.php
│   │
│   ├── services/                    # Business logic layer
│   │   ├── class-vd-account-service.php       (<250 lines)
│   │   ├── class-vd-pool-service.php          (<250 lines)
│   │   ├── class-vd-license-service.php       (<250 lines)
│   │   ├── class-vd-device-service.php        (<250 lines)
│   │   ├── class-vd-encryption-service.php    (<150 lines)
│   │   ├── class-vd-email-service.php         (<200 lines)
│   │   ├── class-vd-credential-builder.php    (<200 lines)
│   │   ├── class-vd-field-resolver.php        (<200 lines)
│   │   ├── class-vd-validation-service.php    (<200 lines)
│   │   ├── class-vd-vps-detector.php          (<250 lines)
│   │   └── class-vd-logger.php                (<150 lines)
│   │
│   ├── repositories/                # Data access layer
│   │   ├── class-vd-account-repository.php    (<200 lines)
│   │   ├── class-vd-pool-repository.php       (<200 lines)
│   │   ├── class-vd-license-repository.php    (<200 lines)
│   │   ├── class-vd-device-repository.php     (<200 lines)
│   │   └── class-vd-config-repository.php     (<200 lines)
│   │
│   ├── integrations/                # External integrations
│   │   ├── class-vd-woocommerce-integration.php  (<250 lines)
│   │   ├── class-vd-lmfwc-integration.php        (<200 lines)
│   │   └── class-vd-pool-capacity-checker.php    (<200 lines)
│   │
│   ├── api/                         # REST API endpoints
│   │   ├── class-vd-api-base.php              (<150 lines)
│   │   ├── class-vd-license-api.php           (<250 lines)
│   │   └── class-vd-portal-api.php            (<300 lines)
│   │
│   ├── helpers/                     # Helper/Utility classes
│   │   ├── class-vd-sanitizer.php             (<150 lines)
│   │   ├── class-vd-validator.php             (<200 lines)
│   │   └── functions.php                      (<200 lines, pure functions)
│   │
│   └── exceptions/                  # Custom exceptions
│       ├── class-vd-exception.php             # Base exception
│       ├── class-vd-account-exception.php
│       ├── class-vd-pool-exception.php
│       ├── class-vd-license-exception.php
│       ├── class-vd-device-exception.php
│       └── class-vd-validation-exception.php
│
├── admin/                           # Admin interface
│   ├── class-vd-admin.php          # Admin main class (<200 lines)
│   │
│   ├── pages/                       # Admin page controllers
│   │   ├── class-vd-accounts-page.php         (<300 lines)
│   │   ├── class-vd-pools-page.php            (<300 lines)
│   │   ├── class-vd-licenses-page.php         (<300 lines)
│   │   ├── class-vd-devices-page.php          (<300 lines)
│   │   ├── class-vd-logs-page.php             (<300 lines)
│   │   ├── class-vd-analytics-page.php        (<300 lines)
│   │   └── class-vd-settings-page.php         (<300 lines)
│   │
│   ├── views/                       # Admin view templates
│   │   ├── accounts/
│   │   │   ├── add.php                        (<200 lines HTML)
│   │   │   ├── edit.php                       (<200 lines HTML)
│   │   │   ├── list.php                       (<250 lines HTML)
│   │   │   └── view.php                       (<200 lines HTML)
│   │   ├── pools/
│   │   │   ├── add.php
│   │   │   ├── edit.php
│   │   │   ├── list.php
│   │   │   └── view.php
│   │   ├── licenses/
│   │   ├── devices/
│   │   ├── logs/
│   │   ├── analytics/
│   │   └── settings/
│   │
│   ├── partials/                    # Reusable view components
│   │   ├── header.php                         (<50 lines)
│   │   ├── footer.php                         (<50 lines)
│   │   ├── pagination.php                     (<100 lines)
│   │   ├── table-actions.php                  (<100 lines)
│   │   ├── form-fields.php                    (<150 lines)
│   │   └── alerts.php                         (<100 lines)
│   │
│   └── ajax/                        # AJAX handlers
│       ├── class-vd-account-ajax.php          (<200 lines)
│       ├── class-vd-pool-ajax.php             (<200 lines)
│       └── class-vd-license-ajax.php          (<200 lines)
│
├── public/                          # Public-facing (if needed)
│   └── class-vd-public.php
│
├── assets/                          # Static assets
│   ├── css/
│   │   ├── admin.css                          (<500 lines)
│   │   ├── admin.min.css
│   │   └── public.css
│   ├── js/
│   │   ├── admin.js                           (<400 lines)
│   │   ├── admin.min.js
│   │   ├── config-builder.js                  (<300 lines)
│   │   └── validation.js                      (<300 lines)
│   └── images/
│       └── icons/
│
├── templates/                       # Email & other templates
│   └── emails/
│       ├── license-delivery.php               (<150 lines)
│       ├── pool-full-notification.php         (<100 lines)
│       ├── account-expiring.php               (<100 lines)
│       └── device-rotation-request.php        (<100 lines)
│
├── languages/                       # Translation files
│   └── vd-license-manager.pot
│
└── tests/                           # Testing (future)
    ├── bootstrap.php
    ├── unit/
    └── integration/
```

### **Database Tables (11 tables)**

```
Prefix: bz_vd_

Tables to create:
1.  bz_vd_provider_accounts         # Provider account credentials
2.  bz_vd_product_pools             # Product to pool mappings
3.  bz_vd_pool_accounts             # Pool to account mappings
4.  bz_vd_cookie_assignments        # Cookie assignments (if used)
5.  bz_vd_product_share_configs     # Product response field configs
6.  bz_vd_device_fingerprints       # Device fingerprint storage
7.  bz_vd_license_devices           # License to device mappings
8.  bz_vd_license_device_limits     # Device limits per license
9.  bz_vd_account_fetch_log         # Account fetch history
10. bz_vd_license_access_log        # License access attempts
11. bz_vd_license_rate_limits       # Rate limiting tracking
```

---

## 🏗️ CODE ORGANIZATION RULES

### **Rule 1: Single Responsibility Principle**

Each class/file should have ONE clear responsibility.

**✅ GOOD:**
```php
// File: class-vd-account-service.php
// Responsibility: Business logic for accounts

class VD_Account_Service {
    public function create_account($data) { ... }
    public function update_account($id, $data) { ... }
    public function delete_account($id) { ... }
    public function validate_account_data($data) { ... }
}

// File: class-vd-account-repository.php
// Responsibility: Database queries for accounts

class VD_Account_Repository {
    public function find($id) { ... }
    public function find_by_provider($provider) { ... }
    public function save($data) { ... }
    public function delete($id) { ... }
}
```

**❌ BAD:**
```php
// File: accounts.php - VIOLATES SRP
// Too many responsibilities!

class Accounts {
    public function add_account() { 
        // Database query
        // Business logic
        // Email sending
        // Rendering HTML
        // All in one method - 500 lines!
    }
}
```

### **Rule 2: Separation of Concerns (Layered Architecture)**

```
Presentation Layer (Views)
         ↓
Controller Layer (Admin Pages / API)
         ↓
Service Layer (Business Logic)
         ↓
Repository Layer (Database Queries)
         ↓
Database
```

**Example:**
```php
// Controller (Admin Page)
class VD_Accounts_Page {
    private $account_service;
    
    public function __construct(VD_Account_Service $service) {
        $this->account_service = $service;
    }
    
    public function handle_create_account() {
        $data = $this->get_form_data();
        $result = $this->account_service->create_account($data);
        $this->render_response($result);
    }
}

// Service (Business Logic)
class VD_Account_Service {
    private $repository;
    private $encryption;
    
    public function create_account($data) {
        $this->validate($data);
        $data['password'] = $this->encryption->encrypt($data['password']);
        return $this->repository->save($data);
    }
}

// Repository (Database)
class VD_Account_Repository {
    public function save($data) {
        global $wpdb;
        return $wpdb->insert($this->table, $data);
    }
}
```

### **Rule 3: Dependency Injection**

Pass dependencies via constructor, NOT via globals or static methods.

**✅ GOOD:**
```php
class VD_Account_Service {
    private $repository;
    private $encryption;
    private $logger;
    
    public function __construct(
        VD_Account_Repository $repository,
        VD_Encryption_Service $encryption,
        VD_Logger $logger
    ) {
        $this->repository = $repository;
        $this->encryption = $encryption;
        $this->logger = $logger;
    }
    
    public function create_account($data) {
        $this->logger->info('Creating account');
        // Use injected dependencies
    }
}
```

**❌ BAD:**
```php
class VD_Account_Service {
    public function create_account($data) {
        global $wpdb; // NO! Don't use globals
        
        // Direct instantiation - hard to test
        $encryption = new VD_Encryption_Service();
        
        // Static method - tight coupling
        VD_Logger::log('Creating account');
    }
}
```

### **Rule 4: DRY (Don't Repeat Yourself)**

If you write the same code twice, extract it into a method/function.

**✅ GOOD:**
```php
class VD_Account_Service {
    public function create_account($data) {
        $this->validate_and_encrypt($data);
        return $this->repository->save($data);
    }
    
    public function update_account($id, $data) {
        $this->validate_and_encrypt($data);
        return $this->repository->update($id, $data);
    }
    
    private function validate_and_encrypt(&$data) {
        $this->validate($data);
        if (isset($data['password'])) {
            $data['password'] = $this->encryption->encrypt($data['password']);
        }
    }
}
```

**❌ BAD:**
```php
class VD_Account_Service {
    public function create_account($data) {
        // Validation code (50 lines)
        // Encryption code (20 lines)
        return $this->repository->save($data);
    }
    
    public function update_account($id, $data) {
        // Same validation code (50 lines) - DUPLICATED!
        // Same encryption code (20 lines) - DUPLICATED!
        return $this->repository->update($id, $data);
    }
}
```

### **Rule 5: Meaningful Names**

Use descriptive names that explain what something does.

**✅ GOOD:**
```php
$active_licenses_for_account = $this->count_active_licenses($account_id);
$is_pool_at_capacity = $this->check_pool_capacity($pool_id);
$encrypted_password = $this->encrypt_password($plain_password);
```

**❌ BAD:**
```php
$cnt = $this->get($id);  // What is cnt? What does get() do?
$x = $this->check($y);   // Meaningless variable names
$data = $this->process($stuff);  // Too vague
```

---

## 📏 FILE SIZE LIMITS

### **CRITICAL: Files MUST NOT exceed these limits**

| File Type | Max Lines | Action if Exceeded |
|-----------|-----------|-------------------|
| **Service Classes** | 250 | Split into multiple service classes |
| **Repository Classes** | 200 | Split by entity or use traits |
| **Controller/Page Classes** | 300 | Extract methods to helper classes |
| **View Templates** | 250 | Break into partials |
| **Helper Functions** | 200 | Split into multiple files by category |
| **Main Plugin File** | 200 | Bootstrap only, delegate to classes |
| **JavaScript Files** | 400 | Split into modules |
| **CSS Files** | 500 | Use SCSS and organize by component |
| **Migration Files** | 150 | One table per migration |

### **What to Do When File Exceeds Limit**

**Example: VD_Account_Service is 400 lines (exceeds 250)**

**Solution:**
```php
// Split into smaller, focused classes

// class-vd-account-service.php (150 lines)
class VD_Account_Service {
    private $creator;
    private $updater;
    
    public function __construct(
        VD_Account_Creator $creator,
        VD_Account_Updater $updater
    ) {
        $this->creator = $creator;
        $this->updater = $updater;
    }
    
    public function create_account($data) {
        return $this->creator->create($data);
    }
    
    public function update_account($id, $data) {
        return $this->updater->update($id, $data);
    }
}

// class-vd-account-creator.php (120 lines)
class VD_Account_Creator {
    public function create($data) {
        // All creation logic here
    }
}

// class-vd-account-updater.php (130 lines)
class VD_Account_Updater {
    public function update($id, $data) {
        // All update logic here
    }
}
```

---

## 🏷️ NAMING CONVENTIONS

### **Classes**

```php
// Format: VD_Entity_Type
// PascalCase with VD_ prefix

class VD_Account_Service { }
class VD_License_Repository { }
class VD_Pool_Capacity_Checker { }
class VD_Encryption_Service { }
```

### **Functions & Methods**

```php
// snake_case
public function get_active_licenses() { }
private function validate_account_data() { }
protected function encrypt_password() { }
```

### **Variables**

```php
// snake_case
$account_id = 123;
$active_licenses = [];
$is_pool_full = false;
$encrypted_password = '...';
```

### **Constants**

```php
// UPPER_SNAKE_CASE
define('VD_PLUGIN_VERSION', '1.0.0');
const MAX_DEVICES_PER_LICENSE = 5;
const DEFAULT_RATE_LIMIT = 10;
```

### **Database Tables**

```php
// Lowercase with prefix
$table_name = $wpdb->prefix . 'vd_license_keys';
// Result: bz_vd_license_keys
```

### **Files**

```php
// Lowercase with hyphens
class-vd-account-service.php
class-vd-encryption-service.php
functions-helpers.php
```

---

## 💻 CODING STANDARDS

### **Follow WordPress PHP Coding Standards**

Reference: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

### **Indentation & Spacing**

```php
// Use 4 spaces (NOT tabs)
class VD_Example {
    
    public function method_name() {
        if ( $condition ) {
            // code here
        }
    }
}

// Spacing around operators and parentheses
if ( $x === $y ) {
    $foo = $bar + $baz;
}

// NOT: if($x===$y){$foo=$bar+$baz;}
```

### **Braces**

```php
// Opening brace on same line
public function example() {
    if ( $condition ) {
        // code
    } else {
        // code
    }
}

// NOT:
public function example()
{
    if ( $condition )
    {
        // code
    }
}
```

### **Line Length**

```php
// Maximum 120 characters per line
// Break long lines intelligently

// ✅ GOOD
$result = $this->repository->find_licenses_by_criteria(
    $account_id,
    $status,
    $limit,
    $offset
);

// ❌ BAD (too long)
$result = $this->repository->find_licenses_by_criteria($account_id, $status, $limit, $offset, $order_by, $order_direction);
```

### **Yoda Conditions**

```php
// WordPress style: constant/literal first
if ( 'active' === $status ) {  // ✅ GOOD
if ( 5 === $count ) {          // ✅ GOOD
if ( true === $flag ) {        // ✅ GOOD

// NOT:
if ( $status === 'active' ) {  // ❌ BAD
if ( $count === 5 ) {          // ❌ BAD
```

### **Array Syntax**

```php
// Use short array syntax
$array = [
    'key1' => 'value1',
    'key2' => 'value2',
];

// NOT: $array = array('key1' => 'value1');
```

### **String Concatenation**

```php
// Spaces around dot
$message = 'Hello ' . $name . '!';

// NOT: $message='Hello'.$name.'!';
```

---

## 🔒 SECURITY REQUIREMENTS

### **Rule 1: NEVER Trust User Input**

**Always sanitize input:**

```php
// ✅ GOOD
$account_login = sanitize_text_field( $_POST['account_login'] );
$capacity = absint( $_POST['capacity'] );
$email = sanitize_email( $_POST['email'] );
$url = esc_url_raw( $_POST['url'] );

// Sanitize arrays
$ids = array_map( 'absint', $_POST['ids'] );
```

**Always validate:**

```php
if ( ! is_email( $email ) ) {
    return new WP_Error( 'invalid_email', __( 'Invalid email address', 'vd-license-manager' ) );
}

if ( empty( $account_login ) ) {
    return new WP_Error( 'missing_login', __( 'Account login is required', 'vd-license-manager' ) );
}

if ( $capacity < 1 || $capacity > 100 ) {
    return new WP_Error( 'invalid_capacity', __( 'Capacity must be between 1 and 100', 'vd-license-manager' ) );
}
```

### **Rule 2: Use Prepared Statements (ALWAYS)**

**✅ GOOD:**
```php
global $wpdb;

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_license_keys 
        WHERE license_key = %s AND status = %s",
        $license_key,
        $status
    )
);

// Multiple values
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->prefix}vd_provider_accounts 
        SET capacity = %d, expires_at = %s 
        WHERE id = %d",
        $capacity,
        $expires_at,
        $account_id
    )
);
```

**❌ BAD - SQL Injection Vulnerability:**
```php
// NEVER do this!
$wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}vd_license_keys 
    WHERE license_key = '$license_key'"
);
```

### **Rule 3: Check Permissions**

```php
// Before ANY admin action
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Unauthorized access', 'vd-license-manager' ) );
}

// For custom capabilities (later)
if ( ! current_user_can( 'vd_manage_accounts' ) ) {
    wp_die( __( 'You do not have permission to manage accounts', 'vd-license-manager' ) );
}
```

### **Rule 4: Use Nonces**

```php
// In form
wp_nonce_field( 'vd_add_account', 'vd_account_nonce' );

// In handler
if ( ! isset( $_POST['vd_account_nonce'] ) || 
     ! wp_verify_nonce( $_POST['vd_account_nonce'], 'vd_add_account' ) ) {
    wp_die( __( 'Security check failed', 'vd-license-manager' ) );
}
```

### **Rule 5: Escape Output**

```php
// ✅ GOOD
echo esc_html( $account_name );
echo esc_url( $account_url );
echo esc_attr( $input_value );
echo wp_kses_post( $description );  // Allow safe HTML

// In attributes
<input type="text" value="<?php echo esc_attr( $value ); ?>">

// In URLs
<a href="<?php echo esc_url( $url ); ?>">Link</a>

// ❌ BAD - XSS vulnerability
echo $account_name;  // NO!
echo $_POST['value'];  // NO!
```

### **Rule 6: Encrypt Sensitive Data**

```php
// ALWAYS encrypt passwords, API keys, tokens
class VD_Encryption_Service {
    
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }
        
        $key = VD_ENCRYPTION_KEY;
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes( 16 );
        
        $encrypted = openssl_encrypt( $value, $method, base64_decode( substr( $key, 7 ) ), 0, $iv );
        
        return base64_encode( $encrypted . '::' . $iv );
    }
    
    public static function decrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }
        
        $key = VD_ENCRYPTION_KEY;
        $method = 'AES-256-CBC';
        
        list( $encrypted_data, $iv ) = explode( '::', base64_decode( $value ), 2 );
        
        return openssl_decrypt( $encrypted_data, $method, base64_decode( substr( $key, 7 ) ), 0, $iv );
    }
}
```

### **Rule 7: Rate Limiting**

```php
// Check rate limits before processing API requests
$rate_limit_key = 'vd_api_' . $license_key;
$attempts = get_transient( $rate_limit_key );

if ( false === $attempts ) {
    $attempts = 0;
}

if ( $attempts >= VD_RATE_LIMIT_MAX_HITS ) {
    return new WP_Error(
        'rate_limit_exceeded',
        __( 'Too many requests. Please try again later.', 'vd-license-manager' ),
        [ 'status' => 429 ]
    );
}

// Increment counter
set_transient( $rate_limit_key, $attempts + 1, VD_RATE_LIMIT_WINDOW );
```

---

## 🗄️ DATABASE GUIDELINES

### **Rule 1: Use WordPress $wpdb**

```php
global $wpdb;
$table_name = $wpdb->prefix . 'vd_license_keys';
```

### **Rule 2: Table Naming Convention**

```
Format: {wordpress_prefix}vd_{entity_name}

Examples:
- bz_vd_provider_accounts
- bz_vd_license_keys
- bz_vd_device_fingerprints
```

### **Rule 3: CRITICAL - Database Schema Reference**

**🎯 GOLDEN RULE: ALWAYS use database_schema.md as SINGLE SOURCE OF TRUTH**

```php
// BEFORE any database operation, check schema file:
// Path: wp-content/plugins/vd-license-manager/docs/database_schema.md
```

**✅ REQUIRED ACTIONS:**

1. **Before creating/modifying tables or columns:**
   - Read `docs/database_schema.md` first
   - Use EXACT table and column names from schema
   - Follow EXACT data types and constraints

2. **When adding new tables/columns:**
   - Update `docs/database_schema.md` FIRST
   - Then implement in code
   - Never create database objects not in schema

3. **When modifying existing structure:**
   - Update `docs/database_schema.md` FIRST
   - Create migration script
   - Test against schema compliance

**❌ NEVER DO:**
```php
// Wrong - hardcoded table/column names without schema reference
$wpdb->query("SELECT login_password FROM accounts");  // Column doesn't exist in schema!
$wpdb->query("SELECT cookie FROM accounts");          // Should be 'cookies'!
```

**✅ ALWAYS DO:**
```php
// Correct - verify against docs/database_schema.md first
$wpdb->query("SELECT account_password FROM {$wpdb->prefix}vd_provider_accounts");  // ✅ Matches schema
$wpdb->query("SELECT cookies FROM {$wpdb->prefix}vd_provider_accounts");           // ✅ Matches schema
```

**Schema Compliance Checklist:**
- [ ] Read `docs/database_schema.md` before ANY database work
- [ ] Verify table names match schema exactly
- [ ] Verify column names match schema exactly
- [ ] Update schema file BEFORE implementing changes
- [ ] Test all queries against actual schema structure

### **Rule 4: Always Use Prepared Statements**

See Security Requirements above.

### **Rule 5: Use Transactions for Multi-Step Operations**

```php
global $wpdb;

$wpdb->query( 'START TRANSACTION' );

try {
    // Step 1: Insert account
    $wpdb->insert(
        $wpdb->prefix . 'vd_provider_accounts',
        $account_data
    );
    $account_id = $wpdb->insert_id;
    
    // Step 2: Assign to pool
    $wpdb->insert(
        $wpdb->prefix . 'vd_pool_accounts',
        [
            'pool_id' => $pool_id,
            'account_id' => $account_id,
        ]
    );
    
    $wpdb->query( 'COMMIT' );
    
    return $account_id;
    
} catch ( Exception $e ) {
    $wpdb->query( 'ROLLBACK' );
    
    VD_Logger::error( 'Transaction failed', [
        'error' => $e->getMessage(),
    ] );
    
    return new WP_Error( 'transaction_failed', $e->getMessage() );
}
```

### **Rule 6: Index Important Columns**

```php
// In migration files
$wpdb->query( "CREATE INDEX idx_license_key ON {$table_name} (license_key)" );
$wpdb->query( "CREATE INDEX idx_status ON {$table_name} (status)" );
$wpdb->query( "CREATE INDEX idx_product_id ON {$table_name} (product_id)" );
$wpdb->query( "CREATE INDEX idx_created_at ON {$table_name} (created_at)" );
```

### **Rule 7: Use Appropriate Data Types**

```php
// Migration example
$sql = "CREATE TABLE {$table_name} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    license_key VARCHAR(255) NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_license_key (license_key),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status)
) {$charset_collate};";
```

---

## 📚 WORDPRESS BEST PRACTICES

### **Hooks & Filters**

```php
// Use add_action and add_filter
add_action( 'woocommerce_order_status_completed', [ $this, 'handle_order_completed' ], 20, 1 );
add_filter( 'vd_license_credentials', [ $this, 'filter_credentials' ], 10, 2 );

// Priority: 20 runs after LMfWC (priority 10)
// Parameters: 1 = number of parameters to accept
```

### **Internationalization (i18n)**

```php
// Always wrap user-facing strings
__( 'License key is required', 'vd-license-manager' );
_e( 'Save Changes', 'vd-license-manager' );
_n( '%s license', '%s licenses', $count, 'vd-license-manager' );

// With variables
sprintf( 
    __( 'Account %s created successfully', 'vd-license-manager' ),
    $account_name 
);
```

### **Enqueue Scripts & Styles**

```php
public function enqueue_admin_assets() {
    wp_enqueue_style(
        'vd-admin-css',
        VD_PLUGIN_URL . 'assets/css/admin.css',
        [],
        VD_PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'vd-admin-js',
        VD_PLUGIN_URL . 'assets/js/admin.js',
        [ 'jquery' ],
        VD_PLUGIN_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script(
        'vd-admin-js',
        'vdAdminData',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'vd-admin-ajax' ),
        ]
    );
}

add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
```

### **AJAX Handlers**

```php
// Register AJAX actions
add_action( 'wp_ajax_vd_delete_account', [ $this, 'ajax_delete_account' ] );

public function ajax_delete_account() {
    // Check nonce
    check_ajax_referer( 'vd-admin-ajax', 'nonce' );
    
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [
            'message' => __( 'Unauthorized', 'vd-license-manager' ),
        ], 403 );
    }
    
    // Get and validate input
    $account_id = absint( $_POST['account_id'] );
    
    // Process
    $result = $this->account_service->delete_account( $account_id );
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [
            'message' => $result->get_error_message(),
        ] );
    }
    
    wp_send_json_success( [
        'message' => __( 'Account deleted successfully', 'vd-license-manager' ),
    ] );
}
```

### **Options & Transients**

```php
// Plugin settings
update_option( 'vd_settings', $settings );
$settings = get_option( 'vd_settings', [] );

// Transients (temporary data)
set_transient( 'vd_cache_licenses', $licenses, HOUR_IN_SECONDS );
$licenses = get_transient( 'vd_cache_licenses' );

// Delete transient
delete_transient( 'vd_cache_licenses' );
```

---

## 🔌 API DEVELOPMENT

### **REST API Structure**

```php
class VD_Portal_API extends WP_REST_Controller {
    
    public function register_routes() {
        register_rest_route(
            'vd/v1',
            '/license/resolve-info',
            [
                'methods' => 'GET',
                'callback' => [ $this, 'get_license_info' ],
                'permission_callback' => '__return_true', // Public endpoint
                'args' => [
                    'license_key' => [
                        'required' => true,
                        'validate_callback' => [ $this, 'validate_license_key' ],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'device_id' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }
    
    public function get_license_info( WP_REST_Request $request ) {
        $license_key = $request->get_param( 'license_key' );
        
        // Process...
        
        return new WP_REST_Response(
            [
                'success' => true,
                'data' => $data,
            ],
            200
        );
    }
    
    public function validate_license_key( $param, $request, $key ) {
        return ! empty( $param ) && strlen( $param ) >= 10;
    }
}
```

### **HTTP Status Codes**

```php
// Success
return new WP_REST_Response( $data, 200 );  // OK
return new WP_REST_Response( $data, 201 );  // Created

// Client Errors
return new WP_Error( 'invalid_license', 'Invalid license key', [ 'status' => 400 ] );  // Bad Request
return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );  // Unauthorized
return new WP_Error( 'forbidden', 'Forbidden', [ 'status' => 403 ] );  // Forbidden
return new WP_Error( 'not_found', 'Not found', [ 'status' => 404 ] );  // Not Found
return new WP_Error( 'rate_limit', 'Too many requests', [ 'status' => 429 ] );  // Rate Limit

// Server Errors
return new WP_Error( 'server_error', 'Internal error', [ 'status' => 500 ] );
```

---

## 🚨 ERROR HANDLING

### **Use WP_Error for User-Facing Errors**

```php
public function create_account( $data ) {
    // Validation errors
    if ( empty( $data['provider'] ) ) {
        return new WP_Error(
            'missing_provider',
            __( 'Provider is required', 'vd-license-manager' )
        );
    }
    
    // Business logic errors
    if ( $this->account_exists( $data['account_login'], $data['provider'] ) ) {
        return new WP_Error(
            'account_exists',
            __( 'An account with this login already exists for this provider', 'vd-license-manager' )
        );
    }
    
    // Success
    return $account_id;
}

// Check for errors
$result = $this->account_service->create_account( $data );

if ( is_wp_error( $result ) ) {
    // Handle error
    echo '<div class="error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
} else {
    // Success
    $account_id = $result;
}
```

### **Use Exceptions for System Errors**

```php
class VD_Encryption_Exception extends Exception { }

public function encrypt( $value ) {
    if ( ! function_exists( 'openssl_encrypt' ) ) {
        throw new VD_Encryption_Exception(
            __( 'OpenSSL PHP extension is required but not available', 'vd-license-manager' )
        );
    }
    
    if ( ! defined( 'VD_ENCRYPTION_KEY' ) ) {
        throw new VD_Encryption_Exception(
            __( 'Encryption key is not configured', 'vd-license-manager' )
        );
    }
    
    // Encrypt...
    return $encrypted;
}

// Usage with try-catch
try {
    $encrypted = $this->encryption->encrypt( $password );
} catch ( VD_Encryption_Exception $e ) {
    VD_Logger::error( 'Encryption failed', [
        'error' => $e->getMessage(),
    ] );
    
    return new WP_Error( 'encryption_failed', __( 'Failed to encrypt sensitive data', 'vd-license-manager' ) );
}
```

### **Logging**

```php
class VD_Logger {
    
    public static function error( $message, $context = [] ) {
        self::log( 'ERROR', $message, $context );
    }
    
    public static function warning( $message, $context = [] ) {
        self::log( 'WARNING', $message, $context );
    }
    
    public static function info( $message, $context = [] ) {
        if ( ! VD_DEBUG ) {
            return;
        }
        self::log( 'INFO', $message, $context );
    }
    
    public static function debug( $message, $context = [] ) {
        if ( ! VD_DEBUG ) {
            return;
        }
        self::log( 'DEBUG', $message, $context );
    }
    
    private static function log( $level, $message, $context ) {
        $log_entry = sprintf(
            "[%s] [%s] %s %s\n",
            gmdate( 'Y-m-d H:i:s' ),
            $level,
            $message,
            ! empty( $context ) ? wp_json_encode( $context ) : ''
        );
        
        error_log( $log_entry, 3, WP_CONTENT_DIR . '/debug-vd.log' );
    }
}

// Usage
VD_Logger::error( 'Pool capacity check failed', [
    'pool_id' => $pool_id,
    'capacity' => $capacity,
    'assigned' => $assigned_count,
] );

VD_Logger::info( 'Account created', [
    'account_id' => $account_id,
    'provider' => $provider,
] );
```

---

## 📝 DOCUMENTATION REQUIREMENTS

### **PHPDoc for Classes**

```php
/**
 * Account Service - Manages provider account business logic
 *
 * Handles creation, updates, deletion, and validation of provider accounts
 * including credential encryption and pool assignment.
 *
 * @package    VD_License_Manager
 * @subpackage Services
 * @since      1.0.0
 * @author     VidieuVN <dev@vidieu.vn>
 */
class VD_Account_Service {
```

### **PHPDoc for Methods**

```php
/**
 * Create a new provider account
 *
 * Validates account data, encrypts sensitive fields (passwords, API keys),
 * and saves to database. Also logs the creation event.
 *
 * @since  1.0.0
 * @access public
 * 
 * @param  array $data {
 *     Account data.
 *     
 *     @type string $provider        Provider name (required)
 *     @type string $account_login   Account login/email (required)
 *     @type string $display_name    Display name for admin
 *     @type int    $capacity        Max licenses (default: 1)
 *     @type string $password        Account password (will be encrypted)
 *     @type array  $custom_fields   Additional custom fields
 * }
 * 
 * @return int|WP_Error Account ID on success, WP_Error on failure
 * 
 * @throws VD_Encryption_Exception If encryption fails
 */
public function create_account( array $data ) {
```

### **Inline Comments for Complex Logic**

```php
public function assign_license_to_pool( $license_id, $product_id ) {
    // Get all pools for this product ordered by priority
    $pools = $this->pool_repository->get_pools_by_product( $product_id );
    
    foreach ( $pools as $pool ) {
        // Check pool capacity before assigning
        // This prevents overselling and ensures service availability
        $capacity = $pool->capacity;
        $assigned = $this->count_assigned_licenses( $pool->id );
        
        if ( $assigned >= $capacity ) {
            // Pool is full, try next pool by priority
            VD_Logger::warning( 'Pool at capacity', [
                'pool_id' => $pool->id,
                'capacity' => $capacity,
                'assigned' => $assigned,
            ] );
            continue;
        }
        
        // Pool has space, assign license
        return $this->assign_to_pool( $license_id, $pool->id );
    }
    
    // No available pool found
    return new WP_Error(
        'no_pool_available',
        __( 'All pools are at full capacity', 'vd-license-manager' )
    );
}
```

### **File Headers**

```php
<?php
/**
 * Account Service
 *
 * Handles all business logic related to provider accounts including
 * creation, updates, validation, and credential management.
 *
 * @package    VD_License_Manager
 * @subpackage Services
 * @since      1.0.0
 * @author     VidieuVN
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

---

## 🧪 TESTING REQUIREMENTS

### **Manual Testing Checklist**

After implementing each feature, create a testing checklist:

```php
/**
 * TESTING CHECKLIST - Account Management
 * 
 * Basic Operations:
 * □ Create account with all required fields
 * □ Create account with custom fields
 * □ Edit account - keep existing password (blank password field)
 * □ Edit account - change password
 * □ Delete account
 * □ Delete account that's assigned to pools (should show warning)
 * 
 * Validation:
 * □ Try creating account without provider - should fail
 * □ Try creating account without login - should fail
 * □ Try creating duplicate account (same login + provider) - should fail
 * □ Try setting capacity to 0 - should fail
 * □ Try setting capacity > 100 - should fail
 * □ Try invalid email in custom fields - should fail
 * 
 * Encryption:
 * □ Verify password is encrypted in database
 * □ Verify password can be decrypted correctly
 * □ Verify API keys are encrypted
 * □ Verify 2FA secrets are encrypted
 * 
 * Integration:
 * □ Account appears in pool assignment dropdown
 * □ Account can be assigned to pool
 * □ Deleting account removes from pools
 * □ Account expiration is respected
 * 
 * Edge Cases:
 * □ Very long account login (255 chars)
 * □ Special characters in password
 * □ Unicode characters in display name
 * □ Null/empty values in optional fields
 * □ Concurrent account creation (race condition)
 */
```

### **Integration Testing**

```php
/**
 * INTEGRATION TEST - Full License Flow
 * 
 * Setup:
 * 1. Create provider account (Helium10)
 * 2. Create pool with account
 * 3. Assign product 8210 to pool
 * 4. Configure product response fields
 * 
 * Test Flow:
 * □ Customer completes order for product 8210
 * □ LMfWC generates license key
 * □ VD plugin receives order_completed hook
 * □ License synced to bz_vd_license_keys
 * □ Pool capacity checked
 * □ Account assigned from pool
 * □ Email sent to customer with license
 * □ Customer calls API with license key
 * □ Device registered
 * □ Credentials returned (cookie only for product 8210)
 * □ Access logged
 * □ Rate limit tracked
 * 
 * Verify:
 * □ Database: license record exists
 * □ Database: pool assignment exists
 * □ Database: device registered
 * □ Database: access log entry
 * □ Email: customer received credentials
 * □ API: subsequent calls with same device work
 * □ API: rate limit prevents excessive calls
 */
```

---

## ⚡ PERFORMANCE GUIDELINES

### **Rule 1: Minimize Database Queries**

**✅ GOOD - Use JOINs:**
```php
$sql = "SELECT 
            l.*,
            a.account_login,
            a.display_name,
            p.pool_name
        FROM {$wpdb->prefix}vd_license_keys l
        LEFT JOIN {$wpdb->prefix}vd_provider_accounts a ON l.assigned_account_id = a.id
        LEFT JOIN {$wpdb->prefix}vd_pools p ON l.assigned_pool_id = p.id
        WHERE l.status = 'active'
        LIMIT 50";

$licenses = $wpdb->get_results( $wpdb->prepare( $sql ) );
```

**❌ BAD - N+1 Query Problem:**
```php
$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vd_license_keys WHERE status = 'active'" );

foreach ( $licenses as $license ) {
    $account = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE id = {$license->account_id}" );
    // Query in loop! If 50 licenses = 50 additional queries!
}
```

### **Rule 2: Use Caching**

```php
public function get_active_licenses_for_account( $account_id ) {
    $cache_key = 'vd_active_licenses_' . $account_id;
    $licenses = wp_cache_get( $cache_key, 'vd-license-manager' );
    
    if ( false === $licenses ) {
        $licenses = $this->repository->get_active_licenses( $account_id );
        wp_cache_set( $cache_key, $licenses, 'vd-license-manager', HOUR_IN_SECONDS );
    }
    
    return $licenses;
}

// Invalidate cache on update
public function update_account( $account_id, $data ) {
    $result = $this->repository->update( $account_id, $data );
    
    if ( ! is_wp_error( $result ) ) {
        wp_cache_delete( 'vd_active_licenses_' . $account_id, 'vd-license-manager' );
    }
    
    return $result;
}
```

### **Rule 3: Select Only Needed Columns**

```php
// ✅ GOOD - Only select what you need
$count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_keys WHERE account_id = %d",
        $account_id
    )
);

// ❌ BAD - Select all columns when you only need count
$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vd_license_keys WHERE account_id = {$account_id}" );
$count = count( $licenses );
```

### **Rule 4: Use Pagination**

```php
public function get_licenses( $page = 1, $per_page = 50 ) {
    $offset = ( $page - 1 ) * $per_page;
    
    global $wpdb;
    $licenses = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_license_keys 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
    
    return $licenses;
}
```

### **Rule 5: Optimize Loops**

```php
// ✅ GOOD - Calculate once
$account_ids = array_column( $licenses, 'account_id' );
$accounts = $this->get_accounts_by_ids( $account_ids );  // Single query with IN clause

foreach ( $licenses as $license ) {
    $license->account = $accounts[ $license->account_id ] ?? null;
}

// ❌ BAD - Query in loop
foreach ( $licenses as $license ) {
    $license->account = $this->get_account( $license->account_id );  // Query per iteration!
}
```

---

## 🔄 GIT WORKFLOW

### **Branch Strategy**

```
main (production-ready)
  └── develop (development)
       ├── feature/account-management
       ├── feature/pool-capacity
       ├── feature/portal-api
       └── bugfix/device-fingerprint
```

**For this project: Push directly to main** (as per requirements)

### **Commit Message Format**

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `docs`: Documentation
- `style`: Code formatting
- `test`: Tests
- `chore`: Maintenance

**Examples:**

```
feat(accounts): Add account service with encryption

- Implemented VD_Account_Service class
- Added create, update, delete methods
- Integrated VD_Encryption_Service for passwords
- Added validation for all required fields

Closes #123

---

fix(pool): Correct capacity calculation for active licenses

Pool capacity was counting all licenses including inactive ones.
Changed to only count licenses with status='active'.

Fixes #456

---

refactor(services): Split AccountService into smaller classes

- Created VD_Account_Creator (120 lines)
- Created VD_Account_Updater (130 lines)
- Created VD_Account_Validator (100 lines)
- Main service now orchestrates sub-services

This improves testability and adheres to SRP.

Related to #789
```

### **When to Commit**

**✅ DO Commit:**
- After completing a logical unit of work
- After each class/file is complete and tested
- Before switching to a different task
- At the end of each work session

**❌ DON'T Commit:**
- Broken/non-functional code
- Code with syntax errors
- Incomplete features (unless using feature branch)
- Commented-out debug code

### **Commit and Push Process**

```bash
# 1. Check status
git status

# 2. Add files
git add includes/services/class-vd-account-service.php
git add includes/repositories/class-vd-account-repository.php

# Or add all
git add .

# 3. Commit with message
git commit -m "feat(accounts): Implement account service with encryption"

# 4. Push to main
git push origin main

# 5. Verify
git log --oneline -5
```

---

## ✅ BEFORE STARTING EACH TASK

### **Pre-Task Checklist:**

1. ✅ **Read the task requirements** from PROJECT_ROADMAP.md
2. ✅ **Review relevant FLOW documentation** (FLOW_1, FLOW_2, FLOW_3, or FLOW_4)
3. ✅ **Check ENVIRONMENT_CONFIG_COMPLETE.md** for configuration values
4. ✅ **Review this CLAUDE.MD** for coding standards
5. ✅ **Understand dependencies** - What other classes/files are needed?
6. ✅ **Plan the implementation:**
   - Which classes need to be created?
   - How will they interact?
   - What database tables are involved?
   - What needs to be tested?
7. ✅ **Check file size limits** - Plan to split if needed
8. ✅ **Consider security** - What needs validation? Encryption?

### **During Implementation:**

1. ✅ **Write clean, documented code** (PHPDoc for all public methods)
2. ✅ **Follow naming conventions** strictly
3. ✅ **Use dependency injection** (no globals in services)
4. ✅ **Implement error handling** (WP_Error for user errors, exceptions for system errors)
5. ✅ **Add security measures** (nonces, sanitization, escaping)
6. ✅ **Log important events** (VD_Logger)
7. ✅ **Stay under file size limits**

### **After Implementation:**

1. ✅ **Manual testing** using the checklist
2. ✅ **Code review** against this CLAUDE.MD
3. ✅ **Verify no hardcoded values** (use constants)
4. ✅ **Check all strings are translatable** (i18n)
5. ✅ **Commit with proper message**
6. ✅ **Push to main branch**
7. ✅ **Update PROJECT_ROADMAP.md** (mark task complete)

---

## 🚀 QUICK START FOR EACH TASK

```
1. Read task in PROJECT_ROADMAP.md
2. Check FLOW documentation (if applicable)
3. Review ENVIRONMENT_CONFIG_COMPLETE.md
4. Plan implementation (classes, files, structure)
5. Code following ALL guidelines in this file
6. Add PHPDoc documentation
7. Manual test using checklist
8. Commit: git commit -m "feat(scope): description"
9. Push: git push origin main
10. Report completion with summary
```

---

## 📖 IMPORTANT REFERENCES

- **FLOW Documents:** Detailed business logic for each flow
- **ENVIRONMENT_CONFIG_COMPLETE.md:** All configuration values
- **PROJECT_ROADMAP.md:** 5-week development plan
- **WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/
- **WordPress Security:** https://developer.wordpress.org/apis/security/
- **WordPress Database:** https://developer.wordpress.org/apis/handbook/database/

---

## ⚠️ CRITICAL RULES - NEVER VIOLATE

1. ❌ **NEVER** exceed file size limits
2. ❌ **NEVER** use SQL without prepared statements
3. ❌ **NEVER** trust user input without sanitization
4. ❌ **NEVER** output data without escaping
5. ❌ **NEVER** commit broken/untested code
6. ❌ **NEVER** hardcode configuration values (use constants)
7. ❌ **NEVER** ignore security best practices
8. ❌ **NEVER** skip PHPDoc documentation
9. ❌ **NEVER** create monolithic files (split when approaching limit)
10. ❌ **NEVER** use deprecated WordPress functions

---

## 💬 COMMUNICATION WITH USER

### **When Starting a Task:**

```
I understand the task: [brief description]

I will create the following files:
- includes/services/class-vd-account-service.php (~200 lines)
- includes/repositories/class-vd-account-repository.php (~150 lines)
- admin/pages/class-vd-accounts-page.php (~250 lines)
- admin/views/accounts/add.php (~150 lines HTML)

Architecture:
- Repository handles database queries
- Service handles business logic and encryption
- Admin page handles HTTP requests and validation
- View renders HTML form

Estimated completion: 3-4 hours

Starting now...
```

### **When Completing a Task:**

```
✅ Task completed: Account Management - Basic CRUD

Files created:
- class-vd-account-service.php (187 lines)
- class-vd-account-repository.php (142 lines)
- class-vd-accounts-page.php (234 lines)
- admin/views/accounts/add.php (156 lines)

Features implemented:
- Account CRUD operations (Create, Read, Update, Delete)
- Password encryption using VD_Encryption_Service
- Validation for all required fields
- Nonce security for forms
- Permission checks (manage_options capability)

Manual testing completed:
✅ Create account with required fields
✅ Create account with custom fields
✅ Edit account (password handling works)
✅ Delete account
✅ Validation prevents invalid data
✅ Encryption working (verified in database)

Committed: feat(accounts): Implement account service
Pushed to main branch ✅

Ready for next task.
```

### **If You Need Clarification:**

```
I need clarification on: [specific question]

Current understanding:
- [what you understand]

Unclear:
- [what's unclear]
- [specific question]

Options I'm considering:
1. [Option A]
2. [Option B]

Please advise which approach is preferred, or provide clarification.
```

---

## 🎯 SUCCESS CRITERIA

**Your code is successful when:**

✅ It works correctly (tested manually)  
✅ It follows ALL guidelines in this document  
✅ It's secure (sanitized input, escaped output, prepared statements)  
✅ It's documented (PHPDoc for all public APIs)  
✅ It's maintainable (small files, clear names, separated concerns)  
✅ It uses configuration from constants (no hardcoded values)  
✅ It handles errors properly (WP_Error or exceptions)  
✅ It logs important events  
✅ It's committed with proper message  
✅ It's pushed to main branch  

---

## 📚 REMEMBER

**You are building a production-ready WordPress plugin that:**
- Handles real customer data and money
- Must be secure, reliable, and performant
- Will be maintained and extended over time
- Must follow WordPress best practices
- Represents professional quality code

**Quality over speed. Do it right the first time.**

**When in doubt:**
1. Check this CLAUDE.MD
2. Review FLOW documentation
3. Check ENVIRONMENT_CONFIG_COMPLETE.md
4. Ask for clarification

**Never guess. Always confirm.**

---

**END OF GUIDELINES**

> These guidelines are comprehensive. Follow them religiously. They exist to ensure you write the best possible code.

**Now go build something great! 🚀**
