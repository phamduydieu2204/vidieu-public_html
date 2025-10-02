<?php
/**
 * Plugin Name: VD License Manager
 * Description: Advanced license management system for Helium10, Midjourney, Freepik with sticky provider assignment and device management
 * Version: 1.0.0
 * Author: VD Team
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vd-license-manager
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VD_LM_VERSION', '1.0.0');
define('VD_LM_PATH', plugin_dir_path(__FILE__));
define('VD_LM_URL', plugin_dir_url(__FILE__));
define('VD_LM_FILE', __FILE__);
define('VD_LM_BASENAME', plugin_basename(__FILE__));
define('VD_LM_TEXT_DOMAIN', 'vd-license-manager');

// Check requirements before loading
if (!vd_check_requirements()) {
    add_action('admin_notices', 'vd_requirements_notice');
    return;
}

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, 'vd_license_manager_activate');
register_deactivation_hook(__FILE__, 'vd_license_manager_deactivate');

// Initialize plugin
add_action('plugins_loaded', 'vd_license_manager_init');

/**
 * Check plugin requirements
 *
 * @return bool True if all requirements met, false otherwise
 */
function vd_check_requirements() {
    global $wp_version;

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        return false;
    }

    // Check WordPress version
    if (version_compare($wp_version, '5.0', '<')) {
        return false;
    }

    // Check required PHP extensions
    $required_extensions = ['openssl', 'json', 'mysqli', 'curl', 'mbstring'];
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            return false;
        }
    }

    return true;
}

/**
 * Display requirements notice
 */
function vd_requirements_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>VD License Manager:</strong> Plugin requirements not met. ';
    echo 'Requires PHP 7.4+, WordPress 5.0+, and required PHP extensions (openssl, json, mysqli, curl, mbstring).';
    echo '</p></div>';
}

/**
 * Plugin activation callback
 */
function vd_license_manager_activate() {
    // Check requirements during activation
    if (!vd_check_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__));
        // Use plain text during activation as text domain is not loaded yet
        wp_die('VD License Manager requires PHP 7.4+, WordPress 5.0+, and required PHP extensions.');
    }

    // Load activator class
    require_once VD_LM_PATH . 'includes/class-vd-activator.php';
    VD_Activator::activate();
}

/**
 * Plugin deactivation callback
 */
function vd_license_manager_deactivate() {
    // Load deactivator class
    require_once VD_LM_PATH . 'includes/class-vd-activator.php';
    VD_Activator::deactivate();
}

/**
 * Initialize plugin
 * Enhanced loading with error handling and debugging
 */
function vd_license_manager_init() {
    try {
        // Load core functions first
        $functions_file = VD_LM_PATH . 'includes/functions.php';
        if (file_exists($functions_file)) {
            require_once $functions_file;
        }

        // Load main plugin class
        $manager_file = VD_LM_PATH . 'includes/class-vd-license-manager.php';
        if (file_exists($manager_file)) {
            require_once $manager_file;
        } else {
            return;
        }

        // Verify class exists before initializing
        if (!class_exists('VD_License_Manager')) {
            return;
        }

        // Initialize
        $manager = VD_License_Manager::get_instance();
        if ($manager) {
            $manager->init();
        }

        // Load Step 4.3 test endpoint
        if (is_admin() || wp_doing_ajax()) {
            $test_file = VD_LM_PATH . 'includes/test-step-4-3-integration-simple.php';
            if (file_exists($test_file)) {
                require_once $test_file;
            }
        }

    } catch (Exception $e) {
        error_log('[VD License Manager] Initialization error: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('[VD License Manager] Fatal initialization error: ' . $e->getMessage());
    }
}

/**
 * Get plugin version
 *
 * @return string Plugin version
 */
function vd_license_manager_version() {
    return VD_LM_VERSION;
}