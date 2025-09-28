<?php
/**
 * VD License Manager - Pre-Loading Environment Check
 * Step 3.4.6.4a - Ultra-Safe Environment Verification for Large File Loading
 *
 * This temporary test script verifies the environment is ready for loading
 * large files (specifically VD_Security_Audit - 65KB) without modifying main plugin code.
 *
 * @package VD_License_Manager
 * @since 3.4.6.4a
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
 * VD Pre-Loading Environment Verifier Class
 */
class VD_PreLoading_Environment_Verifier {

    private $results = [];
    private $warnings = [];
    private $target_file_size = 65142; // VD_Security_Audit file size in bytes
    private $baseline_memory = 0;

    /**
     * Run comprehensive pre-loading environment verification
     */
    public function run_verification() {
        $this->log_section('=== VD LICENSE MANAGER - PRE-LOADING ENVIRONMENT CHECK ===');
        $this->log_section('Step 3.4.6.4a - Ultra-Safe Environment Verification for Large File Loading');
        $this->log_section('Target File: VD_Security_Audit (65KB)');
        $this->log_section('Timestamp: ' . current_time('mysql'));
        $this->log_section('');

        // Capture baseline memory usage
        $this->baseline_memory = memory_get_usage(true);

        // 1. PHP Memory Environment Check
        $this->verify_php_memory_environment();

        // 2. WordPress Memory Environment
        $this->verify_wordpress_memory_environment();

        // 3. File System Readiness
        $this->verify_file_system_readiness();

        // 4. Target File Specifications
        $this->verify_target_file_specifications();

        // 5. Loading Simulation Test
        $this->simulate_large_file_loading();

        // 6. Memory Recovery Test
        $this->test_memory_recovery();

        // 7. WordPress Lifecycle Compatibility
        $this->verify_wordpress_lifecycle_compatibility();

        // 8. Generate Pre-Loading Assessment
        $this->generate_preloading_assessment();

        return $this->results;
    }

    /**
     * Verify PHP memory environment
     */
    private function verify_php_memory_environment() {
        $this->log_section('1. PHP MEMORY ENVIRONMENT VERIFICATION');

        // Memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $this->check_item('PHP Memory Limit', $memory_limit_bytes >= 67108864, true, "Limit: $memory_limit (" . number_format($memory_limit_bytes) . " bytes)");

        // Current memory usage
        $current_memory = memory_get_usage(true);
        $this->check_item('Current Memory Usage', $current_memory > 0, true, 'Usage: ' . number_format($current_memory) . ' bytes');

        // Peak memory usage
        $peak_memory = memory_get_peak_usage(true);
        $this->check_item('Peak Memory Usage', $peak_memory > 0, true, 'Peak: ' . number_format($peak_memory) . ' bytes');

        // Available memory calculation
        $available_memory = $memory_limit_bytes - $current_memory;
        $required_memory = $this->target_file_size * 2; // Conservative estimate
        $this->check_item('Available Memory for Large File', $available_memory >= $required_memory, true,
            "Available: " . number_format($available_memory) . " bytes, Required: " . number_format($required_memory) . " bytes");

        // Memory functions availability
        $memory_functions = ['memory_get_usage', 'memory_get_peak_usage', 'memory_limit'];
        foreach ($memory_functions as $func) {
            $this->check_item("Memory Function: $func", function_exists($func) || ini_get($func) !== false, true);
        }

        $this->log_section('');
    }

    /**
     * Verify WordPress memory environment
     */
    private function verify_wordpress_memory_environment() {
        $this->log_section('2. WORDPRESS MEMORY ENVIRONMENT');

        // WordPress memory limit (if set)
        if (defined('WP_MEMORY_LIMIT')) {
            $wp_memory_limit = WP_MEMORY_LIMIT;
            $wp_memory_bytes = $this->convert_to_bytes($wp_memory_limit);
            $this->check_item('WordPress Memory Limit', $wp_memory_bytes >= 67108864, false, "WP Limit: $wp_memory_limit");
        } else {
            $this->check_item('WordPress Memory Limit', false, false, 'WP_MEMORY_LIMIT not defined');
        }

        // WordPress debug status
        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->check_item('WordPress Debug Mode', true, false, $wp_debug ? 'Enabled' : 'Disabled');

        // WordPress environment type (if available)
        if (function_exists('wp_get_environment_type')) {
            $env_type = wp_get_environment_type();
            $this->check_item('WordPress Environment Type', !empty($env_type), false, "Environment: $env_type");
        }

        $this->log_section('');
    }

