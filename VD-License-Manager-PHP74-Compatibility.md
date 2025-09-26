# VD License Manager - PHP 7.4 Compatibility Assessment

## Overview
Compatibility assessment and code adaptations required for VD License Manager to run on PHP 7.4.27 production environment.

## Environment Context
```bash
Current Environment: PHP 7.4.27
Target Environment: PHP 8.0+ (recommended)
WordPress Version: 6.8.2
Status: COMPATIBILITY REVIEW REQUIRED
```

## PHP Version Compatibility Matrix

### Supported Features in PHP 7.4
✅ **Available Features:**
- Object-oriented programming with typed properties
- Arrow functions (`fn() =>`)
- Null coalescing operator (`??`)
- Spaceship operator (`<=>`)
- Anonymous classes
- Traits and interfaces
- Namespaces
- Exception handling
- PDO and MySQLi extensions
- OpenSSL extension
- JSON extension

❌ **Missing PHP 8+ Features:**
- Union types (`string|int`)
- Named arguments
- Match expressions
- Nullsafe operator (`?->`)
- Constructor property promotion
- Attributes (annotations)
- Mixed type declaration
- Static return type

## Code Adaptation Requirements

### 1. Type Declarations

#### Original PHP 8+ Code (NOT Compatible)
```php
// PHP 8+ syntax - AVOID
class VD_License_Manager {
    public function __construct(
        private string $license_key,
        private int|null $user_id = null,
        private array $config = []
    ) {}

    public function validate(): bool|string {
        return match($this->status) {
            'active' => true,
            'expired' => 'License expired',
            default => 'Invalid status'
        };
    }
}
```

#### PHP 7.4 Compatible Code (REQUIRED)
```php
// PHP 7.4 compatible syntax
class VD_License_Manager {
    private string $license_key;
    private ?int $user_id;
    private array $config;

    public function __construct(string $license_key, ?int $user_id = null, array $config = []) {
        $this->license_key = $license_key;
        $this->user_id = $user_id;
        $this->config = $config;
    }

    /**
     * @return bool|string
     */
    public function validate() {
        switch($this->status) {
            case 'active':
                return true;
            case 'expired':
                return 'License expired';
            default:
                return 'Invalid status';
        }
    }
}
```

### 2. Database Access Patterns

#### PHP 7.4 Compatible Database Manager
```php
<?php

class VD_Database_Manager {
    private $wpdb;
    private string $table_prefix;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }

    /**
     * Get license by key with PHP 7.4 compatibility
     */
    public function get_license_by_key(string $license_key): ?array {
        $table = $this->table_prefix . 'vd_licenses';

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE license_key = %s AND status = 'active'",
                $license_key
            ),
            ARRAY_A
        );

        return $result ?: null;
    }

    /**
     * Create license assignment with error handling
     */
    public function create_license_assignment(int $license_id, int $provider_account_id): bool {
        $table = $this->table_prefix . 'vd_license_assignments';

        try {
            $result = $this->wpdb->insert(
                $table,
                [
                    'license_id' => $license_id,
                    'provider_account_id' => $provider_account_id,
                    'assigned_at' => current_time('mysql'),
                    'status' => 'active'
                ],
                ['%d', '%d', '%s', '%s']
            );

            return $result !== false;
        } catch (Exception $e) {
            error_log('VD License Manager: Assignment creation failed - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update provider account load with concurrency control
     */
    public function increment_provider_load(int $account_id): bool {
        $table = $this->table_prefix . 'vd_provider_accounts';

        // Use traditional approach instead of newer syntax
        $sql = $this->wpdb->prepare(
            "UPDATE {$table} SET current_load = current_load + 1 WHERE id = %d AND current_load < capacity",
            $account_id
        );

        $result = $this->wpdb->query($sql);
        return $result > 0;
    }
}
```

### 3. API Response Handling

