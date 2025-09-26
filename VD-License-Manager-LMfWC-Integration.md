# VD License Manager - LMfWC Integration Guide

## Overview
Integration specifications for VD License Manager with License Manager for WooCommerce (LMfWC), including API connectivity, database access patterns, and data synchronization.

## Environment Configuration

### LMfWC API Credentials
```php
// REST API Authentication
Consumer Key:    ck_208d18a140490def109b29fcc14739765427d8cb
Consumer Secret: cs_36f463fa7f9548f6aff9cf195a3143a064b159ed

// Base64 Authentication Header
Authorization: Basic Y2tfMjA4ZDE4YTE0MDQ5MGRlZjEwOWIyOWZjYzE0NzM5NzY1NDI3ZDhjYjpjc18zNmY0NjNmYTdmOTU0OGY2YWZmOWNmMTk1YTMxNDNhMDY0YjE1OWVk
```

### Database Configuration
```php
// LMfWC Database Tables (with bz_ prefix)
PRIMARY_TABLE = bz_lmfwc_licenses
RELATED_TABLES = [
    'bz_lmfwc_activations',
    'bz_lmfwc_api_keys',
    'bz_lmfwc_generators',
    'bz_lmfwc_licenses_meta'
]

// License Source Configuration
LICENSE_SOURCE = LMFWC_DB  // Direct database access (recommended)
FIELDS_MAPPING = license_key,product_id,status,expires_at
```

## Database Schema Analysis

### LMfWC Licenses Table Structure
```sql
-- Table: bz_lmfwc_licenses
CREATE TABLE bz_lmfwc_licenses (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id             BIGINT UNSIGNED NULL,           -- WooCommerce order reference
    product_id           BIGINT UNSIGNED NOT NULL,       -- WooCommerce product ID
    user_id              BIGINT UNSIGNED NULL,           -- WordPress user ID
    license_key          VARCHAR(255) NOT NULL,          -- Unique license identifier
    hash                 VARCHAR(255) NULL,              -- License key hash
    expires_at           DATETIME NULL,                  -- Expiration timestamp
    valid_for            INT NULL,                       -- Validity period (days)
    source               VARCHAR(255) NULL,              -- License source identifier
    status               TINYINT NOT NULL DEFAULT 1,     -- Status (1=active, 2=inactive, 3=expired, etc.)
    times_activated      INT NULL DEFAULT 0,             -- Current activation count
    times_activated_max  INT NULL,                       -- Maximum allowed activations
    created_at           DATETIME NOT NULL,              -- Creation timestamp
    created_by           BIGINT UNSIGNED NULL,           -- Creator user ID
    updated_at           DATETIME NULL,                  -- Last update timestamp
    updated_by           BIGINT UNSIGNED NULL,           -- Last updater user ID

    UNIQUE KEY uk_license_key (license_key),
    KEY idx_product_id (product_id),
    KEY idx_status (status),
    KEY idx_user_id (user_id),
    KEY idx_expires_at (expires_at)
);
```

### Status Code Mapping
```php
// LMfWC Status Codes → VD License Manager Status
const LMFWC_STATUS_MAPPING = [
    1 => 'active',      // SOLD/DELIVERED
    2 => 'inactive',    // INACTIVE
    3 => 'expired',     // EXPIRED
    4 => 'suspended',   // DISABLED
    // Additional status codes as needed
];
```

## Integration Strategies

### 1. Direct Database Access (Recommended)

