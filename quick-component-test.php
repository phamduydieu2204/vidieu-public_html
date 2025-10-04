<?php
/**
 * Quick Component Test to verify fixes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

echo "<h1>Quick Component Test</h1>\n";

try {
    // Load the module loader
    $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;

        $loader = VD_License_Module_Loader::get_instance();
        $utility_helper = $loader->load_module('utility.helper');

        if ($utility_helper) {
            echo "<h2>Component Direct Access Test Results</h2>\n";
            echo "<ul>\n";

            $test_results = array();

            // Test DataSanitizer
            try {
                $data_sanitizer = $utility_helper->get_data_sanitizer();
                if ($data_sanitizer) {
                    $test_data = '  ACTIVE  ';
                    if (is_string($data_sanitizer)) {
                        $result = $data_sanitizer::sanitize_status_value($test_data);
                    } else {
                        $result = call_user_func(array($data_sanitizer, 'sanitize_status_value'), $test_data);
                    }
                    $test_results['data_sanitizer'] = ($result === 'active');
                }
            } catch (Exception $e) {
                $test_results['data_sanitizer'] = false;
            }

            // Test ResponseBuilder
            try {
                $response_builder = $utility_helper->get_response_builder();
                if ($response_builder) {
                    if (is_string($response_builder)) {
                        $response = $response_builder::create_success_response(array('method' => 'test'), 'Test message');
                    } else {
                        $response = call_user_func(array($response_builder, 'create_success_response'),
                            array('method' => 'test'), 'Test message');
                    }
                    $test_results['response_builder'] = (isset($response['success']) && $response['success'] === true);
                }
            } catch (Exception $e) {
                $test_results['response_builder'] = false;
            }

            // Test DateTimeHelper
            try {
                $datetime_helper = $utility_helper->get_datetime_helper();
                if ($datetime_helper) {
                    if (is_string($datetime_helper)) {
                        $result = $datetime_helper::is_valid_date('2024-12-31 23:59:59');
                    } else {
                        $result = call_user_func(array($datetime_helper, 'is_valid_date'), '2024-12-31 23:59:59');
                    }
                    $test_results['datetime_helper'] = ($result === true);
                }
            } catch (Exception $e) {
                $test_results['datetime_helper'] = false;
            }

            // Test CalculationHelper
            try {
                $calculation_helper = $utility_helper->get_calculation_helper();
                if ($calculation_helper) {
                    if (is_string($calculation_helper)) {
                        $result = $calculation_helper::calculate_percentage(25, 100, 1);
                    } else {
                        $result = call_user_func(array($calculation_helper, 'calculate_percentage'), 25, 100, 1);
                    }
                    $test_results['calculation_helper'] = ($result === 25.0);
                }
            } catch (Exception $e) {
                $test_results['calculation_helper'] = false;
            }

            // Display results
            $passed_count = 0;
            foreach ($test_results as $component => $passed) {
                echo "<li>" . ucfirst(str_replace('_', ' ', $component)) . " Direct Access: " . ($passed ? '✅ PASS' : '❌ FAIL') . "</li>\n";
                if ($passed) $passed_count++;
            }

            echo "<li>Direct Access Success Rate: {$passed_count}/" . count($test_results) . " (" . round(($passed_count / count($test_results)) * 100) . "%)</li>\n";
            echo "<li>Architecture Migration: " . ($passed_count === count($test_results) ? '✅ COMPLETE' : '❌ INCOMPLETE') . "</li>\n";
            echo "</ul>\n";

        } else {
            echo "<p>❌ Failed to load utility helper module</p>\n";
        }
    } else {
        echo "<p>❌ Module Loader file not found</p>\n";
    }

} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>