#### PHP 7.4 Compatible API Response Class
```php
<?php

class VD_API_Response {
    private bool $success;
    private array $data;
    private string $message;
    private int $code;

    public function __construct(bool $success, array $data = [], string $message = '', int $code = 200) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->code = $code;
    }

    public function to_array(): array {
        $response = [
            'success' => $this->success,
            'data' => $this->data
        ];

        if (!empty($this->message)) {
            $response['message'] = $this->message;
        }

        return $response;
    }

    public function send(): void {
        http_response_code($this->code);
        header('Content-Type: application/json');
        echo json_encode($this->to_array(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Factory method for success response
     */
    public static function success(array $data = [], string $message = ''): self {
        return new self(true, $data, $message, 200);
    }

    /**
     * Factory method for error response
     */
    public static function error(string $message, int $code = 400, array $data = []): self {
        return new self(false, $data, $message, $code);
    }
}
```

### 4. Encryption Implementation

#### PHP 7.4 Compatible Encryption Class
```php
<?php

class VD_Security_Manager {
    private string $encryption_key;
    private string $cipher_method = 'AES-256-GCM';

    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }

    private function get_encryption_key(): string {
        if (!defined('VD_ENCRYPTION_KEY')) {
            throw new Exception('VD_ENCRYPTION_KEY not defined in wp-config.php');
        }

        $key = VD_ENCRYPTION_KEY;

        // Handle base64 encoded keys
        if (strpos($key, 'base64:') === 0) {
            $decoded = base64_decode(substr($key, 7));
            if ($decoded === false || strlen($decoded) !== 32) {
                throw new Exception('Invalid base64 encryption key');
            }
            return $decoded;
        }

        if (strlen($key) !== 32) {
            throw new Exception('Encryption key must be exactly 32 bytes');
        }

        return $key;
    }

    /**
     * Encrypt data with AES-256-GCM
     */
    public function encrypt(string $data): string {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }

        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            $this->cipher_method,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length
        );

        if ($encrypted === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        // Combine IV + tag + encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data with AES-256-GCM
     */
    public function decrypt(string $encrypted_data): string {
        if (empty($encrypted_data)) {
            throw new InvalidArgumentException('Encrypted data cannot be empty');
        }

        $data = base64_decode($encrypted_data);
        if ($data === false) {
            throw new Exception('Invalid base64 encrypted data');
        }

        if (strlen($data) < 28) { // 12 (IV) + 16 (tag) = minimum 28 bytes
            throw new Exception('Encrypted data too short');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher_method,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Generate secure hash for data integrity
     */
    public function hash_data(string $data): string {
        return hash_hmac('sha256', $data, $this->encryption_key);
    }

    /**
     * Verify data integrity
     */
    public function verify_hash(string $data, string $hash): bool {
        return hash_equals($hash, $this->hash_data($data));
    }
}
```

### 5. Device Fingerprinting