#### License Lookup Implementation
```php
<?php

class VD_LMfWC_Database_Integration {
    private $wpdb;
    private string $lmfwc_table;
    private string $vd_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
        $this->vd_table = $wpdb->prefix . 'vd_licenses';
    }

    /**
     * Get license information from LMfWC database
     */
    public function get_lmfwc_license(string $license_key): ?array {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    id,
                    license_key,
                    product_id,
                    user_id,
                    order_id,
                    status,
                    expires_at,
                    times_activated,
                    times_activated_max,
                    created_at
                FROM {$this->lmfwc_table}
                WHERE license_key = %s",
                $license_key
            ),
            ARRAY_A
        );

        return $result ?: null;
    }

    /**
     * Validate license with comprehensive checks
     */
    public function validate_license(string $license_key): array {
        $license = $this->get_lmfwc_license($license_key);

        if (!$license) {
            return [
                'valid' => false,
                'error' => 'LICENSE_NOT_FOUND',
                'message' => 'License key not found in database'
            ];
        }

        // Check status
        $status = $this->map_lmfwc_status($license['status']);
        if ($status !== 'active') {
            return [
                'valid' => false,
                'error' => 'LICENSE_INACTIVE',
                'message' => "License status: {$status}",
                'status' => $status
            ];
        }

        // Check expiration
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            return [
                'valid' => false,
                'error' => 'LICENSE_EXPIRED',
                'message' => 'License has expired',
                'expires_at' => $license['expires_at']
            ];
        }

        // Check activation limits (if applicable)
        $max_activations = $license['times_activated_max'];
        $current_activations = $license['times_activated'];

        if ($max_activations && $current_activations >= $max_activations) {
            return [
                'valid' => false,
                'error' => 'ACTIVATION_LIMIT_REACHED',
                'message' => 'Maximum activations reached',
                'activations' => "{$current_activations}/{$max_activations}"
            ];
        }

        return [
            'valid' => true,
            'license' => $license,
            'status' => $status,
            'product_id' => $license['product_id'],
            'user_id' => $license['user_id'],
            'expires_at' => $license['expires_at']
        ];
    }

    /**
     * Map LMfWC status codes to VD status
     */
    private function map_lmfwc_status(int $status_code): string {
        $mapping = [
            1 => 'active',
            2 => 'inactive',
            3 => 'expired',
            4 => 'suspended'
        ];

        return $mapping[$status_code] ?? 'unknown';
    }

    /**
     * Get product configuration for license
     */
    public function get_product_config(int $product_id): array {
        // Default configuration based on provided mappings
        $default_configs = [
            8210 => 'COOKIE',      // Helium10
            1357 => 'USERPASS',    // Midjourney
            6456 => 'USERPASS_2FA' // Freepik
        ];

        $share_type = $default_configs[$product_id] ?? 'USERPASS';

        return [
            'product_id' => $product_id,
            'share_type' => $share_type,
            'max_devices' => $this->get_product_device_limit($product_id),
            'auto_approval_threshold' => $this->get_product_approval_threshold($product_id)
        ];
    }

    private function get_product_device_limit(int $product_id): int {
        // Get from WooCommerce product meta or use default
        $limit = get_post_meta($product_id, '_vd_device_limit', true);
        return $limit ? (int)$limit : 3; // Default 3 devices
    }

    private function get_product_approval_threshold(int $product_id): float {
        // Get from WooCommerce product meta or use default
        $threshold = get_post_meta($product_id, '_vd_approval_threshold', true);
        return $threshold ? (float)$threshold : 25.0; // Default 25% risk threshold
    }

    /**
     * Sync license data to VD tables
     */
    public function sync_license_to_vd(array $lmfwc_license): bool {
        $vd_license_data = [
            'license_key' => $lmfwc_license['license_key'],
            'product_id' => $lmfwc_license['product_id'],
            'order_id' => $lmfwc_license['order_id'],
            'user_id' => $lmfwc_license['user_id'],
            'status' => $this->map_lmfwc_status($lmfwc_license['status']),
            'expires_at' => $lmfwc_license['expires_at'],
            'created_at' => $lmfwc_license['created_at'],
            'updated_at' => current_time('mysql')
        ];

        // Check if license already exists in VD table
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->vd_table} WHERE license_key = %s",
                $lmfwc_license['license_key']
            )
        );

        if ($existing) {
            // Update existing record
            return $this->wpdb->update(
                $this->vd_table,
                $vd_license_data,
                ['license_key' => $lmfwc_license['license_key']]
            ) !== false;
        } else {
            // Insert new record
            return $this->wpdb->insert($this->vd_table, $vd_license_data) !== false;
        }
    }
}
```

### 2. REST API Integration (Fallback)

