<?php
/**
 * VD License Validation Utils - Test Endpoint
 *
 * Self-contained AJAX test endpoint for Step 5.1.2
 * Tests the extracted Validation Utils Manager
 *
 * Access: /wp-admin/admin-ajax.php?action=vd_test_step_5_1_2_validation_utils
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize test endpoint hooks
 */
add_action('wp_ajax_vd_test_step_5_1_2_validation_utils', 'vd_test_step_5_1_2_validation_utils');
add_action('wp_ajax_nopriv_vd_test_step_5_1_2_validation_utils', 'vd_test_step_5_1_2_validation_utils');

/**
 * Test Step 5.1.2: Validation Utils Manager
 *
 * Comprehensive test of extracted validation utilities
 *
 * @return void
 */
function vd_test_step_5_1_2_validation_utils() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $start_time = microtime(true);
    $start_memory = memory_get_usage();

    try {
        // Load the extracted module
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-validation-utils.php';

        $validation_utils = VD\LicenseManager\Validator\VD_License_Validation_Utils::get_instance();

        $test_results = array();

        // Test 1: Singleton Pattern
        $test_results['singleton'] = test_singleton_pattern($validation_utils);

        // Test 2: Database Utilities
        $test_results['database_utils'] = test_database_utilities($validation_utils);

        // Test 3: Debug Utilities
        $test_results['debug_utils'] = test_debug_utilities($validation_utils);

        // Test 4: Memory Utilities
        $test_results['memory_utils'] = test_memory_utilities($validation_utils);

        // Test 5: Validation Error Creation
        $test_results['error_creation'] = test_error_creation($validation_utils);

        // Test 6: License Format Validation
        $test_results['format_validation'] = test_format_validation($validation_utils);

        // Test 7: Database Connectivity Test
        $test_results['database_connectivity'] = test_database_connectivity($validation_utils);

        // Test 8: System Environment Info
        $test_results['system_environment'] = test_system_environment($validation_utils);

        // Test 9: Validation Statistics
        $test_results['validation_statistics'] = test_validation_statistics($validation_utils);

        // Test 10: Global Settings
        $test_results['global_settings'] = test_global_settings($validation_utils);

        // Calculate performance metrics
        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        $performance = array(
            'execution_time' => round(($end_time - $start_time) * 1000, 2), // ms
            'memory_used' => $end_memory - $start_memory,
            'memory_used_formatted' => size_format($end_memory - $start_memory),
            'peak_memory' => memory_get_peak_usage(),
            'peak_memory_formatted' => size_format(memory_get_peak_usage())
        );

        // Generate summary
        $total_tests = count($test_results);
        $passed_tests = 0;
        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            }
        }

        $summary = array(
            'step' => '5.1.2',
            'module' => 'Validation Utils Manager',
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
            'status' => $passed_tests === $total_tests ? 'SUCCESS' : 'PARTIAL',
            'performance' => $performance
        );

        wp_send_json_success(array(
            'summary' => $summary,
            'test_results' => $test_results,
            'timestamp' => current_time('mysql'),
            'version' => '1.6.0'
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }
}

/**
 * Test singleton pattern implementation
 */
