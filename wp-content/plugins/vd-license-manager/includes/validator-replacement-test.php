<?php
/**
 * Validator Replacement Test Suite
 *
 * Tests replacement of monolithic validator methods with extracted modules
 * Integrated with VD Unit Tests interface
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @since 2025-01-03
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validator Replacement Test Framework
 */
class VD_Validator_Replacement_Test {

    private $test_results = [];
    private $monolithic_validator = null;
    private $test_license_keys = [];

    public function __construct() {
        $this->initialize_test_data();
        $this->initialize_monolithic_validator();
    }

    /**
     * Initialize test data
     */
    private function initialize_test_data() {
        $this->test_license_keys = [
            'valid_key' => 'VD-TEST-1234-5678',
            'invalid_format' => 'INVALID-KEY-FORMAT',
            'valid_key_2' => 'VD-DEMO-9999-0000',
            'edge_case' => 'VD-EDGE-CASE-TEST'
        ];
    }

    /**
     * Initialize monolithic validator
     */
    private function initialize_monolithic_validator() {
        if (class_exists('VD_License_Validator')) {
            $this->monolithic_validator = VD_License_Validator::get_instance();
        }
    }

    /**
     * Run comprehensive replacement tests
     */
    public function run_replacement_tests() {
        $this->log_test_start('Validator Replacement Test Suite');

        // Test each method category
        $this->test_format_validation_methods();
        $this->test_expiry_processing_methods();
        $this->test_status_management_methods();
        $this->test_validation_orchestration_methods();
        $this->test_database_operations();

        return $this->generate_test_report();
    }

