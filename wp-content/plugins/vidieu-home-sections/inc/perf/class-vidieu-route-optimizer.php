<?php
/**
 * Vidieu Route-based Optimizer
 * 
 * Conditionally loads features based on current route
 * Only active when VIDIEU_PERF_ROUTE_CONDITIONALS flag is enabled
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Route_Optimizer {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Current route type
     */
    private $route_type = '';
    
    /**
     * Route detection patterns
     */
    private $route_patterns = [
        'home' => ['is_front_page', 'is_home'],
        'shop' => ['is_shop', 'is_product_category', 'is_product_tag'],
        'product' => ['is_product'],
        'cart' => ['is_cart'],
        'checkout' => ['is_checkout'],
        'account' => ['is_account_page'],
        'blog' => ['is_archive', 'is_category', 'is_tag', 'is_single'],
        'contact' => ['is_page']
    ];
    
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
        if (!defined('VIDIEU_PERF_ROUTE_CONDITIONALS') || !VIDIEU_PERF_ROUTE_CONDITIONALS) {
            return;
        }
        
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Early hooks for optimization
        add_action('after_setup_theme', [$this, 'early_optimizations'], 5);
        add_action('init', [$this, 'init_optimizations'], 5);
        add_action('template_redirect', [$this, 'detect_route'], 1);
        add_action('template_redirect', [$this, 'apply_route_optimizations'], 2);
    }
    
    /**
     * Early optimizations before most plugins load
     */
    public function early_optimizations() {
        // Disable emoji support on non-content pages
        add_action('init', function() {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/checkout') !== false || 
                strpos($uri, '/cart') !== false || 
                strpos($uri, '/my-account') !== false) {
                remove_action('wp_head', 'print_emoji_detection_script', 7);
                remove_action('wp_print_styles', 'print_emoji_styles');
                remove_action('admin_print_scripts', 'print_emoji_detection_script');
                remove_action('admin_print_styles', 'print_emoji_styles');
            }
        }, 1);
    }
    
    /**
     * Initialize optimizations
     */
    public function init_optimizations() {
        // Conditionally disable WooCommerce features on non-commerce pages
        if (!$this->is_commerce_page()) {
            // Remove WooCommerce generator tag
            remove_action('wp_head', 'wc_generator_tag');
            
            // Remove cart fragments on non-commerce pages
            add_action('wp_enqueue_scripts', [$this, 'dequeue_wc_cart_fragments'], 11);
            
            // Disable WooCommerce widgets init on non-commerce pages
            remove_action('widgets_init', 'woocommerce_register_widgets');
        }
        
        // Optimize query vars on specific routes
        add_filter('request', [$this, 'optimize_query_vars']);
    }
    
    /**
     * Check if current request is a commerce page
     */
    private function is_commerce_page() {
        $uri = $_SERVER['REQUEST_URI'];
        $commerce_patterns = ['/cart', '/checkout', '/my-account', '/product', '/shop', '/san-pham'];
        
        foreach ($commerce_patterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect current route type
     */
    public function detect_route() {
        foreach ($this->route_patterns as $type => $conditions) {
            foreach ($conditions as $condition) {
                if (function_exists($condition) && call_user_func($condition)) {
                    $this->route_type = $type;
                    
                    // Special handling for contact page
                    if ($type === 'contact' && is_page()) {
                        $page = get_queried_object();
                        if ($page && ($page->post_name === 'contact' || $page->post_name === 'lien-he')) {
                            $this->route_type = 'contact';
                        } else {
                            $this->route_type = 'page';
                        }
                    }
                    
                    break 2;
                }
            }
        }
    }
    
    /**
     * Apply route-specific optimizations
     */
    public function apply_route_optimizations() {
        switch ($this->route_type) {
            case 'home':
                $this->optimize_homepage();
                break;
                
            case 'shop':
            case 'product':
                $this->optimize_shop_pages();
                break;
                
            case 'cart':
            case 'checkout':
                $this->optimize_checkout_pages();
                break;
                
            case 'blog':
                $this->optimize_blog_pages();
                break;
                
            case 'contact':
                $this->optimize_contact_page();
                break;
                
            case 'account':
                $this->optimize_account_pages();
                break;
        }
        
        // Common optimizations for all routes
        $this->apply_common_optimizations();
    }
    
    /**
     * Homepage optimizations
     */
    private function optimize_homepage() {
        // Disable unnecessary queries for homepage
        add_filter('posts_request', [$this, 'optimize_homepage_queries'], 10, 2);
        
        // Remove unnecessary widgets
        add_filter('sidebars_widgets', [$this, 'optimize_homepage_widgets']);
    }
    
    /**
     * Shop/Product page optimizations
     */
    private function optimize_shop_pages() {
        // Pre-cache commonly used product data
        add_action('wp', [$this, 'precache_product_data']);
    }
    
    /**
     * Checkout page optimizations
     */
    private function optimize_checkout_pages() {
        // Remove unnecessary scripts
        add_action('wp_enqueue_scripts', [$this, 'optimize_checkout_scripts'], 100);
        
        // Streamline checkout queries
        add_filter('woocommerce_checkout_fields', [$this, 'optimize_checkout_fields'], 100);
    }
    
    /**
     * Blog page optimizations
     */
    private function optimize_blog_pages() {
        // Optimize post queries
        add_action('pre_get_posts', [$this, 'optimize_blog_queries']);
    }
    
    /**
     * Contact page optimizations
     */
    private function optimize_contact_page() {
        // Remove WooCommerce completely from contact page
        remove_action('wp_enqueue_scripts', 'woocommerce_frontend_scripts');
        
        // Remove unnecessary features
        add_action('wp_enqueue_scripts', [$this, 'minimize_contact_assets'], 100);
    }
    
    /**
     * Account page optimizations
     */
    private function optimize_account_pages() {
        // Cache user data
        add_action('wp', [$this, 'cache_user_data']);
    }
    
    /**
     * Common optimizations for all routes
     */
    private function apply_common_optimizations() {
        // Optimize options autoload
        add_filter('pre_option_active_plugins', [$this, 'optimize_active_plugins']);
        
        // Reduce unnecessary option queries
        add_filter('pre_update_option', [$this, 'prevent_unnecessary_updates'], 10, 3);
        
        // Optimize transient queries
        add_filter('pre_transient_timeout_', [$this, 'optimize_transient_timeout'], 10, 2);
    }
    
    /**
     * Dequeue WooCommerce cart fragments
     */
    public function dequeue_wc_cart_fragments() {
        if (!$this->is_commerce_page()) {
            wp_dequeue_script('wc-cart-fragments');
        }
    }
    
    /**
     * Optimize query vars
     */
    public function optimize_query_vars($query_vars) {
        // Reduce post types queried on non-relevant pages
        if (isset($query_vars['post_type']) && !is_admin()) {
            if ($this->route_type === 'blog' && is_array($query_vars['post_type'])) {
                $query_vars['post_type'] = array_intersect($query_vars['post_type'], ['post']);
            }
        }
        
        return $query_vars;
    }
    
    /**
     * Optimize homepage queries
     */
    public function optimize_homepage_queries($request, $query) {
        if (is_front_page() && $query->is_main_query()) {
            // Cache the main query for homepage
            $cache_key = 'vidieu_homepage_query_' . md5($request);
            $cached = get_transient($cache_key);
            
            if (false === $cached) {
                set_transient($cache_key, $request, 300); // 5 minutes
            }
        }
        
        return $request;
    }
    
    /**
     * Optimize homepage widgets
     */
    public function optimize_homepage_widgets($sidebars_widgets) {
        if (is_front_page()) {
            // Remove heavy widgets from homepage if needed
            // This is a placeholder - implement based on actual widget analysis
        }
        
        return $sidebars_widgets;
    }
    
    /**
     * Pre-cache product data
     */
    public function precache_product_data() {
        if (is_product()) {
            global $product;
            if ($product) {
                // Warm up commonly used product data
                $product->get_price();
                $product->get_regular_price();
                $product->get_sale_price();
                $product->is_in_stock();
                $product->get_stock_quantity();
            }
        }
    }
    
    /**
     * Optimize checkout scripts
     */
    public function optimize_checkout_scripts() {
        // Remove non-essential scripts from checkout
        $scripts_to_remove = [
            'comment-reply',
            'wp-embed'
        ];
        
        foreach ($scripts_to_remove as $handle) {
            wp_dequeue_script($handle);
        }
    }
    
    /**
     * Optimize checkout fields
     */
    public function optimize_checkout_fields($fields) {
        // Cache field configuration
        $cache_key = 'vidieu_checkout_fields';
        $cached_fields = get_transient($cache_key);
        
        if (false === $cached_fields) {
            set_transient($cache_key, $fields, 3600); // 1 hour
        }
        
        return $fields;
    }
    
    /**
     * Optimize blog queries
     */
    public function optimize_blog_queries($query) {
        if (!is_admin() && $query->is_main_query() && (is_archive() || is_home())) {
            // Optimize the number of queries by setting specific fields
            $query->set('no_found_rows', true);
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
        }
    }
    
    /**
     * Minimize contact page assets
     */
    public function minimize_contact_assets() {
        // Get all registered scripts and styles
        global $wp_scripts, $wp_styles;
        
        // Essential scripts/styles for contact page
        $essential_scripts = ['jquery', 'jquery-core', 'jquery-migrate'];
        $essential_styles = ['nasa-style-css', 'nasa-style-rtl-css'];
        
        // Dequeue non-essential items
        if ($wp_scripts) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (!in_array($handle, $essential_scripts) && strpos($handle, 'contact-form') === false) {
                    wp_dequeue_script($handle);
                }
            }
        }
    }
    
    /**
     * Cache user data
     */
    public function cache_user_data() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $cache_key = 'vidieu_user_data_' . $user_id;
            
            $user_data = get_transient($cache_key);
            if (false === $user_data) {
                $user_data = [
                    'orders' => wc_get_orders(['customer_id' => $user_id, 'limit' => 5]),
                    'addresses' => [
                        'billing' => WC()->customer->get_billing(),
                        'shipping' => WC()->customer->get_shipping()
                    ]
                ];
                set_transient($cache_key, $user_data, 900); // 15 minutes
            }
        }
    }
    
    /**
     * Optimize active plugins option
     */
    public function optimize_active_plugins($plugins) {
        // Cache active plugins list
        $cache_key = 'vidieu_active_plugins_' . $this->route_type;
        $cached = wp_cache_get($cache_key, 'options');
        
        if (false === $cached) {
            wp_cache_set($cache_key, $plugins, 'options', 300);
            return $plugins;
        }
        
        return $cached;
    }
    
    /**
     * Prevent unnecessary option updates
     */
    public function prevent_unnecessary_updates($value, $option, $old_value) {
        // Skip update if value hasn't changed
        if ($value === $old_value) {
            return $old_value;
        }
        
        return $value;
    }
    
    /**
     * Optimize transient timeouts
     */
    public function optimize_transient_timeout($value, $transient) {
        // Extend timeout for frequently accessed transients
        $frequent_transients = ['wc_', 'vidieu_'];
        
        foreach ($frequent_transients as $prefix) {
            if (strpos($transient, $prefix) === 0) {
                return max($value, 300); // Minimum 5 minutes
            }
        }
        
        return $value;
    }
}

// Initialize optimizer
Vidieu_Route_Optimizer::get_instance();