<?php
/**
 * Validation Orchestrator Test
 *
 * Comprehensive test for the extracted Validation Orchestrator module
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize orchestrator test endpoint hooks
 */
add_action('wp_ajax_vd_test_validation_orchestrator', 'vd_test_validation_orchestrator');
add_action('wp_ajax_nopriv_vd_test_validation_orchestrator', 'vd_test_validation_orchestrator');

/**
 * Comprehensive test for validation orchestrator
 */
function vd_test_validation_orchestrator() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $start_time = microtime(true);
    $test_results = array();
    $current_test = 'initialization';

    try {
        // Test 1: Load the module
        $current_test = 'module_loading';
        require_once plugin_dir_path(__FILE__) . 'class-vd-license-validation-orchestrator.php';

        $orchestrator = VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();

        $test_results['module_loading'] = array(
            'test' => 'Module Loading',
            'success' => true,
            'details' => array('class_loaded' => get_class($orchestrator))
        );

        // Test 2: Singleton pattern
        $current_test = 'singleton_pattern';
        $instance2 = VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();
        $is_singleton = $orchestrator === $instance2;

        $test_results['singleton_pattern'] = array(
            'test' => 'Singleton Pattern',
            'success' => $is_singleton,
            'details' => array('instances_identical' => $is_singleton)
        );

        // Test 3: Module initialization
        $current_test = 'module_initialization';
        $init_result = $orchestrator->initialize_validation_modules();

        $test_results['module_initialization'] = array(
            'test' => 'Module Initialization',
            'success' => is_array($init_result) && isset($init_result['modules_loaded']),
            'details' => $init_result
        );

        // Test 4: Pipeline configuration
        $current_test = 'pipeline_configuration';
        $pipeline_config = $orchestrator->get_validation_pipeline_configuration();

        $test_results['pipeline_configuration'] = array(
            'test' => 'Pipeline Configuration',
            'success' => is_array($pipeline_config) && count($pipeline_config) >= 5,
            'details' => array(
                'stages_count' => count($pipeline_config),
                'stages' => array_keys($pipeline_config)
            )
        );

        // Test 5: Single license orchestration (using sample data)
        $current_test = 'single_license_orchestration';
        $test_license = 'VD-TEST-2024-' . substr(md5(time()), 0, 8);
        $validation_result = $orchestrator->orchestrate_license_validation($test_license, array(
            'skip_database' => true, // Skip DB operations for test
            'enable_metrics' => true
        ));

        $test_results['single_license_orchestration'] = array(
            'test' => 'Single License Orchestration',
            'success' => is_array($validation_result) && isset($validation_result['is_valid']),
            'details' => array(
                'has_result' => isset($validation_result['is_valid']),
                'has_stages' => isset($validation_result['validation_stages']),
                'has_metrics' => isset($validation_result['performance_metrics'])
            )
        );

        // Test 6: Advanced reporting
        $current_test = 'advanced_reporting';
        $sample_pipeline = array(
            'format_validation' => array('valid' => true, 'execution_time' => 0.5),
            'checksum_validation' => array('valid' => false, 'error' => 'Invalid checksum', 'execution_time' => 1.2)
        );
        $sample_errors = array('Checksum mismatch detected');
        $sample_warnings = array('License approaching expiry');

        $report = $orchestrator->generate_advanced_validation_report(
            $test_license,
            $sample_pipeline,
            $sample_errors,
            $sample_warnings
        );

        $test_results['advanced_reporting'] = array(
            'test' => 'Advanced Reporting',
            'success' => is_array($report) && isset($report['license_key']) && isset($report['summary']),
            'details' => array(
                'has_license_key' => isset($report['license_key']),
                'has_summary' => isset($report['summary']),
                'has_recommendations' => isset($report['recommendations']),
                'has_performance' => isset($report['performance_analysis'])
            )
        );

        // Test 7: Batch validation
        $current_test = 'batch_validation';
        $test_licenses = array(
            'VD-BATCH-TEST-001',
            'VD-BATCH-TEST-002',
            'VD-BATCH-TEST-003'
        );

        $batch_result = $orchestrator->orchestrate_batch_validation($test_licenses, array(
            'skip_database' => true,
            'enable_metrics' => true,
            'batch_size' => 2
        ));

        $test_results['batch_validation'] = array(
            'test' => 'Batch Validation',
            'success' => is_array($batch_result) && isset($batch_result['batch_summary']),
            'details' => array(
                'has_summary' => isset($batch_result['batch_summary']),
                'has_results' => isset($batch_result['validation_results']),
                'processed_count' => isset($batch_result['batch_summary']) ? $batch_result['batch_summary']['total_processed'] : 0
            )
        );

        // Test 8: Dependency container
        $current_test = 'dependency_container';
        $container_status = $orchestrator->get_dependency_container_status();

        $test_results['dependency_container'] = array(
            'test' => 'Dependency Container',
            'success' => is_array($container_status),
            'details' => $container_status
        );

        // Test 9: Performance metrics
        $current_test = 'performance_metrics';
        $metrics = $orchestrator->get_performance_metrics();

        $test_results['performance_metrics'] = array(
            'test' => 'Performance Metrics',
            'success' => is_array($metrics),
            'details' => array(
                'has_metrics' => is_array($metrics),
                'metrics_count' => count($metrics)
            )
        );

        // Test 10: Configuration management
        $current_test = 'configuration_management';
        $config = $orchestrator->get_orchestrator_configuration();

        $test_results['configuration_management'] = array(
            'test' => 'Configuration Management',
            'success' => is_array($config) && isset($config['pipeline_stages']),
            'details' => array(
                'has_pipeline_config' => isset($config['pipeline_stages']),
                'has_dependency_config' => isset($config['dependency_injection']),
                'stage_count' => isset($config['pipeline_stages']) ? count($config['pipeline_stages']) : 0
            )
        );

        // Calculate performance
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);

        // Generate summary
        $total_tests = count($test_results);
        $passed_tests = 0;
        foreach ($test_results as $result) {
            if ($result['success']) {
                $passed_tests++;
            }
        }

        wp_send_json_success(array(
            'summary' => array(
                'step' => '5.1.5',
                'module' => 'Validation Orchestrator',
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $total_tests - $passed_tests,
                'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
                'status' => $passed_tests === $total_tests ? 'SUCCESS' : 'PARTIAL',
                'execution_time' => $execution_time
            ),
            'test_results' => $test_results,
            'implementation_notes' => array(
                'extracted_from' => 'class-vd-license-validator.php',
                'namespace' => 'VD\\LicenseManager\\Validator',
                'pattern' => 'Singleton',
                'functionality' => array(
                    'Pipeline Orchestration',
                    'Dependency Management',
                    'Advanced Reporting',
                    'Batch Processing',
                    'Performance Metrics',
                    'Configuration Management'
                )
            ),
            'timestamp' => current_time('mysql')
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Test execution failed',
            'current_test' => $current_test,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'completed_tests' => $test_results
        ));
    } catch (Error $e) {
        wp_send_json_error(array(
            'message' => 'Fatal error during test',
            'current_test' => $current_test,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'completed_tests' => $test_results
        ));
    }
}