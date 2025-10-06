# 📜 CLAUDE.MD - CODING RULES FOR VD LICENSE MANAGER

## 🎯 CORE PRINCIPLES

### **Project Goal**
Develop WordPress plugin "VD License Manager" for SaaS license management and distribution. Plugin must be:
- Easy to maintain and extend
- Clean, well-structured code
- High performance
- Secure
- WordPress Coding Standards compliant

---

## ⚠️ CRITICAL FILE STRUCTURE RULES

### **GOLDEN RULE: MAX 200-300 LINES PER FILE**

#### **Line Count Definition:**
- ✅ Include: comment blocks, docblocks, blank lines between methods
- ❌ Exclude: consecutive blank lines (>2 counts as 1), file headers

#### **Warning Zones:**
```
🟢 0-200 lines   → OK, continue
🟡 201-250 lines → Warning, consider splitting
🟠 251-300 lines → MAXIMUM LIMIT, MUST STOP
🔴 301+ lines    → VIOLATION, MUST SPLIT FILE
```

#### **When file exceeds 250 lines - MUST do one of:**

**Option 1: Split by logical function**
```php
// BEFORE (300 lines - VIOLATION)
class VD_Admin_Accounts {
    public function render_list() { /* 100 lines */ }
    public function render_add() { /* 120 lines */ }
    public function render_edit() { /* 80 lines */ }
}

// AFTER (3 files)
// File 1: class-vd-admin-accounts-list.php (150 lines)
class VD_Admin_Accounts_List {
    public function render() { /* 100 lines */ }
}

// File 2: class-vd-admin-accounts-add.php (170 lines)
class VD_Admin_Accounts_Add {
    public function render() { /* 120 lines */ }
}

// File 3: class-vd-admin-accounts-edit.php (130 lines)
class VD_Admin_Accounts_Edit {
    public function render() { /* 80 lines */ }
}
```

**Option 2: Extract strategies/helpers**
```php
// BEFORE (280 lines - VIOLATION)
class VD_Assignment {
    public function assign() { /* 50 lines */ }
    public function select_by_sticky() { /* 80 lines */ }
    public function select_by_weighted() { /* 80 lines */ }
    public function select_by_priority() { /* 70 lines */ }
}

// AFTER
// File 1: class-vd-assignment.php (100 lines)
class VD_Assignment {
    public function assign() { /* 80 lines */ }
}

// File 2: class-vd-assignment-strategies.php (230 lines)
class VD_Assignment_Strategies {
    public static function sticky($pool_id) { /* 80 lines */ }
    public static function weighted($pool_id) { /* 80 lines */ }
    public static function priority($pool_id) { /* 70 lines */ }
}
```

**Option 3: Split database tables**
```php
// BEFORE (320 lines - VIOLATION)
class VD_Database {
    public function create_all_tables() { /* 320 lines */ }
}

// AFTER (3 files, each <250 lines)
// File 1: class-vd-db-core.php
class VD_DB_Core {
    public function create_tables() { /* 4 core tables */ }
}

// File 2: class-vd-db-devices.php
class VD_DB_Devices {
    public function create_tables() { /* 3 device tables */ }
}

// File 3: class-vd-db-logs.php
class VD_DB_Logs {
    public function create_tables() { /* 4 log tables */ }
}
```

---

### **METHOD LENGTH RULES**

```
✅ Ideal:    20-40 lines/method
⚠️  Accept:   41-60 lines/method
🚫 Prohibit: 61+ lines/method
```

**When method exceeds 50 lines - MUST split:**

```php
// ❌ WRONG - Method too long (80 lines)
public function process_complex_logic($data) {
    // Validate (15 lines)
    // Process step 1 (20 lines)
    // Process step 2 (20 lines)
    // Save (15 lines)
    // Notify (10 lines)
}

// ✅ CORRECT - Split into smaller methods
public function process_complex_logic($data) {
    $validated = $this->validate_input($data);
    $step1 = $this->process_step_one($validated);
    $step2 = $this->process_step_two($step1);
    $this->save_results($step2);
    $this->send_notifications($step2);
    return $step2;
}

private function validate_input($data) { /* 15 lines */ }
private function process_step_one($data) { /* 20 lines */ }
private function process_step_two($data) { /* 20 lines */ }
private function save_results($data) { /* 15 lines */ }
private function send_notifications($data) { /* 10 lines */ }
```

---

### **CLASS RULES**

**One file = One class (Single Responsibility Principle)**

