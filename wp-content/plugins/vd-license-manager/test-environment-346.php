<?php
/**
 * VD License Manager - Environment Verification Test
 * Step 3.4.6.1 - Ultra-Safe Environment Verification
 *
 * This temporary test script verifies the environment is ready for
 * VD_Security_Audit integration without modifying main plugin code.
 *
 * @package VD_License_Manager
 * @since 3.4.6.1
 * @temp-file This file will be deleted after verification
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If WordPress not loaded, try to load it
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../wp-load.php'
    ];

    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            require_once $wp_load_path;
            $wp_loaded = true;
            break;
        }
    }

    if (!$wp_loaded) {
        die('WordPress environment not found. Please run this script from WordPress admin or ensure proper path.');
    }
}

/**
 * VD License Manager Environment Verification Class
 */
class VD_Environment_Verifier {

    private $results = [];
    private $errors = [];
    private $warnings = [];

    /**
     * Run comprehensive environment verification
     */
    public function run_verification() {
        $this->log_section('=== VD LICENSE MANAGER - ENVIRONMENT VERIFICATION ===');
        $this->log_section('Step 3.4.6.1 - Ultra-Safe Environment Check');
        $this->log_section('Timestamp: ' . current_time('mysql'));
        $this->log_section('');

        // 1. WordPress Core Verification
        $this->verify_wordpress_core();

        // 2. Constants Verification
        $this->verify_constants();

        // 3. Functions Verification
        $this->verify_functions();

        // 4. File System Verification
        $this->verify_file_system();

        // 5. VD License Manager Components
        $this->verify_vd_components();

        // 6. Security Audit File Verification
        $this->verify_security_audit_file();

        // 7. WordPress Hooks System
        $this->verify_hooks_system();

        // 8. Final Summary
        $this->generate_summary();

        return $this->results;
    }

    /**
     * Verify WordPress core functionality
     */
    private function verify_wordpress_core() {
        $this->log_section('1. WORDPRESS CORE VERIFICATION');

        // WordPress version
        global $wp_version;
        $this->check_item('WordPress Version', $wp_version, true, 'Current: ' . $wp_version);

        // ABSPATH constant
        $this->check_item('ABSPATH Constant', defined('ABSPATH'), true, 'Path: ' . (defined('ABSPATH') ? ABSPATH : 'NOT DEFINED'));

        // WordPress functions
        $wp_functions = ['add_action', 'add_filter', 'wp_enqueue_script', 'current_time', 'get_option'];
        foreach ($wp_functions as $func) {
            $this->check_item("WordPress Function: $func", function_exists($func), true);
        }

        // Database connection
        global $wpdb;
        $this->check_item('Database Connection', is_object($wpdb), true, 'wpdb object available');

        if (is_object($wpdb)) {
            $this->check_item('Database Prefix', !empty($wpdb->prefix), true, 'Prefix: ' . $wpdb->prefix);
        }

        $this->log_section('');
    }

    /**
     * Verify required constants
     */
    private function verify_constants() {
        $this->log_section('2. CONSTANTS VERIFICATION');

        // VD License Manager constants
        $vd_constants = [
            'VD_LM_VERSION' => 'Plugin version constant',
            'VD_LM_PATH' => 'Plugin path constant',
            'VD_LM_URL' => 'Plugin URL constant',
            'VD_LM_FILE' => 'Plugin file constant',
            'VD_LM_BASENAME' => 'Plugin basename constant',
            'VD_LM_TEXT_DOMAIN' => 'Text domain constant'
        ];

        foreach ($vd_constants as $constant => $description) {
            $defined = defined($constant);
            $value = $defined ? constant($constant) : 'NOT DEFINED';
            $this->check_item($description, $defined, false, "Value: $value");
        }

        $this->log_section('');
    }

    /**
     * Verify required functions
     */
    private function verify_functions() {
        $this->log_section('3. FUNCTIONS VERIFICATION');

        // WordPress core functions
        $wp_core_functions = ['error_log', 'wp_debug_log'];
        foreach ($wp_core_functions as $func) {
            $this->check_item("WordPress Core Function: $func", function_exists($func), false);
        }

        // VD License Manager custom functions
        $vd_functions = [
            'vd_debug_log' => 'Custom debug logging function',
            'vd_is_admin' => 'Admin check function',
            'vd_is_encryption_key_valid' => 'Encryption key validation function'
        ];

        foreach ($vd_functions as $func => $description) {
            $this->check_item($description, function_exists($func), false);
        }

        $this->log_section('');
    }