#### PHP 7.4 Compatible Device Manager
```php
<?php

class VD_Device_Manager {
    private $wpdb;
    private string $table_prefix;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }

    /**
     * Generate device fingerprint from request data
     */
    public function generate_fingerprint(array $device_info): string {
        // Normalize device information
        $normalized = [
            'user_agent' => $device_info['user_agent'] ?? '',
            'screen_resolution' => $device_info['screen_resolution'] ?? '',
            'timezone' => $device_info['timezone'] ?? '',
            'language' => $device_info['language'] ?? '',
            'platform' => $device_info['platform'] ?? ''
        ];

        // Remove empty values and sort for consistency
        $normalized = array_filter($normalized);
        ksort($normalized);

        // Generate SHA-256 hash
        return hash('sha256', json_encode($normalized));
    }

    /**
     * Calculate risk score for device approval
     */
    public function calculate_risk_score(int $license_id, string $device_fp, array $device_info): float {
        $score = 0.0;

        // Check for existing devices on this license
        $existing_devices = $this->get_license_devices($license_id);
        $device_count = count($existing_devices);

        // Base score from device count
        if ($device_count === 0) {
            $score += 10.0; // First device is lower risk
        } elseif ($device_count >= 3) {
            $score += 30.0; // Multiple devices increase risk
        } else {
            $score += 20.0; // Moderate risk
        }

        // IP address analysis
        $ip_address = $device_info['ip_address'] ?? '';
        if (!empty($ip_address)) {
            $ip_score = $this->analyze_ip_address($ip_address, $license_id);
            $score += $ip_score;
        }

        // User agent analysis
        $user_agent = $device_info['user_agent'] ?? '';
        if (!empty($user_agent)) {
            $ua_score = $this->analyze_user_agent($user_agent);
            $score += $ua_score;
        }

        // Country/location analysis
        $country = $device_info['country'] ?? '';
        if (!empty($country)) {
            $country_score = $this->analyze_country($country, $license_id);
            $score += $country_score;
        }

        return min(100.0, max(0.0, $score));
    }

    private function get_license_devices(int $license_id): array {
        $table = $this->table_prefix . 'vd_device_requests';

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE license_id = %d AND status IN ('approved', 'pending')",
                $license_id
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    private function analyze_ip_address(string $ip_address, int $license_id): float {
        $table = $this->table_prefix . 'vd_device_requests';

        // Check for same IP on this license
        $same_ip_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE license_id = %d AND ip_address = %s",
                $license_id,
                $ip_address
            )
        );

        if ($same_ip_count > 0) {
            return 5.0; // Same IP = lower risk
        }

        // Check for IP across different licenses (potential sharing)
        $other_license_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(DISTINCT license_id) FROM {$table} WHERE ip_address = %s AND license_id != %d",
                $ip_address,
                $license_id
            )
        );

        if ($other_license_count > 3) {
            return 25.0; // High sharing = high risk
        } elseif ($other_license_count > 0) {
            return 15.0; // Some sharing = moderate risk
        }

        return 10.0; // New IP = normal risk
    }

    private function analyze_user_agent(string $user_agent): float {
        // Simple bot detection
        $bot_patterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'
        ];

        $user_agent_lower = strtolower($user_agent);
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return 40.0; // Bot-like = high risk
            }
        }

        // Check for suspicious patterns
        if (strlen($user_agent) < 20) {
            return 20.0; // Too short = suspicious
        }

        if (strlen($user_agent) > 500) {
            return 15.0; // Too long = suspicious
        }

        return 5.0; // Normal user agent
    }

    private function analyze_country(string $country, int $license_id): float {
        $table = $this->table_prefix . 'vd_device_requests';

        // Check for country consistency
        $existing_countries = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT DISTINCT country_code FROM {$table} WHERE license_id = %d AND country_code IS NOT NULL",
                $license_id
            )
        );

        if (empty($existing_countries)) {
            return 5.0; // First country = normal
        }

        if (in_array($country, $existing_countries)) {
            return 0.0; // Same country = low risk
        }

        // High-risk countries (example list)
        $high_risk_countries = ['CN', 'RU', 'KP', 'IR'];
        if (in_array($country, $high_risk_countries)) {
            return 30.0; // High-risk country
        }

        return 10.0; // Different but normal country
    }

    /**
     * Request device approval
     */
    public function request_device_approval(int $license_id, string $device_fp, array $device_info): array {
        $table = $this->table_prefix . 'vd_device_requests';

        // Calculate risk score
        $risk_score = $this->calculate_risk_score($license_id, $device_fp, $device_info);

        // Determine auto-approval (threshold from product settings)
        $auto_approval_threshold = $this->get_auto_approval_threshold($license_id);
        $auto_approved = $risk_score <= $auto_approval_threshold;
        $status = $auto_approved ? 'approved' : 'pending';

        // Insert device request
        $insert_data = [
            'license_id' => $license_id,
            'device_fp' => $device_fp,
            'device_info' => json_encode($device_info),
            'risk_score' => $risk_score,
            'auto_approved' => $auto_approved ? 1 : 0,
            'status' => $status,
            'ip_address' => $device_info['ip_address'] ?? '',
            'user_agent' => $device_info['user_agent'] ?? '',
            'country_code' => $device_info['country'] ?? null,
            'first_seen' => current_time('mysql')
        ];

        if ($auto_approved) {
            $insert_data['approved_at'] = current_time('mysql');
            $insert_data['approved_by'] = 0; // System approval
        }

        $result = $this->wpdb->insert($table, $insert_data);

        if ($result === false) {
            throw new Exception('Failed to insert device request');
        }

        return [
            'device_id' => $this->wpdb->insert_id,
            'status' => $status,
            'risk_score' => $risk_score,
            'auto_approved' => $auto_approved
        ];
    }

    private function get_auto_approval_threshold(int $license_id): float {
        // Get threshold from product settings
        $license_table = $this->table_prefix . 'vd_licenses';
        $settings_table = $this->table_prefix . 'vd_product_settings';

        $threshold = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT ps.auto_approval_threshold
                 FROM {$license_table} l
                 JOIN {$settings_table} ps ON l.product_id = ps.product_id
                 WHERE l.id = %d",
                $license_id
            )
        );

        return $threshold !== null ? (float)$threshold : 25.0; // Default threshold
    }
}
```