#### API Client Implementation
```php
<?php

class VD_LMfWC_API_Integration {
    private string $consumer_key;
    private string $consumer_secret;
    private string $base_url;

    public function __construct() {
        $this->consumer_key = 'ck_208d18a140490def109b29fcc14739765427d8cb';
        $this->consumer_secret = 'cs_36f463fa7f9548f6aff9cf195a3143a064b159ed';
        $this->base_url = get_site_url() . '/wp-json/lmfwc/v2';
    }

    /**
     * Get license via REST API
     */
    public function get_license_api(string $license_key): ?array {
        $url = $this->base_url . '/licenses/' . urlencode($license_key);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            error_log('VD LMfWC API Error: ' . $response->get_error_message());
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log("VD LMfWC API Error: HTTP {$status_code}");
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['data'] ?? null;
    }

    /**
     * Validate license via REST API
     */
    public function validate_license_api(string $license_key): array {
        $url = $this->base_url . '/licenses/validate/' . urlencode($license_key);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return [
                'valid' => false,
                'error' => 'API_ERROR',
                'message' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200 && isset($data['success']) && $data['success']) {
            return [
                'valid' => true,
                'license' => $data['data']
            ];
        }

        return [
            'valid' => false,
            'error' => 'VALIDATION_FAILED',
            'message' => $data['message'] ?? 'Unknown validation error'
        ];
    }

    /**
     * Test API connectivity
     */
    public function test_connection(): array {
        $url = $this->base_url . '/licenses';

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 5
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        return [
            'success' => $status_code === 200,
            'status_code' => $status_code,
            'response_time' => $this->get_response_time($response)
        ];
    }

    private function get_response_time($response): float {
        $headers = wp_remote_retrieve_headers($response);
        return isset($headers['x-response-time']) ? (float)$headers['x-response-time'] : 0.0;
    }
}
```

## Integration Facade

### Unified License Manager
```php
<?php

class VD_License_Integration {
    private $db_integration;
    private $api_integration;
    private string $integration_mode;

    public function __construct() {
        $this->db_integration = new VD_LMfWC_Database_Integration();
        $this->api_integration = new VD_LMfWC_API_Integration();
        $this->integration_mode = defined('VD_LMFWC_MODE') ? VD_LMFWC_MODE : 'database';
    }

    /**
     * Validate license using preferred integration method
     */
    public function validate_license(string $license_key): array {
        try {
            if ($this->integration_mode === 'database') {
                return $this->db_integration->validate_license($license_key);
            } else {
                return $this->api_integration->validate_license_api($license_key);
            }
        } catch (Exception $e) {
            error_log('VD License Integration Error: ' . $e->getMessage());

            // Fallback to alternative method
            try {
                if ($this->integration_mode === 'database') {
                    return $this->api_integration->validate_license_api($license_key);
                } else {
                    return $this->db_integration->validate_license($license_key);
                }
            } catch (Exception $fallback_error) {
                return [
                    'valid' => false,
                    'error' => 'INTEGRATION_FAILURE',
                    'message' => 'Both database and API integration failed'
                ];
            }
        }
    }

    /**
     * Get license information
     */
    public function get_license(string $license_key): ?array {
        if ($this->integration_mode === 'database') {
            return $this->db_integration->get_lmfwc_license($license_key);
        } else {
            return $this->api_integration->get_license_api($license_key);
        }
    }

    /**
     * Get product configuration
     */
    public function get_product_config(int $product_id): array {
        return $this->db_integration->get_product_config($product_id);
    }

    /**
     * Test integration health
     */
    public function health_check(): array {
        $results = [
            'database' => $this->test_database_connection(),
            'api' => $this->api_integration->test_connection()
        ];

        return [
            'overall_status' => $results['database']['success'] || $results['api']['success'],
            'details' => $results
        ];
    }

    private function test_database_connection(): array {
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'lmfwc_licenses';

            $test_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE 1=1 LIMIT 1"
            );

            $result = $wpdb->get_var($test_query);

            return [
                'success' => $result !== null,
                'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

## Testing Integration

### Test License Validation
```php
<?php

class VD_LMfWC_Integration_Test {
    private $integration;

    public function __construct() {
        $this->integration = new VD_License_Integration();
    }

