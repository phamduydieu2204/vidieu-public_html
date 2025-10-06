<?php
/**
 * Plugin Name: VD License Manager
 * Plugin URI: https://vidieu.vn
 * Description: License management and distribution system for SaaS account sharing. Manage provider accounts, assign licenses, control device access, and distribute account information securely.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: VidieuVN
 * Author URI: https://vidieu.vn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vd-license-manager
 * Domain Path: /languages
 * Network: false
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VD_VERSION', '1.0.0');
define('VD_PLUGIN_FILE', __FILE__);
define('VD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define database table prefix
global $wpdb;
define('VD_TABLE_PREFIX', $wpdb->prefix);

// Define security constants
define('VD_FINGERPRINT_SALT', 'vd_fp_salt_2025');

// Define cache constants
define('VD_GEO_CACHE_DURATION', 7 * DAY_IN_SECONDS);

// Define rate limiting constants
define('VD_RATE_LIMIT_WINDOW', 300);        // 5 minutes
define('VD_RATE_LIMIT_MAX_HITS', 10);

// Define API constants
define('VD_CORS_ENABLED', false);
define('VD_CORS_ORIGINS', '*');

/**
 * Main plugin class
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_License_Manager {

    /**
     * Plugin loader instance
     *
     * @since 1.0.0
     * @var VD_Loader
     */
    private $loader;

    /**
     * Single instance of the plugin
     *
     * @since 1.0.0
     * @var VD_License_Manager|null
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @since 1.0.0
     * @return VD_License_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        require_once VD_PLUGIN_PATH . 'includes/class-vd-loader.php';
        $this->loader = new VD_Loader();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Initialize plugin
     *
     * @since 1.0.0
     */
    public function init() {
        // Initialize plugin components after WordPress is loaded
        do_action('vd_license_manager_init');
    }

    /**
     * Load plugin text domain for translations
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'vd-license-manager',
            false,
            dirname(VD_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Plugin activation hook
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Prevent direct activation if requirements not met
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            wp_die(
                esc_html__('VD License Manager requires PHP 7.4+. Current version: ' . PHP_VERSION, 'vd-license-manager'),
                esc_html__('Plugin Activation Error', 'vd-license-manager'),
                array('back_link' => true)
            );
        }

        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            wp_die(
                esc_html__('VD License Manager requires WordPress 5.0+. Current version: ' . $wp_version, 'vd-license-manager'),
                esc_html__('Plugin Activation Error', 'vd-license-manager'),
                array('back_link' => true)
            );
        }

        // Load and run activator
        require_once VD_PLUGIN_PATH . 'includes/class-vd-activator.php';
        VD_Activator::activate();

        // Set activation flag
        update_option('vd_license_manager_activated', time());

        // Trigger activation action
        do_action('vd_license_manager_activate');
    }

    /**
     * Plugin deactivation hook
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Load deactivator class
        require_once VD_PLUGIN_PATH . 'includes/class-vd-deactivator.php';
        VD_Deactivator::deactivate();
    }

}

/**
 * Get main plugin instance
 *
 * @since 1.0.0
 * @return VD_License_Manager
 */
function vd_license_manager() {
    return VD_License_Manager::get_instance();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('VD_License_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('VD_License_Manager', 'deactivate'));

// Initialize the plugin
vd_license_manager();