## Performance Considerations for PHP 7.4

### 1. Memory Management
```php
// Efficient memory usage in PHP 7.4
class VD_Memory_Manager {
    private const MAX_BATCH_SIZE = 100;

    public function process_large_dataset(array $data): array {
        $results = [];
        $chunks = array_chunk($data, self::MAX_BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $chunk_results = $this->process_chunk($chunk);
            $results = array_merge($results, $chunk_results);

            // Force garbage collection for large datasets
            if (memory_get_usage() > 64 * 1024 * 1024) { // 64MB
                gc_collect_cycles();
            }
        }

        return $results;
    }

    private function process_chunk(array $chunk): array {
        // Process chunk and return results
        return array_map(function($item) {
            return $this->transform_item($item);
        }, $chunk);
    }
}
```

### 2. Database Query Optimization
```php
// Optimized queries for PHP 7.4
class VD_Query_Optimizer {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Optimized license lookup with prepared statements
     */
    public function get_licenses_batch(array $license_keys): array {
        if (empty($license_keys)) {
            return [];
        }

        $table = $this->wpdb->prefix . 'vd_licenses';
        $placeholders = implode(',', array_fill(0, count($license_keys), '%s'));

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$table} WHERE license_key IN ({$placeholders})",
            ...$license_keys
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);
        return $results ?: [];
    }

    /**
     * Paginated results for large datasets
     */
    public function get_paginated_results(string $table, int $page = 1, int $per_page = 25): array {
        $offset = ($page - 1) * $per_page;

        $total_query = "SELECT COUNT(*) FROM {$table}";
        $total = (int)$this->wpdb->get_var($total_query);

        $data_query = $this->wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        $data = $this->wpdb->get_results($data_query, ARRAY_A);

        return [
            'data' => $data ?: [],
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
}
```

## Testing Strategy for PHP 7.4

### Unit Test Compatibility
```php
<?php
// PHPUnit compatible with PHP 7.4
use PHPUnit\Framework\TestCase;

class VD_License_Manager_Test extends TestCase {
    private $license_manager;

    protected function setUp(): void {
        parent::setUp();
        $this->license_manager = new VD_License_Manager();
    }

    public function test_license_validation_php74(): void {
        $test_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

        $result = $this->license_manager->validate_license($test_license);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertTrue($result['valid']);
    }

    public function test_encryption_compatibility(): void {
        $security_manager = new VD_Security_Manager();
        $test_data = 'Test encryption data';

        $encrypted = $security_manager->encrypt($test_data);
        $decrypted = $security_manager->decrypt($encrypted);

        $this->assertEquals($test_data, $decrypted);
    }

    /**
     * Test device fingerprinting with PHP 7.4
     */
    public function test_device_fingerprinting(): void {
        $device_manager = new VD_Device_Manager();

        $device_info = [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'screen_resolution' => '1920x1080',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'language' => 'vi-VN',
            'platform' => 'Win32'
        ];

        $fingerprint = $device_manager->generate_fingerprint($device_info);

        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint)); // SHA-256 hex length
    }
}
```

## Deployment Checklist for PHP 7.4