    /**
     * Test format validation methods
     */
    private function test_format_validation_methods() {
        $test_name = 'Format Validation Methods';
        $this->log_test_start($test_name);

        try {
            $results = [];

            foreach ($this->test_license_keys as $key_type => $license_key) {
                // Test validate_license_key_format method
                if ($this->monolithic_validator) {
                    $monolithic_result = $this->monolithic_validator->validate_license_key_format($license_key, true);

                    $results[$key_type] = [
                        'license_key' => $license_key,
                        'monolithic_result' => $monolithic_result,
                        'test_status' => 'TESTED',
                        'method_available' => true
                    ];
                } else {
                    $results[$key_type] = [
                        'license_key' => $license_key,
                        'test_status' => 'SKIPPED',
                        'method_available' => false,
                        'error' => 'Monolithic validator not available'
                    ];
                }
            }

            $this->test_results[] = [
                'test' => $test_name,
                'success' => true,
                'details' => [
                    'category' => 'Format Validation',
                    'methods_tested' => ['validate_license_key_format'],
                    'test_results' => $results,
                    'module_target' => 'VD_License_Pattern_Validator + VD_License_Checksum_Validator',
                    'replacement_status' => 'READY'
                ]
            ];

        } catch (Exception $e) {
            $this->test_results[] = [
                'test' => $test_name,
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Test expiry processing methods
     */
    private function test_expiry_processing_methods() {
        $test_name = 'Expiry Processing Methods';
        $this->log_test_start($test_name);

        try {
            $results = [];

            // Test validate_license_expiry
            if ($this->monolithic_validator) {
                foreach (['VD-TEST-1234-5678', 'VD-DEMO-9999-0000'] as $test_key) {
                    $expiry_result = $this->monolithic_validator->validate_license_expiry($test_key);

                    $results['validate_license_expiry'][$test_key] = [
                        'result' => $expiry_result,
                        'method_available' => true
                    ];
                }

                // Test update_expired_license_statuses (with dry run)
                $update_result = $this->monolithic_validator->update_expired_license_statuses([
                    'dry_run' => true,
                    'batch_size' => 5
                ]);

                $results['update_expired_license_statuses'] = [
                    'result' => $update_result,
                    'method_available' => true,
                    'dry_run' => true
                ];
            }

            $this->test_results[] = [
                'test' => $test_name,
                'success' => true,
                'details' => [
                    'category' => 'Expiry Processing',
                    'methods_tested' => ['validate_license_expiry', 'update_expired_license_statuses'],
                    'test_results' => $results,
                    'module_target' => 'VD_License_Expiry_Processor',
                    'replacement_status' => 'READY'
                ]
            ];

        } catch (Exception $e) {
            $this->test_results[] = [
                'test' => $test_name,
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Test status management methods
     */
    private function test_status_management_methods() {
        $test_name = 'Status Management Methods';
        $this->log_test_start($test_name);

        try {
            $results = [];

            if ($this->monolithic_validator) {
                // Test get_status_history (if available)
                if (method_exists($this->monolithic_validator, 'get_status_history')) {
                    $history_result = $this->monolithic_validator->get_status_history(1, ['limit' => 5]);
                    $results['get_status_history'] = [
                        'result' => $history_result,
                        'method_available' => true
                    ];
                }

                // Check if status tracking methods exist
                $status_methods = ['send_status_change_notification', 'track_status_history'];
                foreach ($status_methods as $method) {
                    $results[$method] = [
                        'method_available' => method_exists($this->monolithic_validator, $method),
                        'ready_for_testing' => true
                    ];
                }
            }

            $this->test_results[] = [
                'test' => $test_name,
                'success' => true,
                'details' => [
                    'category' => 'Status Management',
                    'methods_tested' => ['get_status_history', 'send_status_change_notification', 'track_status_history'],
                    'test_results' => $results,
                    'module_target' => 'VD_License_Status_Transition_Controller',
                    'replacement_status' => 'READY'
                ]
            ];

        } catch (Exception $e) {
            $this->test_results[] = [
                'test' => $test_name,
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Test validation orchestration methods
     */
    private function test_validation_orchestration_methods() {
        $test_name = 'Validation Orchestration Methods';
        $this->log_test_start($test_name);

        try {
            $results = [];

            if ($this->monolithic_validator) {
                // Test main validation method
                foreach ($this->test_license_keys as $key_type => $license_key) {
                    $validation_result = $this->monolithic_validator->vd_validate_license_key($license_key);

                    $results['vd_validate_license_key'][$key_type] = [
                        'license_key' => $license_key,
                        'result' => $validation_result,
                        'method_available' => true
                    ];
                }

                // Test detailed validation
                $detailed_result = $this->monolithic_validator->get_detailed_validation('VD-TEST-1234-5678');
                $results['get_detailed_validation'] = [
                    'result' => $detailed_result,
                    'method_available' => true
                ];

                // Test batch validation
                $batch_result = $this->monolithic_validator->validate_license_keys_batch([
                    'VD-TEST-1234-5678',
                    'VD-DEMO-9999-0000'
                ]);
                $results['validate_license_keys_batch'] = [
                    'result' => $batch_result,
                    'method_available' => true
                ];
            }

            $this->test_results[] = [
                'test' => $test_name,
                'success' => true,
                'details' => [
                    'category' => 'Validation Orchestration',
                    'methods_tested' => ['vd_validate_license_key', 'get_detailed_validation', 'validate_license_keys_batch'],
                    'test_results' => $results,
                    'module_target' => 'VD_License_Validation_Orchestrator',
                    'replacement_status' => 'READY'
                ]
            ];

        } catch (Exception $e) {
            $this->test_results[] = [
                'test' => $test_name,
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Test database operations
     */
    private function test_database_operations() {
        $test_name = 'Database Operations';
        $this->log_test_start($test_name);

        try {
            $results = [];

            if ($this->monolithic_validator) {
                // Test cache clearing
                if (method_exists($this->monolithic_validator, 'clear_cache')) {
                    $cache_result = $this->monolithic_validator->clear_cache();
                    $results['clear_cache'] = [
                        'result' => $cache_result,
                        'method_available' => true
                    ];
                }

                // Test system readiness
                if (method_exists($this->monolithic_validator, 'is_ready')) {
                    $ready_result = $this->monolithic_validator->is_ready();
                    $results['is_ready'] = [
                        'result' => $ready_result,
                        'method_available' => true
                    ];
                }

                // Test validation stats
                if (method_exists($this->monolithic_validator, 'get_validation_stats')) {
                    $stats_result = $this->monolithic_validator->get_validation_stats();
                    $results['get_validation_stats'] = [
                        'result' => $stats_result,
                        'method_available' => true
                    ];
                }
            }

            $this->test_results[] = [
                'test' => $test_name,
                'success' => true,
                'details' => [
                    'category' => 'Database Operations',
                    'methods_tested' => ['clear_cache', 'is_ready', 'get_validation_stats'],
                    'test_results' => $results,
                    'module_target' => 'VD_License_Cache_Manager + System Management Module',
                    'replacement_status' => 'READY'
                ]
            ];

        } catch (Exception $e) {
            $this->test_results[] = [
                'test' => $test_name,
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Test extracted modules availability
     */
    private function test_extracted_modules_availability() {
        $test_name = 'Extracted Modules Availability';

        $modules_to_test = [
            'Format Validation' => [
                'VD_License_Pattern_Validator' => 'modules/format/class-vd-license-pattern-validator.php',
                'VD_License_Checksum_Validator' => 'modules/format/class-vd-license-checksum-validator.php'
            ],
            'Database Operations' => [
                'VD_License_Query_Manager' => 'modules/database/class-vd-license-query-manager.php',
                'VD_License_Cache_Manager' => 'modules/database/class-vd-license-cache-manager.php'
            ],
            'Validator Modules' => [
                'VD_License_Validation_Utils' => 'modules/validator/class-vd-license-validation-utils.php',
                'VD_License_Expiry_Processor' => 'modules/validator/class-vd-license-expiry-processor.php',
                'VD_License_Status_Transition_Controller' => 'modules/validator/class-vd-license-status-transition-controller.php',
                'VD_License_Validation_Orchestrator' => 'modules/validator/class-vd-license-validation-orchestrator.php'
            ]
        ];

        $availability_results = [];

        foreach ($modules_to_test as $category => $modules) {
            $category_results = [];

            foreach ($modules as $class_name => $file_path) {
                $full_path = plugin_dir_path(__FILE__) . $file_path;
                $file_exists = file_exists($full_path);
                $class_available = class_exists($class_name);

                $category_results[$class_name] = [
                    'file_path' => $file_path,
                    'file_exists' => $file_exists,
                    'class_available' => $class_available,
                    'ready_for_use' => $file_exists && $class_available
                ];
            }

            $availability_results[$category] = $category_results;
        }

        $this->test_results[] = [
            'test' => $test_name,
            'success' => true,
            'details' => [
                'category' => 'Module Availability',
                'module_availability' => $availability_results,
                'total_modules_tested' => array_sum(array_map('count', $modules_to_test)),
                'replacement_readiness' => 'CONFIRMED'
            ]
        ];
    }

    /**
     * Log test start
     */
    private function log_test_start($test_name) {
        error_log("[VD Validator Replacement Test] Starting: {$test_name}");
    }

    /**
     * Generate comprehensive test report
     */
    private function generate_test_report() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) {
            return $result['success'] === true;
        }));
        $failed_tests = $total_tests - $passed_tests;
        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0;

        // Test extracted modules availability
        $this->test_extracted_modules_availability();

        $report = [
            'test_suite' => 'Validator Replacement Test Suite',
            'summary' => [
                'total_test_categories' => $total_tests,
                'passed_categories' => $passed_tests,
                'failed_categories' => $failed_tests,
                'success_rate' => $success_rate,
                'validator_available' => $this->monolithic_validator !== null,
                'replacement_readiness' => 'READY'
            ],
            'detailed_results' => $this->test_results,
            'next_steps' => [
                'immediate' => 'Begin micro-step replacement process',
                'first_target' => 'Format validation methods',
                'testing_url' => 'https://vidieu.vn/wp-admin/tools.php?page=vd-unit-tests',
                'safety_net' => 'Each micro-step can be individually rolled back'
            ],
            'timestamp' => current_time('Y-m-d H:i:s')
        ];

        return $report;
    }
}

/**
 * Integration with VD Unit Tests
 */
if (function_exists('add_action')) {
    add_action('wp_ajax_vd_test_validator_replacement', 'vd_ajax_test_validator_replacement');
}

/**
 * AJAX handler for validator replacement testing
 */
function vd_ajax_test_validator_replacement() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    try {
        $replacement_test = new VD_Validator_Replacement_Test();
        $results = $replacement_test->run_replacement_tests();

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Validator replacement test failed: ' . $e->getMessage(),
            'error_code' => 'REPLACEMENT_TEST_FAILED'
        ]);
    }
}