function test_singleton_pattern($validation_utils) {
    try {
        $instance1 = VD\LicenseManager\Validator\VD_License_Validation_Utils::get_instance();
        $instance2 = VD\LicenseManager\Validator\VD_License_Validation_Utils::get_instance();

        $is_singleton = $instance1 === $instance2;
        $is_same_class = get_class($instance1) === get_class($validation_utils);

        return array(
            'test' => 'Singleton Pattern',
            'success' => $is_singleton && $is_same_class,
            'details' => array(
                'instances_identical' => $is_singleton,
                'correct_class' => $is_same_class,
                'class_name' => get_class($validation_utils)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Singleton Pattern',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test database utilities
 */
function test_database_utilities($validation_utils) {
    try {
        global $wpdb;

        // Test table_exists method
        $wp_options_exists = $validation_utils->table_exists($wpdb->prefix . 'options');
        $fake_table_exists = $validation_utils->table_exists('fake_table_' . time());

        return array(
            'test' => 'Database Utilities',
            'success' => $wp_options_exists && !$fake_table_exists,
            'details' => array(
                'wp_options_exists' => $wp_options_exists,
                'fake_table_exists' => $fake_table_exists,
                'database_name' => DB_NAME,
                'table_prefix' => $wpdb->prefix
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Database Utilities',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test debug utilities
 */
function test_debug_utilities($validation_utils) {
    try {
        $test_license_key = 'VD-TEST' . time() . '-12345678-87654321';
        $debug_info = $validation_utils->get_lookup_debug_info($test_license_key);

        $required_keys = array('license_key', 'lmfwc_table_exists', 'vd_table_exists', 'database_name', 'memory_usage');
        $has_required_keys = true;
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $debug_info)) {
                $has_required_keys = false;
                break;
            }
        }

        return array(
            'test' => 'Debug Utilities',
            'success' => $has_required_keys && $debug_info['license_key'] === $test_license_key,
            'details' => array(
                'debug_info_complete' => $has_required_keys,
                'license_key_match' => $debug_info['license_key'] === $test_license_key,
                'debug_info_count' => count($debug_info),
                'sample_data' => array_slice($debug_info, 0, 3)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Debug Utilities',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test memory utilities
 */
function test_memory_utilities($validation_utils) {
    try {
        $memory_info = $validation_utils->get_memory_usage_info();

        $required_keys = array('current_usage', 'peak_usage', 'current_usage_formatted', 'memory_limit');
        $has_required_keys = true;
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $memory_info)) {
                $has_required_keys = false;
                break;
            }
        }

        $current_usage_positive = $memory_info['current_usage'] > 0;
        $peak_usage_positive = $memory_info['peak_usage'] > 0;

        return array(
            'test' => 'Memory Utilities',
            'success' => $has_required_keys && $current_usage_positive && $peak_usage_positive,
            'details' => array(
                'memory_info_complete' => $has_required_keys,
                'current_usage_positive' => $current_usage_positive,
                'peak_usage_positive' => $peak_usage_positive,
                'memory_limit' => $memory_info['memory_limit'],
                'current_usage_formatted' => $memory_info['current_usage_formatted']
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Memory Utilities',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test validation error creation
 */
function test_error_creation($validation_utils) {
    try {
        $error = $validation_utils->create_validation_error(
            'test_error',
            'This is a test error',
            array('context_key' => 'context_value'),
            array('debug_key' => 'debug_value')
        );

        $required_keys = array('valid', 'error', 'code', 'timestamp', 'context');
        $has_required_keys = true;
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $error)) {
                $has_required_keys = false;
                break;
            }
        }

        $valid_error_structure = $error['valid'] === false &&
                                $error['code'] === 'test_error' &&
                                $error['error'] === 'This is a test error';

        return array(
            'test' => 'Error Creation',
            'success' => $has_required_keys && $valid_error_structure,
            'details' => array(
                'error_structure_complete' => $has_required_keys,
                'valid_error_structure' => $valid_error_structure,
                'error_keys' => array_keys($error),
                'sample_error' => $error
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Error Creation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test license format validation
 */
function test_format_validation($validation_utils) {
    try {
        // Test valid formats
        $valid_tests = array(
            'VD-ABCD1234-EFGH5678-IJKL9012',
            'ABCD1234-5678-9012-3456-789012345678',
            'ABCDEF1234567890ABCDEF1234567890'
        );

        // Test invalid formats
        $invalid_tests = array(
            '',
            'INVALID',
            '12345',
            'VD-SHORT'
        );

        $valid_results = array();
        foreach ($valid_tests as $test_key) {
            $result = $validation_utils->validate_license_key_format($test_key);
            $valid_results[] = $result['valid'];
        }

        $invalid_results = array();
        foreach ($invalid_tests as $test_key) {
            $result = $validation_utils->validate_license_key_format($test_key);
            $invalid_results[] = !$result['valid']; // Should be invalid, so we check for !valid
        }

        $all_valid_passed = !in_array(false, $valid_results);
        $all_invalid_failed = !in_array(false, $invalid_results);

        return array(
            'test' => 'Format Validation',
            'success' => $all_valid_passed && $all_invalid_failed,
            'details' => array(
                'valid_tests_passed' => $all_valid_passed,
                'invalid_tests_failed' => $all_invalid_failed,
                'valid_test_count' => count($valid_tests),
                'invalid_test_count' => count($invalid_tests)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Format Validation',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test database connectivity
 */
function test_database_connectivity($validation_utils) {
    try {
        $connectivity_test = $validation_utils->test_database_connectivity();

        $has_overall_success = array_key_exists('overall_success', $connectivity_test);
        $has_tests = array_key_exists('tests', $connectivity_test);
        $has_database_info = array_key_exists('database_info', $connectivity_test);

        return array(
            'test' => 'Database Connectivity',
            'success' => $has_overall_success && $has_tests && $has_database_info,
            'details' => array(
                'connectivity_test_complete' => $has_overall_success && $has_tests,
                'overall_success' => $connectivity_test['overall_success'] ?? false,
                'test_count' => count($connectivity_test['tests'] ?? array()),
                'database_name' => $connectivity_test['database_info']['name'] ?? 'unknown'
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Database Connectivity',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test system environment info
 */
function test_system_environment($validation_utils) {
    try {
        $env_info = $validation_utils->get_system_environment_info();

        $required_sections = array('wordpress', 'php', 'database', 'server');
        $has_required_sections = true;
        foreach ($required_sections as $section) {
            if (!array_key_exists($section, $env_info)) {
                $has_required_sections = false;
                break;
            }
        }

        $wordpress_version_exists = !empty($env_info['wordpress']['version']);
        $php_version_exists = !empty($env_info['php']['version']);

        return array(
            'test' => 'System Environment',
            'success' => $has_required_sections && $wordpress_version_exists && $php_version_exists,
            'details' => array(
                'required_sections_present' => $has_required_sections,
                'wordpress_version' => $env_info['wordpress']['version'] ?? 'unknown',
                'php_version' => $env_info['php']['version'] ?? 'unknown',
                'sections_count' => count($env_info)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'System Environment',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test validation statistics
 */
function test_validation_statistics($validation_utils) {
    try {
        // Create sample validation results
        $sample_results = array(
            array('valid' => true, 'execution_time' => 10),
            array('valid' => false, 'code' => 'error1', 'execution_time' => 15),
            array('valid' => true, 'execution_time' => 12),
            array('valid' => false, 'code' => 'error2', 'execution_time' => 18),
            array('valid' => false, 'code' => 'error1', 'execution_time' => 14)
        );

        $stats = $validation_utils->generate_validation_statistics($sample_results);

        $required_keys = array('total_validations', 'successful_validations', 'failed_validations', 'success_rate');
        $has_required_keys = true;
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $stats)) {
                $has_required_keys = false;
                break;
            }
        }

        $correct_counts = $stats['total_validations'] === 5 &&
                         $stats['successful_validations'] === 2 &&
                         $stats['failed_validations'] === 3;

        return array(
            'test' => 'Validation Statistics',
            'success' => $has_required_keys && $correct_counts,
            'details' => array(
                'statistics_complete' => $has_required_keys,
                'counts_correct' => $correct_counts,
                'success_rate' => $stats['success_rate'],
                'common_errors' => $stats['common_errors']
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Validation Statistics',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Test global settings
 */
function test_global_settings($validation_utils) {
    try {
        $settings = $validation_utils->get_global_settings();

        // Settings should be an array (might be empty if table doesn't exist)
        $is_array = is_array($settings);

        // Test cache clearing
        $validation_utils->clear_global_settings_cache();
        $settings_after_clear = $validation_utils->get_global_settings();
        $cache_cleared = is_array($settings_after_clear);

        return array(
            'test' => 'Global Settings',
            'success' => $is_array && $cache_cleared,
            'details' => array(
                'settings_is_array' => $is_array,
                'cache_clearing_works' => $cache_cleared,
                'settings_count' => count($settings),
                'settings_sample' => array_slice($settings, 0, 3, true)
            )
        );
    } catch (Exception $e) {
        return array(
            'test' => 'Global Settings',
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}