    /**
     * Test with provided test license
     */
    public function test_validation(): array {
        $test_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

        $result = $this->integration->validate_license($test_license);

        return [
            'test_license' => $test_license,
            'validation_result' => $result,
            'expected_product_id' => 8210,
            'expected_share_type' => 'COOKIE',
            'test_passed' => $result['valid'] &&
                           $result['product_id'] == 8210
        ];
    }

    /**
     * Test product configuration
     */
    public function test_product_configs(): array {
        $test_products = [8210, 1357, 6456];
        $results = [];

        foreach ($test_products as $product_id) {
            $config = $this->integration->get_product_config($product_id);
            $results[$product_id] = $config;
        }

        return $results;
    }

    /**
     * Test health checks
     */
    public function test_health(): array {
        return $this->integration->health_check();
    }

    /**
     * Run comprehensive integration test
     */
    public function run_full_test(): array {
        return [
            'timestamp' => current_time('mysql'),
            'environment' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'table_prefix' => $GLOBALS['wpdb']->prefix
            ],
            'health_check' => $this->test_health(),
            'license_validation' => $this->test_validation(),
            'product_configs' => $this->test_product_configs()
        ];
    }
}
```

## WP-CLI Integration Commands

### CLI Commands for Testing
```php
<?php

if (defined('WP_CLI') && WP_CLI) {

    /**
     * VD License Manager LMfWC integration commands
     */
    class VD_LMfWC_CLI_Commands {

        /**
         * Test LMfWC integration
         *
         * ## OPTIONS
         *
         * [--license=<license_key>]
         * : Test specific license key
         *
         * ## EXAMPLES
         *
         *     wp vd test-lmfwc
         *     wp vd test-lmfwc --license=H10D-DIJD-14RC-SOLE-6KUV30
         */
        public function test_lmfwc($args, $assoc_args) {
            $integration_test = new VD_LMfWC_Integration_Test();

            if (isset($assoc_args['license'])) {
                $license_key = $assoc_args['license'];
                $integration = new VD_License_Integration();
                $result = $integration->validate_license($license_key);

                WP_CLI::line("Testing license: {$license_key}");
                WP_CLI::line("Result: " . ($result['valid'] ? 'VALID' : 'INVALID'));
                if (!$result['valid']) {
                    WP_CLI::line("Error: {$result['error']} - {$result['message']}");
                }
            } else {
                $results = $integration_test->run_full_test();
                WP_CLI::line("Full Integration Test Results:");
                WP_CLI::line(json_encode($results, JSON_PRETTY_PRINT));
            }
        }

        /**
         * Check LMfWC health
         */
        public function health() {
            $integration = new VD_License_Integration();
            $health = $integration->health_check();

            WP_CLI::line("LMfWC Integration Health Check:");
            WP_CLI::line("Overall Status: " . ($health['overall_status'] ? 'HEALTHY' : 'UNHEALTHY'));
            WP_CLI::line("Database: " . ($health['details']['database']['success'] ? 'OK' : 'FAILED'));
            WP_CLI::line("API: " . ($health['details']['api']['success'] ? 'OK' : 'FAILED'));
        }

        /**
         * Validate specific license
         */
        public function validate($args, $assoc_args) {
            if (empty($args[0])) {
                WP_CLI::error('License key required');
            }

            $license_key = $args[0];
            $integration = new VD_License_Integration();
            $result = $integration->validate_license($license_key);

            if ($result['valid']) {
                WP_CLI::success("License {$license_key} is VALID");
                WP_CLI::line("Product ID: {$result['product_id']}");
                WP_CLI::line("Status: {$result['status']}");
            } else {
                WP_CLI::error("License {$license_key} is INVALID: {$result['message']}");
            }
        }
    }

    WP_CLI::add_command('vd test-lmfwc', ['VD_LMfWC_CLI_Commands', 'test_lmfwc']);
    WP_CLI::add_command('vd health', ['VD_LMfWC_CLI_Commands', 'health']);
    WP_CLI::add_command('vd validate', ['VD_LMfWC_CLI_Commands', 'validate']);
}
```

## Configuration Verification

### Environment Setup Verification
```bash
# 1. Verify LMfWC tables exist
wp db query "SHOW TABLES LIKE 'bz_lmfwc_%'"

