<?php
/**
 * VD License Manager Test Bootstrap
 *
 * WordPress PHPUnit test bootstrap for VD License Manager Phase 2 modules
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

// Set testing environment
define('VD_TESTING', true);
define('VD_DEBUG', true);

// WordPress test configuration
$_tests_dir = getenv('WP_TESTS_DIR');

// If WP_TESTS_DIR is not set, try common locations
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Fallback locations for WordPress test suite
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    $possible_locations = [
        '/tmp/wordpress-tests-lib',
        '/var/tmp/wordpress-tests-lib',
        dirname(__FILE__) . '/../../../../tests/phpunit/includes',
        dirname(__FILE__) . '/../../../../../wordpress-develop/tests/phpunit/includes'
    ];

    foreach ($possible_locations as $location) {
        if (file_exists($location . '/functions.php')) {
            $_tests_dir = dirname($location);
            break;
        }
    }
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    throw new Exception(
        "Could not find WordPress test suite. Please install wordpress-tests-lib or set WP_TESTS_DIR environment variable.\n" .
        "Installation guide: https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/"
    );
}

// WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load VD License Manager plugin for testing
 */
function _manually_load_vd_license_manager() {
    // Load plugin dependencies first
    if (file_exists(WP_PLUGIN_DIR . '/license-manager-for-woocommerce/license-manager-for-woocommerce.php')) {
        require_once WP_PLUGIN_DIR . '/license-manager-for-woocommerce/license-manager-for-woocommerce.php';
    }

    // Load VD License Manager
    require dirname(__FILE__) . '/../vd-license-manager.php';

    // Initialize modules for testing
    if (class_exists('VD_License_Dependency_Container')) {
        $container = VD_License_Dependency_Container::get_instance();
        $container->initialize();
    }
}

// Hook plugin loading
tests_add_filter('muplugins_loaded', '_manually_load_vd_license_manager');

/**
 * Set up test database configuration
 */
function _setup_test_database() {
    // Create test tables if they don't exist
    global $wpdb;

    // VD License Manager test tables
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vd_test_licenses (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        license_key varchar(255) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'inactive',
        product_id bigint(20) unsigned DEFAULT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        expires_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY license_key (license_key),
        KEY product_id (product_id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

tests_add_filter('wp_install', '_setup_test_database');

// Start WordPress test suite
require $_tests_dir . '/includes/bootstrap.php';

// Load VD License Manager test utilities
require_once dirname(__FILE__) . '/class-vd-test-case.php';
require_once dirname(__FILE__) . '/class-vd-test-factory.php';
require_once dirname(__FILE__) . '/class-vd-test-utils.php';

// Set up autoloader for test classes
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'VD_Test_') === 0) {
        $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        $file_path = dirname(__FILE__) . '/utils/' . $file_name;

        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// Initialize test environment
do_action('vd_test_bootstrap_loaded');

echo "VD License Manager Test Bootstrap Loaded Successfully\n";
echo "Testing Environment: " . (VD_TESTING ? 'ENABLED' : 'DISABLED') . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHPUnit Version: " . PHPUnit\Runner\Version::id() . "\n";
echo "----------------------------------------\n";