```php
// ❌ WRONG - Multiple classes in one file
// File: classes.php
class VD_Helper { }
class VD_Validator { }

// ✅ CORRECT - Each class in separate file
// File: class-vd-helper.php
class VD_Helper { }

// File: class-vd-validator.php
class VD_Validator { }
```

**Exception allowed:**
- Interface + Simple implementation (<100 lines total)
- Abstract + Concrete class if tightly related

---

## 📁 DIRECTORY STRUCTURE

```
vd-license-manager/
├── vd-license-manager.php          (max 200 lines)
├── uninstall.php                   (max 150 lines)
├── includes/
│   ├── class-vd-loader.php         (max 150 lines)
│   ├── class-vd-activator.php      (max 150 lines)
│   ├── class-vd-deactivator.php    (max 100 lines)
│   │
│   ├── database/                   (Each max 250 lines)
│   │   ├── class-vd-db-core.php
│   │   ├── class-vd-db-devices.php
│   │   └── class-vd-db-logs.php
│   │
│   ├── helpers/                    (Each max 200 lines)
│   │   ├── class-vd-security.php
│   │   ├── class-vd-data.php
│   │   └── class-vd-datetime.php
│   │
│   ├── core/                       (Each max 250 lines)
│   │   ├── class-vd-license-verify.php
│   │   ├── class-vd-rate-limit.php
│   │   ├── class-vd-assignment.php
│   │   └── class-vd-assignment-strategies.php
│   │
│   └── api/                        (Each max 250 lines)
│       ├── class-vd-rest-api.php
│       ├── class-vd-api-device-verify.php
│       ├── class-vd-api-account-info.php
│       ├── class-vd-api-history.php
│       └── class-vd-api-device-rotate.php
│
├── admin/pages/                    (Each max 250 lines)
│   ├── class-vd-admin-dashboard.php
│   ├── class-vd-admin-accounts-list.php
│   ├── class-vd-admin-accounts-add.php
│   └── ...
│
└── public/pages/                   (Each max 250 lines)
    ├── class-vd-portal-main.php
    ├── class-vd-portal-account-info.php
    └── ...
```

---

## 📝 WORDPRESS CODING STANDARDS

### **Naming Conventions:**
```php
// Classes: PascalCase with prefix
class VD_License_Manager { }

// Functions: snake_case with prefix
function vd_get_license_info() { }

// Variables: snake_case
$license_key = 'ABC-123';

// Constants: UPPERCASE with prefix
define('VD_VERSION', '1.0.0');

// Hooks: lowercase with prefix
add_action('vd_license_activated', 'callback');
```

### **File Naming:**
```
class-vd-{name}.php          → Classes
interface-vd-{name}.php      → Interfaces
trait-vd-{name}.php          → Traits
```

---

## 📚 DOCUMENTATION

### **PHPDoc required for all classes and public methods:**

```php
/**
 * Verify license and device information
 *
 * @package    VD_License_Manager
 * @subpackage Core
 * @since      1.0.0
 */
class VD_License_Verify {
    
    /**
     * Verify a license key
     *
     * @since  1.0.0
     * @param  string $license_key The license key to verify
     * @return array {
     *     @type bool   $ok         Success status
     *     @type int    $license_id License ID if found
     *     @type string $error      Error message if failed
     * }
     */
    public function verify_license($license_key) {
        // Implementation
    }
}
```

### **Inline comments for complex logic:**

```php
// Step 1: Validate input structure
if (!$this->is_valid_structure($data)) {
    return false;
}

// Step 2: Calculate weighted distribution
// Formula: weight / total_weight * 100
$total_weight = array_sum(array_column($data, 'weight'));
```

---

## 🔒 SECURITY

### **Always sanitize input:**
```php
$license_key = sanitize_text_field($_POST['license_key']);
$email = sanitize_email($_POST['email']);
$url = esc_url_raw($_POST['url']);
```

### **Always escape output:**
```php
echo esc_html($license_key);
echo esc_attr($device_name);
echo esc_url($download_link);
```

### **Always use prepared statements:**
```php
// ✅ CORRECT
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vd_accounts WHERE provider = %s",
    $provider
));

// ❌ WRONG - SQL injection risk
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}vd_accounts WHERE provider = '$provider'"
);
```

### **Nonce verification:**
```php
// Form
wp_nonce_field('vd_save_account', 'vd_account_nonce');

// Process
if (!wp_verify_nonce($_POST['vd_account_nonce'], 'vd_save_account')) {
    wp_die('Security check failed');
}
```

### **Capability checks:**
```php
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}
```

---

## 🚨 ERROR HANDLING

### **Use WP_Error, not exceptions:**