    /**
     * Verify file system access
     */
    private function verify_file_system() {
        $this->log_section('4. FILE SYSTEM VERIFICATION');

        // Plugin directory
        $plugin_dir = WP_PLUGIN_DIR . '/vd-license-manager';
        $this->check_item('Plugin Directory Exists', is_dir($plugin_dir), true, "Path: $plugin_dir");

        if (is_dir($plugin_dir)) {
            $this->check_item('Plugin Directory Readable', is_readable($plugin_dir), true);

            // Key directories
            $key_dirs = ['includes', 'admin', 'public'];
            foreach ($key_dirs as $dir) {
                $dir_path = $plugin_dir . '/' . $dir;
                $this->check_item("Directory: $dir", is_dir($dir_path), true, "Path: $dir_path");
            }
        }

        $this->log_section('');
    }

    /**
     * Verify VD License Manager components
     */
    private function verify_vd_components() {
        $this->log_section('5. VD LICENSE MANAGER COMPONENTS');

        // Main plugin file
        $main_file = WP_PLUGIN_DIR . '/vd-license-manager/vd-license-manager.php';
        $this->check_item('Main Plugin File', file_exists($main_file), true, "Path: $main_file");

        // Core classes
        $core_classes = [
            'class-vd-license-manager.php' => 'Main license manager class',
            'class-vd-encryption-manager.php' => 'Encryption manager class',
            'class-vd-database-manager.php' => 'Database manager class',
            'class-vd-security-manager.php' => 'Security manager class',
            'class-vd-capability-manager.php' => 'Capability manager class'
        ];

        foreach ($core_classes as $file => $description) {
            $file_path = WP_PLUGIN_DIR . '/vd-license-manager/includes/' . $file;
            $this->check_item($description, file_exists($file_path), true, "Path: $file_path");
        }

        // Check if classes are loaded
        $loaded_classes = [
            'VD_License_Manager' => 'Main license manager class loaded',
            'VD_Encryption_Manager' => 'Encryption manager class loaded',
            'VD_Security_Manager' => 'Security manager class loaded',
            'VD_Capability_Manager' => 'Capability manager class loaded'
        ];

        foreach ($loaded_classes as $class => $description) {
            $this->check_item($description, class_exists($class), false);
        }

        $this->log_section('');
    }

    /**
     * Verify VD_Security_Audit file and class
     */
    private function verify_security_audit_file() {
        $this->log_section('6. VD_SECURITY_AUDIT VERIFICATION');

        // Security audit file
        $security_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-security-audit.php';
        $file_exists = file_exists($security_file);
        $this->check_item('VD_Security_Audit File Exists', $file_exists, true, "Path: $security_file");

        if ($file_exists) {
            $this->check_item('VD_Security_Audit File Readable', is_readable($security_file), true);

            // File size check
            $file_size = filesize($security_file);
            $this->check_item('VD_Security_Audit File Size', $file_size > 1000, true, "Size: " . number_format($file_size) . " bytes");

            // Try to check if class definition exists in file
            $file_content = file_get_contents($security_file);
            $class_defined = strpos($file_content, 'class VD_Security_Audit') !== false;
            $this->check_item('VD_Security_Audit Class Definition', $class_defined, true);

            // Check if class is already loaded
            $this->check_item('VD_Security_Audit Class Loaded', class_exists('VD_Security_Audit'), false);
        }

        $this->log_section('');
    }

    /**
     * Verify WordPress hooks system
     */
    private function verify_hooks_system() {
        $this->log_section('7. WORDPRESS HOOKS SYSTEM');

        // Hook functions
        $hook_functions = ['add_action', 'add_filter', 'do_action', 'apply_filters', 'has_action', 'has_filter'];
        foreach ($hook_functions as $func) {
            $this->check_item("Hook Function: $func", function_exists($func), true);
        }

        // Test hook registration (safe test)
        $test_hook = 'vd_test_hook_verification_346';
        add_action($test_hook, '__return_true');
        $hook_registered = has_action($test_hook);
        $this->check_item('Hook Registration Test', $hook_registered, true);

        // Remove test hook
        remove_action($test_hook, '__return_true');

        $this->log_section('');
    }

