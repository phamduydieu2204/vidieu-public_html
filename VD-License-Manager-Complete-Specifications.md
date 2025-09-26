# VD License Manager - Complete Technical Specifications

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Database Schema](#database-schema)
3. [Business Logic](#business-logic)
4. [Plugin Architecture](#plugin-architecture)
5. [API Specifications](#api-specifications)
6. [Security Implementation](#security-implementation)
7. [UI/UX Specifications](#ui-ux-specifications)
8. [Integration Details](#integration-details)

---

## 🎯 System Overview

### Mục Tiêu
Phát triển WordPress plugin quản lý license tự động cho các tool như Helium10, Midjourney, Freepik với khả năng:
- Chia sẻ cookie/credentials an toàn
- Kiểm soát thiết bị thông minh
- Phân phối tải tự động
- Tự động duyệt thiết bị với risk scoring
- Rate limiting thông minh

### Phạm Vi
- WordPress plugin tích hợp WooCommerce + License Manager for WooCommerce (LMfWC)
- Quản lý 3 loại provider: Helium10, Midjourney, Freepik
- Hỗ trợ 3 share types: cookie, credentials, credentials_2fa
- Kiểm soát thiết bị và rate limiting
- Admin dashboard và customer portal

---

## 🗄️ Database Schema

### 1. Bảng Provider Accounts
```sql
CREATE TABLE vd_provider_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('helium10','midjourney','freepik') NOT NULL,
  share_type ENUM('cookie','credentials','credentials_2fa') NOT NULL,
  account_name VARCHAR(255) NOT NULL,
  capacity INT UNSIGNED NOT NULL DEFAULT 10,
  current_load INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_provider_status (provider, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Bảng Content Storage (Cookie/Credentials)
```sql
CREATE TABLE vd_content_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_account_id BIGINT UNSIGNED NOT NULL,
  version_number INT UNSIGNED NOT NULL,
  content_type ENUM('cookie','credentials') NOT NULL,
  encrypted_content MEDIUMTEXT NOT NULL,
  content_hash VARCHAR(64) NOT NULL,
  format ENUM('json','netscape','headers','plain') NOT NULL DEFAULT 'json',
  scope VARCHAR(255) NULL,
  expires_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_account_version (provider_account_id, version_number),
  INDEX idx_active_content (provider_account_id, is_active),
  FOREIGN KEY (provider_account_id) REFERENCES vd_provider_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Bảng License Management (Core)
```sql
CREATE TABLE vd_licenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_key VARCHAR(64) NOT NULL UNIQUE,
  product_id BIGINT UNSIGNED NOT NULL,         -- WooCommerce product_id
  order_id BIGINT UNSIGNED NULL,               -- WooCommerce order_id
  user_id BIGINT UNSIGNED NULL,                -- WordPress user_id
  status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active',
  max_devices INT UNSIGNED NULL,               -- Override device limit cho license này
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_id (product_id),
  INDEX idx_status_expires (status, expires_at),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Bảng License Assignments (Sticky)
```sql
CREATE TABLE vd_license_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT UNSIGNED NOT NULL,
  provider_account_id BIGINT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_accessed DATETIME NULL,
  status ENUM('active','migrating','inactive') NOT NULL DEFAULT 'active',
  UNIQUE KEY uq_license_assignment (license_id),
  INDEX idx_account_load (provider_account_id, status),
  FOREIGN KEY (license_id) REFERENCES vd_licenses(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_account_id) REFERENCES vd_provider_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. Bảng Product Provider Mapping
```sql
CREATE TABLE vd_product_provider_mapping (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  provider_account_id BIGINT UNSIGNED NOT NULL,
  allocation_strategy ENUM('round_robin','least_loaded','sequential') NOT NULL DEFAULT 'least_loaded',
  priority INT NOT NULL DEFAULT 1,              -- Thứ tự ưu tiên (1 = cao nhất)
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_product_provider_priority (product_id, provider_account_id, priority),
  INDEX idx_product_active (product_id, is_active),
  FOREIGN KEY (provider_account_id) REFERENCES vd_provider_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6. Bảng Device Management
```sql
CREATE TABLE vd_device_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT UNSIGNED NOT NULL,
  device_fp VARCHAR(64) NOT NULL,
  device_info JSON NOT NULL,
  risk_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  auto_approved TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('pending','approved','blocked','over_limit') NOT NULL DEFAULT 'pending',
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NOT NULL,
  country_code VARCHAR(2) NULL,
  first_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME NULL,
  approved_by BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  UNIQUE KEY uq_license_device (license_id, device_fp),
  INDEX idx_status (status),
  INDEX idx_auto_approved (auto_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7. Bảng Device Limits
```sql
CREATE TABLE vd_product_device_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  max_devices INT UNSIGNED NOT NULL DEFAULT 3,
  auto_approval_enabled TINYINT(1) NOT NULL DEFAULT 1,
  grace_period_hours INT UNSIGNED NOT NULL DEFAULT 72,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vd_license_device_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT UNSIGNED NOT NULL,
  max_devices INT UNSIGNED NOT NULL,
  auto_approval_enabled TINYINT(1) NOT NULL DEFAULT 1,
  grace_period_hours INT UNSIGNED NOT NULL DEFAULT 72,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_license (license_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 8. Bảng Access Logs
```sql
CREATE TABLE vd_access_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT UNSIGNED NOT NULL,
  device_fp VARCHAR(64) NOT NULL,
  provider_account_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NOT NULL,
  country_code VARCHAR(2) NULL,
  content_version INT UNSIGNED NULL,
  response_time_ms INT UNSIGNED NULL,
  status ENUM('success','fail','blocked','rate_limited','device_limit_exceeded','content_changed_bypass') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_license_device (license_id, device_fp),
  INDEX idx_created_at (created_at),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 9. Bảng Rate Limiting
```sql
CREATE TABLE vd_license_rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT UNSIGNED NOT NULL,
  window_seconds INT NOT NULL DEFAULT 300,
  max_hits INT NOT NULL DEFAULT 10,
  last_reset DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  current_count INT NOT NULL DEFAULT 0,
  last_content_hash VARCHAR(64) NULL,
  bypass_on_content_change TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_license_window (license_id, window_seconds),
  INDEX idx_reset_time (last_reset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vd_rate_limit_configs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('product','license','global') NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  window_seconds INT NOT NULL DEFAULT 300,
  max_hits INT NOT NULL DEFAULT 10,
  bypass_on_content_change TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_entity_config (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 10. Bảng Audit Trail
```sql
CREATE TABLE vd_audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(50) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(100) NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_created_at (created_at),
  INDEX idx_user_action (user_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔧 Business Logic

### 1. Complete License Resolution Flow

#### Main License Resolution Logic
```php
/**
 * Complete license resolution theo logic mong muốn
 * Bao gồm tất cả 4 bước kiểm tra
 */
function resolve_license_info($license_key, $device_fp, $device_info) {
    // BƯỚC 1: Kiểm tra license còn hạn
    $license = get_license_by_key($license_key);
    if (!$license) {
        return ['success' => false, 'error' => 'License không tồn tại'];
    }

    if ($license['status'] !== 'active') {
        return ['success' => false, 'error' => 'License đã bị tạm khóa'];
    }

    if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
        return ['success' => false, 'error' => 'License đã hết hạn'];
    }

    // BƯỚC 2: Kiểm tra rate limiting
    $rate_limit_check = check_rate_limit($license['id']);
    if (!$rate_limit_check['allowed']) {
        return [
            'success' => false,
            'error' => 'Vượt quá giới hạn request. Vui lòng thử lại sau ' . $rate_limit_check['retry_after'] . ' phút'
        ];
    }

    // BƯỚC 3: Kiểm tra device hợp lệ
    $device_check = check_device_validity($license['id'], $device_fp, $device_info);
    if (!$device_check['valid']) {
        return ['success' => false, 'error' => $device_check['message']];
    }

    // BƯỚC 4: Xác định sản phẩm và cấp phát tài khoản
    $assignment = get_or_create_assignment($license);
    if (!$assignment) {
        return ['success' => false, 'error' => 'Không thể cấp phát tài khoản. Vui lòng liên hệ hỗ trợ.'];
    }

    // Log access và trả kết quả
    log_access($license['id'], $device_fp, $assignment['provider_account_id'], 'success');

    return [
        'success' => true,
        'license_id' => $license['id'],
        'product_id' => $license['product_id'],
        'assigned_account_id' => $assignment['provider_account_id'],
        'content' => get_provider_content($assignment['provider_account_id']),
        'rate_limit' => $rate_limit_check
    ];
}
```

#### BƯỚC 4: Product-Based Assignment Logic
```php
/**
 * Logic cấp phát tài khoản theo sản phẩm
 * Implement các strategy: least_loaded, round_robin, sequential
 */
function get_or_create_assignment($license) {
    // Kiểm tra xem license đã được assign chưa (sticky)
    $existing = get_existing_assignment($license['id']);
    if ($existing && $existing['status'] === 'active') {
        return $existing;
    }

    // Lấy danh sách provider accounts cho product này
    $product_providers = get_product_provider_accounts($license['product_id']);
    if (empty($product_providers)) {
        error_log("No provider accounts configured for product {$license['product_id']}");
        return false;
    }

    // Chọn provider account theo strategy
    $selected_provider = select_provider_by_strategy($product_providers);
    if (!$selected_provider) {
        return false;
    }

    // Tạo assignment mới
    return create_license_assignment($license['id'], $selected_provider['id']);
}

/**
 * Chọn provider account theo allocation strategy
 */
function select_provider_by_strategy($product_providers) {
    // Group theo strategy
    $strategies = [];
    foreach ($product_providers as $pp) {
        $strategies[$pp['allocation_strategy']][] = $pp;
    }

    // Ưu tiên strategy: least_loaded > round_robin > sequential
    if (isset($strategies['least_loaded'])) {
        return select_least_loaded_provider($strategies['least_loaded']);
    } elseif (isset($strategies['round_robin'])) {
        return select_round_robin_provider($strategies['round_robin']);
    } elseif (isset($strategies['sequential'])) {
        return select_sequential_provider($strategies['sequential']);
    }

    return null;
}

/**
 * Strategy: Chọn tài khoản có ít license nhất
 */
function select_least_loaded_provider($providers) {
    $loads = [];
    foreach ($providers as $provider) {
        $current_load = get_provider_current_load($provider['provider_account_id']);
        $loads[] = [
            'provider' => $provider,
            'load' => $current_load,
            'load_percentage' => ($current_load / $provider['capacity']) * 100
        ];
    }

    // Sắp xếp theo load percentage tăng dần
    usort($loads, function($a, $b) {
        return $a['load_percentage'] <=> $b['load_percentage'];
    });

    // Chọn provider có load thấp nhất và chưa đầy
    foreach ($loads as $load) {
        if ($load['load'] < $load['provider']['capacity']) {
            return $load['provider'];
        }
    }

    return null; // Tất cả provider đã đầy
}

/**
 * Strategy: Cấp phát theo thứ tự lần lượt
 */
function select_sequential_provider($providers) {
    // Sắp xếp theo priority (1 = cao nhất)
    usort($providers, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });

    // Chọn provider đầu tiên chưa đầy
    foreach ($providers as $provider) {
        $current_load = get_provider_current_load($provider['provider_account_id']);
        if ($current_load < $provider['capacity']) {
            return $provider;
        }
    }

    return null;
}
```

### 2. Advanced Device Validation

#### Device Validity Check với Multiple Scenarios
```php
/**
 * Kiểm tra device validity với logic phức tạp
 */
function check_device_validity($license_id, $device_fp, $device_info) {
    // Lấy device limits hiệu lực (license > product > global)
    $limits = get_effective_device_limit($license_id);

    // Kiểm tra device đã được approve chưa
    $existing_device = get_device_request($license_id, $device_fp);

    if ($existing_device) {
        // Device đã tồn tại
        switch ($existing_device['status']) {
            case 'approved':
                return ['valid' => true, 'device' => $existing_device];

            case 'blocked':
                return [
                    'valid' => false,
                    'message' => 'Thiết bị này đã bị chặn. Vui lòng liên hệ hỗ trợ.'
                ];

            case 'pending':
                return [
                    'valid' => false,
                    'message' => 'Thiết bị đang chờ phê duyệt. Admin sẽ xem xét trong 24h.'
                ];

            case 'over_limit':
                // Trong grace period
                return [
                    'valid' => false,
                    'message' => 'Thiết bị vượt giới hạn nhưng đang trong thời gian gia hạn. Vui lòng nâng cấp gói.'
                ];
        }
    }

    // Device mới - kiểm tra có vượt limit không
    $approved_devices = count_approved_devices($license_id);

    if ($approved_devices >= $limits['max_devices']) {
        return [
            'valid' => false,
            'message' => "Đã đạt giới hạn {$limits['max_devices']} thiết bị. Vui lòng nâng cấp gói hoặc liên hệ để tăng giới hạn."
        ];
    }

    // Tạo device request mới với risk scoring
    $risk_score = calculate_risk_score($license_id, $device_fp, $device_info);
    $auto_approved = should_auto_approve($license_id, $device_fp, $risk_score);

    $device_request = create_device_request([
        'license_id' => $license_id,
        'device_fp' => $device_fp,
        'device_info' => json_encode($device_info),
        'risk_score' => $risk_score,
        'auto_approved' => $auto_approved,
        'status' => $auto_approved ? 'approved' : 'pending',
        'ip_address' => $device_info['ip'],
        'user_agent' => $device_info['user_agent'],
        'country_code' => $device_info['country'] ?? null
    ]);

    if ($auto_approved) {
        return ['valid' => true, 'device' => $device_request];
    } else {
        return [
            'valid' => false,
            'message' => 'Thiết bị mới cần phê duyệt thủ công do risk score cao. Admin sẽ xem xét trong 24h.'
        ];
    }
}
```

### 3. Smart Auto-Approval System

#### Risk Scoring Algorithm
```php
function calculate_risk_score($license_id, $device_fp, $device_info) {
    $score = 0;

    // Base factors
    $existing_devices = count_approved_devices($license_id);
    $score += min($existing_devices * 10, 30); // Max 30 points for device count

    // Geographic diversity (penalty for too many countries)
    $countries = get_device_countries($license_id);
    if (count($countries) > 2) {
        $score += (count($countries) - 2) * 15;
    }

    // Device fingerprint similarity
    $similar_devices = find_similar_devices($device_fp, $license_id);
    if ($similar_devices > 0) {
        $score -= 10; // Bonus for similar devices (same user)
    }

    // IP reputation check
    if (is_ip_suspicious($device_info['ip'])) {
        $score += 25;
    }

    // Time-based patterns
    $access_pattern = analyze_access_pattern($license_id);
    if ($access_pattern['suspicious']) {
        $score += 20;
    }

    return min(max($score, 0), 100); // Clamp 0-100
}
```

#### Auto-Approval Decision Tree
```php
function should_auto_approve($license_id, $device_fp, $risk_score) {
    // Check if auto-approval is enabled
    if (!is_auto_approval_enabled($license_id)) {
        return false;
    }

    // Get effective device limit
    $limits = get_effective_device_limit($license_id);
    $current_count = count_approved_devices($license_id);

    // Hard limit check
    if ($current_count >= $limits['max_devices']) {
        return false;
    }

    // Risk-based approval
    if ($risk_score >= 50) {
        return false; // Manual review required
    }

    // Rate limit violation check
    if (has_recent_rate_limit_violations($license_id, 3)) {
        return false;
    }

    return true;
}
```

### 2. Device Limit Hierarchy & Grace Period

#### Effective Limit Resolution
```php
function get_effective_device_limit($license_id) {
    // Try license-specific limits first
    $license_limit = get_license_device_limit($license_id);
    if ($license_limit !== null) {
        return $license_limit; // License overrides everything
    }

    // Fall back to product-level limits
    $product_id = get_license_product_id($license_id);
    $product_limit = get_product_device_limit($product_id);
    if ($product_limit !== null) {
        return $product_limit;
    }

    // Default global limit
    return [
        'max_devices' => 3,
        'auto_approval_enabled' => true,
        'grace_period_hours' => 72
    ];
}
```

#### Grace Period Management
```php
function handle_device_limit_reduction($license_id, $old_limit, $new_limit) {
    if ($new_limit >= $old_limit) {
        return; // No grace period needed
    }

    $approved_devices = get_approved_devices($license_id);
    $over_limit_devices = array_slice($approved_devices, $new_limit);

    foreach ($over_limit_devices as $device) {
        // Mark as over_limit with grace period
        update_device_status($device['id'], 'over_limit');

        // Schedule automatic revocation
        $grace_hours = get_effective_device_limit($license_id)['grace_period_hours'];
        wp_schedule_single_event(
            time() + ($grace_hours * 3600),
            'vd_revoke_over_limit_device',
            [$device['id']]
        );

        // Send notification
        send_grace_period_notification($license_id, $device, $grace_hours);
    }
}
```

### 3. Provider Failover System

#### Failover Priority Logic
```php
function select_best_provider_account($provider_type, $exclude_accounts = []) {
    $accounts = get_active_provider_accounts($provider_type, $exclude_accounts);

    if (empty($accounts)) {
        return null;
    }

    // Sort by load factor (current_load / capacity)
    usort($accounts, function($a, $b) {
        $load_a = $a['current_load'] / max($a['capacity'], 1);
        $load_b = $b['current_load'] / max($b['capacity'], 1);
        return $load_a <=> $load_b;
    });

    // Return account with lowest load
    return $accounts[0];
}
```

### 4. Smart Rate Limiting

#### Rate Limit Check với Content Change Bypass
```php
function check_rate_limit($license_id, $current_content_hash) {
    $rate_limit = get_license_rate_limit($license_id);

    // Check if content changed
    if ($rate_limit['bypass_on_content_change'] &&
        $rate_limit['last_content_hash'] !== $current_content_hash) {

        // Reset counter and update hash
        reset_rate_limit_counter($license_id, $current_content_hash);
        return ['allowed' => true, 'bypassed' => true];
    }

    // Normal rate limit check
    if ($rate_limit['current_count'] >= $rate_limit['max_hits']) {
        $reset_time = strtotime($rate_limit['last_reset']) + $rate_limit['window_seconds'];

        if (time() >= $reset_time) {
            reset_rate_limit_counter($license_id, $current_content_hash);
            return ['allowed' => true, 'bypassed' => false];
        }

        return [
            'allowed' => false,
            'reset_at' => date('c', $reset_time),
            'bypassed' => false
        ];
    }

    // Increment counter
    increment_rate_limit_counter($license_id);

    return [
        'allowed' => true,
        'remaining' => $rate_limit['max_hits'] - $rate_limit['current_count'] - 1,
        'bypassed' => false
    ];
}
```

---

## 🏗️ Plugin Architecture

### Folder Structure
```
vd-license-manager/
├── vd-license-manager.php          # Main plugin file
├── includes/
│   ├── class-vd-license-manager.php     # Core plugin class
│   ├── class-license-core.php           # License validation & management
│   ├── class-device-manager.php         # Device fingerprinting & control
│   ├── class-content-manager.php        # Cookie/credentials management
│   ├── class-assignment-engine.php      # Sticky assignment logic
│   ├── class-provider-failover.php      # Provider account failover
│   ├── class-risk-calculator.php        # Risk scoring algorithms
│   ├── class-rate-limiter.php          # Rate limiting logic
│   └── class-security-manager.php       # Encryption/decryption
├── api/
│   ├── class-customer-api.php          # Customer-facing endpoints
│   ├── class-admin-api.php             # Admin management endpoints
│   └── class-webhook-handler.php       # External webhooks
├── admin/
│   ├── class-admin-dashboard.php       # Main admin interface
│   ├── class-provider-manager.php      # Provider account management
│   ├── class-device-approval.php       # Device approval interface
│   ├── class-audit-viewer.php          # Audit log viewer
│   ├── class-rate-limit-config.php     # Rate limiting configuration
│   ├── views/
│   │   ├── dashboard.php               # Admin dashboard template
│   │   ├── provider-accounts.php       # Provider management UI
│   │   ├── device-management.php       # Device approval UI
│   │   ├── audit-logs.php             # Audit log viewer UI
│   │   └── settings.php               # Plugin settings UI
│   └── assets/
│       ├── css/admin-style.css         # Admin CSS
│       └── js/admin-script.js          # Admin JavaScript
├── public/
│   ├── class-customer-portal.php       # Customer interface
│   ├── class-license-lookup.php        # License validation frontend
│   ├── templates/
│   │   ├── customer-portal.php         # Customer portal template
│   │   ├── license-form.php           # License input form
│   │   ├── content-display.php        # Dynamic content display
│   │   └── device-management.php       # Customer device management
│   └── assets/
│       ├── css/public-style.css        # Public CSS
│       └── js/
│           ├── public-script.js        # Public JavaScript
│           ├── device-fingerprint.js   # Device fingerprinting
│           └── content-handler.js      # Copy+download functionality
├── security/
│   ├── class-encryption.php           # OpenSSL encryption wrapper
│   ├── class-input-validator.php      # Input validation functions
│   └── class-audit-logger.php         # Comprehensive audit logging
├── integrations/
│   ├── class-woocommerce.php          # WooCommerce hooks & filters
│   ├── class-lmfwc.php               # LMfWC API integration
│   └── providers/
│       ├── class-helium10.php         # Helium10-specific logic
│       ├── class-midjourney.php       # Midjourney-specific logic
│       └── class-freepik.php          # Freepik-specific logic
├── languages/
│   ├── vd-license-manager.pot         # Translation template
│   └── vd-license-manager-vi.po       # Vietnamese translations
└── migrations/
    ├── 001-initial-tables.php         # Initial database setup
    ├── 002-add-rate-limiting.php      # Rate limiting tables
    └── 003-add-audit-logs.php         # Audit logging tables
```

### Core Classes

#### `class-vd-license-manager.php`
```php
class VD_License_Manager {
    private static $instance = null;
    private $license_core;
    private $device_manager;
    private $content_manager;
    private $assignment_engine;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->load_dependencies();
        $this->setup_hooks();
        $this->init_components();
    }

    private function load_dependencies() {
        require_once VD_LM_PATH . 'includes/class-license-core.php';
        require_once VD_LM_PATH . 'includes/class-device-manager.php';
        require_once VD_LM_PATH . 'includes/class-content-manager.php';
        require_once VD_LM_PATH . 'includes/class-assignment-engine.php';
        // ... load other dependencies
    }
}
```

#### `class-license-core.php`
```php
class VD_License_Core {
    public function validate_license($license_key) {
        // Integrate với LMfWC để validate
        $lmfwc = new VD_LMfWC_Integration();
        return $lmfwc->validate_license($license_key);
    }

    public function resolve_license_info($license_key, $device_fp) {
        // Main business logic cho license resolution
        $validation = $this->validate_license($license_key);
        if (!$validation['success']) {
            return $validation;
        }

        $license_data = $validation['data'];
        $license_id = $license_data['id'];

        // Check device limits và auto-approval
        $device_status = $this->device_manager->check_device_status($license_id, $device_fp);

        // Get assigned provider account
        $assignment = $this->assignment_engine->get_or_create_assignment($license_id);

        // Get content
        $content = $this->content_manager->get_latest_content($assignment['provider_account_id']);

        return [
            'success' => true,
            'license_id' => $license_id,
            'assigned_account_id' => $assignment['provider_account_id'],
            'content' => $content,
            'device_status' => $device_status
        ];
    }
}
```

---

## 🔌 API Specifications

### 1. Customer API Endpoint

#### POST `/wp-json/vd/v1/license/resolve-info`
```php
// Request - Enhanced với device info
{
  "license_key": "VD-1234-ABCD-5678",
  "device_fp": "a1b2c3d4e5f6...",
  "device_info": {
    "ip": "1.2.3.4",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "country": "VN",
    "browser": "Chrome",
    "os": "Windows",
    "screen_resolution": "1920x1080",
    "timezone": "Asia/Ho_Chi_Minh"
  }
}

// Response - Success
{
  "ok": true,
  "product_id": 6789,
  "license_id": 12345,
  "assigned_account_id": 7,
  "share_type": "credentials_2fa",
  "content": {
    "credentials": {
      "email": "account@helium10.com",
      "password": "encrypted_password",
      "totp_secret": "ABCD1234EFGH5678",
      "recovery_email": "recovery@email.com"
    }
  },
  "devices": [
    {
      "device_fp": "a1b2c3d4e5f6...",
      "status": "approved",
      "first_seen": "2025-09-20T10:30:00Z",
      "approved_at": "2025-09-20T10:31:15Z",
      "auto_approved": true,
      "country": "VN",
      "last_access": "2025-09-25T08:15:00Z"
    }
  ],
  "history": [
    {
      "timestamp": "2025-09-25T08:15:00Z",
      "ip": "1.2.3.4",
      "country": "VN",
      "status": "success",
      "response_time": 145
    }
  ],
  "rate_limit": {
    "window_seconds": 300,
    "max_hits": 10,
    "current_count": 3,
    "remaining": 7,
    "reset_at": "2025-09-25T10:30:00Z",
    "bypassed": false
  }
}

// Response - Error
{
  "ok": false,
  "error_code": "device_limit_exceeded",
  "message": "Đã đạt giới hạn 3 thiết bị. Vui lòng nâng cấp gói hoặc liên hệ để tăng giới hạn.",
  "details": {
    "current_devices": 3,
    "max_devices": 3,
    "can_upgrade": true
  }
}
```

### 2. Admin API Endpoints

#### Content Management
```php
POST /wp-json/vd/v1/admin/content/upload
{
  "provider_account_id": 7,
  "content_type": "credentials",
  "data": {
    "email": "new@helium10.com",
    "password": "newpassword123",
    "totp_secret": "NEWTOTP123456789"
  }
}

POST /wp-json/vd/v1/admin/device/approve
{
  "device_request_id": 123,
  "action": "approve", // approve, block, pending
  "notes": "Verified via customer support"
}

GET /wp-json/vd/v1/admin/stats/assignments
Response: {
  "total_licenses": 1500,
  "active_assignments": 1200,
  "provider_distribution": {
    "helium10": 800,
    "midjourney": 300,
    "freepik": 100
  },
  "load_balancing": {
    "account_1": {"load": 80, "capacity": 100},
    "account_2": {"load": 60, "capacity": 80}
  }
}
```

#### Product Provider Mapping Management
```php
// Cấu hình provider accounts cho product
POST /wp-json/vd/v1/admin/product/provider-mapping
{
  "product_id": 8210,
  "provider_mappings": [
    {
      "provider_account_id": 1,
      "allocation_strategy": "least_loaded",
      "priority": 1,
      "is_active": true
    },
    {
      "provider_account_id": 2,
      "allocation_strategy": "least_loaded",
      "priority": 2,
      "is_active": true
    },
    {
      "provider_account_id": 3,
      "allocation_strategy": "sequential",
      "priority": 3,
      "is_active": true
    }
  ]
}

// Lấy danh sách provider cho product
GET /wp-json/vd/v1/admin/product/{product_id}/providers
Response: {
  "product_id": 8210,
  "providers": [
    {
      "provider_account_id": 1,
      "account_name": "helium10-main-01",
      "provider": "helium10",
      "allocation_strategy": "least_loaded",
      "priority": 1,
      "current_load": 5,
      "capacity": 10,
      "load_percentage": 50
    }
  ]
}

// Admin override assignment cho license cụ thể
POST /wp-json/vd/v1/admin/license/reassign
{
  "license_id": 12345,
  "new_provider_account_id": 7,
  "reason": "Customer request account change"
}
```

#### Rate Limiting Management
```php
POST /wp-json/vd/v1/admin/rate-limit/config
{
  "entity_type": "license", // product, license, global
  "entity_id": 12345,
  "window_seconds": 300,
  "max_hits": 15,
  "bypass_on_content_change": true
}

GET /wp-json/vd/v1/admin/rate-limit/stats
Response: {
  "total_requests_today": 15000,
  "rate_limited_requests": 150,
  "bypass_count": 45,
  "top_licenses": [
    {"license_id": 123, "requests": 500, "rate_limited": 10},
    {"license_id": 456, "requests": 450, "rate_limited": 5}
  ]
}
```

---

## 🔒 Security Implementation

### 1. OpenSSL Encryption (Application-level)

#### Key Configuration
```bash
# Tạo encryption key (32 bytes base64):
php -r 'echo base64_encode(random_bytes(32)).PHP_EOL;'
```

#### wp-config.php (KHÔNG commit file này):
```php
define('VD_ENCRYPTION_KEY', 'base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

#### Envelope Encryption với OpenSSL AES-256-GCM
```php
/**
 * Encrypt plaintext với AES-256-GCM
 */
function vd_encrypt_aes_gcm($plaintext) {
    if (!defined('VD_ENCRYPTION_KEY') || empty(VD_ENCRYPTION_KEY)) {
        throw new Exception('Encryption key not defined');
    }

    $key = base64_decode(str_replace('base64:', '', VD_ENCRYPTION_KEY));
    if (strlen($key) !== 32) {
        throw new Exception('Encryption key must be 32 bytes');
    }

    $iv = random_bytes(12); // 96-bit IV cho GCM
    $tag = '';

    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        throw new Exception('Encryption failed');
    }

    // Format: iv(12) + tag(16) + ciphertext
    return base64_encode($iv . $tag . $cipher);
}

/**
 * Decrypt base64 blob
 */
function vd_decrypt_aes_gcm($b64blob) {
    if (!defined('VD_ENCRYPTION_KEY') || empty(VD_ENCRYPTION_KEY)) {
        throw new Exception('Encryption key not defined');
    }

    $key = base64_decode(str_replace('base64:', '', VD_ENCRYPTION_KEY));
    $data = base64_decode($b64blob);

    if (strlen($data) < 28) { // iv(12) + tag(16) = minimum 28 bytes
        throw new Exception('Invalid encrypted data');
    }

    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $cipher = substr($data, 28);

    $plaintext = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new Exception('Decryption failed - data corrupted or wrong key');
    }

    return $plaintext;
}
```

### 2. Input Validation Rules

#### Validation Functions
```php
function vd_validate_device_fp($device_fp) {
    return preg_match('/^[a-f0-9]{64}$/', $device_fp);
}

function vd_validate_license_key($license_key) {
    return preg_match('/^[A-Z0-9\-]{8,32}$/', $license_key);
}

function vd_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function vd_validate_totp_secret($secret) {
    // Base32 string, 16-32 chars
    return preg_match('/^[A-Z2-7]{16,32}=*$/', $secret);
}

function vd_validate_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function vd_sanitize_username($username) {
    $username = trim($username);
    if (strlen($username) > 191) {
        $username = substr($username, 0, 191);
    }
    // Remove control characters
    return preg_replace('/[\x00-\x1F\x7F]/', '', $username);
}
```

#### SQL Injection Prevention
```php
// ✅ ĐÚNG: Luôn dùng $wpdb->prepare()
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vd_licenses WHERE license_key = %s",
    $license_key
));

// ✅ ĐÚNG: Dùng format array với insert/update
$wpdb->insert(
    "{$wpdb->prefix}vd_device_requests",
    [
        'license_id' => $license_id,
        'device_fp' => $device_fp,
        'status' => 'pending'
    ],
    ['%d', '%s', '%s'] // format array
);

// ❌ SAI: Không bao giờ nối trực tiếp
$wpdb->query("SELECT * FROM table WHERE id = $id"); // NGUY HIỂM!
```

---

## 🎨 UI/UX Specifications

### 1. Customer Portal Template

#### License Input Form
```html
<div class="vd-license-portal">
    <form id="vd-license-form">
        <div class="form-group">
            <label for="license_key">License Key:</label>
            <input type="text" id="license_key" name="license_key"
                   placeholder="VD-1234-ABCD-5678"
                   pattern="[A-Z0-9\-]{8,32}" required>
        </div>
        <button type="submit" class="btn btn-primary">Truy cập</button>
    </form>

    <div id="license-content" style="display:none;">
        <!-- Dynamic content will be loaded here -->
    </div>
</div>
```

#### Dynamic Content Display
```html
<div class="vd-content-display" data-license-key="VD-1234-ABCD">
    <!-- Cookie Display -->
    <div v-if="shareType === 'cookie'">
        <h4>Cookie Data</h4>
        <div class="content-box">
            <textarea readonly class="form-control">{{ content.cookie.text }}</textarea>
            <div class="content-actions">
                <button class="btn btn-secondary" onclick="copyAndDownload(this.previousElementSibling, 'cookie')">
                    Copy & Download
                </button>
                <small class="text-muted">Version: {{ content.cookie.version }} | Updated: {{ content.cookie.updatedAt }}</small>
            </div>
        </div>
    </div>

    <!-- Credentials Display -->
    <div v-if="shareType.startsWith('credentials')">
        <h4>Account Credentials</h4>

        <div class="form-group">
            <label>Email:</label>
            <input type="text" readonly :value="content.credentials.email" class="form-control">
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="copyAndDownload(this.previousElementSibling, 'email')">
                Copy & Download
            </button>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <div class="input-group">
                <input type="password" readonly :value="content.credentials.password"
                       id="pwd-field" class="form-control">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">Show</button>
                    <button class="btn btn-outline-secondary"
                            onclick="copyAndDownload('#pwd-field', 'password')">
                        Copy & Download
                    </button>
                </div>
            </div>
        </div>

        <!-- 2FA Secret (only for credentials_2fa) -->
        <div v-if="shareType === 'credentials_2fa'" class="form-group">
            <label>2FA Secret:</label>
            <input type="text" readonly :value="content.credentials.totp_secret"
                   class="form-control font-monospace">
            <small class="text-muted">Nhập vào Google Authenticator</small>
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="copyAndDownload(this.previousElementSibling.previousElementSibling, '2fa-secret')">
                Copy & Download
            </button>
        </div>
    </div>
</div>
```

#### JavaScript Functions for Copy+Download
```javascript
function copyAndDownload(element, type) {
    const value = typeof element === 'string' ? document.querySelector(element).value : element.value;

    // Copy to clipboard
    navigator.clipboard.writeText(value).then(() => {
        showToast('Đã sao chép vào clipboard!', 'success');
    });

    // Generate filename based on type and current datetime
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    const licenseKey = document.querySelector('[data-license-key]')?.dataset.licenseKey || 'unknown';

    const filenames = {
        'email': `${licenseKey}_email_${timestamp}.txt`,
        'password': `${licenseKey}_password_${timestamp}.txt`,
        '2fa-secret': `${licenseKey}_2fa-secret_${timestamp}.txt`,
        'cookie': `${licenseKey}_cookie_${timestamp}.txt`
    };

    const filename = filenames[type] || `${licenseKey}_content_${timestamp}.txt`;

    // Create and download file
    const blob = new Blob([value], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    showToast(`Đã tải xuống: ${filename}`, 'info');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} toast-notification`;
    toast.textContent = message;
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;';
    document.body.appendChild(toast);
    setTimeout(() => document.body.removeChild(toast), 3000);
}
```

### 2. Device Management Tab
```html
<div class="vd-device-tab">
    <h4>Thiết Bị Đã Đăng Ký (<span class="device-count">2/3</span>)</h4>

    <div class="device-list">
        <div class="device-item approved">
            <div class="device-info">
                <strong>Chrome on Windows</strong>
                <span class="device-fp">a1b2c3...f6</span>
            </div>
            <div class="device-meta">
                <span class="country-flag">🇻🇳</span> Vietnam
                <span class="first-seen">First seen: 2025-09-20</span>
                <span class="last-access">Last access: 2 hours ago</span>
            </div>
            <div class="device-status">
                <span class="badge badge-success">Approved (Auto)</span>
            </div>
        </div>

        <div class="device-item pending">
            <div class="device-info">
                <strong>Safari on iPhone</strong>
                <span class="device-fp">x9y8z7...k3</span>
            </div>
            <div class="device-meta">
                <span class="country-flag">🇺🇸</span> United States
                <span class="first-seen">First seen: 1 hour ago</span>
                <span class="risk-score">Risk: <span class="text-warning">Medium (35)</span></span>
            </div>
            <div class="device-status">
                <span class="badge badge-warning">Pending Review</span>
                <small class="text-muted">Admin sẽ xem xét trong 24h</small>
            </div>
        </div>
    </div>

    <div class="device-actions">
        <button class="btn btn-outline-primary" onclick="refreshDeviceList()">
            Refresh
        </button>
        <small class="text-muted">
            Còn lại <strong>1 slot</strong> thiết bị.
            <a href="#upgrade">Nâng cấp gói</a> để tăng giới hạn.
        </small>
    </div>
</div>
```

### 3. Admin Dashboard

#### Provider Account Management
```html
<div class="vd-admin-providers">
    <div class="provider-stats">
        <div class="stat-card">
            <h3>Active Accounts</h3>
            <span class="stat-number">12</span>
        </div>
        <div class="stat-card">
            <h3>Total Capacity</h3>
            <span class="stat-number">240</span>
        </div>
        <div class="stat-card">
            <h3>Current Load</h3>
            <span class="stat-number">85%</span>
        </div>
    </div>

    <div class="provider-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Account Name</th>
                    <th>Share Type</th>
                    <th>Load</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Helium10</td>
                    <td>main-h10-01</td>
                    <td><span class="badge badge-info">credentials_2fa</span></td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar" style="width: 80%">16/20</div>
                        </div>
                    </td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                        <button class="button">Edit</button>
                        <button class="button">Test</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

---

## 🔌 Integration Details

### 1. WooCommerce Integration

#### Order Completion Hook
```php
// Hook khi order hoàn tất → tạo license tự động
add_action('woocommerce_order_status_completed', 'vd_create_license_on_order_complete');

function vd_create_license_on_order_complete($order_id) {
    $order = wc_get_order($order_id);

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        // Kiểm tra sản phẩm có enable VD License Manager không
        if (get_post_meta($product_id, '_vd_enable_license', true) === 'yes') {
            $license_data = [
                'order_id' => $order_id,
                'product_id' => $product_id,
                'user_id' => $order->get_user_id(),
                'quantity' => $item->get_quantity()
            ];

            vd_create_licenses_for_product($license_data);
        }
    }
}
```

#### Product Meta Fields
```php
// Thêm meta fields cho admin product
add_action('woocommerce_product_options_general_product_data', 'vd_add_product_fields');
add_action('woocommerce_admin_process_product_object', 'vd_save_product_fields');

function vd_add_product_fields() {
    echo '<div class="options_group">';

    woocommerce_wp_checkbox([
        'id' => '_vd_enable_license',
        'label' => __('Enable VD License Manager', 'vd-license-manager'),
        'description' => __('Enable automatic license creation for this product', 'vd-license-manager')
    ]);

    woocommerce_wp_select([
        'id' => '_vd_provider_type',
        'label' => __('Provider Type', 'vd-license-manager'),
        'options' => [
            'helium10' => 'Helium10',
            'midjourney' => 'Midjourney',
            'freepik' => 'Freepik'
        ]
    ]);

    woocommerce_wp_text_input([
        'id' => '_vd_license_duration',
        'label' => __('License Duration (days)', 'vd-license-manager'),
        'type' => 'number',
        'custom_attributes' => ['min' => '1']
    ]);

    echo '</div>';
}
```

### 2. LMfWC API Integration

#### License Creation với LMfWC
```php
class VD_LMfWC_Integration {

    /**
     * Tạo license thông qua LMfWC API
     */
    public function create_license($order_id, $product_id, $user_id) {
        $api_endpoint = get_option('lmfwc_rest_api_url', home_url('/wp-json/lmfwc/v2/'));

        $license_data = [
            'order_id' => $order_id,
            'product_id' => $product_id,
            'user_id' => $user_id,
            'status' => 1, // active
            'times_activated_max' => get_post_meta($product_id, '_vd_device_limit', true) ?: 3,
            'expires_at' => $this->calculate_expiry_date($product_id)
        ];

        $response = wp_remote_post($api_endpoint . 'licenses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_api_key(),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($license_data)
        ]);

        if (is_wp_error($response)) {
            error_log('VD License Manager: Failed to create license - ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['data'] ?? false;
    }

    /**
     * Validate license thông qua LMfWC
     */
    public function validate_license($license_key) {
        $api_endpoint = get_option('lmfwc_rest_api_url', home_url('/wp-json/lmfwc/v2/'));

        $response = wp_remote_get($api_endpoint . "licenses/validate/{$license_key}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_api_key()
            ]
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'API connection failed'];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return [
            'success' => $body['success'] ?? false,
            'data' => $body['data'] ?? null
        ];
    }

    private function get_api_key() {
        return get_option('lmfwc_rest_api_key');
    }
}
```

### 3. WordPress User Roles & Capabilities

#### Custom Capabilities
```php
// Đăng ký capabilities khi activate plugin
function vd_add_custom_capabilities() {
    $capabilities = [
        'vd_manage_licenses',
        'vd_manage_provider_accounts',
        'vd_manage_device_limits',
        'vd_view_audit_logs',
        'vd_approve_devices',
        'vd_manage_rate_limits'
    ];

    // Add to Administrator
    $admin_role = get_role('administrator');
    foreach ($capabilities as $cap) {
        $admin_role->add_cap($cap);
    }

    // Tạo custom role cho License Manager
    add_role('vd_license_manager', 'VD License Manager', [
        'read' => true,
        'vd_manage_licenses' => true,
        'vd_approve_devices' => true,
        'vd_view_audit_logs' => true
    ]);
}
register_activation_hook(__FILE__, 'vd_add_custom_capabilities');
```

---

## ⚠️ Lưu Ý Quan Trọng

### Security Notes
- **Encryption Key**: Backup `VD_ENCRYPTION_KEY` an toàn - mất key = mất dữ liệu
- **Database Security**: Encrypted content không thể đọc được nếu không có key
- **Input Validation**: Tất cả user input phải validate trước khi xử lý

### Performance Notes
- **Rate Limiting**: Smart bypass khi content thay đổi
- **Database Indexing**: Optimize cho các query thường dùng
- **Caching**: Device fingerprint cache 24h ở client-side

### Operational Notes
- **Monitoring**: Track rate limiting, device approval rates
- **Maintenance**: Clean logs >90 days, monitor provider health
- **Scaling**: Horizontal scaling qua multiple provider accounts

---

*Tài liệu này cung cấp đầy đủ technical specifications để triển khai VD License Manager plugin.*