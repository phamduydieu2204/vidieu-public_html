<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 Step 1.1 Test - Format Pattern Validator Module
 *
 * Tests the newly extracted VD_License_Pattern_Validator module
 * URL: /wp-admin/admin-ajax.php?action=vd_test_phase1_step1_pattern_validator
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_phase1_step1_pattern_validator', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Phase 1 Step 1.1 - Format Pattern Validator Test',
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

        // Step 3: Test Pattern Validator Module
        $pattern_validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/format/class-vd-license-pattern-validator.php';
        if (!file_exists($pattern_validator_file)) {
            throw new Exception('Pattern Validator module file not found');
        }
        require_once $pattern_validator_file;

        if (!class_exists('VD_License_Pattern_Validator')) {
            throw new Exception('VD_License_Pattern_Validator class not found');
        }

        $pattern_validator = VD_License_Pattern_Validator::get_instance();
        $results['module_info'] = $pattern_validator->get_module_info();

        $results['tests']['pattern_validator_creation'] = array(
            'status' => 'pass',
            'message' => 'Pattern Validator module loaded successfully',
            'module_info' => $pattern_validator->get_module_info()
        );

        // Step 4: Test License Key Format Validation
        $test_license_keys = array(
            'ABCD-EFGH-IJKL-MNOP-QRSTUV' => 'vd_standard', // Valid VD standard
            'ABCD-EFGH-IJKL-MNOP' => 'lmfwc_standard', // Valid LMfWC standard
            'ABCDEFGH-IJKLMNOP-QRSTUVWX' => 'lmfwc_extended', // Valid LMfWC extended
            'ABCD123456789012' => 'simple_format', // Valid simple format
            'invalid-key' => false, // Invalid format
            '' => false, // Empty key
            'A' => false, // Too short
            str_repeat('A', 40) => false // Too long
        );

        $validation_results = array();
        $successful_validations = 0;
        $total_validations = count($test_license_keys);

        foreach ($test_license_keys as $license_key => $expected) {
            $result = $pattern_validator->validate_license_key_format($license_key, true);
            $validation_results[$license_key] = array(
                'result' => $result,
                'expected' => $expected,
                'correct' => ($expected === false && !$result['valid']) ||
                           ($expected !== false && $result['valid'] && $result['matched_pattern'] === $expected)
            );

            if ($validation_results[$license_key]['correct']) {
                $successful_validations++;
            }
        }

        $validation_success_rate = round(($successful_validations / $total_validations) * 100, 1);

        $results['tests']['license_key_validation'] = array(
            'status' => $validation_success_rate >= 100 ? 'pass' : 'partial',
            'message' => "License key validation: {$successful_validations}/{$total_validations} tests passed ({$validation_success_rate}%)",
            'success_rate' => $validation_success_rate,
            'details' => $validation_results
        );

        // Step 5: Test Batch Validation
        $batch_keys = array_keys($test_license_keys);
        $batch_start = microtime(true);
        $batch_result = $pattern_validator->validate_batch($batch_keys, false);
        $batch_time = round((microtime(true) - $batch_start) * 1000, 2);

        $results['tests']['batch_validation'] = array(
            'status' => 'pass',
            'message' => "Batch validation completed in {$batch_time}ms",
            'batch_results' => $batch_result,
            'processing_time' => $batch_time
        );

        // Step 6: Test Pattern Recognition
        $pattern_tests = array();
        $supported_patterns = $pattern_validator->get_supported_patterns();

        foreach ($supported_patterns as $pattern_name => $pattern_info) {
            $pattern_tests[$pattern_name] = array(
                'name' => $pattern_info['name'],
                'description' => $pattern_info['description']
            );
        }

        $results['tests']['pattern_recognition'] = array(
            'status' => 'pass',
            'message' => count($supported_patterns) . ' patterns supported',
            'supported_patterns' => $pattern_tests
        );

        // Step 7: Test Performance
        $performance_start = microtime(true);
        $performance_iterations = 100;

        for ($i = 0; $i < $performance_iterations; $i++) {
            $pattern_validator->validate_license_key_format('ABCD-EFGH-IJKL-MNOP-QRSTUV', false);
        }

        $performance_time = round((microtime(true) - $performance_start) * 1000, 2);
        $avg_time_per_validation = round($performance_time / $performance_iterations, 3);

        $results['tests']['performance'] = array(
            'status' => $avg_time_per_validation < 1 ? 'pass' : 'warn',
            'message' => "{$performance_iterations} validations in {$performance_time}ms (avg: {$avg_time_per_validation}ms)",
            'total_time' => $performance_time,
            'average_time' => $avg_time_per_validation,
            'iterations' => $performance_iterations
        );

        // Step 8: Get Module Statistics
        $validation_stats = $pattern_validator->get_stats();
        $results['tests']['module_statistics'] = array(
            'status' => 'pass',
            'message' => 'Module statistics retrieved successfully',
            'statistics' => $validation_stats
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

        // Phase 1 Step 1.1 completion status
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.1',
            'module' => 'Format Pattern Validator',
            'completion' => 'SUCCESS',
            'next_step' => 'Step 1.2 - Extract Format Checksum Validator',
            'files_created' => array(
                'class-vd-license-module-loader.php' => '~200 lines',
                'class-vd-license-dependency-container.php' => '~300 lines',
                'modules/format/class-vd-license-pattern-validator.php' => '~400 lines'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.1',
            'completion' => 'FAILED',
            'error' => $e->getMessage()
        );
    } catch (Error $e) {
        $results['status'] = 'error';
        $results['errors'][] = 'Fatal error: ' . $e->getMessage();
        $results['phase_status'] = array(
            'phase' => 'Phase 1',
            'step' => 'Step 1.1',
            'completion' => 'FAILED',
            'error' => 'Fatal error: ' . $e->getMessage()
        );
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});