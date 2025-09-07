<?php
/**
 * QuickView Compatibility Layer
 * 
 * Prevents auto-scroll to top when selecting variations in QuickView popup
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Compatibility
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_QuickView_Compat {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Debug flag
     */
    private $debug = false;
    
    /**
     * Get instance
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
        // Set debug flag
        $this->debug = defined('VIDIEU_QV_DEBUG') && VIDIEU_QV_DEBUG;
        
        // Initialize on appropriate hooks
        add_action('init', array($this, 'init_compat'), 5);
    }
    
    /**
     * Initialize compatibility
     */
    public function init_compat() {
        // Only run on frontend
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Hook into appropriate actions
        add_action('wp', array($this, 'setup_quickview_compat'), 20);
    }
    
    /**
     * Setup QuickView compatibility based on page context
     */
    public function setup_quickview_compat() {
        // Only apply on pages that might have QuickView (homepage, shop, archives)
        if (!is_front_page() && !is_home() && !is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        // Check if theme has QuickView functionality
        if (!$this->has_quickview_support()) {
            return;
        }
        
        // Enqueue compatibility assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_compat_assets'), 25);
        
        // Remove any preloads for quickview assets
        add_action('template_redirect', array($this, 'remove_quickview_preloads'), 1);
        
        // Add debug flag to localization if needed
        if ($this->debug) {
            add_filter('vidieu_quickview_localize', array($this, 'add_debug_flag'));
        }
    }
    
    /**
     * Check if theme supports QuickView
     */
    private function has_quickview_support() {
        // Check for NASA/Elessi theme quick view functionality
        if (function_exists('elessi_quickview') || 
            function_exists('nasa_quickview') || 
            class_exists('NASA_WC_AJAX') ||
            defined('NASA_THEME_ACTIVE')) {
            return true;
        }
        
        // Check if QuickView scripts are registered
        global $wp_scripts;
        if (isset($wp_scripts->registered['nasa-quickview']) || 
            isset($wp_scripts->registered['elessi-quickview'])) {
            return true;
        }
        
        return apply_filters('vidieu_has_quickview', false);
    }
    
    /**
     * Enqueue compatibility assets
     */
    public function enqueue_compat_assets() {
        // Guard against admin
        if (is_admin()) {
            return;
        }
        
        // Get correct plugin URL - go up from compat directory
        $plugin_url = plugin_dir_url(dirname(__FILE__));  // This gets the plugin root URL
        $plugin_path = plugin_dir_path(dirname(__FILE__)); // This gets the plugin root path
        
        // Relative paths from plugin root
        $css_rel = 'assets/css/quickview-compat.css';
        $js_rel = 'assets/js/quickview-compat.js';
        
        // Build full URLs
        $css_url = $plugin_url . $css_rel;
        $js_url = $plugin_url . $js_rel;
        
        // File paths for version check
        $css_file = $plugin_path . $css_rel;
        $js_file = $plugin_path . $js_rel;
        
        // Use file modification time for cache busting
        $css_version = file_exists($css_file) ? filemtime($css_file) : VD_HOME_VERSION;
        $js_version = file_exists($js_file) ? filemtime($js_file) : VD_HOME_VERSION;
        
        // Register and enqueue JavaScript
        wp_register_script(
            'vidieu-quickview-compat',
            $js_url,
            array('jquery'),
            $js_version,
            true // In footer
        );
        
        // Localize script
        wp_localize_script('vidieu-quickview-compat', 'vidieuQVCompat', array(
            'debug' => $this->debug ? '1' : '0',
            'isHome' => (int) (is_front_page() || is_home()),
            'isShop' => (int) is_shop(),
            'isMobile' => (int) wp_is_mobile()
        ));
        
        wp_enqueue_script('vidieu-quickview-compat');
        
        // Enqueue CSS - NO PRELOAD
        wp_enqueue_style(
            'vidieu-quickview-compat',
            $css_url,
            array(),
            $css_version
        );
    }
    
    /**
     * Add debug flag to localization
     */
    public function add_debug_flag($data) {
        $data['debug'] = '1';
        return $data;
    }
    
    /**
     * Remove any preloads for quickview assets
     */
    public function remove_quickview_preloads() {
        if (is_admin()) {
            return;
        }
        
        // Use output buffer to remove preloads
        ob_start(function($html) {
            // Remove any preload for quickview-compat CSS
            $html = preg_replace(
                '#<link[^>]+rel=["\']preload["\'][^>]+quickview-compat\.css[^>]*>\s*#i',
                '',
                $html
            );
            
            // Remove any preload for quickview-compat JS
            $html = preg_replace(
                '#<link[^>]+rel=["\']preload["\'][^>]+quickview-compat\.js[^>]*>\s*#i',
                '',
                $html
            );
            
            return $html;
        });
    }
}

// Initialize the compatibility layer
Vidieu_QuickView_Compat::get_instance();