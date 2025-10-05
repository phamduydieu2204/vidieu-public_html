<?php
/**
 * Simple Test Step 4.3.1: Validation Infrastructure
 *
 * Simplified test for Step 4.3.1 Validation Infrastructure module
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Simple Test Step 4.3.1: Validation Infrastructure</h1>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";

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

// Load core dependencies in correct order with error checking
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

// Special handling for validator with error capture
$validator_file = $plugin_dir . 'includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    // Capture any errors during validator loading
    ob_start();
    $error_handler = set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        require_once $validator_file;
        $output = ob_get_clean();
        if ($output) {
            echo "⚠️ Validator loading output: " . htmlspecialchars($output) . "<br>";
        }
        echo "✅ Loaded: includes/class-vd-license-validator.php<br>";
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "❌ Validator loading error: " . $e->getMessage() . "<br>";
        if ($output) {
            echo "📋 Output: " . htmlspecialchars($output) . "<br>";
        }
    } finally {
        set_error_handler($error_handler);
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

echo "<h2>Step 3: Check Class Availability</h2>";

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

echo "<h2>Step 4: Test Step 4.3.1 Infrastructure Module</h2>";

try {
    // Test infrastructure module
    if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
        $infrastructure = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();
        echo "✅ Infrastructure module instantiated successfully<br>";

        // Test basic methods
        $test_license_data = array(
            'id' => 12345,
            'license_key' => 'TEST-INFRASTRUCTURE-001',
            'status' => 'active',
            'user_id' => 1
        );

        echo "<h3>Testing Infrastructure Methods:</h3>";

        // Test extract_license_key (correct method name)
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

        // Test transform_context_to_options (correct method name)
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

        // Test map_orchestrator_result_to_legacy_format (correct method name)
        if (method_exists($infrastructure, 'map_orchestrator_result_to_legacy_format')) {
            $test_result = array(
                'valid' => true,
                'checks_passed' => 5,
                'checks_failed' => 0
            );

            try {
                $mapped = $infrastructure->map_orchestrator_result_to_legacy_format($test_result);
                echo "✅ map_orchestrator_result_to_legacy_format: Mapped successfully<br>";
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

        // Test additional Infrastructure methods
        echo "<h4>Additional Infrastructure Methods:</h4>";

        // Test get_validation_statistics
        if (method_exists($infrastructure, 'get_validation_statistics')) {
            try {
                $stats = $infrastructure->get_validation_statistics();
                echo "✅ get_validation_statistics: Retrieved statistics<br>";
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

        // Test get_debug_info
        if (method_exists($infrastructure, 'get_debug_info')) {
            try {
                $debug = $infrastructure->get_debug_info();
                echo "✅ get_debug_info: Retrieved debug information<br>";
            } catch (Exception $e) {
                echo "⚠️ get_debug_info error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ get_debug_info method not found<br>";
        }

    } else {
        echo "❌ Infrastructure module class not available<br>";
    }

} catch (Exception $e) {
    echo "❌ Exception during testing: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 5: Integration Test with Validator</h2>";

try {
    if (class_exists('VD_License_Validator')) {
        echo "✅ VD_License_Validator class available<br>";

        // Try to get instance
        if (method_exists('VD_License_Validator', 'get_instance')) {
            $validator = VD_License_Validator::get_instance();
            echo "✅ Validator instance created<br>";

            // Check if validator can access infrastructure
            if (method_exists($validator, 'get_infrastructure_manager')) {
                echo "✅ Validator has infrastructure integration<br>";
            } else {
                echo "⚠️ Validator infrastructure integration not found<br>";
            }
        } else {
            echo "❌ get_instance method not found in VD_License_Validator<br>";
        }
    } else {
        echo "❌ VD_License_Validator class not available<br>";

        // Debug: List all available classes
        echo "<h3>Available Classes:</h3>";
        $all_classes = get_declared_classes();
        $vd_classes = array_filter($all_classes, function($class) {
            return strpos($class, 'VD_') === 0 || strpos($class, 'VD\\') !== false;
        });

        foreach ($vd_classes as $class) {
            echo "- $class<br>";
        }
    }

} catch (Exception $e) {
    echo "❌ Integration test error: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 6: Module Info & Summary</h2>";

if (class_exists('VD\\LicenseManager\\Infrastructure\\VD_License_Validation_Infrastructure')) {
    $infrastructure = VD\LicenseManager\Infrastructure\VD_License_Validation_Infrastructure::get_instance();

    if (method_exists($infrastructure, 'get_module_info')) {
        $info = $infrastructure->get_module_info();
        echo "<h3>Module Information:</h3>";
        echo "<pre>" . print_r($info, true) . "</pre>";
    }
}

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>✅ Test Summary</h3>";
echo "<p><strong>Step 4.3.1 Status:</strong> Infrastructure module loaded and tested</p>";
echo "<p><strong>Main Functions:</strong></p>";
echo "<ul>";
echo "<li>License key extraction from payload</li>";
echo "<li>Context parameter transformation</li>";
echo "<li>Orchestrator result mapping</li>";
echo "<li>Check counting utilities</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>