# 2. Check test license exists
wp db query "SELECT license_key, product_id, status FROM bz_lmfwc_licenses WHERE license_key = 'H10D-DIJD-14RC-SOLE-6KUV30'"

# 3. Test API connectivity
wp vd health

# 4. Validate test license
wp vd validate H10D-DIJD-14RC-SOLE-6KUV30

# 5. Run full integration test
wp vd test-lmfwc
```

### PHP Configuration Check
```php
<?php
// Add to wp-config.php for integration testing

// LMfWC Integration Mode
define('VD_LMFWC_MODE', 'database'); // or 'api'

// Debug mode for integration testing
define('VD_DEBUG_LMFWC', true);

// Log integration events
if (defined('VD_DEBUG_LMFWC') && VD_DEBUG_LMFWC) {
    add_action('vd_license_validated', function($license_key, $result) {
        error_log("VD LMfWC: License {$license_key} validation - " .
                  ($result['valid'] ? 'VALID' : 'INVALID'));
    }, 10, 2);
}
```

## Integration Monitoring

### Performance Monitoring
```php
<?php

class VD_LMfWC_Monitor {
    private static array $metrics = [];

    public static function log_validation_time(string $license_key, float $duration): void {
        self::$metrics[] = [
            'type' => 'validation',
            'license_key' => $license_key,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];

        // Log slow validations
        if ($duration > 1.0) {
            error_log("VD LMfWC: Slow validation for {$license_key}: {$duration}s");
        }
    }

    public static function get_metrics(): array {
        return self::$metrics;
    }

    public static function get_average_validation_time(): float {
        $validations = array_filter(self::$metrics, function($m) {
            return $m['type'] === 'validation';
        });

        if (empty($validations)) {
            return 0.0;
        }

        $total_time = array_sum(array_column($validations, 'duration'));
        return $total_time / count($validations);
    }
}
```

## Troubleshooting Guide

### Common Issues

#### 1. API Authentication Failure
```bash
# Test API credentials
curl -X GET "https://vidieu.vn/wp-json/lmfwc/v2/licenses" \
  -H "Authorization: Basic Y2tfMjA4ZDE4YTE0MDQ5MGRlZjEwOWIyOWZjYzE0NzM5NzY1NDI3ZDhjYjpjc18zNmY0NjNmYTdmOTU0OGY2YWZmOWNmMTk1YTMxNDNhMDY0YjE1OWVk"
```

#### 2. Database Table Missing
```sql
-- Check if LMfWC tables exist
SHOW TABLES LIKE 'bz_lmfwc_%';

-- Create missing indexes if needed
CREATE INDEX idx_license_key ON bz_lmfwc_licenses(license_key);
CREATE INDEX idx_product_status ON bz_lmfwc_licenses(product_id, status);
```

#### 3. Performance Issues
```php
// Enable query logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SAVEQUERIES', true);

// Check slow queries
add_action('shutdown', function() {
    global $wpdb;
    foreach ($wpdb->queries as $query) {
        if ($query[1] > 0.5) { // Queries taking > 0.5 seconds
            error_log("Slow query: {$query[0]} ({$query[1]}s)");
        }
    }
});
```

## Next Steps

### Integration Deployment Checklist
- [ ] ✅ Verify LMfWC tables exist with bz_ prefix
- [ ] ✅ Test API credentials with provided consumer key/secret
- [ ] ✅ Validate test license H10D-DIJD-14RC-SOLE-6KUV30
- [ ] ✅ Confirm product mappings (8210→COOKIE, 1357→USERPASS, 6456→USERPASS_2FA)
- [ ] ✅ Set up integration monitoring
- [ ] ✅ Configure fallback between database and API access
- [ ] ✅ Test with production traffic

### Performance Optimization
- Use database access for license validation (faster)
- Implement caching for frequently accessed licenses
- Monitor query performance with slow query logging
- Set up health monitoring for both integration methods

The integration is now ready for implementation with comprehensive error handling, fallback mechanisms, and monitoring capabilities.