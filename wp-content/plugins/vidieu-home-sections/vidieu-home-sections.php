<?php
/**
 * Plugin Name: Vidieu Home Sections
 * Description: Tạo các khối sản phẩm và bài viết tùy biến cho trang chủ Vidieu.vn
 * Version: 1.1.1
 * Author: Vidieu Development Team
 * Text Domain: vidieu-home-sections
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * 
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VD_HOME_VERSION', '1.1.1');
define('VD_HOME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VD_HOME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VD_HOME_TEXT_DOMAIN', 'vidieu-home-sections');

/**
 * Main plugin class
 */
class Vidieu_Home_Sections {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain(VD_HOME_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-assets.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-shortcodes.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-ajax.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-ajax-optimized.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-menus.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-wpbakery.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-templates.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-admin.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-pagination.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-buy-now.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-translations.php';
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-page-sidebar-mappings.php';
        
        // Performance optimizations - V2 Ultimate priority based on HAR analysis
        if (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-ultimate.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-ultimate.php';
        } elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-safe.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-safe.php';
        } elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-stepped.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-stepped.php';
        } elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-enhanced.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-enhanced.php';
        } elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2.php';
        } elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard.php')) {
            require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize classes
        VD_Assets::get_instance();
        VD_Shortcodes::get_instance();
        VD_Ajax::get_instance();
        VD_Ajax_Optimized::get_instance();
        VD_Menus::get_instance();
        VD_WPBakery::get_instance();
        VD_Templates::get_instance();
        VD_Admin::get_instance();
        VD_Pagination::get_instance();
        VD_Buy_Now::get_instance();
        VD_Translations::get_instance();
        VD_Page_Sidebar_Mappings::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set plugin version
        update_option('vd_home_sections_version', VD_HOME_VERSION);
        
        // Create page mappings table
        require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-page-sidebar-mappings.php';
        VD_Page_Sidebar_Mappings::create_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
Vidieu_Home_Sections::get_instance();