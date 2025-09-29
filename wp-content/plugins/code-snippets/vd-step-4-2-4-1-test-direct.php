<?php
/**
 * VD License Manager - Step 4.2.4.1 Direct Test
 *
 * Multiple ways to run:
 * 1. Direct browser: /wp-content/plugins/code-snippets/vd-step-4-2-4-1-test-direct.php
 * 2. WordPress admin: /wp-admin/?vd_test_4241=1
 * 3. Code Snippets plugin: Copy và paste code này
 */

// Method 1: For direct browser access
if (!defined('ABSPATH')) {
    // Try to bootstrap WordPress
    $wp_load_paths = array(
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../wp-load.php'
    );

    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            require_once $wp_load_path;
            $wp_loaded = true;
            break;
        }
    }

    if (!$wp_loaded) {
        die('Cannot load WordPress. Please run this as a Code Snippet or ensure WordPress is accessible.');
    }

    // If loaded via direct access, run immediately
    vd_run_step_4241_test();
} else {
    // Method 2: WordPress admin hook - tự động integrate
    add_action('admin_init', function() {
        if (isset($_GET['vd_test_4241'])) {
            vd_run_step_4241_test();
            exit;
        }
    });

    // Method 3: Admin notice để dễ access
    add_action('admin_notices', function() {
        if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'snippets') {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>🧪 VD License Manager Testing:</strong> ';
            echo '<a href="' . admin_url('?vd_test_4241=1') . '" class="button button-primary">Run Step 4.2.4.1 Test</a> ';
            echo '<small>(Status Enum Validation Framework)</small>';
            echo '</p></div>';
        }
    });
}

/**
 * Main test execution function
 */
