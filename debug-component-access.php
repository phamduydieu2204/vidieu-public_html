<?php
/**
 * Debug Component Direct Access Issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

echo "<h1>Debug Component Direct Access</h1>\n";

try {
    // Load the module loader
    $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;

        $loader = VD_License_Module_Loader::get_instance();
        $utility_helper = $loader->load_module('utility.helper');

        if ($utility_helper) {
            echo "<h2>Testing Component Direct Access</h2>\n";

            // Test DataSanitizer
            echo "<h3>DataSanitizer Test</h3>\n";
            try {
                $data_sanitizer = $utility_helper->get_data_sanitizer();
                echo "Data Sanitizer loaded: " . ($data_sanitizer ? 'YES' : 'NO') . "\n";
                if ($data_sanitizer) {
                    echo "Class: " . $data_sanitizer . "\n";
                    $test_data = '  ACTIVE  ';
                    $result = call_user_func(array($data_sanitizer, 'sanitize_status_value'), $test_data);
                    echo "Test result: " . var_export($result, true) . "\n";
                    echo "Expected: 'active'\n";
                    echo "Test passed: " . ($result === 'active' ? 'YES' : 'NO') . "\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }

            // Test ResponseBuilder
            echo "<h3>ResponseBuilder Test</h3>\n";
            try {
                $response_builder = $utility_helper->get_response_builder();
                echo "Response Builder loaded: " . ($response_builder ? 'YES' : 'NO') . "\n";
                if ($response_builder) {
                    echo "Class: " . $response_builder . "\n";
                    $response = call_user_func(array($response_builder, 'create_success_response'),
                        array('method' => 'test'), 'Test message');
                    echo "Test result: " . var_export($response, true) . "\n";
                    $test_passed = (isset($response['success']) && $response['success'] === true);
                    echo "Test passed: " . ($test_passed ? 'YES' : 'NO') . "\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }

            // Test DateTimeHelper
            echo "<h3>DateTimeHelper Test</h3>\n";
            try {
                $datetime_helper = $utility_helper->get_datetime_helper();
                echo "DateTime Helper loaded: " . ($datetime_helper ? 'YES' : 'NO') . "\n";
                if ($datetime_helper) {
                    echo "Class: " . $datetime_helper . "\n";

                    // Test with the exact date from the test
                    $test_date = '2024-12-31 23:59:59';
                    echo "Testing date: " . $test_date . "\n";
                    $result = call_user_func(array($datetime_helper, 'is_valid_date'), $test_date);
                    echo "Test result: " . var_export($result, true) . "\n";
                    echo "Expected: true\n";
                    echo "Test passed: " . ($result === true ? 'YES' : 'NO') . "\n";

                    // Test with simple date format
                    $test_date2 = '2024-12-31';
                    echo "Testing simple date: " . $test_date2 . "\n";
                    $result2 = call_user_func(array($datetime_helper, 'is_valid_date'), $test_date2);
                    echo "Test result: " . var_export($result2, true) . "\n";
                    echo "Test passed: " . ($result2 === true ? 'YES' : 'NO') . "\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }

            // Test CalculationHelper
            echo "<h3>CalculationHelper Test</h3>\n";
            try {
                $calculation_helper = $utility_helper->get_calculation_helper();
                echo "Calculation Helper loaded: " . ($calculation_helper ? 'YES' : 'NO') . "\n";
                if ($calculation_helper) {
                    echo "Class: " . $calculation_helper . "\n";
                    $result = call_user_func(array($calculation_helper, 'calculate_percentage'), 25, 100, 1);
                    echo "Test result: " . var_export($result, true) . "\n";
                    echo "Expected: 25.0\n";
                    echo "Test passed: " . ($result === 25.0 ? 'YES' : 'NO') . "\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }

        } else {
            echo "Failed to load utility helper\n";
        }
    } else {
        echo "Module loader not found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "<hr>\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
?>