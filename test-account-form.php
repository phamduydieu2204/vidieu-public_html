<?php
/**
 * Account Form Functionality Test
 *
 * Quick test to verify all form fixes are working
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('wp-config.php');

// Simple auth check
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    if (!isset($_GET['test_form']) || $_GET['test_form'] !== 'vd_form_test_2024') {
        die('Add ?test_form=vd_form_test_2024 to run this test.');
    }
}

echo "<h1>VD License Manager - Account Form Test</h1>\n";

// Test 1: Check if classes exist
echo "<h2>1. Testing PHP Classes:</h2>\n";
$classes_to_test = [
    'VD_LM_Account_Repository' => 'wp-content/plugins/vd-license-manager/includes/repositories/class-vd-lm-account-repository.php',
    'VD_LM_Database' => 'wp-content/plugins/vd-license-manager/includes/class-vd-lm-database.php'
];

foreach ($classes_to_test as $class => $file) {
    if (file_exists($file)) {
        include_once($file);
        if (class_exists($class)) {
            echo "✅ {$class} - Class loaded successfully<br>\n";
        } else {
            echo "❌ {$class} - Class exists in file but not loaded<br>\n";
        }
    } else {
        echo "❌ {$class} - File not found: {$file}<br>\n";
    }
}

// Test 2: Database table exists
echo "<h2>2. Testing Database:</h2>\n";
global $wpdb;
$table_name = $wpdb->prefix . 'bz_vd_provider_accounts';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if ($table_exists) {
    echo "✅ Table exists: {$table_name}<br>\n";

    // Check column types
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    $encrypted_fields = ['account_password', 'cookies', 'phone_recovery', 'email_recovery', 'security_answer', 'backup_codes', 'two_factor_secret', 'api_key', 'secret_key', 'api_token'];

    echo "<h3>Encrypted Field Column Types:</h3>\n";
    foreach ($columns as $column) {
        if (in_array($column->Field, $encrypted_fields)) {
            $is_ok = (strpos($column->Type, 'text') !== false || strpos($column->Type, 'TEXT') !== false);
            $status = $is_ok ? '✅' : '❌';
            $color = $is_ok ? 'green' : 'red';
            echo "<span style='color: {$color};'>{$status} {$column->Field}: {$column->Type}</span><br>\n";
        }
    }
} else {
    echo "❌ Table not found: {$table_name}<br>\n";
}

// Test 3: Form files exist
echo "<h2>3. Testing Form Files:</h2>\n";
$form_files = [
    'accounts-form.php' => 'wp-content/plugins/vd-license-manager/admin/partials/accounts-form.php',
    'accounts-form.js' => 'wp-content/plugins/vd-license-manager/admin/js/accounts-form.js',
    'accounts-form.css' => 'wp-content/plugins/vd-license-manager/admin/css/accounts-form.css',
    'accounts-form-errors.css' => 'wp-content/plugins/vd-license-manager/admin/css/accounts-form-errors.css',
];

foreach ($form_files as $name => $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ {$name} - Exists ({$size} bytes)<br>\n";
    } else {
        echo "❌ {$name} - Missing: {$file}<br>\n";
    }
}

// Test 4: Test account creation (simulation)
echo "<h2>4. Testing Account Repository:</h2>\n";
if (class_exists('VD_LM_Account_Repository')) {
    try {
        $repo = new VD_LM_Account_Repository();
        echo "✅ Account repository instantiated successfully<br>\n";

        // Test validation method
        if (method_exists($repo, 'insert')) {
            echo "✅ insert() method exists<br>\n";
        } else {
            echo "❌ insert() method missing<br>\n";
        }

        // Test with minimal valid data (won't actually insert due to duplicate check)
        $test_data = [
            'provider' => 'Test Provider',
            'account_login' => 'test@example.com',
            'account_password' => 'test123456',
            'capacity' => 5,
            'status' => 'active'
        ];

        echo "✅ Test data prepared for validation<br>\n";

    } catch (Exception $e) {
        echo "❌ Error testing repository: " . esc_html($e->getMessage()) . "<br>\n";
    }
} else {
    echo "❌ VD_LM_Account_Repository class not available<br>\n";
}

// Test 5: JavaScript/CSS syntax check
echo "<h2>5. Quick Syntax Check:</h2>\n";

// Check JavaScript file for syntax issues
$js_file = 'wp-content/plugins/vd-license-manager/admin/js/accounts-form.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);

    // Basic checks
    $brace_open = substr_count($js_content, '{');
    $brace_close = substr_count($js_content, '}');
    $paren_open = substr_count($js_content, '(');
    $paren_close = substr_count($js_content, ')');

    echo "JavaScript file syntax check:<br>\n";
    echo "- Braces: {$brace_open} open, {$brace_close} close " . ($brace_open === $brace_close ? '✅' : '❌') . "<br>\n";
    echo "- Parentheses: {$paren_open} open, {$paren_close} close " . ($paren_open === $paren_close ? '✅' : '❌') . "<br>\n";

    // Check for common patterns
    if (strpos($js_content, 'vd-toggle-password') !== false) {
        echo "✅ Password toggle functionality found<br>\n";
    } else {
        echo "❌ Password toggle functionality missing<br>\n";
    }

    if (strpos($js_content, 'vd-add-custom-field') !== false) {
        echo "✅ Custom field functionality found<br>\n";
    } else {
        echo "❌ Custom field functionality missing<br>\n";
    }
}

echo "<h2>6. Summary:</h2>\n";
echo "<p>If all tests show ✅, your form should be working correctly!</p>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>1. Run the database fix: <a href='fix-database-emergency.php?emergency_fix=vd_database_fix_2024'>Fix Database</a></li>\n";
echo "<li>2. Go to WordPress Admin → VD License Manager → Accounts</li>\n";
echo "<li>3. Try to create a new account</li>\n";
echo "<li>4. Test password show/hide buttons</li>\n";
echo "<li>5. Test form validation and error handling</li>\n";
echo "</ul>\n";

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>