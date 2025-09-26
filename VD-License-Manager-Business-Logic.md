# VD License Manager - Business Logic

## 📋 Table of Contents
1. [Logic Overview](#logic-overview)
2. [4-Step License Resolution](#4-step-license-resolution)
3. [Assignment Strategies](#assignment-strategies)
4. [Device Management](#device-management)
5. [Rate Limiting](#rate-limiting)
6. [Security & Validation](#security--validation)
7. [Edge Cases](#edge-cases)

---

## 🎯 Logic Overview

### Core Process Flow
```
Customer Request → License Resolution (4 steps) → Content Delivery
                      ↓
    [1] License Validity → [2] Rate Limiting → [3] Device Check → [4] Assignment
```

### Key Principles
- **Security First**: Validate everything, trust nothing
- **Sticky Assignment**: Mỗi license gắn với 1 provider account
- **Settings Inheritance**: License Settings > Product Settings > Global Settings
- **Manual Override**: Admin có thể thay đổi tài khoản thủ công
- **Smart Automation**: Auto-approve low-risk devices
- **Graceful Degradation**: Handle failures elegantly
- **Audit Everything**: Full activity logging

---

## 🔄 4-Step License Resolution

### Complete Resolution Function
```php
/**
 * Main license resolution logic
 * Implements exactly the desired business flow
 */
function resolve_license_info($license_key, $device_fp, $device_info) {
    try {
        // BƯỚC 1: Kiểm tra license còn hạn
        $license_check = validate_license_expiry($license_key);
        if (!$license_check['valid']) {
            return create_error_response($license_check['error'], $license_check['code']);
        }
        $license = $license_check['license'];

        // BƯỚC 2: Kiểm tra rate limiting
        $rate_check = validate_rate_limits($license['id']);
        if (!$rate_check['allowed']) {
            return create_error_response(
                "Vượt quá giới hạn request. Vui lòng thử lại sau {$rate_check['retry_minutes']} phút",
                'rate_limited',
                ['retry_after' => $rate_check['retry_after']]
            );
        }

        // BƯỚC 3: Kiểm tra thiết bị hợp lệ
        $device_check = validate_device_access($license['id'], $device_fp, $device_info);
        if (!$device_check['valid']) {
            return create_error_response($device_check['message'], $device_check['code']);
        }

        // BƯỚC 4: Xác định sản phẩm và cấp phát tài khoản
        $assignment = get_or_create_assignment($license);
        if (!$assignment) {
            return create_error_response(
                'Không thể cấp phát tài khoản. Vui lòng liên hệ hỗ trợ.',
                'no_provider_available'
            );
        }

        // Lấy content được filter theo cấu hình chia sẻ của product
        $content = get_filtered_provider_content($assignment['provider_account_id'], $license['product_id']);
        log_successful_access($license['id'], $device_fp, $assignment['provider_account_id'], $rate_check);

        return create_success_response([
            'license_id' => $license['id'],
            'product_id' => $license['product_id'],
            'assigned_account_id' => $assignment['provider_account_id'],
            'provider' => $assignment['provider'],
            'share_type' => $assignment['share_type'],
            'content' => $content,  // Content đã được filter theo cấu hình
            'rate_limit' => $rate_check,
            'device_status' => $device_check['device']
        ]);

    } catch (Exception $e) {
        error_log("VD License Resolution Error: " . $e->getMessage());
        return create_error_response('System error occurred', 'system_error');
    }
}
```

---

## ⚙️ Settings Inheritance Logic

### Settings Priority System
```php
/**
 * Get effective settings for a license
 * Priority: License Override > Product Settings > Global Settings
 */
function get_license_settings($license_id, $product_id) {
    global $wpdb;

    // 1. Try license-specific override first
    $license_override = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_license_settings_override
         WHERE license_id = %d",
        $license_id
    ), ARRAY_A);

    // 2. Get product settings
    $product_settings = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_product_settings
         WHERE product_id = %d",
        $product_id
    ), ARRAY_A);

    // 3. Get global defaults
    $global_settings = vd_get_global_settings();

    // Merge settings with priority
    $effective_settings = [
        'max_devices' => $license_override['max_devices']
            ?? $product_settings['max_devices']
            ?? $global_settings['default_max_devices']
            ?? 3,

        'rate_limit_requests' => $license_override['rate_limit_requests']
            ?? $product_settings['rate_limit_requests']
            ?? $global_settings['default_rate_limit_requests']
            ?? 100,

        'rate_limit_window_hours' => $license_override['rate_limit_window_hours']
            ?? $product_settings['rate_limit_window_hours']
            ?? $global_settings['default_rate_limit_window_hours']
            ?? 1,

        'auto_approval_enabled' => $license_override['auto_approval_enabled']
            ?? $product_settings['auto_approval_enabled']
            ?? ($global_settings['auto_approval_enabled'] === 'true')
            ?? true,

        'grace_period_hours' => $license_override['grace_period_hours']
            ?? $product_settings['grace_period_hours']
            ?? $global_settings['grace_period_hours']
            ?? 72
    ];

    return $effective_settings;
}

/**
 * Get global settings as key-value array
 */
function vd_get_global_settings() {
    global $wpdb;

    $settings = $wpdb->get_results(
        "SELECT setting_key, setting_value FROM {$wpdb->prefix}vd_global_settings",
        ARRAY_A
    );

    $global_config = [];
    foreach ($settings as $setting) {
        $global_config[$setting['setting_key']] = $setting['setting_value'];
    }

    return $global_config;
}

/**
 * Xác định nguồn settings đang được sử dụng
 */
function vd_get_settings_source($license_id, $product_id) {
    global $wpdb;

    // Check license override
    $license_override = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_license_settings_override WHERE license_id = %d",
        $license_id
    ));

    if ($license_override && (
        $license_override['max_devices'] !== null ||
        $license_override['rate_limit_requests'] !== null ||
        $license_override['rate_limit_window_hours'] !== null
    )) {
        return [
            'source' => 'license_override',
            'description' => 'Cài đặt riêng cho license này',
            'override_id' => $license_override['id']
        ];
    }

    // Check product settings
    $product_settings = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_product_settings WHERE product_id = %d",
        $product_id
    ));

    if ($product_settings) {
        return [
            'source' => 'product_settings',
            'description' => 'Cài đặt mặc định của sản phẩm',
            'product_id' => $product_id
        ];
    }

    return [
        'source' => 'global_settings',
        'description' => 'Cài đặt mặc định toàn cục'
    ];
}
```

### Admin Query Functions (Tra cứu thông tin)
```php
/**
 * Tra cứu thông tin theo product - License mapping
 */
function vd_get_product_license_stats($product_id) {
    global $wpdb;

    $stats = $wpdb->get_row($wpdb->prepare("
        SELECT
            COUNT(l.id) as total_licenses,
            SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_licenses,
            SUM(CASE WHEN l.status = 'expired' THEN 1 ELSE 0 END) as expired_licenses,
            SUM(CASE WHEN l.status = 'suspended' THEN 1 ELSE 0 END) as suspended_licenses,
            COUNT(DISTINCT la.provider_account_id) as assigned_providers,
            COUNT(dr.id) as total_devices,
            SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices
        FROM {$wpdb->prefix}vd_licenses l
        LEFT JOIN {$wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
        LEFT JOIN {$wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
        WHERE l.product_id = %d
        GROUP BY l.product_id
    ", $product_id), ARRAY_A);

    // Get product settings
    $product_settings = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_product_settings WHERE product_id = %d",
        $product_id
    ), ARRAY_A);

    // Get assigned provider accounts
    $assigned_providers = $wpdb->get_results($wpdb->prepare("
        SELECT
            pa.id,
            pa.account_name,
            pa.provider,
            pa.status,
            COUNT(la.id) as license_count
        FROM {$wpdb->prefix}vd_provider_accounts pa
        INNER JOIN {$wpdb->prefix}vd_license_assignments la ON pa.id = la.provider_account_id
        INNER JOIN {$wpdb->prefix}vd_licenses l ON la.license_id = l.id
        WHERE l.product_id = %d AND la.status = 'active'
        GROUP BY pa.id, pa.account_name, pa.provider, pa.status
        ORDER BY license_count DESC
    ", $product_id), ARRAY_A);

    return [
        'product_id' => $product_id,
        'stats' => $stats ?: [
            'total_licenses' => 0,
            'active_licenses' => 0,
            'expired_licenses' => 0,
            'suspended_licenses' => 0,
            'assigned_providers' => 0,
            'total_devices' => 0,
            'approved_devices' => 0
        ],
        'settings' => $product_settings,
        'assigned_providers' => $assigned_providers
    ];
}

/**
 * Tra cứu thông tin theo tên tài khoản provider
 */
function vd_get_provider_account_stats($account_name) {
    global $wpdb;

    $provider_account = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_name = %s",
        $account_name
    ), ARRAY_A);

    if (!$provider_account) {
        return ['error' => 'Tài khoản không tồn tại'];
    }

    // Get assigned licenses
    $assigned_licenses = $wpdb->get_results($wpdb->prepare("
        SELECT
            l.id,
            l.license_key,
            l.product_id,
            l.status,
            l.expires_at,
            la.assigned_at,
            la.last_accessed,
            la.assignment_method,
            COUNT(dr.id) as device_count,
            SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices
        FROM {$wpdb->prefix}vd_licenses l
        INNER JOIN {$wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
        LEFT JOIN {$wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
        WHERE la.provider_account_id = %d AND la.status = 'active'
        GROUP BY l.id, l.license_key, l.product_id, l.status, l.expires_at, la.assigned_at, la.last_accessed, la.assignment_method
        ORDER BY la.last_accessed DESC
    ", $provider_account['id']), ARRAY_A);

    // Get products this account serves
    $served_products = $wpdb->get_results($wpdb->prepare("
        SELECT
            l.product_id,
            COUNT(DISTINCT l.id) as license_count,
            AVG(al.response_time_ms) as avg_response_time,
            COUNT(al.id) as total_requests,
            SUM(CASE WHEN al.status = 'success' THEN 1 ELSE 0 END) as successful_requests
        FROM {$wpdb->prefix}vd_licenses l
        INNER JOIN {$wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
        LEFT JOIN {$wpdb->prefix}vd_access_logs al ON l.id = al.license_id AND al.provider_account_id = %d
        WHERE la.provider_account_id = %d AND la.status = 'active'
        GROUP BY l.product_id
        ORDER BY license_count DESC
    ", $provider_account['id'], $provider_account['id']), ARRAY_A);

    return [
        'account_info' => $provider_account,
        'assigned_licenses' => $assigned_licenses,
        'served_products' => $served_products,
        'stats' => [
            'total_assigned_licenses' => count($assigned_licenses),
            'total_served_products' => count($served_products),
            'capacity_usage' => $provider_account['current_load'] . '/' . $provider_account['capacity']
        ]
    ];
}

/**
 * Tra cứu danh sách license với thông tin liên quan
 */
function vd_get_license_list_with_details($filters = []) {
    global $wpdb;

    $where_clauses = [];
    $params = [];

    if (!empty($filters['product_id'])) {
        $where_clauses[] = "l.product_id = %d";
        $params[] = $filters['product_id'];
    }

    if (!empty($filters['status'])) {
        $where_clauses[] = "l.status = %s";
        $params[] = $filters['status'];
    }

    if (!empty($filters['provider_account_id'])) {
        $where_clauses[] = "la.provider_account_id = %d";
        $params[] = $filters['provider_account_id'];
    }

    if (!empty($filters['has_overrides'])) {
        $where_clauses[] = "lso.id IS " . ($filters['has_overrides'] ? 'NOT NULL' : 'NULL');
    }

    $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

    $sql = "
        SELECT
            l.id,
            l.license_key,
            l.product_id,
            l.status,
            l.expires_at,
            l.created_at,
            pa.account_name as assigned_account,
            pa.provider as provider_type,
            la.assignment_method,
            la.assigned_at,
            COUNT(DISTINCT dr.id) as total_devices,
            SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_devices,
            (CASE WHEN lso.id IS NOT NULL THEN 'yes' ELSE 'no' END) as has_override,
            ps.max_devices as product_max_devices,
            lso.max_devices as license_max_devices
        FROM {$wpdb->prefix}vd_licenses l
        LEFT JOIN {$wpdb->prefix}vd_license_assignments la ON l.id = la.license_id
        LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON la.provider_account_id = pa.id
        LEFT JOIN {$wpdb->prefix}vd_device_requests dr ON l.id = dr.license_id
        LEFT JOIN {$wpdb->prefix}vd_license_settings_override lso ON l.id = lso.license_id
        LEFT JOIN {$wpdb->prefix}vd_product_settings ps ON l.product_id = ps.product_id
        {$where_sql}
        GROUP BY l.id, l.license_key, l.product_id, l.status, l.expires_at, l.created_at,
                 pa.account_name, pa.provider, la.assignment_method, la.assigned_at,
                 lso.id, ps.max_devices, lso.max_devices
        ORDER BY l.created_at DESC
    ";

    if (empty($params)) {
        $results = $wpdb->get_results($sql, ARRAY_A);
    } else {
        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    return $results;
}
```

### Manual Account Assignment
```php
/**
 * Admin function to manually assign provider account to license
 */
function vd_admin_assign_provider_account($license_id, $provider_account_id, $admin_user_id, $reason = '') {
    global $wpdb;

    try {
        $wpdb->query('START TRANSACTION');

        // Get current assignment
        $current_assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_license_assignments WHERE license_id = %d",
            $license_id
        ), ARRAY_A);

        // Get provider account info
        $provider_account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE id = %d",
            $provider_account_id
        ), ARRAY_A);

        if (!$provider_account) {
            throw new Exception("Provider account không tồn tại");
        }

        // Check if provider account is active
        if ($provider_account['status'] !== 'active') {
            throw new Exception("Provider account không hoạt động");
        }

        // Log assignment history
        if ($current_assignment) {
            $old_provider = $wpdb->get_row($wpdb->prepare(
                "SELECT account_name FROM {$wpdb->prefix}vd_provider_accounts WHERE id = %d",
                $current_assignment['provider_account_id']
            ));

            // Insert history record
            $wpdb->insert(
                "{$wpdb->prefix}vd_license_assignment_history",
                [
                    'license_id' => $license_id,
                    'old_provider_account_id' => $current_assignment['provider_account_id'],
                    'new_provider_account_id' => $provider_account_id,
                    'changed_by' => $admin_user_id,
                    'change_method' => 'manual',
                    'change_reason' => $reason,
                    'old_account_name' => $old_provider['account_name'] ?? 'Unknown',
                    'new_account_name' => $provider_account['account_name']
                ]
            );

            // Update current assignment
            $wpdb->update(
                "{$wpdb->prefix}vd_license_assignments",
                [
                    'provider_account_id' => $provider_account_id,
                    'assigned_by' => $admin_user_id,
                    'assignment_method' => 'manual',
                    'change_reason' => $reason,
                    'assigned_at' => current_time('mysql')
                ],
                ['license_id' => $license_id]
            );

        } else {
            // Create new assignment
            $wpdb->insert(
                "{$wpdb->prefix}vd_license_assignments",
                [
                    'license_id' => $license_id,
                    'provider_account_id' => $provider_account_id,
                    'assigned_by' => $admin_user_id,
                    'assignment_method' => 'manual',
                    'change_reason' => $reason,
                    'assigned_at' => current_time('mysql'),
                    'status' => 'active'
                ]
            );

            // Log history
            $wpdb->insert(
                "{$wpdb->prefix}vd_license_assignment_history",
                [
                    'license_id' => $license_id,
                    'old_provider_account_id' => null,
                    'new_provider_account_id' => $provider_account_id,
                    'changed_by' => $admin_user_id,
                    'change_method' => 'manual',
                    'change_reason' => $reason ?: 'Initial manual assignment',
                    'old_account_name' => null,
                    'new_account_name' => $provider_account['account_name']
                ]
            );
        }

        // Update provider account load (if using capacity tracking)
        update_provider_account_load($current_assignment['provider_account_id'] ?? null, $provider_account_id);

        $wpdb->query('COMMIT');

        // Log audit trail
        vd_log_audit_trail('license_assignment', $license_id, 'manual_assign',
            ['old_provider' => $current_assignment['provider_account_id'] ?? null],
            ['new_provider' => $provider_account_id, 'reason' => $reason],
            $admin_user_id
        );

        return [
            'success' => true,
            'message' => "License đã được gán tài khoản {$provider_account['account_name']}",
            'assignment_id' => $current_assignment['id'] ?? $wpdb->insert_id
        ];

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');

        return [
            'success' => false,
            'message' => 'Lỗi khi gán tài khoản: ' . $e->getMessage()
        ];
    }
}
```

---

## ✅ BƯỚC 1: License Validity Check

```php
/**
 * Validate license expiry and status
 */
function validate_license_expiry($license_key) {
    global $wpdb;

    // Input validation
    if (!vd_validate_license_key($license_key)) {
        return ['valid' => false, 'error' => 'Invalid license key format', 'code' => 'invalid_format'];
    }

    // Get license from database
    $license = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_licenses WHERE license_key = %s",
        $license_key
    ), ARRAY_A);

    if (!$license) {
        return ['valid' => false, 'error' => 'License không tồn tại', 'code' => 'license_not_found'];
    }

    // Check status
    if ($license['status'] === 'suspended') {
        return ['valid' => false, 'error' => 'License đã bị tạm khóa', 'code' => 'license_suspended'];
    }

    if ($license['status'] === 'expired') {
        return ['valid' => false, 'error' => 'License đã hết hạn', 'code' => 'license_expired'];
    }

    // Check expiry date
    if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
        // Update status to expired
        $wpdb->update(
            "{$wpdb->prefix}vd_licenses",
            ['status' => 'expired'],
            ['id' => $license['id']],
            ['%s'],
            ['%d']
        );

        return ['valid' => false, 'error' => 'License đã hết hạn', 'code' => 'license_expired'];
    }

    // Check if expiring soon (warning)
    $days_until_expiry = null;
    if ($license['expires_at']) {
        $days_until_expiry = ceil((strtotime($license['expires_at']) - time()) / (24 * 3600));
    }

    return [
        'valid' => true,
        'license' => $license,
        'days_until_expiry' => $days_until_expiry
    ];
}
```

---

## ⚡ BƯỚC 2: Rate Limiting Check

```php
/**
 * Smart rate limiting với content change bypass
 * Sử dụng settings inheritance system
 */
function validate_rate_limits($license_id) {
    global $wpdb;

    // Get license info to get product_id
    $license = $wpdb->get_row($wpdb->prepare(
        "SELECT id, product_id FROM {$wpdb->prefix}vd_licenses WHERE id = %d",
        $license_id
    ), ARRAY_A);

    if (!$license) {
        return ['allowed' => false, 'error' => 'License not found'];
    }

    // Get effective settings using inheritance
    $settings = get_license_settings($license_id, $license['product_id']);

    $window_seconds = $settings['rate_limit_window_hours'] * 3600; // Convert hours to seconds
    $max_hits = $settings['rate_limit_requests'];

    // Get current rate limit state
    $rate_limit = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_license_rate_limits
         WHERE license_id = %d AND window_seconds = %d",
        $license_id, $window_seconds
    ), ARRAY_A);

    if (!$rate_limit) {
        // Create new rate limit record
        $wpdb->insert(
            "{$wpdb->prefix}vd_license_rate_limits",
            [
                'license_id' => $license_id,
                'window_seconds' => $window_seconds,
                'max_hits' => $max_hits,
                'current_count' => 0,
                'bypass_on_content_change' => 1
            ],
            ['%d', '%d', '%d', '%d', '%d']
        );

        return [
            'allowed' => true,
            'window_seconds' => $window_seconds,
            'max_hits' => $max_hits,
            'current_count' => 0,
            'remaining' => $max_hits,
            'bypassed' => false,
            'reset_time' => time() + $window_seconds
        ];
    }

    // Check if window has expired
    $window_start = strtotime($rate_limit['last_reset']);
    $window_end = $window_start + $config['window_seconds'];
    $now = time();

    if ($now >= $window_end) {
        // Reset window
        $wpdb->update(
            "{$wpdb->prefix}vd_license_rate_limits",
            [
                'current_count' => 0,
                'last_reset' => current_time('mysql')
            ],
            ['id' => $rate_limit['id']],
            ['%d', '%s'],
            ['%d']
        );

        $rate_limit['current_count'] = 0;
        $rate_limit['last_reset'] = current_time('mysql');
    }

    // Check content change bypass
    $current_content_hash = get_current_content_hash($license_id);
    if ($rate_limit['bypass_on_content_change'] &&
        $rate_limit['last_content_hash'] &&
        $rate_limit['last_content_hash'] !== $current_content_hash) {

        // Content changed - reset counter and bypass
        $wpdb->update(
            "{$wpdb->prefix}vd_license_rate_limits",
            [
                'current_count' => 0,
                'last_content_hash' => $current_content_hash,
                'last_reset' => current_time('mysql')
            ],
            ['id' => $rate_limit['id']],
            ['%d', '%s', '%s'],
            ['%d']
        );

        return [
            'allowed' => true,
            'window_seconds' => $config['window_seconds'],
            'max_hits' => $config['max_hits'],
            'current_count' => 0,
            'remaining' => $config['max_hits'],
            'bypassed' => true,
            'bypass_reason' => 'content_changed'
        ];
    }

    // Check if limit exceeded
    if ($rate_limit['current_count'] >= $config['max_hits']) {
        $retry_after = $window_end - $now;
        $retry_minutes = ceil($retry_after / 60);

        return [
            'allowed' => false,
            'retry_after' => $retry_after,
            'retry_minutes' => $retry_minutes,
            'reset_at' => date('c', $window_end),
            'current_count' => $rate_limit['current_count'],
            'max_hits' => $config['max_hits']
        ];
    }

    // Increment counter
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}vd_license_rate_limits
         SET current_count = current_count + 1,
             last_content_hash = %s
         WHERE id = %d",
        $current_content_hash, $rate_limit['id']
    ));

    return [
        'allowed' => true,
        'window_seconds' => $config['window_seconds'],
        'max_hits' => $config['max_hits'],
        'current_count' => $rate_limit['current_count'] + 1,
        'remaining' => $config['max_hits'] - $rate_limit['current_count'] - 1,
        'reset_at' => date('c', $window_end),
        'bypassed' => false
    ];
}
```

---

## 📱 BƯỚC 3: Device Validity Check

```php
/**
 * Comprehensive device validation với multiple scenarios
 */
function validate_device_access($license_id, $device_fp, $device_info) {
    global $wpdb;

    // Validate device fingerprint format
    if (!vd_validate_device_fp($device_fp)) {
        return ['valid' => false, 'message' => 'Invalid device fingerprint', 'code' => 'invalid_device_fp'];
    }

    // Get license info to get product_id
    $license = $wpdb->get_row($wpdb->prepare(
        "SELECT id, product_id FROM {$wpdb->prefix}vd_licenses WHERE id = %d",
        $license_id
    ), ARRAY_A);

    if (!$license) {
        return ['valid' => false, 'message' => 'License not found', 'code' => 'license_not_found'];
    }

    // Get effective settings using inheritance
    $settings = get_license_settings($license_id, $license['product_id']);
    $max_devices = $settings['max_devices'];
    $auto_approval_enabled = $settings['auto_approval_enabled'];

    // Check if device already exists
    $existing_device = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_device_requests
         WHERE license_id = %d AND device_fp = %s",
        $license_id, $device_fp
    ), ARRAY_A);

    if ($existing_device) {
        return handle_existing_device($existing_device, $settings, $device_info);
    }

    // New device - check limits
    $approved_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vd_device_requests
         WHERE license_id = %d AND status = 'approved'",
        $license_id
    ));

    if ($approved_count >= $max_devices) {
        return [
            'valid' => false,
            'message' => "Đã đạt giới hạn {$max_devices} thiết bị. Vui lòng nâng cấp gói hoặc liên hệ để tăng giới hạn.",
            'code' => 'device_limit_exceeded',
            'details' => [
                'current_devices' => $approved_count,
                'max_devices' => $max_devices,
                'settings_source' => vd_get_settings_source($license_id, $license['product_id'])
            ]
        ];
    }

    // Create new device request
    return create_new_device_request($license_id, $device_fp, $device_info, $settings);
}

/**
 * Handle existing device scenarios
 */
function handle_existing_device($device, $limits, $device_info) {
    global $wpdb;

    // Update last seen
    $wpdb->update(
        "{$wpdb->prefix}vd_device_requests",
        ['first_seen' => current_time('mysql')],
        ['id' => $device['id']],
        ['%s'], ['%d']
    );

    switch ($device['status']) {
        case 'approved':
            return ['valid' => true, 'device' => $device];

        case 'blocked':
            return [
                'valid' => false,
                'message' => 'Thiết bị này đã bị chặn. Vui lòng liên hệ hỗ trợ.',
                'code' => 'device_blocked'
            ];

        case 'pending':
            $waiting_hours = (time() - strtotime($device['first_seen'])) / 3600;
            return [
                'valid' => false,
                'message' => 'Thiết bị đang chờ phê duyệt. Admin sẽ xem xét trong 24h.',
                'code' => 'device_pending_approval',
                'details' => [
                    'waiting_hours' => round($waiting_hours, 1),
                    'risk_score' => $device['risk_score']
                ]
            ];

        case 'over_limit':
            // Check if still in grace period
            $grace_end = strtotime($device['approved_at']) + ($limits['grace_period_hours'] * 3600);
            if (time() < $grace_end) {
                $remaining_hours = ceil(($grace_end - time()) / 3600);
                return [
                    'valid' => true,  // Allow access during grace period
                    'device' => $device,
                    'grace_period' => true,
                    'grace_remaining_hours' => $remaining_hours
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => 'Thiết bị đã hết thời gian gia hạn. Vui lòng nâng cấp gói.',
                    'code' => 'device_grace_expired'
                ];
            }

        default:
            return [
                'valid' => false,
                'message' => 'Trạng thái thiết bị không hợp lệ.',
                'code' => 'device_invalid_status'
            ];
    }
}

/**
 * Create new device request với risk scoring
 */
function create_new_device_request($license_id, $device_fp, $device_info, $limits) {
    global $wpdb;

    // Calculate risk score
    $risk_score = calculate_risk_score($license_id, $device_fp, $device_info);

    // Determine if should auto-approve
    $should_auto_approve = $limits['auto_approval_enabled'] && should_auto_approve($license_id, $device_fp, $risk_score);

    // Create device request
    $device_data = [
        'license_id' => $license_id,
        'device_fp' => $device_fp,
        'device_info' => json_encode($device_info),
        'risk_score' => $risk_score,
        'auto_approved' => $should_auto_approve ? 1 : 0,
        'status' => $should_auto_approve ? 'approved' : 'pending',
        'ip_address' => $device_info['ip'],
        'user_agent' => $device_info['user_agent'],
        'country_code' => $device_info['country'] ?? null,
        'approved_at' => $should_auto_approve ? current_time('mysql') : null
    ];

    $wpdb->insert("{$wpdb->prefix}vd_device_requests", $device_data);
    $device_data['id'] = $wpdb->insert_id;

    if ($should_auto_approve) {
        return ['valid' => true, 'device' => $device_data];
    } else {
        return [
            'valid' => false,
            'message' => 'Thiết bị mới cần phê duyệt thủ công do risk score cao. Admin sẽ xem xét trong 24h.',
            'code' => 'device_pending_approval',
            'details' => [
                'risk_score' => $risk_score,
                'auto_approval_threshold' => 50
            ]
        ];
    }
}
```

---

## 🏢 BƯỚC 4: Product-Based Assignment

```php
/**
 * Assignment logic với product-provider mapping
 */
function get_or_create_assignment($license) {
    global $wpdb;

    // Check existing assignment (sticky)
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT la.*, pa.provider, pa.share_type, pa.account_name
         FROM {$wpdb->prefix}vd_license_assignments la
         JOIN {$wpdb->prefix}vd_provider_accounts pa ON la.provider_account_id = pa.id
         WHERE la.license_id = %d AND la.status = 'active'",
        $license['id']
    ), ARRAY_A);

    if ($existing) {
        // Update last accessed
        $wpdb->update(
            "{$wpdb->prefix}vd_license_assignments",
            ['last_accessed' => current_time('mysql')],
            ['id' => $existing['id']],
            ['%s'], ['%d']
        );

        return $existing;
    }

    // No existing assignment - create new one
    return create_new_assignment($license);
}

/**
 * Create new assignment theo product provider mapping
 */
function create_new_assignment($license) {
    global $wpdb;

    // Get available providers for this product
    $available_providers = get_available_providers_for_product($license['product_id']);

    if (empty($available_providers)) {
        error_log("No provider accounts configured for product {$license['product_id']}");
        return false;
    }

    // Select provider theo strategy
    $selected_provider = select_provider_by_strategy($available_providers);

    if (!$selected_provider) {
        error_log("No available provider capacity for product {$license['product_id']}");
        return false;
    }

    // Create assignment
    $assignment_data = [
        'license_id' => $license['id'],
        'provider_account_id' => $selected_provider['provider_account_id'],
        'status' => 'active'
    ];

    $wpdb->insert("{$wpdb->prefix}vd_license_assignments", $assignment_data);

    // Update provider load
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}vd_provider_accounts
         SET current_load = current_load + 1
         WHERE id = %d",
        $selected_provider['provider_account_id']
    ));

    // Return assignment with provider info
    return array_merge($assignment_data, [
        'id' => $wpdb->insert_id,
        'provider' => $selected_provider['provider'],
        'share_type' => $selected_provider['share_type'],
        'account_name' => $selected_provider['account_name']
    ]);
}
```

---

## 🔄 Assignment Strategies

### 1. Least Loaded Strategy
```php
/**
 * Chọn provider có load percentage thấp nhất
 */
function select_least_loaded_provider($providers) {
    $loads = [];

    foreach ($providers as $provider) {
        $load_percentage = ($provider['current_load'] / max($provider['capacity'], 1)) * 100;

        // Skip if at capacity
        if ($provider['current_load'] >= $provider['capacity']) {
            continue;
        }

        $loads[] = [
            'provider' => $provider,
            'load_percentage' => $load_percentage,
            'available_slots' => $provider['capacity'] - $provider['current_load']
        ];
    }

    if (empty($loads)) {
        return null; // All providers at capacity
    }

    // Sort by load percentage (ascending), then by available slots (descending)
    usort($loads, function($a, $b) {
        $load_diff = $a['load_percentage'] <=> $b['load_percentage'];
        if ($load_diff === 0) {
            return $b['available_slots'] <=> $a['available_slots'];
        }
        return $load_diff;
    });

    return $loads[0]['provider'];
}
```

### 2. Sequential Strategy
```php
/**
 * Cấp phát theo thứ tự priority - hết A mới đến B
 */
function select_sequential_provider($providers) {
    // Sort by priority (1 = highest priority)
    usort($providers, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });

    // Select first provider with available capacity
    foreach ($providers as $provider) {
        if ($provider['current_load'] < $provider['capacity']) {
            return $provider;
        }
    }

    return null; // All providers at capacity
}
```

### 3. Round Robin Strategy
```php
/**
 * Phân phối đều theo vòng tròn
 */
function select_round_robin_provider($providers) {
    global $wpdb;

    // Get last used provider for round robin
    $last_assignment = $wpdb->get_var($wpdb->prepare(
        "SELECT provider_account_id
         FROM {$wpdb->prefix}vd_license_assignments
         WHERE provider_account_id IN (%s)
         ORDER BY assigned_at DESC LIMIT 1",
        implode(',', array_column($providers, 'provider_account_id'))
    ));

    // Sort providers by ID to ensure consistent order
    usort($providers, function($a, $b) {
        return $a['provider_account_id'] <=> $b['provider_account_id'];
    });

    // Find next provider in sequence
    $start_index = 0;
    if ($last_assignment) {
        foreach ($providers as $i => $provider) {
            if ($provider['provider_account_id'] == $last_assignment) {
                $start_index = ($i + 1) % count($providers);
                break;
            }
        }
    }

    // Try each provider starting from next in sequence
    for ($i = 0; $i < count($providers); $i++) {
        $index = ($start_index + $i) % count($providers);
        $provider = $providers[$index];

        if ($provider['current_load'] < $provider['capacity']) {
            return $provider;
        }
    }

    return null; // All providers at capacity
}
```

---

## 🎲 Risk Scoring Algorithm

```php
/**
 * Calculate device risk score (0-100)
 */
function calculate_risk_score($license_id, $device_fp, $device_info) {
    $score = 0;

    // Factor 1: Existing device count (0-30 points)
    $existing_devices = get_license_device_count($license_id);
    $score += min($existing_devices * 10, 30);

    // Factor 2: Geographic diversity penalty (0-25 points)
    $countries = get_license_countries($license_id);
    if (count($countries) > 2) {
        $score += (count($countries) - 2) * 8;
    }

    // Factor 3: IP reputation (0-25 points)
    if (is_ip_suspicious($device_info['ip'])) {
        $score += 25;
    }

    // Factor 4: Device similarity bonus (-10 to +15 points)
    $similarity = calculate_device_similarity($device_fp, $license_id);
    if ($similarity > 0.8) {
        $score -= 10; // Bonus for similar devices
    } elseif ($similarity < 0.3) {
        $score += 15; // Penalty for very different devices
    }

    // Factor 5: Time-based access patterns (0-20 points)
    $access_pattern = analyze_access_patterns($license_id);
    if ($access_pattern['suspicious']) {
        $score += 20;
    }

    // Factor 6: Browser/OS combination (0-15 points)
    if (is_uncommon_browser_os_combo($device_info['browser'], $device_info['os'])) {
        $score += 15;
    }

    // Clamp to 0-100 range
    return max(0, min(100, $score));
}

/**
 * Auto-approval decision logic
 */
function should_auto_approve($license_id, $device_fp, $risk_score) {
    // Always require manual review for high risk
    if ($risk_score >= 70) {
        return false;
    }

    // Check for recent rate limit violations
    if (has_recent_violations($license_id)) {
        return false;
    }

    // Check for rapid device additions
    if (has_rapid_device_additions($license_id)) {
        return false;
    }

    // Medium risk - time-based decision
    if ($risk_score >= 50) {
        // Only auto-approve during business hours for medium risk
        $hour = (int) date('G');
        return $hour >= 9 && $hour <= 17;
    }

    // Low risk - auto approve
    return true;
}
```

---

## 🔒 Security & Validation

### Input Validation
```php
function vd_validate_license_key($license_key) {
    return preg_match('/^[A-Z0-9\-]{8,32}$/', $license_key);
}

function vd_validate_device_fp($device_fp) {
    return preg_match('/^[a-f0-9]{64}$/', $device_fp);
}

function vd_validate_device_info($device_info) {
    $required = ['ip', 'user_agent', 'browser', 'os'];

    foreach ($required as $field) {
        if (empty($device_info[$field])) {
            return false;
        }
    }

    return filter_var($device_info['ip'], FILTER_VALIDATE_IP) !== false;
}
```

### SQL Injection Prevention
```php
// ✅ CORRECT: Always use prepared statements
$license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vd_licenses WHERE license_key = %s",
    $license_key
));

// ✅ CORRECT: Use format specifiers for inserts
$wpdb->insert(
    "{$wpdb->prefix}vd_device_requests",
    [
        'license_id' => $license_id,
        'device_fp' => $device_fp,
        'risk_score' => $risk_score
    ],
    ['%d', '%s', '%f']
);
```

---

## ⚠️ Edge Cases & Error Handling

### 1. Provider Account Failures
```php
function handle_provider_failure($provider_account_id, $license_id) {
    global $wpdb;

    // Mark provider as maintenance
    $wpdb->update(
        "{$wpdb->prefix}vd_provider_accounts",
        ['status' => 'maintenance'],
        ['id' => $provider_account_id]
    );

    // Find alternative provider
    $license = get_license_by_id($license_id);
    $alternatives = get_available_providers_for_product($license['product_id']);

    if (!empty($alternatives)) {
        // Reassign to new provider
        $new_provider = select_provider_by_strategy($alternatives);
        if ($new_provider) {
            reassign_license($license_id, $new_provider['provider_account_id']);
            return true;
        }
    }

    // No alternatives available
    send_admin_alert("Provider {$provider_account_id} failed and no alternatives available");
    return false;
}
```

### 2. Configurable Field Sharing Logic
```php
/**
 * Lấy thông tin tài khoản được filter theo cấu hình chia sẻ của product
 */
function get_filtered_provider_content($provider_account_id, $product_id) {
    global $wpdb;

    // Lấy thông tin đầy đủ từ content_versions
    $content = $wpdb->get_row($wpdb->prepare(
        "SELECT cv.*, pa.provider
         FROM {$wpdb->prefix}vd_content_versions cv
         JOIN {$wpdb->prefix}vd_provider_accounts pa ON cv.provider_account_id = pa.id
         WHERE cv.provider_account_id = %d AND cv.is_active = 1
         ORDER BY cv.version_number DESC LIMIT 1",
        $provider_account_id
    ), ARRAY_A);

    if (!$content) {
        return null;
    }

    // Lấy cấu hình trường nào được chia sẻ cho product này
    $sharing_config = $wpdb->get_results($wpdb->prepare(
        "SELECT field_name, display_name, sort_order, is_sensitive
         FROM {$wpdb->prefix}vd_product_field_sharing_config
         WHERE product_id = %d AND is_shared = 1
         ORDER BY sort_order ASC",
        $product_id
    ), ARRAY_A);

    if (empty($sharing_config)) {
        // Không có cấu hình chia sẻ -> không chia sẻ gì cả
        return [];
    }

    // Filter content theo cấu hình
    $filtered_content = [];

    foreach ($sharing_config as $config) {
        $field_name = $config['field_name'];
        $display_name = $config['display_name'] ?: ucfirst(str_replace('_', ' ', $field_name));

        if (isset($content[$field_name]) && $content[$field_name] !== null) {
            $filtered_content[$display_name] = $content[$field_name];

            // Thêm metadata cho trường nhạy cảm
            if ($config['is_sensitive']) {
                $filtered_content[$display_name . '_type'] = 'sensitive';
            }
        }
    }

    return $filtered_content;
}

/**
 * Quản lý cấu hình field sharing cho admin
 */
function vd_admin_update_product_field_sharing($product_id, $field_configs) {
    global $wpdb;

    // Bắt đầu transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Xóa cấu hình cũ
        $wpdb->delete(
            "{$wpdb->prefix}vd_product_field_sharing_config",
            ['product_id' => $product_id]
        );

        // Thêm cấu hình mới
        foreach ($field_configs as $field_name => $config) {
            $wpdb->insert(
                "{$wpdb->prefix}vd_product_field_sharing_config",
                [
                    'product_id' => $product_id,
                    'field_name' => $field_name,
                    'is_shared' => $config['is_shared'] ? 1 : 0,
                    'display_name' => $config['display_name'],
                    'sort_order' => $config['sort_order'],
                    'is_sensitive' => $config['is_sensitive'] ? 1 : 0
                ],
                ['%d', '%s', '%d', '%s', '%d', '%d']
            );
        }

        $wpdb->query('COMMIT');

        // Log audit
        vd_log_audit('product_field_sharing', $product_id, 'update', null, $field_configs, get_current_user_id());

        return ['success' => true];

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Lấy danh sách tất cả các trường có thể chia sẻ
 */
function vd_get_available_share_fields() {
    return [
        // Login info
        'email' => [
            'label' => 'Email đăng nhập',
            'category' => 'Login Info',
            'is_sensitive' => false
        ],
        'password' => [
            'label' => 'Mật khẩu',
            'category' => 'Login Info',
            'is_sensitive' => true
        ],
        'twofa_code' => [
            'label' => 'Mã 2FA',
            'category' => 'Login Info',
            'is_sensitive' => true
        ],
        'cookies' => [
            'label' => 'Cookie đăng nhập',
            'category' => 'Login Info',
            'is_sensitive' => true
        ],

        // Recovery info
        'recovery_email' => [
            'label' => 'Email khôi phục',
            'category' => 'Recovery Info',
            'is_sensitive' => false
        ],
        'recovery_password' => [
            'label' => 'Mật khẩu khôi phục',
            'category' => 'Recovery Info',
            'is_sensitive' => true
        ],
        'recovery_twofa_code' => [
            'label' => 'Mã 2FA khôi phục',
            'category' => 'Recovery Info',
            'is_sensitive' => true
        ],

        // Account metadata
        'account_registration_date' => [
            'label' => 'Ngày đăng ký',
            'category' => 'Account Info',
            'is_sensitive' => false
        ],
        'account_expiry_date' => [
            'label' => 'Ngày hết hạn',
            'category' => 'Account Info',
            'is_sensitive' => false
        ],
        'registration_amount' => [
            'label' => 'Số tiền đăng ký',
            'category' => 'Account Info',
            'is_sensitive' => false
        ],
        'status' => [
            'label' => 'Trạng thái tài khoản',
            'category' => 'Account Info',
            'is_sensitive' => false
        ],

        // Operational info
        'assigned_licenses_count' => [
            'label' => 'Số license gán',
            'category' => 'Operational',
            'is_sensitive' => false
        ],
        'last_checked_at' => [
            'label' => 'Lần check cuối',
            'category' => 'Operational',
            'is_sensitive' => false
        ],
        'last_success_at' => [
            'label' => 'Lần thành công cuối',
            'category' => 'Operational',
            'is_sensitive' => false
        ],
        'error_count' => [
            'label' => 'Số lỗi',
            'category' => 'Operational',
            'is_sensitive' => false
        ],
        'notes' => [
            'label' => 'Ghi chú',
            'category' => 'Operational',
            'is_sensitive' => false
        ]
    ];
}

/**
 * Lấy cấu hình field sharing hiện tại của product
 */
function vd_get_product_field_sharing_config($product_id) {
    global $wpdb;

    $config = $wpdb->get_results($wpdb->prepare(
        "SELECT field_name, is_shared, display_name, sort_order, is_sensitive
         FROM {$wpdb->prefix}vd_product_field_sharing_config
         WHERE product_id = %d
         ORDER BY sort_order ASC",
        $product_id
    ), ARRAY_A);

    // Convert to associative array
    $result = [];
    foreach ($config as $item) {
        $result[$item['field_name']] = [
            'is_shared' => (bool)$item['is_shared'],
            'display_name' => $item['display_name'],
            'sort_order' => (int)$item['sort_order'],
            'is_sensitive' => (bool)$item['is_sensitive']
        ];
    }

    return $result;
}
```

### 3. Concurrent Access Handling
```php
function handle_concurrent_device_creation($license_id, $device_fp) {
    global $wpdb;

    // Use INSERT IGNORE to handle race conditions
    $result = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->prefix}vd_device_requests
         (license_id, device_fp, status, first_seen)
         VALUES (%d, %s, 'pending', %s)",
        $license_id, $device_fp, current_time('mysql')
    ));

    if ($wpdb->insert_id === 0) {
        // Record already exists - get existing
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_device_requests
             WHERE license_id = %d AND device_fp = %s",
            $license_id, $device_fp
        ), ARRAY_A);
    }

    return ['id' => $wpdb->insert_id, 'status' => 'pending'];
}
```

### 3. Grace Period Management
```php
function handle_device_limit_reduction($license_id, $old_limit, $new_limit) {
    global $wpdb;

    if ($new_limit >= $old_limit) {
        return; // No action needed
    }

    // Get approved devices ordered by approval date
    $devices = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vd_device_requests
         WHERE license_id = %d AND status = 'approved'
         ORDER BY approved_at ASC",
        $license_id
    ), ARRAY_A);

    // Mark excess devices as over_limit
    $excess_devices = array_slice($devices, $new_limit);
    $device_limits = get_effective_device_limits($license_id);

    foreach ($excess_devices as $device) {
        // Update status
        $wpdb->update(
            "{$wpdb->prefix}vd_device_requests",
            ['status' => 'over_limit'],
            ['id' => $device['id']]
        );

        // Schedule revocation after grace period
        wp_schedule_single_event(
            time() + ($device_limits['grace_period_hours'] * 3600),
            'vd_revoke_over_limit_device',
            [$device['id']]
        );

        // Send notification
        send_device_grace_period_notification($license_id, $device, $device_limits['grace_period_hours']);
    }
}
```

---

**🎯 Business Logic hoàn chỉnh implementing chính xác 4-step flow với comprehensive error handling và security measures.**