    /**
     * Verify file system readiness
     */
    private function verify_file_system_readiness() {
        $this->log_section('3. FILE SYSTEM READINESS');

        // Target file path
        $target_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-security-audit.php';
        $this->check_item('Target File Exists', file_exists($target_file), true, "Path: $target_file");

        if (file_exists($target_file)) {
            // File permissions
            $this->check_item('Target File Readable', is_readable($target_file), true);

            // File size verification
            $actual_size = filesize($target_file);
            $size_match = abs($actual_size - $this->target_file_size) < 1000; // Allow 1KB variance
            $this->check_item('Target File Size Verification', $size_match, true,
                "Expected: " . number_format($this->target_file_size) . " bytes, Actual: " . number_format($actual_size) . " bytes");

            // Directory permissions
            $target_dir = dirname($target_file);
            $this->check_item('Target Directory Readable', is_readable($target_dir), true, "Directory: $target_dir");
        }

        $this->log_section('');
    }

    /**
     * Verify target file specifications
     */
    private function verify_target_file_specifications() {
        $this->log_section('4. TARGET FILE SPECIFICATIONS');

        $target_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-security-audit.php';

        if (file_exists($target_file)) {
            // File content analysis (safe read of header only)
            $file_handle = fopen($target_file, 'r');
            if ($file_handle) {
                $header_content = fread($file_handle, 1024); // Read first 1KB only
                fclose($file_handle);

                // Verify PHP opening tag
                $has_php_tag = strpos($header_content, '<?php') === 0;
                $this->check_item('Valid PHP File Header', $has_php_tag, true);

                // Verify class definition exists
                $has_class_def = strpos($header_content, 'class VD_Security_Audit') !== false;
                $this->check_item('VD_Security_Audit Class Definition', $has_class_def, true);

                // Verify ABSPATH protection
                $has_abspath_check = strpos($header_content, 'ABSPATH') !== false;
                $this->check_item('ABSPATH Security Check', $has_abspath_check, true);
            }

            // Line count estimation (safe)
            $line_count = 0;
            $file_handle = fopen($target_file, 'r');
            if ($file_handle) {
                while (!feof($file_handle)) {
                    fgets($file_handle);
                    $line_count++;
                    if ($line_count > 2000) break; // Safety limit
                }
                fclose($file_handle);
                $this->check_item('File Complexity (Line Count)', $line_count > 1000, false, "Lines: ~$line_count");
            }
        }

        $this->log_section('');
    }

    /**
     * Simulate large file loading
     */
    private function simulate_large_file_loading() {
        $this->log_section('5. LARGE FILE LOADING SIMULATION');

        // Memory before simulation
        $memory_before = memory_get_usage(true);
        $this->check_item('Pre-Simulation Memory Usage', $memory_before > 0, true, 'Memory: ' . number_format($memory_before) . ' bytes');

        // Simulate reading large content (without executing)
        $target_file = WP_PLUGIN_DIR . '/vd-license-manager/includes/class-vd-security-audit.php';

        if (file_exists($target_file)) {
            // Safe content reading simulation
            $content_read = false;
            $file_handle = fopen($target_file, 'r');
            if ($file_handle) {
                $chunk_size = 8192; // 8KB chunks
                $total_read = 0;
                while (!feof($file_handle) && $total_read < $this->target_file_size) {
                    $chunk = fread($file_handle, $chunk_size);
                    $total_read += strlen($chunk);
                    // Memory check every 16KB
                    if ($total_read % 16384 === 0) {
                        $current_mem = memory_get_usage(true);
                        if ($current_mem > $memory_before + ($this->target_file_size * 3)) {
                            $this->warnings[] = 'High memory usage during simulation';
                            break;
                        }
                    }
                }
                fclose($file_handle);
                $content_read = $total_read > 0;
            }

            $this->check_item('File Content Reading Simulation', $content_read, true, "Read: " . number_format($total_read) . " bytes");

            // Memory after simulation
            $memory_after = memory_get_usage(true);
            $memory_increase = $memory_after - $memory_before;
            $this->check_item('Memory Increase During Simulation', $memory_increase < ($this->target_file_size * 2), true,
                'Increase: ' . number_format($memory_increase) . ' bytes');
        }

        $this->log_section('');
    }

    /**
     * Test memory recovery
     */
    private function test_memory_recovery() {
        $this->log_section('6. MEMORY RECOVERY TEST');

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $cycles_collected = gc_collect_cycles();
            $this->check_item('Garbage Collection Available', function_exists('gc_collect_cycles'), true,
                "Cycles collected: $cycles_collected");
        }

