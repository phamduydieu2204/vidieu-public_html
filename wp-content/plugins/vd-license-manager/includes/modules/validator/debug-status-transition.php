<?php
/**
 * Debug Status Transition Controller
 *
 * Simple diagnostic script to identify syntax errors
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */

if (!defined('ABSPATH')) {
    // Allow direct access for testing
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
}

echo "Starting Status Transition Controller debug...\n";

try {
    echo "1. Testing file inclusion...\n";

    // Test if the file can be included
    $file_path = plugin_dir_path(__FILE__) . 'class-vd-license-status-transition-controller.php';

    if (!file_exists($file_path)) {
        echo "ERROR: File does not exist at: $file_path\n";
        exit;
    }

    echo "2. File exists, attempting to include...\n";
    require_once $file_path;

    echo "3. File included successfully, testing class...\n";

    if (!class_exists('VD\LicenseManager\Validator\VD_License_Status_Transition_Controller')) {
        echo "ERROR: Class does not exist\n";
        exit;
    }

    echo "4. Class exists, testing instantiation...\n";
    $controller = VD\LicenseManager\Validator\VD_License_Status_Transition_Controller::get_instance();

    echo "5. Instance created successfully!\n";
    echo "Class: " . get_class($controller) . "\n";

    echo "6. Testing basic method...\n";
    $enums = $controller->get_valid_status_enums();
    echo "Status enums count: " . count($enums) . "\n";

    echo "SUCCESS: Status Transition Controller is working!\n";

} catch (ParseError $e) {
    echo "PARSE ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}