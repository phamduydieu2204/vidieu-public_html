<?php
/**
 * Debug CalculationHelper batch progress test
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress configuration
require_once ABSPATH . 'wp-config.php';

echo "<h1>Debug CalculationHelper Batch Progress</h1>\n";

try {
    // Include the module loader
    $module_loader_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-module-loader.php';
    if (file_exists($module_loader_file)) {
        require_once $module_loader_file;

        // Get utility helper instance
        $loader = VD_License_Module_Loader::get_instance();
        $utility_helper = $loader->load_module('utility.helper');
        $calculation_helper = $utility_helper->get_calculation_helper();

        if ($calculation_helper) {
            echo "<h2>Testing calculate_batch_progress(150, 200, 50)</h2>\n";

            $result = call_user_func(array($calculation_helper, 'calculate_batch_progress'), 150, 200, 50);

            echo "<h3>Expected vs Actual:</h3>\n";
            echo "<ul>\n";
            echo "<li>percentage: expected 75.0, got " . var_export($result['percentage'], true) . "</li>\n";
            echo "<li>batches_completed: expected 3, got " . var_export($result['batches_completed'], true) . "</li>\n";
            echo "<li>items_remaining: expected 50, got " . var_export($result['items_remaining'], true) . "</li>\n";
            echo "<li>batches_remaining: got " . var_export($result['batches_remaining'], true) . "</li>\n";
            echo "<li>total_batches: got " . var_export($result['total_batches'], true) . "</li>\n";
            echo "</ul>\n";

            echo "<h3>Full Result:</h3>\n";
            echo "<pre>" . print_r($result, true) . "</pre>\n";

            // Test manual calculation
            echo "<h3>Manual Calculation:</h3>\n";
            $percentage_manual = (150 / 200) * 100;
            $batches_completed_manual = ceil(150 / 50);
            $items_remaining_manual = 200 - 150;

            echo "<ul>\n";
            echo "<li>percentage manual: " . $percentage_manual . "</li>\n";
            echo "<li>batches_completed manual: " . $batches_completed_manual . "</li>\n";
            echo "<li>items_remaining manual: " . $items_remaining_manual . "</li>\n";
            echo "</ul>\n";

            // Test the exact conditions
            echo "<h3>Test Conditions:</h3>\n";
            $cond1 = isset($result['percentage']) && $result['percentage'] === 75.0;
            $cond2 = isset($result['batches_completed']) && $result['batches_completed'] === 3;
            $cond3 = isset($result['items_remaining']) && $result['items_remaining'] === 50;

            echo "<ul>\n";
            echo "<li>percentage === 75.0: " . ($cond1 ? 'TRUE' : 'FALSE') . "</li>\n";
            echo "<li>batches_completed === 3: " . ($cond2 ? 'TRUE' : 'FALSE') . "</li>\n";
            echo "<li>items_remaining === 50: " . ($cond3 ? 'TRUE' : 'FALSE') . "</li>\n";
            echo "<li>ALL CONDITIONS: " . ($cond1 && $cond2 && $cond3 ? 'PASS' : 'FAIL') . "</li>\n";
            echo "</ul>\n";

        } else {
            echo "<p>❌ Failed to load CalculationHelper</p>\n";
        }
    } else {
        echo "<p>❌ Module loader not found</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>