        // Memory after cleanup
        $memory_after_gc = memory_get_usage(true);
        $memory_difference = $memory_after_gc - $this->baseline_memory;
        $this->check_item('Memory Recovery Efficiency', $memory_difference < 1048576, true,
            'Difference from baseline: ' . number_format($memory_difference) . ' bytes');

        $this->log_section('');
    }

    /**
     * Verify WordPress lifecycle compatibility
     */
    private function verify_wordpress_lifecycle_compatibility() {
        $this->log_section('7. WORDPRESS LIFECYCLE COMPATIBILITY');

        // Check current hook context
        $current_filter = current_filter();
        $this->check_item('WordPress Hook Context', !empty($current_filter) || !did_action('wp_loaded'), false,
            'Current context: ' . ($current_filter ?: 'early-loading'));

        // WordPress loading stage
        $wp_loaded = did_action('wp_loaded');
        $this->check_item('WordPress Fully Loaded', $wp_loaded, false, $wp_loaded ? 'Loaded' : 'Loading');

        // Plugin loading context
        $plugins_loaded = did_action('plugins_loaded');
        $this->check_item('Plugins Loading Stage', $plugins_loaded, false, $plugins_loaded ? 'Completed' : 'In Progress');

        // Admin context if applicable
        if (function_exists('is_admin')) {
            $is_admin = is_admin();
            $this->check_item('Admin Context Detection', true, false, $is_admin ? 'Admin' : 'Frontend');
        }

        $this->log_section('');
    }

    /**
     * Generate pre-loading assessment
     */
    private function generate_preloading_assessment() {
        $this->log_section('8. PRE-LOADING ASSESSMENT');

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

        // Determine loading readiness
        $loading_readiness = 'READY';
        if ($failed_checks > 3) {
            $loading_readiness = 'NOT_READY';
        } elseif ($failed_checks > 0 || $warning_checks > 2) {
            $loading_readiness = 'CAUTION';
        }

        $this->log_section("Large File Loading Readiness: $loading_readiness");

        // Recommendations
        $this->log_section('');
        $this->log_section('RECOMMENDATIONS:');

        if ($loading_readiness === 'READY') {
            $this->log_section('✅ Environment is ready for Step 3.4.6.4b - Conditional Loading Declaration');
            $this->log_section('✅ Memory environment suitable for 65KB file loading');
            $this->log_section('✅ File system access verified and functional');
        } elseif ($loading_readiness === 'CAUTION') {
            $this->log_section('⚠️ Environment has some concerns - proceed with extra monitoring');
            $this->log_section('⚠️ Consider memory optimization before large file loading');
        } else {
            $this->log_section('❌ Environment not ready - resolve critical issues before proceeding');
            $this->log_section('❌ Memory or file system issues detected');
        }

        if (!empty($this->warnings)) {
            $this->log_section('');
            $this->log_section('WARNINGS TO MONITOR:');
            foreach ($this->warnings as $warning) {
                $this->log_section("⚠️ $warning");
            }
        }

        $this->log_section('');
        $this->log_section('=== END PRE-LOADING ASSESSMENT ===');
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

        if ($status === 'WARNING') {
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
            error_log('[VD Pre-Loading Check 3.4.6.4a] ' . $message);
        }
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convert_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last_char = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $numeric_value = (int) $memory_limit;

        switch ($last_char) {
            case 'g':
                $numeric_value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $numeric_value *= 1024 * 1024;
                break;
            case 'k':
                $numeric_value *= 1024;
                break;
        }

        return $numeric_value;
    }

    /**
     * Get verification results
     */
    public function get_results() {
        return [
            'results' => $this->results,
            'warnings' => $this->warnings
        ];
    }
}

// Run verification if accessed directly or via WordPress admin
if (!class_exists('WP_CLI') || (isset($_GET['run_preloading_check']) && $_GET['run_preloading_check'] === '1')) {
    echo "<pre>";
    $verifier = new VD_PreLoading_Environment_Verifier();
    $results = $verifier->run_verification();
    echo "</pre>";

    // Display results summary in HTML if in browser
    if (!defined('WP_CLI') && isset($_SERVER['HTTP_HOST'])) {
        echo "<h3>Pre-Loading Environment Check Complete</h3>";
        echo "<p>Check the output above for detailed results.</p>";
        echo "<p><strong>Next Step:</strong> If verification shows READY, proceed with Step 3.4.6.4b - Conditional Loading Declaration</p>";
        echo "<p><em>This temporary test file can be safely deleted after verification.</em></p>";
    }
}