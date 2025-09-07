<?php
/**
 * Vidieu Policy Engine - Route-based Script/Style Management
 * 
 * Intelligent policy system that preserves functionality while optimizing performance
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Policy_Engine {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Policy modes
     */
    const MODE_LEARNING = 'learning';
    const MODE_ALLOWLIST = 'allowlist';
    const MODE_DENYLIST = 'denylist';
    const MODE_DISABLED = 'disabled';
    
    /**
     * Current mode
     */
    private $mode = self::MODE_LEARNING;
    
    /**
     * Learning data
     */
    private $learning_data = array();
    
    /**
     * Route policies
     */
    private $policies = array();
    
    /**
     * Global required handles - NEVER block these
     */
    private $global_required = array(
        // Core WordPress/jQuery
        'jquery', 'jquery-core', 'jquery-migrate',
        'underscore', 'backbone', 'wp-util',
        'wp-i18n', 'wp-hooks', 'wp-polyfill',
        
        // WooCommerce Core
        'woocommerce', 'wc-cart', 'wc-checkout',
        'wc-add-to-cart', 'wc-cart-fragments',
        'wc-country-select', 'wc-address-i18n',
        'wc-password-strength-meter',
        
        // Payment Critical - NEVER block
        'vcb-mh', 'vcb-mh-public', 'vcb-gateway',
        'sweetalert2', 'swal2', 'sweet-alert',
        
        // Theme Core
        'elessi-theme-js', 'elessi-theme-css'
    );
    
    /**
     * Route-specific required handles
     */
    private $route_required = array(
        'cart' => array(
            // Cart functionality
            'wc-add-to-cart-variation',
            'selectWoo', 'select2',
            
            // Image/Gallery - MUST NOT BLOCK
            'magnific-popup', 'jquery-magnific-popup',
            'owl-carousel', 'slick', 'slick-slider',
            'jquery-easyzoom', 'zoom',
            'lazy', 'lazyload', 'unveil',
            
            // Theme specific
            'nasa-core-js', 'nasa-single-product'
        ),
        
        'checkout' => array(
            // Checkout core
            'wc-checkout', 'jquery-blockui',
            'jquery-payment', 'wc-credit-card-form',
            'jquery-validate',
            
            // Payment gateways
            'stripe', 'wc-stripe-checkout',
            'paypal-checkout', 'square-payments'
        ),
        
        'order-received' => array(
            // CRITICAL - Payment confirmation
            'vcb-mh', 'vcb-mh-public',
            'sweetalert2', 'swal2',
            'accounting', 'round',
            
            // Analytics/Tracking
            'google-analytics', 'ga',
            'facebook-pixel', 'gtag'
        ),
        
        'product' => array(
            // Product functionality
            'wc-single-product',
            'wc-add-to-cart-variation',
            'flexslider', 'photoswipe',
            'zoom', 'magnific-popup',
            
            // Reviews
            'comment-reply',
            'wp-mediaelement'
        )
    );
    
    /**
     * Known bloat/duplicate patterns
     */
    private $denylist_patterns = array(
        // Builders (unless on specific pages)
        'elementor' => array('except' => array('elementor-preview')),
        'beaver-builder' => array('except' => array('fl_builder')),
        'divi' => array('except' => array('et_fb')),
        
        // Sliders (unless needed)
        'revslider' => array('except' => array('home')),
        'layerslider' => array('except' => array('home')),
        
        // Social (defer to footer)
        'instagram-feed' => array('defer' => true),
        'facebook-sdk' => array('defer' => true),
        
        // Unnecessary on most pages
        'wp-embed' => array('except' => array('single')),
        'comment-reply' => array('except' => array('single', 'product')),
        
        // Font duplicates
        'font-awesome-4' => array('if_exists' => 'font-awesome-5'),
        'fontawesome-4' => array('if_exists' => 'fontawesome-5')
    );
    
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
        // Get mode from constant or option
        $this->mode = defined('VIDIEU_PERF_POLICY_MODE') 
            ? VIDIEU_PERF_POLICY_MODE 
            : get_option('vidieu_perf_policy_mode', self::MODE_LEARNING);
        
        // Initialize
        add_action('init', array($this, 'init'), 1);
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Disabled mode - do nothing
        if ($this->mode === self::MODE_DISABLED) {
            return;
        }
        
        // Load existing policies
        $this->load_policies();
        
        // Hook into WordPress
        add_action('wp_enqueue_scripts', array($this, 'apply_policy'), 9999);
        add_action('wp_print_scripts', array($this, 'late_policy_check'), 9999);
        add_action('wp_print_styles', array($this, 'late_style_check'), 9999);
        
        // Learning mode hooks
        if ($this->mode === self::MODE_LEARNING) {
            add_action('wp_print_scripts', array($this, 'learn_scripts'), 1);
            add_action('wp_print_styles', array($this, 'learn_styles'), 1);
            add_action('wp_footer', array($this, 'save_learning_data'), 9999);
        }
        
        // Re-init after AJAX
        add_action('wp_footer', array($this, 'inject_ajax_reinit'), 999);
        
        // Admin notice
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'show_policy_status'), 9999);
        }
    }
    
    /**
     * Get current route
     */
    private function get_current_route() {
        if (is_front_page() || is_home()) {
            return 'home';
        } elseif (is_product()) {
            return 'product';
        } elseif (is_cart()) {
            return 'cart';
        } elseif (is_checkout() && !is_wc_endpoint_url('order-received')) {
            return 'checkout';
        } elseif (is_wc_endpoint_url('order-received')) {
            return 'order-received';
        } elseif (is_shop() || is_product_category() || is_product_tag()) {
            return 'shop';
        } elseif (is_single()) {
            return 'single';
        } elseif (is_page()) {
            return 'page';
        }
        return 'other';
    }
    
    /**
     * Check if handle is globally required
     */
    private function is_globally_required($handle) {
        // Check exact match
        if (in_array($handle, $this->global_required)) {
            return true;
        }
        
        // Check patterns (e.g., vcb-mh-*)
        foreach ($this->global_required as $pattern) {
            if (strpos($handle, $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if handle is route required
     */
    private function is_route_required($handle, $route) {
        if (!isset($this->route_required[$route])) {
            return false;
        }
        
        // Check exact match
        if (in_array($handle, $this->route_required[$route])) {
            return true;
        }
        
        // Check patterns
        foreach ($this->route_required[$route] as $pattern) {
            if (strpos($handle, $pattern) === 0 || strpos($handle, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Apply policy based on mode
     */
    public function apply_policy() {
        $route = $this->get_current_route();
        
        // Learning mode - don't block anything
        if ($this->mode === self::MODE_LEARNING) {
            return;
        }
        
        // Get scripts and styles
        global $wp_scripts, $wp_styles;
        
        if ($this->mode === self::MODE_ALLOWLIST) {
            $this->apply_allowlist_policy($route);
        } elseif ($this->mode === self::MODE_DENYLIST) {
            $this->apply_denylist_policy($route);
        }
    }
    
    /**
     * Apply allowlist policy
     */
    private function apply_allowlist_policy($route) {
        global $wp_scripts, $wp_styles;
        
        // Get allowed handles for this route
        $allowed_scripts = isset($this->policies[$route]['scripts']) 
            ? $this->policies[$route]['scripts'] 
            : array();
        
        $allowed_styles = isset($this->policies[$route]['styles']) 
            ? $this->policies[$route]['styles'] 
            : array();
        
        // Process scripts
        foreach ($wp_scripts->queue as $handle) {
            // Never block globally required
            if ($this->is_globally_required($handle)) {
                continue;
            }
            
            // Never block route required
            if ($this->is_route_required($handle, $route)) {
                continue;
            }
            
            // Check if in allowlist
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
            }
        }
        
        // Process styles
        foreach ($wp_styles->queue as $handle) {
            // Never block globally required
            if ($this->is_globally_required($handle)) {
                continue;
            }
            
            // Never block route required
            if ($this->is_route_required($handle, $route)) {
                continue;
            }
            
            // Check if in allowlist
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
            }
        }
    }
    
    /**
     * Apply denylist policy
     */
    private function apply_denylist_policy($route) {
        global $wp_scripts, $wp_styles;
        
        foreach ($this->denylist_patterns as $pattern => $rules) {
            // Check exceptions
            if (isset($rules['except']) && in_array($route, $rules['except'])) {
                continue;
            }
            
            // Check conditional blocking
            if (isset($rules['if_exists'])) {
                $exists = false;
                foreach ($wp_scripts->registered as $h => $s) {
                    if (strpos($h, $rules['if_exists']) !== false) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) continue;
            }
            
            // Block matching handles
            foreach ($wp_scripts->queue as $handle) {
                if (strpos($handle, $pattern) !== false && !$this->is_globally_required($handle)) {
                    if (isset($rules['defer']) && $rules['defer']) {
                        // Move to footer instead of blocking
                        if (isset($wp_scripts->registered[$handle])) {
                            $wp_scripts->registered[$handle]->args = 1; // in_footer = true
                        }
                    } else {
                        wp_dequeue_script($handle);
                    }
                }
            }
            
            // Same for styles
            foreach ($wp_styles->queue as $handle) {
                if (strpos($handle, $pattern) !== false && !$this->is_globally_required($handle)) {
                    wp_dequeue_style($handle);
                }
            }
        }
    }
    
    /**
     * Learning mode - capture scripts
     */
    public function learn_scripts() {
        if ($this->mode !== self::MODE_LEARNING) {
            return;
        }
        
        global $wp_scripts;
        $route = $this->get_current_route();
        
        $this->learning_data[$route]['scripts'] = array();
        
        foreach ($wp_scripts->queue as $handle) {
            $script = $wp_scripts->registered[$handle] ?? null;
            if ($script) {
                $this->learning_data[$route]['scripts'][$handle] = array(
                    'src' => $script->src ?? '',
                    'deps' => $script->deps ?? array(),
                    'ver' => $script->ver ?? '',
                    'args' => $script->args ?? null,
                    'extra' => $script->extra ?? array()
                );
            }
        }
    }
    
    /**
     * Learning mode - capture styles
     */
    public function learn_styles() {
        if ($this->mode !== self::MODE_LEARNING) {
            return;
        }
        
        global $wp_styles;
        $route = $this->get_current_route();
        
        $this->learning_data[$route]['styles'] = array();
        
        foreach ($wp_styles->queue as $handle) {
            $style = $wp_styles->registered[$handle] ?? null;
            if ($style) {
                $this->learning_data[$route]['styles'][$handle] = array(
                    'src' => $style->src ?? '',
                    'deps' => $style->deps ?? array(),
                    'ver' => $style->ver ?? '',
                    'args' => $style->args ?? 'all',
                    'extra' => $style->extra ?? array()
                );
            }
        }
    }
    
    /**
     * Save learning data
     */
    public function save_learning_data() {
        if ($this->mode !== self::MODE_LEARNING || empty($this->learning_data)) {
            return;
        }
        
        $route = $this->get_current_route();
        $upload_dir = wp_upload_dir();
        $perf_dir = $upload_dir['basedir'] . '/vidieu-perf';
        
        // Create directory if not exists
        if (!file_exists($perf_dir)) {
            wp_mkdir_p($perf_dir);
        }
        
        // Save route-specific data
        $filename = $perf_dir . '/policy-learning-' . $route . '.json';
        $existing = file_exists($filename) ? json_decode(file_get_contents($filename), true) : array();
        
        // Merge with existing
        if (isset($existing[$route])) {
            $this->learning_data[$route] = array_merge_recursive($existing[$route], $this->learning_data[$route]);
        }
        
        file_put_contents($filename, json_encode($this->learning_data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load policies
     */
    private function load_policies() {
        $upload_dir = wp_upload_dir();
        $policy_file = $upload_dir['basedir'] . '/vidieu-perf/policies.json';
        
        if (file_exists($policy_file)) {
            $this->policies = json_decode(file_get_contents($policy_file), true);
        }
    }
    
    /**
     * Late check for scripts
     */
    public function late_policy_check() {
        // Quick safety check for critical pages
        if (is_wc_endpoint_url('order-received')) {
            $this->ensure_payment_scripts();
        } elseif (is_cart()) {
            $this->ensure_cart_scripts();
        }
    }
    
    /**
     * Ensure payment scripts are loaded
     */
    private function ensure_payment_scripts() {
        global $wp_scripts;
        
        // Critical payment scripts
        $payment_scripts = array('vcb-mh', 'vcb-mh-public', 'sweetalert2', 'swal2');
        
        foreach ($payment_scripts as $handle) {
            // Check if dequeued and re-enqueue
            if (!in_array($handle, $wp_scripts->queue) && isset($wp_scripts->registered[$handle])) {
                wp_enqueue_script($handle);
            }
        }
    }
    
    /**
     * Ensure cart functionality scripts
     */
    private function ensure_cart_scripts() {
        global $wp_scripts;
        
        // Critical cart scripts
        $cart_scripts = array(
            'wc-cart', 'wc-cart-fragments', 
            'magnific-popup', 'jquery-magnific-popup'
        );
        
        foreach ($cart_scripts as $handle) {
            if (!in_array($handle, $wp_scripts->queue) && isset($wp_scripts->registered[$handle])) {
                wp_enqueue_script($handle);
            }
        }
    }
    
    /**
     * Inject AJAX re-initialization
     */
    public function inject_ajax_reinit() {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        ?>
        <script id="vidieu-ajax-reinit">
        jQuery(function($) {
            // Re-init after WooCommerce AJAX
            $(document.body).on('updated_wc_div updated_cart_totals', function() {
                // Re-init lightbox
                if (typeof $.fn.magnificPopup !== 'undefined') {
                    $('.product-lightbox, .zoom').magnificPopup({
                        type: 'image',
                        gallery: { enabled: true }
                    });
                }
                
                // Re-init lazy load
                if (typeof $.fn.unveil !== 'undefined') {
                    $('img[data-src]').unveil(200);
                } else if (typeof LazyLoad !== 'undefined') {
                    new LazyLoad();
                }
                
                // Re-init zoom
                if (typeof $.fn.easyzoom !== 'undefined') {
                    $('.easyzoom').easyzoom();
                }
                
                // Trigger custom event
                $(document.body).trigger('vidieu_ajax_reinit');
            });
            
            // MutationObserver fallback
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            $(document.body).trigger('vidieu_dom_updated');
                        }
                    });
                });
                
                observer.observe(document.querySelector('.woocommerce'), {
                    childList: true,
                    subtree: true
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Show policy status for admins
     */
    public function show_policy_status() {
        $route = $this->get_current_route();
        ?>
        <!-- Vidieu Policy Engine Status -->
        <!-- Mode: <?php echo esc_html($this->mode); ?> -->
        <!-- Route: <?php echo esc_html($route); ?> -->
        <!-- Global Required: <?php echo count($this->global_required); ?> handles -->
        <!-- Route Required: <?php echo isset($this->route_required[$route]) ? count($this->route_required[$route]) : 0; ?> handles -->
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Policy_Engine::get_instance();
}, 1);