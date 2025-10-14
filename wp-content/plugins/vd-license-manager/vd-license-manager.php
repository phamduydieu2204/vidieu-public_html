<?php
/**
 * Plugin Name: VD License Manager
 * Plugin URI: https://vidieu.vn
 * Description: Quản lý chia sẻ tài khoản provider qua license keys
 * Version: 1.0.0
 * Author: Vidieu Team
 * Author URI: https://vidieu.vn
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: vd-license-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 *
 * @package VD_License_Manager
 * @since   1.0.0
 */

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin version
 *
 * @since 1.0.0
 */
define( 'VD_PLUGIN_VERSION', '1.0.0' );

/**
 * Plugin file path
 *
 * @since 1.0.0
 */
define( 'VD_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path
 *
 * @since 1.0.0
 */
define( 'VD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 *
 * @since 1.0.0
 */
define( 'VD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename
 *
 * @since 1.0.0
 */
define( 'VD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin text domain
 *
 * @since 1.0.0
 */
define( 'VD_TEXT_DOMAIN', 'vd-license-manager' );

// Debug: Verify constants are set
error_log( 'VD Constants: DIR=' . VD_PLUGIN_DIR . ', URL=' . VD_PLUGIN_URL );

/**
 * Manual autoloader for VD_LM_* classes
 *
 * This ensures all plugin classes are loaded properly
 * Works as primary autoloader or fallback if composer fails
 *
 * @since 1.0.0
 */
spl_autoload_register( function( $class ) {
    // Only handle VD_LM_ prefixed classes
    if ( strpos( $class, 'VD_LM_' ) !== 0 ) {
        return;
    }

    // Convert class name to file path
    // VD_LM_Encryption_Service -> class-vd-lm-encryption-service.php
    $file = str_replace( '_', '-', strtolower( $class ) );
    $file = 'class-' . $file . '.php';

    // Define search directories in priority order
    $search_dirs = array(
        VD_PLUGIN_DIR . 'includes/',
        VD_PLUGIN_DIR . 'includes/services/',
        VD_PLUGIN_DIR . 'includes/repositories/',
        VD_PLUGIN_DIR . 'includes/utils/',
        VD_PLUGIN_DIR . 'admin/',
        VD_PLUGIN_DIR . 'public/',
    );

    // Search for the class file in each directory
    foreach ( $search_dirs as $dir ) {
        $file_path = $dir . $file;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            return;
        }
    }
} );

/**
 * Load Composer autoloader if available (optional)
 *
 * @since 1.0.0
 */
if ( file_exists( VD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once VD_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Load core plugin classes
 *
 * @since 1.0.0
 */
require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-license-manager.php';
require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-activator.php';
require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-deactivator.php';

// Load portal setup (optimized two-column layout)
if ( file_exists( VD_PLUGIN_DIR . 'includes/class-vd-portal-setup.php' ) ) {
    require_once VD_PLUGIN_DIR . 'includes/class-vd-portal-setup.php';
}

// Load License Sync Admin
if ( is_admin() && file_exists( VD_PLUGIN_DIR . 'admin/class-vd-license-sync-admin.php' ) ) {
    require_once VD_PLUGIN_DIR . 'admin/class-vd-license-sync-admin.php';
    error_log('VD Loader: Loaded License Sync Admin');
}

// Load REST API handler
if ( file_exists( VD_PLUGIN_DIR . 'includes/class-vd-rest-api.php' ) ) {
    require_once VD_PLUGIN_DIR . 'includes/class-vd-rest-api.php';
    error_log('VD Loader: Loaded REST API Handler');
}

// Load Cron Handler
$cron_file = VD_PLUGIN_DIR . 'includes/class-vd-lm-cron.php';
if (file_exists($cron_file)) {
    require_once $cron_file;
    error_log('VD Loader: Loaded Cron Handler from: ' . $cron_file);
}

// Load Migration Scripts
$migration_file = VD_PLUGIN_DIR . 'includes/migrations/fix-double-prefix.php';
if (file_exists($migration_file)) {
    require_once $migration_file;
    error_log('VD Loader: Loaded Migration Script: fix-double-prefix.php');
}


/**
 * Plugin activation hook
 *
 * Runs when plugin is activated
 *
 * @since 1.0.0
 */
function activate_vd_license_manager() {
    VD_LM_Activator::activate();
    do_action('vd_lm_activated');
}

/**
 * Plugin deactivation hook
 *
 * Runs when plugin is deactivated
 *
 * @since 1.0.0
 */
function deactivate_vd_license_manager() {
    VD_LM_Deactivator::deactivate();
    do_action('vd_lm_deactivated');
}

register_activation_hook( __FILE__, 'activate_vd_license_manager' );
register_deactivation_hook( __FILE__, 'deactivate_vd_license_manager' );

/**
 * Check for required dependencies
 *
 * @since 1.0.0
 * @return bool True if all dependencies are met
 */
function vd_check_dependencies() {
    // Check WordPress version
    global $wp_version;
    if ( version_compare( $wp_version, '6.0', '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__( 'VD License Manager requires WordPress 6.0 or higher.', 'vd-license-manager' );
            echo '</p></div>';
        } );
        return false;
    }

    // Check PHP version
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__( 'VD License Manager requires PHP 7.4 or higher.', 'vd-license-manager' );
            echo '</p></div>';
        } );
        return false;
    }

    // Check if WooCommerce is active
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__( 'VD License Manager requires WooCommerce to be installed and activated.', 'vd-license-manager' );
            echo '</p></div>';
        } );
        return false;
    }

    // Check if License Manager for WooCommerce is active
    if ( ! in_array( 'license-manager-for-woocommerce/license-manager-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__( 'VD License Manager requires License Manager for WooCommerce (LMfWC) to be installed and activated.', 'vd-license-manager' );
            echo '</p></div>';
        } );
        return false;
    }

    return true;
}

/**
 * Initialize and run the plugin
 *
 * @since 1.0.0
 */
function run_vd_license_manager() {
    // Check dependencies first
    if ( ! vd_check_dependencies() ) {
        return;
    }

    // Initialize the plugin
    $plugin = new VD_LM_License_Manager();
    $plugin->run();
}

/**
 * Start the plugin after WordPress is fully loaded
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', 'run_vd_license_manager' );

/**
 * Add plugin action links
 *
 * @since 1.0.0
 * @param array $links Existing action links
 * @return array Modified action links
 */
function vd_plugin_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=vd-license-manager' ) . '">' . esc_html__( 'Settings', 'vd-license-manager' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . VD_PLUGIN_BASENAME, 'vd_plugin_action_links' );