<?php

namespace VD\LicenseManager\Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Validation Utils Manager
 *
 * Extracted utility functions from monolithic VD_License_Validator
 * Handles database utils, debug utils, and validation reporting
 *
 * Step 5.1.2: Validator Refactoring - Validation Utils Manager Extraction
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_License_Validation_Utils {

    /**
     * Singleton instance
     *
     * @var VD_License_Validation_Utils|null
     */
    private static $instance = null;

    /**
     * Global settings cache
     *
     * @var array|null
     */
    private static $global_config = null;

    /**
     * Get singleton instance
     *
     * @return VD_License_Validation_Utils
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Prevent cloning
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     */
    private function __wakeup() {
        // Prevent unserialization
    }

    /**
     * Check if database table exists
     *
     * Extracted from VD_License_Validator::table_exists()
     * Step 5.1.2 - Database utility extraction
     *
     * @since 1.6.0
     * @param string $table_name Table name to check
     * @return bool True if table exists, false otherwise
     */
    public function table_exists($table_name) {
        global $wpdb;

        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));

        return $table_exists > 0;
    }

    /**
     * Get global system settings
     *
     * Extracted from VD_License_Validator::get_global_settings()
     * Step 5.1.2 - Configuration utility extraction
     *
     * @since 1.6.0
     * @return array Global configuration settings
     */
    public function get_global_settings() {
        global $wpdb;

        if (self::$global_config !== null) {
            return self::$global_config;
        }

        $settings_table = $wpdb->prefix . 'vd_global_settings';

        // Check if settings table exists first
        if (!$this->table_exists($settings_table)) {
            self::$global_config = array();
            return self::$global_config;
        }

        $settings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT setting_key, setting_value FROM %i",
                $settings_table
            ),
            ARRAY_A
        );

        self::$global_config = array();
        if ($settings) {
            foreach ($settings as $setting) {
                self::$global_config[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return self::$global_config;
    }

    /**
     * Get database lookup debug information
     *
     * Extracted from VD_License_Validator::get_lookup_debug_info()
     * Step 5.1.2 - Debug utility extraction
     *
     * @since 1.6.0
     * @param string $license_key License key for debugging
     * @return array Debug information array
     */
    public function get_lookup_debug_info($license_key) {
        global $wpdb;

        $lmfwc_table = 'bz_lmfwc_licenses';
        $vd_table = $wpdb->prefix . 'vd_licenses';

        return array(
            'license_key' => $license_key,
            'lmfwc_table_exists' => $this->table_exists($lmfwc_table),
            'vd_table_exists' => $this->table_exists($vd_table),
            'lmfwc_table_name' => $lmfwc_table,
            'vd_table_name' => $vd_table,
            'database_name' => DB_NAME,
            'wpdb_prefix' => $wpdb->prefix,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_usage' => $this->get_memory_usage_info()
        );
    }

    /**
     * Get memory usage information for debugging
     *
     * Step 5.1.2 - Enhanced debug utility
     *
     * @since 1.6.0
     * @return array Memory usage information
     */
    public function get_memory_usage_info() {
        return array(
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'current_usage_formatted' => size_format(memory_get_usage(true)),
            'peak_usage_formatted' => size_format(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'usage_percentage' => $this->calculate_memory_usage_percentage()
        );
    }

    /**
     * Calculate memory usage percentage
     *
     * Step 5.1.2 - Memory monitoring utility
     *
     * @since 1.6.0
     * @return float Memory usage percentage
     */
    private function calculate_memory_usage_percentage() {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return 0.0; // Unlimited memory
        }

        $limit_bytes = $this->convert_memory_limit_to_bytes($limit);
        $current_usage = memory_get_usage(true);

        if ($limit_bytes <= 0) {
            return 0.0;
        }

        return round(($current_usage / $limit_bytes) * 100, 2);
    }

    /**
     * Convert memory limit string to bytes
     *
     * Step 5.1.2 - Memory calculation utility
     *
     * @since 1.6.0
     * @param string $limit Memory limit string (e.g., "256M")
     * @return int Memory limit in bytes
     */
    private function convert_memory_limit_to_bytes($limit) {
        $limit = trim($limit);
        $last_char = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last_char) {
            case 'g':
                $value *= 1024;
                // Fall through
            case 'm':
                $value *= 1024;
                // Fall through
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Log successful license validation for audit
     *
     * Extracted from VD_License_Validator::log_license_validation_success()
     * Step 5.1.2 - Audit logging utility extraction
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $license License data
     * @return void
     */
    public function log_license_validation_success($license_key, $license) {
        if (!function_exists('vd_debug_log')) {
            return;
        }

        vd_debug_log(sprintf(
            '[VD License Validator] Successful validation: %s (ID: %s, Product: %s, Source: %s)',
            $license_key,
            $license['id'] ?? 'unknown',
            $license['product_id'] ?? 'unknown',
            $license['source_table'] ?? 'unknown'
        ));

        // Enhanced logging with validation context
        $this->log_validation_context($license_key, $license, 'success');
    }

    /**
     * Log validation context for detailed debugging
     *
     * Step 5.1.2 - Enhanced validation logging
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $license License data
     * @param string $result_type Validation result type
     * @return void
     */
    public function log_validation_context($license_key, $license, $result_type) {
        if (!defined('VD_DEBUG') || !VD_DEBUG) {
            return;
        }

        $context = array(
            'license_key' => substr($license_key, 0, 8) . '...', // Partial key for security
            'license_id' => $license['id'] ?? null,
            'status' => $license['status'] ?? null,
            'result_type' => $result_type,
            'timestamp' => current_time('mysql'),
            'memory_usage' => $this->get_memory_usage_info(),
            'request_info' => $this->get_request_context()
        );

        if (function_exists('vd_debug_log')) {
            vd_debug_log('[VD Validation Context] ' . wp_json_encode($context));
        }
    }

    /**
     * Get current request context for logging
     *
     * Step 5.1.2 - Request context utility
     *
     * @since 1.6.0
     * @return array Request context information
     */
    public function get_request_context() {
        return array(
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'is_admin' => is_admin(),
            'is_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'current_user_id' => get_current_user_id()
        );
    }

    /**
     * Create standardized validation error array
     *
     * Extracted from VD_License_Validator::create_status_validation_error()
     * Step 5.1.2 - Error handling utility extraction
     *
     * @since 1.6.0
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context
     * @param array $debug_info Debug information
     * @return array Standardized error array
     */
    public function create_validation_error($code, $message, $context = array(), $debug_info = array()) {
        $error = array(
            'valid' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );

        // Add debug info if debugging is enabled
        if (defined('VD_DEBUG') && VD_DEBUG) {
            $error['debug_info'] = array_merge($debug_info, array(
                'memory_usage' => $this->get_memory_usage_info(),
                'request_context' => $this->get_request_context()
            ));
        }

        return $error;
    }

    /**
     * Generate validation statistics summary
     *
     * Step 5.1.2 - Validation statistics utility
     *
     * @since 1.6.0
     * @param array $validation_results Array of validation results
     * @return array Statistics summary
     */
    public function generate_validation_statistics($validation_results) {
        if (empty($validation_results)) {
            return array(
                'total_validations' => 0,
                'successful_validations' => 0,
                'failed_validations' => 0,
                'success_rate' => 0.0,
                'common_errors' => array(),
                'performance_metrics' => array()
            );
        }

        $total = count($validation_results);
        $successful = 0;
        $failed = 0;
        $errors = array();
        $execution_times = array();

        foreach ($validation_results as $result) {
            if (isset($result['valid']) && $result['valid']) {
                $successful++;
            } else {
                $failed++;
                if (isset($result['code'])) {
                    $errors[] = $result['code'];
                }
            }

            if (isset($result['execution_time'])) {
                $execution_times[] = $result['execution_time'];
            }
        }

        return array(
            'total_validations' => $total,
            'successful_validations' => $successful,
            'failed_validations' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0.0,
            'common_errors' => array_count_values($errors),
            'performance_metrics' => array(
                'avg_execution_time' => !empty($execution_times) ? array_sum($execution_times) / count($execution_times) : 0,
                'min_execution_time' => !empty($execution_times) ? min($execution_times) : 0,
                'max_execution_time' => !empty($execution_times) ? max($execution_times) : 0
            )
        );
    }

    /**
     * Clear global settings cache
     *
     * Step 5.1.2 - Cache management utility
     *
     * @since 1.6.0
     * @return void
     */
    public function clear_global_settings_cache() {
        self::$global_config = null;
    }

    /**
     * Get system environment information for debugging
     *
     * Step 5.1.2 - System diagnostics utility
     *
     * @since 1.6.0
     * @return array System environment information
     */
    public function get_system_environment_info() {
        global $wpdb;

        return array(
            'wordpress' => array(
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
                'site_url' => site_url(),
                'home_url' => home_url()
            ),
            'php' => array(
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ),
            'database' => array(
                'version' => $wpdb->db_version(),
                'prefix' => $wpdb->prefix,
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate
            ),
            'server' => array(
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'php_sapi' => php_sapi_name(),
                'https' => is_ssl(),
                'timezone' => wp_timezone_string()
            )
        );
    }

    /**
     * Validate license key format using basic patterns
     *
     * Step 5.1.2 - Basic validation utility
     *
     * @since 1.6.0
     * @param string $license_key License key to validate
     * @return array Validation result
     */
    public function validate_license_key_format($license_key) {
        if (empty($license_key)) {
            return $this->create_validation_error(
                'empty_license_key',
                'License key cannot be empty'
            );
        }

        // Basic format patterns
        $patterns = array(
            '/^VD-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/', // VD format
            '/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', // UUID format
            '/^[A-Z0-9]{16,32}$/' // Simple alphanumeric
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $license_key)) {
                return array(
                    'valid' => true,
                    'format' => 'recognized_pattern',
                    'pattern_matched' => $pattern
                );
            }
        }

        return $this->create_validation_error(
            'invalid_format',
            'License key format is not recognized',
            array('license_key_length' => strlen($license_key))
        );
    }

    /**
     * Test database connectivity and tables
     *
     * Step 5.1.2 - Database connectivity testing
     *
     * @since 1.6.0
     * @return array Database test results
     */
    public function test_database_connectivity() {
        global $wpdb;

        $tests = array();

        // Test basic database connection
        $tests['connection'] = array(
            'test' => 'Database Connection',
            'success' => !empty($wpdb->dbh),
            'details' => $wpdb->dbh ? 'Connected' : 'Not connected'
        );

        // Test VD licenses table
        $vd_table = $wpdb->prefix . 'vd_licenses';
        $tests['vd_licenses_table'] = array(
            'test' => 'VD Licenses Table',
            'success' => $this->table_exists($vd_table),
            'details' => $this->table_exists($vd_table) ? 'Table exists' : 'Table not found'
        );

        // Test LMFWC table (if exists)
        $lmfwc_table = 'bz_lmfwc_licenses';
        $tests['lmfwc_table'] = array(
            'test' => 'LMFWC Licenses Table',
            'success' => $this->table_exists($lmfwc_table),
            'details' => $this->table_exists($lmfwc_table) ? 'Table exists' : 'Table not found (optional)'
        );

        // Test global settings table
        $settings_table = $wpdb->prefix . 'vd_global_settings';
        $tests['settings_table'] = array(
            'test' => 'Global Settings Table',
            'success' => $this->table_exists($settings_table),
            'details' => $this->table_exists($settings_table) ? 'Table exists' : 'Table not found'
        );

        return array(
            'overall_success' => !in_array(false, array_column($tests, 'success')),
            'tests' => $tests,
            'database_info' => array(
                'name' => DB_NAME,
                'host' => DB_HOST,
                'charset' => DB_CHARSET,
                'version' => $wpdb->db_version()
            )
        );
    }
}