    /**
     * Generate final summary
     */
    private function generate_summary() {
        $this->log_section('8. VERIFICATION SUMMARY');

        $total_checks = count($this->results);
        $passed_checks = count(array_filter($this->results, function($result) {
            return $result['status'] === 'PASS';
        }));
        $failed_checks = count(array_filter($this->results, function($result) {
            return $result['status'] === 'FAIL';
        }));
        $warning_checks = count(array_filter($this->results, function($result) {
            return $result['status'] === 'WARNING';
        }));

        $this->log_section("Total Checks: $total_checks");
        $this->log_section("Passed: $passed_checks");
        $this->log_section("Failed: $failed_checks");
        $this->log_section("Warnings: $warning_checks");
        $this->log_section("Success Rate: " . round(($passed_checks / $total_checks) * 100, 2) . "%");

        // Determine overall status
        $overall_status = 'READY';
        if ($failed_checks > 5) {
            $overall_status = 'NOT_READY';
        } elseif ($failed_checks > 0 || $warning_checks > 3) {
            $overall_status = 'CAUTION';
        }

        $this->log_section("Overall Status: $overall_status");

        // Recommendations
        $this->log_section('');
        $this->log_section('RECOMMENDATIONS:');

        if ($overall_status === 'READY') {
            $this->log_section('✅ Environment is ready for Step 3.4.6.2 - Safe Variable Declaration');
        } elseif ($overall_status === 'CAUTION') {
            $this->log_section('⚠️ Environment has some issues but may proceed with extra caution');
        } else {
            $this->log_section('❌ Environment not ready - resolve critical issues before proceeding');
        }

        if (!empty($this->errors)) {
            $this->log_section('');
            $this->log_section('CRITICAL ISSUES TO RESOLVE:');
            foreach ($this->errors as $error) {
                $this->log_section("❌ $error");
            }
        }

        if (!empty($this->warnings)) {
            $this->log_section('');
            $this->log_section('WARNINGS TO CONSIDER:');
            foreach ($this->warnings as $warning) {
                $this->log_section("⚠️ $warning");
            }
        }

        $this->log_section('');
        $this->log_section('=== END VERIFICATION REPORT ===');
    }

    /**
     * Check individual item
     */
    private function check_item($name, $condition, $critical = false, $details = '') {
        $status = $condition ? 'PASS' : ($critical ? 'FAIL' : 'WARNING');

        $result = [
            'name' => $name,
            'status' => $status,
            'critical' => $critical,
            'details' => $details
        ];

        $this->results[] = $result;

        $status_icon = [
            'PASS' => '✅',
            'FAIL' => '❌',
            'WARNING' => '⚠️'
        ][$status];

        $this->log_section("$status_icon [$status] $name" . ($details ? " - $details" : ''));

        if ($status === 'FAIL') {
            $this->errors[] = $name;
        } elseif ($status === 'WARNING') {
            $this->warnings[] = $name;
        }
    }

    /**
     * Log section
     */
    private function log_section($message) {
        echo $message . "\n";

        // Also log to WordPress if possible
        if (function_exists('error_log')) {
            error_log('[VD Environment Verification 3.4.6.1] ' . $message);
        }
    }

    /**
     * Get verification results
     */
    public function get_results() {
        return [
            'results' => $this->results,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
}

// Run verification if accessed directly or via WordPress admin
if (!class_exists('WP_CLI') || (isset($_GET['run_verification']) && $_GET['run_verification'] === '1')) {
    echo "<pre>";
    $verifier = new VD_Environment_Verifier();
    $results = $verifier->run_verification();
    echo "</pre>";

    // Display results summary in HTML if in browser
    if (!defined('WP_CLI') && isset($_SERVER['HTTP_HOST'])) {
        echo "<h3>Verification Complete</h3>";
        echo "<p>Check the output above for detailed results.</p>";
        echo "<p><strong>Next Step:</strong> If verification passed, proceed with Step 3.4.6.2 - Safe Variable Declaration</p>";
        echo "<p><em>This temporary test file can be safely deleted after verification.</em></p>";
    }
}