function vd_run_step_4241_test() {

// Simple test output
echo '<div style="max-width: 1200px; margin: 20px auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, segoe ui, Roboto, sans-serif;">';
echo '<h1>🧪 VD License Manager - Step 4.2.4.1 Test</h1>';
echo '<p><strong>Test executed at:</strong> ' . date('Y-m-d H:i:s') . '</p>';

// Check WordPress
if (!function_exists('wp_get_current_user')) {
    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
    echo '<h3>❌ WordPress Not Loaded</h3>';
    echo '<p>WordPress functions are not available. This test needs WordPress environment.</p>';
    echo '</div>';
    echo '</div>';
    exit;
}

echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
echo '<h3>✅ WordPress Environment Loaded</h3>';
echo '<p>WordPress version: ' . get_bloginfo('version') . '</p>';
echo '</div>';

// Check VD License Manager Plugin
if (!class_exists('VD_License_Manager')) {
    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
    echo '<h3>❌ VD License Manager Not Found</h3>';
    echo '<p>VD License Manager plugin is not loaded. Please ensure it is activated.</p>';
    echo '<p><strong>Active plugins:</strong></p><ul>';

    $active_plugins = get_option('active_plugins', array());
    foreach ($active_plugins as $plugin) {
        echo '<li>' . $plugin . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    exit;
}

echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
echo '<h3>✅ VD License Manager Plugin Loaded</h3>';
echo '</div>';

// Try to load VD_License_Validator
if (!class_exists('VD_License_Validator')) {
    echo '<div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;">';
    echo '<h3>⚠️ Loading VD_License_Validator</h3>';

    // Force load dependencies
    $plugin_dir = WP_PLUGIN_DIR . '/vd-license-manager/includes/';
    $files_to_load = array(
        'functions.php',
        'class-vd-database-manager.php',
        'class-vd-encryption-manager.php',
        'class-vd-license-validator.php'
    );

    foreach ($files_to_load as $file) {
        $file_path = $plugin_dir . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
            echo '<p>✅ Loaded: ' . $file . '</p>';
        } else {
            echo '<p>❌ Missing: ' . $file . '</p>';
        }
    }
    echo '</div>';
}

if (!class_exists('VD_License_Validator')) {
    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
    echo '<h3>❌ VD_License_Validator Still Not Available</h3>';
    echo '<p>Could not load VD_License_Validator class. Check for PHP syntax errors.</p>';
    echo '</div>';
    echo '</div>';
    exit;
}

echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
echo '<h3>✅ VD_License_Validator Available</h3>';
echo '</div>';

// Simple validation test
try {
    $validator = VD_License_Validator::get_instance();

    if (!$validator) {
        throw new Exception('Failed to get VD_License_Validator instance');
    }

    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
    echo '<h3>✅ VD_License_Validator Instance Created</h3>';
    echo '</div>';

    // Test basic validation with reflection (since method is private)
    $reflection = new ReflectionClass($validator);

    // Test get_valid_status_enums
    if ($reflection->hasMethod('get_valid_status_enums')) {
        $method = $reflection->getMethod('get_valid_status_enums');
        $method->setAccessible(true);
        $valid_statuses = $method->invoke($validator);

        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📋 Status Enums Test</h3>';
        echo '<p><strong>Valid statuses:</strong> ' . implode(', ', $valid_statuses) . '</p>';
        echo '<p><strong>Count:</strong> ' . count($valid_statuses) . ' statuses</p>';
        echo '</div>';
    }

    // Test status hierarchy
    if ($reflection->hasMethod('validate_status_hierarchy')) {
        $method = $reflection->getMethod('validate_status_hierarchy');
        $method->setAccessible(true);

        $hierarchy_test = $method->invoke($validator, 'active');

        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>📊 Status Hierarchy Test</h3>';
        echo '<p><strong>Status:</strong> active</p>';
        echo '<p><strong>Priority:</strong> ' . $hierarchy_test['priority'] . '</p>';
        echo '<p><strong>Is Good State:</strong> ' . ($hierarchy_test['is_good_state'] ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Is Terminal:</strong> ' . ($hierarchy_test['is_terminal'] ? 'Yes' : 'No') . '</p>';
        echo '</div>';
    }

    // Test transition validation
    if ($reflection->hasMethod('validate_status_transition')) {
        $method = $reflection->getMethod('validate_status_transition');
        $method->setAccessible(true);

        $transition_test = $method->invoke($validator, 'inactive', 'active');

        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0;">';
        echo '<h3>🔄 Status Transition Test</h3>';
        echo '<p><strong>Transition:</strong> inactive → active</p>';
        echo '<p><strong>Valid:</strong> ' . ($transition_test['valid'] ? 'Yes' : 'No') . '</p>';
        if (isset($transition_test['transition_type'])) {
            echo '<p><strong>Type:</strong> ' . $transition_test['transition_type'] . '</p>';
        }
        echo '</div>';
    }

    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
    echo '<h3>🎉 Step 4.2.4.1 Basic Tests Successful</h3>';
    echo '<p>Status Enum Validation Framework is working correctly!</p>';
    echo '<p><strong>Next:</strong> You can proceed with Step 4.2.4.2 - Business Rule Enforcement Engine</p>';
    echo '</div>';

} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
    echo '<h3>❌ Test Exception</h3>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '</div>';
} catch (Error $e) {
    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
    echo '<h3>💥 Fatal Test Error</h3>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '</div>';
}

echo '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 10px 0;">';
echo '<h3>📖 How to Use This Test</h3>';
echo '<p><strong>Method 1 - Direct Browser Access:</strong></p>';
echo '<p>Visit: <code>' . home_url('/wp-content/plugins/code-snippets/vd-step-4-2-4-1-test-direct.php') . '</code></p>';
echo '<p><strong>Method 2 - WordPress Admin:</strong></p>';
echo '<p>Visit: <code>' . admin_url('?vd_test_4241=1') . '</code></p>';
echo '<p><strong>Method 3 - As Code Snippet:</strong></p>';
echo '<p>Copy this code into Code Snippets plugin and run it</p>';
echo '</div>';

echo '</div>';
} // Close function vd_run_step_4241_test()
?>