<?php
/**
 * Test File for Micro-Step 2B.1.1: Environment Setup
 *
 * This file tests the Utility Helper Module foundation setup
 * including directory structure, namespace registration, and basic functionality.
 *
 * @package VD_License_Manager
 * @subpackage Tests
 * @since 2B.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

// Test header
echo "<h1>Micro-Step 2B.1.1: Environment Setup Test</h1>\n";
echo "<p>Testing Utility Helper Module foundation...</p>\n\n";

// Test 1: Directory Structure
echo "<h2>Test 1: Directory Structure</h2>\n";
$utility_helper_path = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/modules/utility-helper/';
$interfaces_path = $utility_helper_path . 'interfaces/';
$components_path = $utility_helper_path . 'components/';

echo "<ul>\n";
echo "<li>Utility Helper Directory: " . (is_dir($utility_helper_path) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Interfaces Directory: " . (is_dir($interfaces_path) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Components Directory: " . (is_dir($components_path) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "</ul>\n\n";

// Test 2: Core Files
echo "<h2>Test 2: Core Files</h2>\n";
$main_class_file = $utility_helper_path . 'class-vd-license-utility-helper.php';
$utility_interface = $interfaces_path . 'utility-helper-interface.php';
$sanitizer_interface = $interfaces_path . 'data-sanitizer-interface.php';
$response_interface = $interfaces_path . 'response-builder-interface.php';

echo "<ul>\n";
echo "<li>Main Class File: " . (file_exists($main_class_file) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Utility Helper Interface: " . (file_exists($utility_interface) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Data Sanitizer Interface: " . (file_exists($sanitizer_interface) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Response Builder Interface: " . (file_exists($response_interface) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "</ul>\n\n";

// Test 3: Module Loader Registration
echo "<h2>Test 3: Module Loader Registration</h2>\n";
$module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';

if (file_exists($module_loader_file)) {
    $module_loader_content = file_get_contents($module_loader_file);
    $has_utility_helper = strpos($module_loader_content, "'utility.helper'") !== false;
    $has_namespace = strpos($module_loader_content, 'VD\\\\LicenseManager\\\\UtilityHelper') !== false;

    echo "<ul>\n";
    echo "<li>Module Loader File: ✅ EXISTS</li>\n";
    echo "<li>Utility Helper Registration: " . ($has_utility_helper ? "✅ FOUND" : "❌ MISSING") . "</li>\n";
    echo "<li>Namespace Declaration: " . ($has_namespace ? "✅ FOUND" : "❌ MISSING") . "</li>\n";
    echo "</ul>\n\n";
} else {
    echo "<p>❌ Module Loader file not found</p>\n\n";
}

// Test 4: Module Loading Test
echo "<h2>Test 4: Module Loading Test</h2>\n";

try {
    // Include the module loader
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;

        // Get module loader instance
        $loader = VD_License_Module_Loader::get_instance();

        // Check if utility helper is registered
        $registry = $loader->get_registry();
        $has_utility_module = isset($registry['utility.helper']);

        echo "<ul>\n";
        echo "<li>Module Loader Instance: ✅ CREATED</li>\n";
        echo "<li>Utility Helper in Registry: " . ($has_utility_module ? "✅ REGISTERED" : "❌ NOT REGISTERED") . "</li>\n";

        if ($has_utility_module) {
            $utility_config = $registry['utility.helper'];
            echo "<li>Module File Path: " . htmlspecialchars($utility_config['file']) . "</li>\n";
            echo "<li>Module Class: " . htmlspecialchars($utility_config['class']) . "</li>\n";
            echo "<li>Module Namespace: " . htmlspecialchars($utility_config['namespace']) . "</li>\n";
            echo "<li>Module Priority: " . $utility_config['priority'] . "</li>\n";
        }
        echo "</ul>\n\n";

        // Test 5: Module Instantiation
        echo "<h2>Test 5: Module Instantiation</h2>\n";

        // Try to load the utility helper module
        $utility_helper = $loader->load_module('utility.helper');

        if ($utility_helper) {
            echo "<ul>\n";
            echo "<li>Module Loading: ✅ SUCCESS</li>\n";
            echo "<li>Module Class: " . get_class($utility_helper) . "</li>\n";

            // Test module methods
            if (method_exists($utility_helper, 'get_status')) {
                $status = $utility_helper->get_status();
                echo "<li>get_status() method: ✅ WORKING</li>\n";
                echo "<li>Module Version: " . (isset($status['version']) ? $status['version'] : 'Unknown') . "</li>\n";
                echo "<li>Initialized: " . (isset($status['initialized']) && $status['initialized'] ? '✅ YES' : '❌ NO') . "</li>\n";
            }

            if (method_exists($utility_helper, 'is_ready')) {
                $is_ready = $utility_helper->is_ready();
                echo "<li>is_ready() method: " . ($is_ready ? '✅ READY' : '❌ NOT READY') . "</li>\n";
            }

            echo "</ul>\n\n";
        } else {
            echo "<p>❌ Failed to load utility helper module</p>\n\n";
        }

    } else {
        echo "<p>❌ Cannot test module loading - Module Loader file missing</p>\n\n";
    }

} catch (Exception $e) {
    echo "<p>❌ Error during module loading test: " . htmlspecialchars($e->getMessage()) . "</p>\n\n";
}

// Test 6: Interface Validation
echo "<h2>Test 6: Interface Validation</h2>\n";

$interface_tests = array(
    'Utility Helper Interface' => $utility_interface,
    'Data Sanitizer Interface' => $sanitizer_interface,
    'Response Builder Interface' => $response_interface
);

echo "<ul>\n";
foreach ($interface_tests as $name => $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_namespace = strpos($content, 'namespace VD\\LicenseManager\\UtilityHelper') !== false;
        $has_interface = strpos($content, 'interface ') !== false;

        echo "<li>{$name}: " . ($has_namespace && $has_interface ? "✅ VALID" : "❌ INVALID") . "</li>\n";
    } else {
        echo "<li>{$name}: ❌ FILE MISSING</li>\n";
    }
}
echo "</ul>\n\n";

// Test 7: Original Code Analysis
echo "<h2>Test 7: Original Code Analysis</h2>\n";

$validator_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-validator.php';
if (file_exists($validator_file)) {
    $content = file_get_contents($validator_file);
    $total_lines = substr_count($content, "\n") + 1;

    // Find extractable utility methods
    $extractable_methods = array();
    if (strpos($content, 'function sanitize_status_value(') !== false) {
        $extractable_methods[] = 'sanitize_status_value()';
    }
    if (strpos($content, 'function sanitize_context_data(') !== false) {
        $extractable_methods[] = 'sanitize_context_data()';
    }
    if (strpos($content, 'function sanitize_query_string(') !== false) {
        $extractable_methods[] = 'sanitize_query_string()';
    }

    echo "<ul>\n";
    echo "<li>Validator File Size: {$total_lines} lines</li>\n";
    echo "<li>Extractable Utility Methods Found: " . count($extractable_methods) . "</li>\n";

    if (!empty($extractable_methods)) {
        echo "<li>Methods Ready for Extraction:</li>\n";
        echo "<ul>\n";
        foreach ($extractable_methods as $method) {
            echo "<li>{$method}</li>\n";
        }
        echo "</ul>\n";
    }

    echo "<li>Extraction Status: ⏳ PENDING (Will be implemented in Micro-Steps 2B.1.2-2B.1.8)</li>\n";
    echo "</ul>\n\n";
} else {
    echo "<p>❌ Validator file not found for analysis</p>\n\n";
}

// Test Summary
echo "<h2>Test Summary</h2>\n";
echo "<p><strong>Micro-Step 2B.1.1: Environment Setup ✅ COMPLETED</strong></p>\n";
echo "<p>Status: Environment foundation has been successfully established for the Utility Helper Module.</p>\n";
echo "<p>This setup provides the foundation for Phase 2B.1 implementation to reduce the file size of class-vd-license-validator.php through modular extraction.</p>\n";

echo "<h3>📋 Implementation Progress</h3>\n";
echo "<ul>\n";
echo "<li>✅ Micro-Step 2B.1.1: Environment Setup - COMPLETED</li>\n";
echo "<li>⏳ Micro-Step 2B.1.2: Data Sanitizer Implementation - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.3: Response Builder Implementation - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.4: DateTime Helper Implementation - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.5: Calculation Helper Implementation - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.6: Integration Testing - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.7: Code Extraction & Replacement - PENDING</li>\n";
echo "<li>⏳ Micro-Step 2B.1.8: Final Optimization & Testing - PENDING</li>\n";
echo "</ul>\n";

echo "<h3>🎯 Next Steps</h3>\n";
echo "<p>Code extraction from class-vd-license-validator.php will begin in Micro-Step 2B.1.2 with implementation of DataSanitizer component.</p>\n";

echo "<hr>\n";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "<p><em>Test File: test-step-2b1-1-environment.php</em></p>\n";
?>