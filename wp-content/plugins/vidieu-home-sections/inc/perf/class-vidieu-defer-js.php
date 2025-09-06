<?php
/**
 * Vidieu JavaScript Optimization
 * 
 * Defer/async non-critical scripts to reduce TBT and improve INP
 * Only active when VIDIEU_PERF_DEFER_JS flag is enabled
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Defer_JS {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Critical scripts that should NOT be deferred
     * These scripts are essential for initial functionality
     */
    private $critical_scripts = [
        'jquery-core',
        'jquery-migrate',
        'jquery',
        'wp-polyfill',
        'wp-hooks',
        'wp-i18n',
        'underscore',
        'backbone',
    ];
    
    /**
     * Scripts that should be loaded with async (independent scripts)
     */
    private $async_scripts = [
        'google-analytics',
        'gtag',
        'facebook-pixel',
        'tiktok-pixel',
        'hotjar',
        'clarity',
    ];
    
    /**
     * WooCommerce critical scripts (for cart/checkout)
     */
    private $wc_critical = [
        'wc-checkout',
        'wc-cart',
        'wc-cart-fragments',
        'wc-add-to-cart',
        'wc-add-to-cart-variation',
        'woocommerce',
        'wc-single-product',
        'selectWoo',
        'wc-country-select',
        'wc-address-i18n',
    ];
    
    /**
     * Payment gateway scripts (only for checkout)
     */
    private $payment_scripts = [
        'stripe',
        'stripe-js',
        'paypal-checkout',
        'square-payment',
        'wc-stripe-payment-request',
    ];
    
    /**
     * Current route type
     */
    private $current_route = '';
    
    /**
     * Get singleton instance
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
        // Only run if flag is enabled and on frontend
        if (!defined('VIDIEU_PERF_DEFER_JS') || !VIDIEU_PERF_DEFER_JS) {
            return;
        }
        
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Initialize hooks
        add_action('init', [$this, 'init_optimization'], 1);
        add_action('template_redirect', [$this, 'detect_route'], 1);
        
        // Script optimization
        add_filter('script_loader_tag', [$this, 'optimize_script_loading'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'conditionally_dequeue_scripts'], 100);
        
        // Prevent certain scripts from loading on non-relevant pages
        add_action('wp_enqueue_scripts', [$this, 'route_based_enqueue'], 99);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        // Add inline script to handle deferred script execution
        add_action('wp_head', [$this, 'add_defer_helper'], 1);
    }
    
    /**
     * Add helper script for deferred execution
     */
    public function add_defer_helper() {
        ?>
        <script>
        // Vidieu defer helper - ensures proper execution order
        window.vidieuDeferredScripts = [];
        window.vidieuScriptsLoaded = false;
        document.addEventListener('DOMContentLoaded', function() {
            window.vidieuScriptsLoaded = true;
            // Trigger any waiting scripts
            if (window.vidieuDeferredScripts.length > 0) {
                window.vidieuDeferredScripts.forEach(function(fn) {
                    if (typeof fn === 'function') fn();
                });
                window.vidieuDeferredScripts = [];
            }
        });
        </script>
        <?php
    }
    
    /**
     * Detect current route
     */
    public function detect_route() {
        if (is_front_page() || is_home()) {
            $this->current_route = 'home';
        } elseif (function_exists('is_shop') && is_shop()) {
            $this->current_route = 'shop';
        } elseif (function_exists('is_product') && is_product()) {
            $this->current_route = 'product';
        } elseif (function_exists('is_cart') && is_cart()) {
            $this->current_route = 'cart';
        } elseif (function_exists('is_checkout') && is_checkout()) {
            $this->current_route = 'checkout';
        } elseif (function_exists('is_account_page') && is_account_page()) {
            $this->current_route = 'account';
        } elseif (is_single() && get_post_type() === 'post') {
            $this->current_route = 'post';
        } elseif (is_page()) {
            $page = get_queried_object();
            if ($page && ($page->post_name === 'contact' || $page->post_name === 'lien-he')) {
                $this->current_route = 'contact';
            } else {
                $this->current_route = 'page';
            }
        } else {
            $this->current_route = 'other';
        }
    }
    
    /**
     * Optimize script loading with defer/async
     */
    public function optimize_script_loading($tag, $handle, $src) {
        // Skip if already has defer or async
        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }
        
        // Skip inline scripts
        if (empty($src)) {
            return $tag;
        }
        
        // Check if script should be async
        if ($this->should_async($handle)) {
            return str_replace(' src=', ' async src=', $tag);
        }
        
        // Check if script should be deferred
        if ($this->should_defer($handle)) {
            return str_replace(' src=', ' defer src=', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Check if script should be loaded with async
     */
    private function should_async($handle) {
        // Analytics and tracking scripts can be async
        foreach ($this->async_scripts as $async_handle) {
            if (strpos($handle, $async_handle) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if script should be deferred
     */
    private function should_defer($handle) {
        // Never defer critical scripts
        if (in_array($handle, $this->critical_scripts)) {
            return false;
        }
        
        // Never defer WooCommerce critical on commerce pages
        if ($this->is_commerce_page() && in_array($handle, $this->wc_critical)) {
            return false;
        }
        
        // Never defer payment scripts on checkout
        if ($this->current_route === 'checkout' && in_array($handle, $this->payment_scripts)) {
            return false;
        }
        
        // Check for specific patterns that should not be deferred
        $no_defer_patterns = [
            'jquery-ui',
            'wc-checkout',
            'wc-cart',
            'stripe',
            'paypal',
            'admin-bar',
            'wp-embed',
        ];
        
        foreach ($no_defer_patterns as $pattern) {
            if (strpos($handle, $pattern) !== false) {
                return false;
            }
        }
        
        // Default: defer non-critical scripts
        return true;
    }
    
    /**
     * Check if current page is commerce-related
     */
    private function is_commerce_page() {
        return in_array($this->current_route, ['shop', 'product', 'cart', 'checkout', 'account']);
    }
    
    /**
     * Route-based script enqueue/dequeue
     */
    public function route_based_enqueue() {
        // Skip if WooCommerce not active
        if (!function_exists('is_woocommerce')) {
            return;
        }
        
        // Non-commerce pages: dequeue heavy WooCommerce scripts
        if (!$this->is_commerce_page()) {
            $scripts_to_remove = [
                'wc-cart-fragments',
                'wc-checkout',
                'wc-add-to-cart',
                'wc-add-to-cart-variation',
                'selectWoo',
                'wc-country-select',
                'wc-address-i18n',
                'woocommerce',
            ];
            
            foreach ($scripts_to_remove as $handle) {
                wp_dequeue_script($handle);
            }
            
            // Also remove WooCommerce styles on non-commerce pages
            $styles_to_remove = [
                'woocommerce-layout',
                'woocommerce-smallscreen',
                'woocommerce-general',
                'select2',
            ];
            
            foreach ($styles_to_remove as $handle) {
                wp_dequeue_style($handle);
            }
        }
        
        // Contact page: remove most scripts except essentials
        if ($this->current_route === 'contact') {
            $essential_scripts = [
                'jquery',
                'jquery-core',
                'jquery-migrate',
                'contact-form-7',
                'google-recaptcha',
            ];
            
            global $wp_scripts;
            if ($wp_scripts && isset($wp_scripts->registered)) {
                foreach ($wp_scripts->registered as $handle => $script) {
                    // Skip if essential or contains contact form patterns
                    if (!in_array($handle, $essential_scripts) && 
                        strpos($handle, 'contact') === false &&
                        strpos($handle, 'cf7') === false &&
                        strpos($handle, 'recaptcha') === false) {
                        wp_dequeue_script($handle);
                    }
                }
            }
        }
    }
    
    /**
     * Conditionally dequeue scripts based on route
     */
    public function conditionally_dequeue_scripts() {
        // Remove emoji scripts on all pages (they can be deferred)
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Remove wp-embed on non-post pages
        if (!is_singular('post')) {
            wp_dequeue_script('wp-embed');
        }
        
        // Remove comment-reply on pages without comments
        if (!is_singular() || !comments_open() || !get_option('thread_comments')) {
            wp_dequeue_script('comment-reply');
        }
        
        // Remove Block Library CSS on non-Gutenberg pages
        if (!has_blocks()) {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-blocks-style');
        }
    }
    
    /**
     * Get list of deferred scripts for debugging
     */
    public function get_deferred_scripts() {
        global $wp_scripts;
        $deferred = [];
        
        if ($wp_scripts && isset($wp_scripts->registered)) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if ($this->should_defer($handle)) {
                    $deferred[] = $handle;
                }
            }
        }
        
        return $deferred;
    }
}

// Initialize JavaScript optimization
Vidieu_Defer_JS::get_instance();