### Pre-deployment Verification
```bash
# 1. Check PHP version and extensions
php -v  # Should show 7.4.27
php -m | grep -E "(openssl|json|mysqli|curl|mbstring)"

# 2. Verify WordPress compatibility
wp core version  # Should be 6.8.2

# 3. Test database connectivity
wp db check

# 4. Verify encryption key format
wp eval 'echo defined("VD_ENCRYPTION_KEY") ? "Key defined" : "Key missing";'
```

### Performance Monitoring
```php
// Performance monitoring for PHP 7.4
class VD_Performance_Monitor {
    private static array $timers = [];

    public static function start_timer(string $name): void {
        self::$timers[$name] = microtime(true);
    }

    public static function end_timer(string $name): float {
        if (!isset(self::$timers[$name])) {
            return 0.0;
        }

        $duration = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);

        if (defined('VD_DEBUG_MODE') && VD_DEBUG_MODE) {
            error_log("VD Performance: {$name} took {$duration}s");
        }

        return $duration;
    }

    public static function log_memory_usage(string $context): void {
        if (defined('VD_DEBUG_MODE') && VD_DEBUG_MODE) {
            $memory = memory_get_usage(true) / 1024 / 1024;
            error_log("VD Memory: {$context} - {$memory}MB");
        }
    }
}
```

## Migration Path to PHP 8+

### Future Upgrade Considerations
```php
// Prepare for future PHP 8+ migration
class VD_Future_Compatibility {
    /**
     * This method will be enhanced in PHP 8+
     * Current: Traditional switch
     * Future: Match expressions
     */
    public function handle_license_status(string $status): string {
        // PHP 7.4 compatible
        switch ($status) {
            case 'active':
                return 'License is active and valid';
            case 'expired':
                return 'License has expired';
            case 'suspended':
                return 'License is temporarily suspended';
            default:
                return 'Unknown license status';
        }

        // TODO: Replace with match expression in PHP 8+
        /*
        return match($status) {
            'active' => 'License is active and valid',
            'expired' => 'License has expired',
            'suspended' => 'License is temporarily suspended',
            default => 'Unknown license status'
        };
        */
    }

    /**
     * Type hints prepared for union types
     * Current: DocBlock annotations
     * Future: Native union types
     */
    /**
     * @param string|int $identifier
     * @return array|null
     */
    public function find_by_identifier($identifier): ?array {
        // Implementation here
        return null;
    }

    // TODO: Replace with union types in PHP 8+
    // public function find_by_identifier(string|int $identifier): ?array
}
```

## Recommendations

### ⚠️ Critical Actions Required

1. **PHP Extension Verification**
   ```bash
   # Verify these extensions are available:
   php -m | grep -E "(openssl|json)"
   ```

2. **Code Review for PHP 8+ Syntax**
   - Remove any match expressions
   - Convert union types to DocBlock annotations
   - Replace nullsafe operators with null checks
   - Use traditional switch statements

3. **Performance Optimization**
   - Implement query result caching
   - Use efficient array operations
   - Monitor memory usage during operation

4. **Testing Strategy**
   - Test all encryption/decryption operations
   - Verify database connectivity and queries
   - Test API endpoints under load
   - Validate WordPress hook compatibility

### 📈 Upgrade Path
**Recommended Timeline:**
- **Phase 1**: Deploy on PHP 7.4 with compatibility code
- **Phase 2**: Plan PHP 8.1+ upgrade (6-12 months)
- **Phase 3**: Refactor to use PHP 8+ native features

**Benefits of PHP 8+ Upgrade:**
- Better performance (JIT compilation)
- Improved type safety
- Cleaner syntax with match expressions
- Better error handling
- Enhanced security features

## Conclusion

VD License Manager can be successfully deployed on PHP 7.4.27 with the adaptations outlined in this document. The main requirements are:

1. ✅ Remove PHP 8+ specific syntax
2. ✅ Use traditional type declarations
3. ✅ Implement proper error handling
4. ✅ Optimize for PHP 7.4 performance characteristics
5. ✅ Maintain backward compatibility

All core functionality including encryption, database operations, device management, and API endpoints will work correctly on PHP 7.4 with these modifications.