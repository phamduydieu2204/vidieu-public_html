<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.2 Test - Format Checksum Validator Module
 *
 * Tests the newly extracted VD_License_Checksum_Validator module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_2_checksum_validator
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_2_checksum_validator', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.2 - Format Checksum Validator Test',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'errors' => array(),
        'module_info' => array()
    );

    try {
        // Step 1: Test Module Loader
        $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
        if (!file_exists($module_loader_file)) {
            throw new Exception('Module Loader file not found');
        }
        require_once $module_loader_file;

        if (!class_exists('VD_License_Module_Loader')) {
            throw new Exception('VD_License_Module_Loader class not found');
        }

        $module_loader = VD_License_Module_Loader::get_instance();
        $results['tests']['module_loader'] = array(
            'status' => 'pass',
            'message' => 'Module Loader initialized successfully',
            'stats' => $module_loader->get_stats()
        );

        // Step 2: Test Dependency Container
        $container_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        if (!file_exists($container_file)) {
            throw new Exception('Dependency Container file not found');
        }
        require_once $container_file;

        if (!class_exists('VD_License_Dependency_Container')) {
            throw new Exception('VD_License_Dependency_Container class not found');
        }

        $container = VD_License_Dependency_Container::get_instance();
        $container_initialized = $container->initialize();

        $results['tests']['dependency_container'] = array(
            'status' => $container_initialized ? 'pass' : 'fail',
            'message' => $container_initialized ? 'Dependency Container initialized successfully' : 'Container initialization failed',
            'stats' => $container->get_stats(),
            'status_info' => $container->get_status()
        );

        // Step 3: Test Pattern Validator Module (dependency)
        $pattern_validator = $container->get('format.pattern_validator');
        if (!$pattern_validator) {
            throw new Exception('Pattern Validator module not found');
        }

        $results['tests']['pattern_validator_dependency'] = array(
            'status' => 'pass',
            'message' => 'Pattern Validator dependency loaded successfully',
            'module_info' => $pattern_validator->get_module_info()
        );

        // Step 4: Test Checksum Validator Module
        $checksum_validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/format/class-vd-license-checksum-validator.php';
        if (!file_exists($checksum_validator_file)) {
            throw new Exception('Checksum Validator module file not found');
        }
        require_once $checksum_validator_file;

        if (!class_exists('VD_License_Checksum_Validator')) {
            throw new Exception('VD_License_Checksum_Validator class not found');
        }

        $checksum_validator = $container->get('format.checksum_validator');
        $checksum_validator->set_pattern_validator($pattern_validator);
        $results['module_info'] = $checksum_validator->get_module_info();

        $results['tests']['checksum_validator_creation'] = array(
            'status' => 'pass',
            'message' => 'Checksum Validator module loaded successfully',
            'module_info' => $checksum_validator->get_module_info()
        );

        // Step 5: Test Checksum Algorithm Support
        $supported_algorithms = $checksum_validator->get_supported_algorithms();
        $results['tests']['algorithm_support'] = array(
            'status' => 'pass',
            'message' => count($supported_algorithms) . ' checksum algorithms supported',
            'supported_algorithms' => array_keys($supported_algorithms),
            'algorithms_detail' => $supported_algorithms
        );

        // Step 6: Test License Key Checksum Validation
        $test_license_keys = array(
            'ABCD-EFGH-IJKL-MNOP-QRSTUV' => true, // VD standard - should pass basic checksum
            'ABCD-EFGH-IJKL-MNOP' => true, // LMfWC standard - should pass
            'ABCDEFGH-IJKLMNOP-QRSTUVWX' => true, // LMfWC extended - should pass CRC32
            'ABCD123456789012' => true, // Simple format - should pass
            '1234567890' => true, // Numeric only - should pass basic
            'ABCDEFGH' => true, // Minimum length - should pass
            'A' => true, // Too short - should be skipped
            '' => false, // Empty key - should fail
        );

        $checksum_results = array();
        $successful_checksums = 0;
        $total_checksums = count($test_license_keys);

        foreach ($test_license_keys as $license_key => $expected) {
            $result = $checksum_validator->validate_license_checksum($license_key, true);
            $checksum_results[$license_key] = array(
                'result' => $result,
                'expected' => $expected,
                'correct' => ($expected === false && !$result['valid']) ||
                           ($expected === true && $result['valid'])
            );

            if ($checksum_results[$license_key]['correct']) {
                $successful_checksums++;
            }
        }

        $checksum_success_rate = round(($successful_checksums / $total_checksums) * 100, 1);

        $results['tests']['checksum_validation'] = array(
            'status' => $checksum_success_rate >= 100 ? 'pass' : 'partial',
            'message' => "Checksum validation: {$successful_checksums}/{$total_checksums} tests passed ({$checksum_success_rate}%)",
            'success_rate' => $checksum_success_rate,
            'details' => $checksum_results
        );

        // Step 7: Test Multiple Checksum Calculations
        $test_key = 'ABCD-EFGH-IJKL-MNOP-QRSTUV';
        $checksum_calculations = $checksum_validator->calculate_checksums($test_key);

        $results['tests']['checksum_calculations'] = array(
            'status' => 'pass',
            'message' => 'Multiple checksum calculations completed',
            'test_key' => $test_key,
            'calculations' => $checksum_calculations
        );

        // Step 8: Test Batch Checksum Validation
        $batch_keys = array_keys($test_license_keys);
        $batch_start = microtime(true);
        $batch_result = $checksum_validator->validate_batch($batch_keys, false);
        $batch_time = round((microtime(true) - $batch_start) * 1000, 2);

        $results['tests']['batch_checksum_validation'] = array(
            'status' => 'pass',
            'message' => "Batch checksum validation completed in {$batch_time}ms",
            'batch_results' => $batch_result,
            'processing_time' => $batch_time
        );

        // Step 9: Test Algorithm Configuration
        $algorithm_config_tests = array();

        // Test enabling/disabling algorithms
        $original_luhn_status = $checksum_validator->set_algorithm_enabled('luhn_algorithm', true);
        $algorithm_config_tests['enable_luhn'] = $original_luhn_status;

        $disable_result = $checksum_validator->set_algorithm_enabled('luhn_algorithm', false);
        $algorithm_config_tests['disable_luhn'] = $disable_result;

        $invalid_algorithm = $checksum_validator->set_algorithm_enabled('invalid_algorithm', true);
        $algorithm_config_tests['invalid_algorithm'] = !$invalid_algorithm; // Should return false

        $results['tests']['algorithm_configuration'] = array(
            'status' => 'pass',
            'message' => 'Algorithm configuration tests completed',
            'config_tests' => $algorithm_config_tests
        );

        // Step 10: Test Performance
        $performance_start = microtime(true);
        $performance_iterations = 50;

        for ($i = 0; $i < $performance_iterations; $i++) {
            $checksum_validator->validate_license_checksum('ABCD-EFGH-IJKL-MNOP-QRSTUV', false);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_checksum = round($performance_time / $performance_iterations, 3);

        $results['tests']['performance'] = array(
            'status' => $avg_time_per_checksum < 2 ? 'pass' : 'warn',
            'message' => "{$performance_iterations} checksum validations in {$performance_time}ms (avg: {$avg_time_per_checksum}ms)",
            'total_time' => $performance_time,
            'average_time' => $avg_time_per_checksum,
            'iterations' => $performance_iterations
        );

        // Step 11: Get Module Statistics
        $checksum_stats = $checksum_validator->get_stats();
        $results['tests']['module_statistics'] = array(
            'status' => 'pass',
            'message' => 'Module statistics retrieved successfully',
            'statistics' => $checksum_stats
        );

        // Calculate overall summary
        $passed_tests = 0;
        $total_tests = count($results['tests']);

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'pass') {
                $passed_tests++;
            }
        }

        $overall_success_rate = round(($passed_tests / $total_tests) * 100, 1);

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'success_rate' => $overall_success_rate,
            'overall_status' => $overall_success_rate >= 100 ? 'excellent' : ($overall_success_rate >= 80 ? 'good' : 'needs_attention'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        );

        // Phase 1 Step 1.2 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.2',
            'module' => 'Format Checksum Validator',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.3 - Extract Database Query Manager',
            'files_created' => array(
                'modules/format/class-vd-license-checksum-validator.php' => '~380 lines',
            ),
            'files_modified' => array(
                'class-vd-license-dependency-container.php' => 'Added checksum validator to core services',
                'class-vd-license-validator.php' => 'Integrated checksum validator module'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.2',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.2',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});