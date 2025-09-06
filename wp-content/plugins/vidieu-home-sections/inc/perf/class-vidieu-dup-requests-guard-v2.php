<?php
/**
 * Vidieu Duplicate Requests Guard V2
 * 
 * Enhanced version với fixes cho các issues chưa được giải quyết
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2 {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Emergency kill switch
     */
    const DISABLE_OPTIMIZATION = 'VIDIEU_DISABLE_DUP_OPTIMIZATION';
    
    /**
     * Track removed scripts
     */
    private $removed_scripts = array();
    private $removed_styles = array();
    
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
        if (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION)) {
            return;
        }
        
        // Hook vào init để chắc chắn WordPress đã load
        add_action('init', array($this, 'init_optimization'), 1);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        // Skip admin và AJAX requests
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Phase 1: Critical 404 fixes - Hook sớm hơn
        add_action('wp_loaded', array($this, 'fix_404_errors_early'), 1);
        add_filter('style_loader_src', array($this, 'fix_resource_paths'), 1, 2);
        add_filter('script_loader_src', array($this, 'fix_resource_paths'), 1, 2);
        
        // Phase 2: reCAPTCHA fixes - Multiple hooks để chắc chắn
        add_action('wp_enqueue_scripts', array($this, 'remove_duplicate_recaptcha'), 5);
        add_action('wp_print_scripts', array($this, 'remove_duplicate_recaptcha_late'), 999);
        add_filter('script_loader_tag', array($this, 'filter_recaptcha_tags'), 10, 3);
        add_action('wp_head', array($this, 'block_inline_recaptcha'), 1);
        
        // Phase 3: Route optimization  
        add_action('wp', array($this, 'setup_route_optimization'), 1);
        
        // Phase 4: Cart/Checkout specific
        add_action('wp_enqueue_scripts', array($this, 'optimize_woocommerce_assets'), 999);
        
        // Logging
        add_action('wp_footer', array($this, 'output_debug_info'), 9999);
    }
    
    /**
     * Fix 404 errors early
     */
    public function fix_404_errors_early() {
        // Force fix font path
        add_filter('theme_mod_custom_css', function($css) {
            $css .= '
            /* Fix font 404 */
            @font-face {
                font-family: "main-font";
                src: url("' . get_stylesheet_directory_uri() . '/assets/fonts/main-font.woff") format("woff");
                font-display: swap;
            }';
            return $css;
        });
    }
    
    /**
     * Fix resource paths to prevent 404s
     */
    public function fix_resource_paths($src, $handle) {
        if (empty($src)) {
            return $src;
        }
        
        // Fix main-font.woff2
        if (strpos($src, 'main-font.woff2') !== false) {
            // Check multiple possible locations
            $possible_paths = array(
                get_stylesheet_directory() . '/assets/fonts/main-font.woff2',
                get_stylesheet_directory() . '/assets/fonts/main-font.woff',
                get_template_directory() . '/assets/fonts/main-font.woff',
                get_stylesheet_directory() . '/fonts/main-font.woff'
            );
            
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $url = str_replace(ABSPATH, site_url('/'), $path);
                    $this->log_fix("Fixed font path: $handle -> $url");
                    return $url;
                }
            }
            
            // If not found, remove it
            $this->log_fix("Removed missing font: $handle");
            return false;
        }
        
        // Fix style.min.css
        if (strpos($src, 'style.min.css') !== false && strpos($src, 'elessi-theme') !== false) {
            $non_min = str_replace('style.min.css', 'style.css', $src);
            $path = str_replace(site_url('/'), ABSPATH, strtok($non_min, '?'));
            
            if (file_exists($path)) {
                $this->log_fix("Fixed minified CSS to non-minified: $handle");
                return $non_min;
            }
        }
        
        // Fix Elementor Google Fonts
        if (strpos($src, 'uploads/elementor/google-fonts') !== false) {
            $path = str_replace(site_url('/'), ABSPATH, strtok($src, '?'));
            if (!file_exists($path)) {
                $this->log_fix("Removed missing Elementor font: $handle");
                return false;
            }
        }
        
        return $src;
    }
    
    /**
     * Remove duplicate reCAPTCHA - Early phase
     */
    public function remove_duplicate_recaptcha() {
        global $wp_scripts;
        
        $recaptcha_found = false;
        $valid_handle = null;
        
        // Find and keep only first valid reCAPTCHA
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!isset($script->src)) continue;
            
            if (strpos($script->src, 'google.com/recaptcha') !== false || 
                strpos($script->src, 'gstatic.com/recaptcha') !== false) {
                
                if (!$recaptcha_found) {
                    $recaptcha_found = true;
                    $valid_handle = $handle;
                    $this->log_fix("Keeping reCAPTCHA: $handle");
                } else {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Removed duplicate reCAPTCHA: $handle");
                }
            }
        }
    }
    
    /**
     * Remove duplicate reCAPTCHA - Late phase
     */
    public function remove_duplicate_recaptcha_late() {
        global $wp_scripts;
        
        // Second pass to catch late additions
        $recaptcha_urls = array();
        
        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle])) continue;
            
            $src = $wp_scripts->registered[$handle]->src;
            if (empty($src)) continue;
            
            // Check if it's reCAPTCHA
            if (strpos($src, 'recaptcha') !== false || strpos($src, 'gstatic.com') !== false) {
                $clean_url = strtok($src, '?');
                
                if (in_array($clean_url, $recaptcha_urls)) {
                    wp_dequeue_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Late removal of duplicate reCAPTCHA: $handle");
                } else {
                    $recaptcha_urls[] = $clean_url;
                }
            }
        }
    }
    
    /**
     * Filter script tags to prevent duplicate reCAPTCHA
     */
    public function filter_recaptcha_tags($tag, $handle, $src) {
        static $recaptcha_loaded = false;
        
        if (strpos($src, 'recaptcha') !== false || strpos($src, 'gstatic.com') !== false) {
            if ($recaptcha_loaded) {
                $this->log_fix("Blocked duplicate reCAPTCHA tag: $handle");
                return '<!-- Blocked duplicate reCAPTCHA: ' . esc_html($handle) . ' -->';
            }
            $recaptcha_loaded = true;
        }
        
        return $tag;
    }
    
    /**
     * Block inline reCAPTCHA scripts
     */
    public function block_inline_recaptcha() {
        ob_start(function($buffer) {
            // Count reCAPTCHA occurrences
            $count = substr_count($buffer, 'grecaptcha');
            if ($count > 1) {
                // Keep only first occurrence
                $pos = strpos($buffer, 'grecaptcha');
                $buffer = substr($buffer, 0, $pos + 10) . 
                         preg_replace('/grecaptcha/', 'grecaptcha_blocked', substr($buffer, $pos + 10));
                $this->log_fix("Blocked " . ($count - 1) . " inline reCAPTCHA scripts");
            }
            return $buffer;
        });
    }
    
    /**
     * Setup route-based optimization
     */
    public function setup_route_optimization() {
        // Cart page optimization
        if (is_cart()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_cart_page'), 1000);
        }
        
        // Checkout page optimization  
        if (is_checkout()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_checkout_page'), 1000);
        }
        
        // Contact page
        if (is_page('contact')) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_contact_page'), 1000);
        }
    }
    
    /**
     * Optimize Cart page
     */
    public function optimize_cart_page() {
        // Remove duplicate WooCommerce scripts
        $this->remove_duplicate_woo_scripts();
        
        // Remove unnecessary scripts
        $unnecessary = array(
            'wc-add-to-cart-variation',
            'wc-single-product',
            'zoom',
            'flexslider',
            'photoswipe'
        );
        
        foreach ($unnecessary as $handle) {
            wp_dequeue_script($handle);
            $this->log_fix("Removed from cart: $handle");
        }
    }
    
    /**
     * Optimize Checkout page
     */
    public function optimize_checkout_page() {
        // Remove duplicate WooCommerce scripts
        $this->remove_duplicate_woo_scripts();
        
        // Keep payment scripts but remove others
        $unnecessary = array(
            'wc-single-product',
            'zoom',
            'flexslider',
            'photoswipe',
            'yith-woocompare-main'
        );
        
        foreach ($unnecessary as $handle) {
            wp_dequeue_script($handle);
            $this->log_fix("Removed from checkout: $handle");
        }
    }
    
    /**
     * Optimize Contact page
     */
    public function optimize_contact_page() {
        // Remove all WooCommerce scripts except cart fragments
        global $wp_scripts;
        
        foreach ($wp_scripts->queue as $handle) {
            if (strpos($handle, 'wc-') === 0 && $handle !== 'wc-cart-fragments') {
                wp_dequeue_script($handle);
                $this->log_fix("Removed from contact: $handle");
            }
        }
    }
    
    /**
     * Remove duplicate WooCommerce scripts
     */
    private function remove_duplicate_woo_scripts() {
        global $wp_scripts;
        
        $woo_scripts = array();
        
        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle])) continue;
            
            $src = $wp_scripts->registered[$handle]->src;
            if (strpos($src, '/woocommerce/assets/') !== false) {
                $filename = basename($src);
                
                if (isset($woo_scripts[$filename])) {
                    wp_dequeue_script($handle);
                    $this->log_fix("Removed duplicate WooCommerce script: $handle");
                } else {
                    $woo_scripts[$filename] = $handle;
                }
            }
        }
    }
    
    /**
     * Optimize WooCommerce assets globally
     */
    public function optimize_woocommerce_assets() {
        // Combine small WooCommerce CSS files
        if (defined('WC_VERSION')) {
            add_filter('woocommerce_enqueue_styles', array($this, 'optimize_woo_styles'));
        }
    }
    
    /**
     * Optimize WooCommerce styles
     */
    public function optimize_woo_styles($styles) {
        // Remove small CSS files on non-WooCommerce pages
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            unset($styles['woocommerce-general']);
            unset($styles['woocommerce-layout']);
            unset($styles['woocommerce-smallscreen']);
            $this->log_fix("Removed WooCommerce styles on non-WC page");
        }
        
        return $styles;
    }
    
    /**
     * Debug logging
     */
    private $fixes_log = array();
    
    private function log_fix($message) {
        $this->fixes_log[] = $message;
    }
    
    /**
     * Output debug info
     */
    public function output_debug_info() {
        if (!empty($this->fixes_log) && current_user_can('manage_options')) {
            echo "\n<!-- Vidieu Dup Requests Guard V2 Log:\n";
            echo "Total fixes: " . count($this->fixes_log) . "\n";
            echo "Removed scripts: " . count($this->removed_scripts) . "\n";
            echo "Removed styles: " . count($this->removed_styles) . "\n\n";
            
            foreach ($this->fixes_log as $log) {
                echo " - " . esc_html($log) . "\n";
            }
            echo "-->\n";
        }
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2::get_instance();
}, 1);