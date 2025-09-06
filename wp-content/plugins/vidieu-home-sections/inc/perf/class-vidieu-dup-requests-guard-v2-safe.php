<?php
/**
 * Vidieu Duplicate Requests Guard V2 - Safe Version
 * 
 * Minimal implementation to avoid 500 errors
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2_Safe {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Tracking arrays
     */
    private $removed_scripts = array();
    private $removed_styles = array();
    private $fixes_log = array();
    
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
        // Check kill switch
        if (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION) {
            return;
        }
        
        add_action('init', array($this, 'init_optimization'), 1);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        // Skip admin, ajax, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Skip CLI
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }
        
        $this->log_fix('V2 Safe initialization started');
        
        // Basic optimization only
        $this->setup_basic_optimization();
        
        // Route-based optimization
        add_action('wp', array($this, 'setup_route_optimization'), 1);
        
        // Debug output for admin
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'output_debug_info'), 9999);
        }
    }
    
    /**
     * Setup basic optimization
     */
    private function setup_basic_optimization() {
        // Remove emoji
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Remove generator
        remove_action('wp_head', 'wp_generator');
        
        // Basic cleanup
        add_action('wp_enqueue_scripts', array($this, 'remove_basic_duplicates'), 999);
    }
    
    /**
     * Remove basic duplicates
     */
    public function remove_basic_duplicates() {
        // Remove dashicons for non-logged in
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
            $this->removed_styles[] = 'dashicons';
        }
        
        // Remove wp-embed
        wp_dequeue_script('wp-embed');
        $this->removed_scripts[] = 'wp-embed';
    }
    
    /**
     * Setup route optimization
     */
    public function setup_route_optimization() {
        if (is_cart() || is_checkout()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_cart_checkout'), 9999);
        }
    }
    
    /**
     * Optimize cart/checkout - SAFE version
     */
    public function optimize_cart_checkout() {
        global $wp_scripts, $wp_styles;
        
        // Only remove clearly unnecessary items
        $remove_patterns = array(
            'elementor',
            'revslider',
            'instagram-feed',
            'yith-woocompare'
        );
        
        // Remove scripts matching patterns
        foreach ($wp_scripts->registered as $handle => $script) {
            foreach ($remove_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    wp_dequeue_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Removed script: $handle");
                }
            }
        }
        
        // Remove styles matching patterns
        foreach ($wp_styles->registered as $handle => $style) {
            foreach ($remove_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    wp_dequeue_style($handle);
                    $this->removed_styles[] = $handle;
                    $this->log_fix("Removed style: $handle");
                }
            }
        }
    }
    
    /**
     * Log helper
     */
    private function log_fix($message) {
        $this->fixes_log[] = $message;
    }
    
    /**
     * Get current page type
     */
    private function get_current_page_type() {
        if (is_front_page()) return 'Home';
        if (is_cart()) return 'Cart';
        if (is_checkout()) return 'Checkout';
        if (is_product()) return 'Product';
        if (is_shop()) return 'Shop';
        if (is_page('contact') || is_page('lien-he')) return 'Contact';
        if (is_single()) return 'Post';
        return 'Other';
    }
    
    /**
     * Output debug info
     */
    public function output_debug_info() {
        $total_removed = count($this->removed_scripts) + count($this->removed_styles);
        
        echo "\n<!-- ===== Vidieu V2 Safe Report =====\n";
        echo "Page Type: " . $this->get_current_page_type() . "\n";
        echo "Scripts removed: " . count($this->removed_scripts) . "\n";
        echo "Styles removed: " . count($this->removed_styles) . "\n";
        echo "Total removed: " . $total_removed . "\n";
        
        if (!empty($this->removed_scripts)) {
            echo "\nRemoved Scripts:\n";
            foreach ($this->removed_scripts as $handle) {
                echo " - " . $handle . "\n";
            }
        }
        
        if (!empty($this->removed_styles)) {
            echo "\nRemoved Styles:\n";
            foreach ($this->removed_styles as $handle) {
                echo " - " . $handle . "\n";
            }
        }
        
        echo "\nKill Switch: " . (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION ? 'ACTIVE' : 'inactive') . "\n";
        echo "===== End Report ===== -->\n";
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2_Safe::get_instance();
}, 1);