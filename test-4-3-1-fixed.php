<?php
/**
 * Test Step 4.3.1: Validation Infrastructure - Fixed Version
 *
 * Fixed test with correct method names and error handling
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Test Step 4.3.1: Validation Infrastructure (Fixed)</h1>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Version: Fixed with correct method names</p>";

echo "<h2>Step 1: Load WordPress Environment</h2>";

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/wp-config.php');
    require_once(ABSPATH . 'wp-load.php');
}

echo "✅ WordPress loaded<br>";

echo "<h2>Step 2: Load Plugin Files</h2>";

$plugin_dir = dirname(__FILE__) . '/wp-content/plugins/vd-license-manager/';

// Check plugin directory
if (!is_dir($plugin_dir)) {
    echo "❌ Plugin directory not found: $plugin_dir<br>";
    exit;
}
echo "✅ Plugin directory found<br>";

// Load main plugin file
$plugin_file = $plugin_dir . 'vd-license-manager.php';
if (file_exists($plugin_file)) {
    require_once $plugin_file;
    echo "✅ Main plugin file loaded<br>";
} else {
    echo "❌ Main plugin file not found<br>";
}

// Load core dependencies in correct order
$core_files = array(
    'includes/class-vd-license-manager.php',
    'includes/class-vd-license-module-loader.php'
);

foreach ($core_files as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        require_once $full_path;
        echo "✅ Loaded: $file<br>";
    } else {
        echo "❌ Not found: $file<br>";
    }
}

// Special handling for validator with detailed error checking
$validator_file = $plugin_dir . 'includes/class-vd-license-validator.php';
echo "<h3>Loading VD_License_Validator:</h3>";

if (file_exists($validator_file)) {
    echo "✅ Validator file exists<br>";

    // Check file content first
    $file_content = file_get_contents($validator_file);
    $has_class_definition = strpos($file_content, 'class VD_License_Validator') !== false;
    echo ($has_class_definition ? "✅" : "❌") . " File contains class definition<br>";

    // Check for PHP syntax errors
    $syntax_check = exec("php -l '$validator_file' 2>&1", $output, $return_code);
    if ($return_code === 0) {
        echo "✅ PHP syntax check passed<br>";
    } else {
        echo "❌ PHP syntax error: " . implode(' ', $output) . "<br>";
    }

    // Try to include the file
    try {
        require_once $validator_file;
        echo "✅ File included successfully<br>";

        if (class_exists('VD_License_Validator')) {
            echo "✅ VD_License_Validator class now available<br>";
        } else {
            echo "❌ Class still not available after include<br>";
        }
    } catch (Exception $e) {
        echo "❌ Exception during include: " . $e->getMessage() . "<br>";
    } catch (ParseError $e) {
        echo "❌ Parse error: " . $e->getMessage() . "<br>";
    } catch (Error $e) {
        echo "❌ Fatal error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Validator file not found<br>";
}

// Load Step 4.3.1 infrastructure module
$infrastructure_file = $plugin_dir . 'includes/modules/infrastructure/class-vd-license-validation-infrastructure.php';
if (file_exists($infrastructure_file)) {
    require_once $infrastructure_file;
    echo "✅ Step 4.3.1 Infrastructure module loaded<br>";
} else {
    echo "❌ Step 4.3.1 Infrastructure module not found<br>";
}

echo "<h2>Step 3: Class Availability Check</h2>";

$required_classes = array(
    'VD_License_Manager' => 'Core Manager',
    'VD_License_Module_Loader' => 'Module Loader',
    'VD_License_Validator' => 'Main Validator',
    'VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure' => 'Step 4.3.1 Infrastructure'
);

foreach ($required_classes as $class => $description) {
    if (class_exists($class)) {
        echo "✅ $description ($class) - Available<br>";
    } else {
        echo "❌ $description ($class) - Missing<br>";
    }
}

echo "<h2>Step 4: Test Infrastructure Module (Correct Method Names)</h2>";

try {
    if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
        $infrastructure = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
        echo "✅ Infrastructure module instantiated successfully<br>";

        echo "<h3>Testing Infrastructure Methods (Fixed Names):</h3>";

        // Test extract_license_key (CORRECT method name)
        if (method_exists($infrastructure, 'extract_license_key')) {
            $test_license = array(
                'license_key' => 'TEST-KEY-001',
                'domain' => 'test.vidieu.vn'
            );

            try {
                $extracted_key = $infrastructure->extract_license_key($test_license);
                echo "✅ extract_license_key: " . $extracted_key . "<br>";
            } catch (Exception $e) {
                echo "⚠️ extract_license_key error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ extract_license_key method not found<br>";
        }

        // Test transform_context_to_options (CORRECT method name)
        if (method_exists($infrastructure, 'transform_context_to_options')) {
            $test_context = array(
                'user_id' => 123,
                'site_url' => 'https://test.vidieu.vn'
            );
            $test_license = array(
                'license_key' => 'TEST-KEY-001'
            );

            try {
                $transformed = $infrastructure->transform_context_to_options($test_context, $test_license);
                echo "✅ transform_context_to_options: Transformed " . count($transformed) . " options<br>";
            } catch (Exception $e) {
                echo "⚠️ transform_context_to_options error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ transform_context_to_options method not found<br>";
        }

        // Test map_orchestrator_result_to_legacy_format (CORRECT method name)
        if (method_exists($infrastructure, 'map_orchestrator_result_to_legacy_format')) {
            $test_result = array(
                'valid' => true,
                'checks_passed' => 5,
                'checks_failed' => 0
            );

            try {
                $mapped = $infrastructure->map_orchestrator_result_to_legacy_format($test_result);
                echo "✅ map_orchestrator_result_to_legacy_format: Mapped successfully<br>";
                echo "   Result keys: " . implode(', ', array_keys($mapped)) . "<br>";
            } catch (Exception $e) {
                echo "⚠️ map_orchestrator_result_to_legacy_format error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ map_orchestrator_result_to_legacy_format method not found<br>";
        }

        // Test count_orchestrator_checks
        if (method_exists($infrastructure, 'count_orchestrator_checks')) {
            $test_result = array(
                'checks_passed' => 8,
                'checks_failed' => 2,
                'checks_skipped' => 1
            );

            try {
                $counts = $infrastructure->count_orchestrator_checks($test_result);
                echo "✅ count_orchestrator_checks: " . json_encode($counts) . "<br>";
            } catch (Exception $e) {
                echo "⚠️ count_orchestrator_checks error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ count_orchestrator_checks method not found<br>";
        }

        echo "<h4>Additional Infrastructure Methods:</h4>";

        // Test get_validation_statistics
        if (method_exists($infrastructure, 'get_validation_statistics')) {
            try {
                $stats = $infrastructure->get_validation_statistics();
                echo "✅ get_validation_statistics: Retrieved " . count($stats) . " statistics<br>";
            } catch (Exception $e) {
                echo "⚠️ get_validation_statistics error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ get_validation_statistics method not found<br>";
        }

        // Test get_status
        if (method_exists($infrastructure, 'get_status')) {
            try {
                $status = $infrastructure->get_status();
                echo "✅ get_status: " . $status . "<br>";
            } catch (Exception $e) {
                echo "⚠️ get_status error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ get_status method not found<br>";
        }

        // Test health_check
        if (method_exists($infrastructure, 'health_check')) {
            try {
                $health = $infrastructure->health_check();
                echo "✅ health_check: " . ($health ? 'Healthy' : 'Issues detected') . "<br>";
            } catch (Exception $e) {
                echo "⚠️ health_check error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ health_check method not found<br>";
        }

        // List all available methods for debugging
        echo "<h4>All Available Methods:</h4>";
        $methods = get_class_methods($infrastructure);
        foreach ($methods as $method) {
            if (strpos($method, '__') !== 0) { // Skip magic methods
                echo "- $method<br>";
            }
        }

    } else {
        echo "❌ Infrastructure module class not available<br>";
    }

} catch (Exception $e) {
    echo "❌ Exception during testing: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 5: Final Summary</h2>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>✅ Test Summary - Fixed Version</h3>";
echo "<p><strong>Step 4.3.1 Status:</strong> Infrastructure module tested with correct method names</p>";
echo "<p><strong>Key Changes:</strong></p>";
echo "<ul>";
echo "<li>Used correct method name: extract_license_key</li>";
echo "<li>Used correct method name: transform_context_to_options</li>";
echo "<li>Used correct method name: map_orchestrator_result_to_legacy_format</li>";
echo "<li>Added comprehensive error handling for VD_License_Validator</li>";
echo "<li>Listed all available methods for verification</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>