```php
// ✅ CORRECT - WordPress way
public function process() {
    $result = $this->some_operation();
    
    if (is_wp_error($result)) {
        error_log('VD Error: ' . $result->get_error_message());
        return $result;
    }
    
    return $result;
}

// ❌ WRONG - Don't use exceptions
public function process() {
    try {
        $result = $this->some_operation();
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}
```

---

## ⚡ PERFORMANCE

### **Use caching:**
```php
public function get_geolocation($ip) {
    $cache_key = 'vd_geo_' . md5($ip);
    $cached = get_transient($cache_key);
    
    if (false !== $cached) {
        return $cached;
    }
    
    $geo_data = $this->call_api($ip);
    set_transient($cache_key, $geo_data, 7 * DAY_IN_SECONDS);
    
    return $geo_data;
}
```

### **Avoid N+1 queries:**
```php
// ❌ WRONG
foreach ($licenses as $license) {
    $account = $wpdb->get_row("SELECT * FROM accounts WHERE id = {$license->account_id}");
}

// ✅ CORRECT
$account_ids = array_column($licenses, 'account_id');
$accounts = $wpdb->get_results(
    "SELECT * FROM accounts WHERE id IN (" . implode(',', $account_ids) . ")"
);
```

---

## 🔧 DEVELOPMENT WORKFLOW

### **Before writing code:**
```
□ Read phase requirements carefully
□ Identify files to create
□ Estimate line count
□ If >250 lines → plan file splitting strategy
□ Identify dependencies
□ Check naming conventions
```

### **While writing code (every 50 lines):**
```
□ Any method >40 lines that needs splitting?
□ Any duplicate code to extract?
□ Can extract helper methods?
□ Comments sufficient?
```

### **When reaching 200 lines - WARNING:**
```
⚠️  File at 200 lines.
    
    Consider:
    1. Can split methods to separate file?
    2. Can move logic to helper class?
    3. Can group related methods into separate class?
    
    MAX LIMIT: 250 lines
```

### **When reaching 250 lines - STOP:**
```
🛑 File at 250 lines - MAXIMUM LIMIT
   
   MUST do one of:
   [1] Split class into multiple files
   [2] Move static methods to helper class
   [3] Extract private methods to trait
   [4] Refactor to reduce complexity
   
   DO NOT exceed 300 lines.
```

### **After completing:**
```
□ File ≤ 250 lines (max 300 if absolutely necessary)
□ Each method ≤ 50 lines
□ PHPDoc complete
□ Code formatted per WPCS
□ No security issues
□ No SQL injection
□ Proper error handling
□ Nonce + capability checks
□ Manually tested
□ No PHP errors/warnings
```

---

## 🚨 VIOLATIONS

### **CRITICAL (fix immediately):**
```
🔴 File >300 lines
🔴 Method >80 lines
🔴 SQL injection vulnerability
🔴 XSS vulnerability
🔴 Missing nonce
🔴 No capability check
```
**→ STOP, refactor immediately**

### **HIGH (fix in current phase):**
```
🟠 File 251-300 lines
🟠 Method 61-80 lines
🟠 Missing PHPDoc
🟠 No input sanitization
🟠 No output escaping
```
**→ Complete phase but prioritize refactor**

### **MEDIUM (can fix later):**
```
🟡 Code duplication
🟡 Complex logic
🟡 Missing comments
🟡 Poor variable names
```
**→ Note in TODO**

---

## ✅ GOLDEN RULES SUMMARY

```
┌─────────────────────────────────────────────┐
│  🎯 GOLDEN RULES                            │
├─────────────────────────────────────────────┤
│                                             │
│  1. EACH FILE ≤ 300 LINES (ideal ≤ 250)   │
│  2. EACH METHOD ≤ 60 LINES (ideal ≤ 40)   │
│  3. ONE FILE = ONE CLASS                    │
│  4. SPLIT FILES WHEN NEEDED                 │
│  5. CODE MUST BE READABLE & MAINTAINABLE    │
│                                             │
│  🔒 SECURITY                                │
│  • Sanitize input                           │
│  • Escape output                            │
│  • Nonce verification                       │
│  • Capability checks                        │
│  • Prepared statements                      │
│                                             │
│  📚 DOCUMENTATION                           │
│  • PHPDoc for classes & public methods      │
│  • Inline comments for complex logic        │
│                                             │
│  ⚡ PERFORMANCE                             │
│  • Cache when possible                      │
│  • Avoid N+1 queries                        │
│  • Lazy load classes                        │
│                                             │
└─────────────────────────────────────────────┘
```

---

**These rules are MANDATORY for the entire project.**

**Compliance = High code quality = Easy maintenance = Project success! 🚀**