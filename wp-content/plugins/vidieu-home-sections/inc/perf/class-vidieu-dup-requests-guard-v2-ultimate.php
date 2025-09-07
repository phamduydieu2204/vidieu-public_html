<?php
/**
 * Vidieu Duplicate Requests Guard V2 - Ultimate Version
 * 
 * Based on HAR analysis with strict whitelist and enhanced logging
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2_Ultimate {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Feature flags
     */
    const ENABLE_BASIC_OPTIMIZATION = true;
    const ENABLE_NUCLEAR_RECAPTCHA = true;
    const ENABLE_CART_CHECKOUT_WHITELIST = true;
    const ENABLE_OUTPUT_BUFFERING = true;
    const ENABLE_ORDER_RECEIVED_OPT = true;
    
    /**
     * Emergency kill switch
     */
    const DISABLE_OPTIMIZATION = 'VIDIEU_DISABLE_DUP_OPTIMIZATION';
    
    /**
     * Valid reCAPTCHA key
     */
    const VALID_RECAPTCHA_KEY = '6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ';
    
    /**
     * Tracking arrays
     */
    private $removed_scripts = array();
    private $removed_styles = array();
    private $fixes_log = array();
    private $recaptcha_sources = array();
    private $recaptcha_count = 0;
    
    /**
     * Whitelist tracking
     */
    private $scripts_before_whitelist = 0;
    private $styles_before_whitelist = 0;
    private $scripts_after_whitelist = 0;
    private $styles_after_whitelist = 0;
    private $domain_requests = array();
    private $whitelist_applied = false;
    private $current_hook = '';
    private $current_priority = 0;
    
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
        if (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION) {
            return;
        }
        
        add_action('init', array($this, 'init_optimization'), 1);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        
        $this->log_fix('V2 Ultimate initialization started');
        
        // Basic optimization
        if (self::ENABLE_BASIC_OPTIMIZATION) {
            $this->setup_basic_optimization();
        }
        
        // Nuclear reCAPTCHA
        if (self::ENABLE_NUCLEAR_RECAPTCHA) {
            $this->setup_nuclear_recaptcha();
        }
        
        // Route-based optimization
        add_action('wp', array($this, 'setup_route_optimization'), 1);
        
        // Output buffering for cart/checkout/order-received
        if (self::ENABLE_OUTPUT_BUFFERING) {
            add_action('template_redirect', array($this, 'setup_output_buffering'), 1);
        }
        
        // Enhanced monitoring for admins
        add_action('wp_footer', array($this, 'output_enhanced_admin_log'), 9999);
    }
    
    /**
     * Setup route optimization
     */
    public function setup_route_optimization() {
        if (is_cart()) {
            // Multiple hooks for cart
            add_action('wp_enqueue_scripts', array($this, 'ultimate_cart_whitelist'), 9999);
            add_action('wp_print_scripts', array($this, 'late_cart_cleanup'), 1);
            add_action('wp_print_styles', array($this, 'late_cart_style_cleanup'), 1);
            add_action('wp_print_footer_scripts', array($this, 'final_cart_cleanup'), 9999);
            $this->log_fix('Cart optimization registered on 4 hooks');
            
        } elseif (is_checkout() && !is_wc_endpoint_url('order-received')) {
            // Multiple hooks for checkout
            add_action('wp_enqueue_scripts', array($this, 'ultimate_checkout_whitelist'), 9999);
            add_action('wp_print_scripts', array($this, 'late_checkout_cleanup'), 1);
            add_action('wp_print_styles', array($this, 'late_checkout_style_cleanup'), 1);
            add_action('wp_print_footer_scripts', array($this, 'final_checkout_cleanup'), 9999);
            $this->log_fix('Checkout optimization registered on 4 hooks');
            
        } elseif (is_wc_endpoint_url('order-received') && self::ENABLE_ORDER_RECEIVED_OPT) {
            // Order-received specific optimization
            add_action('wp_enqueue_scripts', array($this, 'ultimate_order_received_whitelist'), 9999);
            add_action('wp_print_scripts', array($this, 'late_order_received_cleanup'), 1);
            add_action('wp_print_styles', array($this, 'late_order_received_style_cleanup'), 1);
            $this->log_fix('Order-received optimization registered on 3 hooks');
        }
    }
    
    /**
     * ULTIMATE CART WHITELIST - Based on HAR analysis
     */
    public function ultimate_cart_whitelist() {
        global $wp_scripts, $wp_styles;
        
        $this->current_hook = 'wp_enqueue_scripts';
        $this->current_priority = 9999;
        $this->whitelist_applied = true;
        
        // Record before counts
        $this->scripts_before_whitelist = count($wp_scripts->queue);
        $this->styles_before_whitelist = count($wp_styles->queue);
        
        // STRICT Cart Scripts Whitelist - Target: <25 scripts
        $allowed_scripts = array(
            // Core (6)
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'jquery-blockui',
            'js-cookie',
            'underscore',
            
            // WooCommerce Cart Core (8)
            'woocommerce',
            'wc-add-to-cart',
            'wc-cart',
            'wc-cart-fragments',
            'selectWoo',
            'select2',
            'wc-country-select',
            'wc-address-i18n',
            
            // i18n & Utils (3)
            'wp-i18n',
            'wp-hooks',
            'wp-polyfill',
            
            // Theme Core Only (2)
            'elessi-theme-js',
            
            // AJAX Cart (1)
            'wc-add-to-cart-variation'
        );
        
        // Remove ALL scripts not in whitelist
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->removed_scripts[$handle] = $this->get_script_source($handle);
                $this->log_fix("Cart removed script: $handle");
            }
        }
        
        // STRICT Cart Styles Whitelist - Target: <10 styles
        $allowed_styles = array(
            // WooCommerce Core (3)
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            
            // Theme Core (2)
            'elessi-style',
            'elessi-style-css',
            
            // Select2 (1)
            'select2'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                $this->removed_styles[$handle] = $this->get_style_source($handle);
                $this->log_fix("Cart removed style: $handle");
            }
        }
        
        // Record after counts
        $this->scripts_after_whitelist = count($allowed_scripts);
        $this->styles_after_whitelist = count($allowed_styles);
        
        $this->log_fix("Cart whitelist applied - Scripts: {$this->scripts_before_whitelist} → {$this->scripts_after_whitelist}");
        $this->log_fix("Cart whitelist applied - Styles: {$this->styles_before_whitelist} → {$this->styles_after_whitelist}");
        
        // Block common unnecessary
        $this->block_common_unnecessary();
    }
    
    /**
     * ULTIMATE CHECKOUT WHITELIST - Based on HAR analysis
     */
    public function ultimate_checkout_whitelist() {
        global $wp_scripts, $wp_styles;
        
        $this->current_hook = 'wp_enqueue_scripts';
        $this->current_priority = 9999;
        $this->whitelist_applied = true;
        
        // Record before counts
        $this->scripts_before_whitelist = count($wp_scripts->queue);
        $this->styles_before_whitelist = count($wp_styles->queue);
        
        // STRICT Checkout Scripts Whitelist - Target: <30 scripts
        $allowed_scripts = array(
            // Core (7)
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'jquery-blockui',
            'js-cookie',
            'underscore',
            'wp-util',
            
            // WooCommerce Checkout Core (10)
            'woocommerce',
            'wc-checkout',
            'wc-cart-fragments',
            'selectWoo',
            'select2',
            'wc-country-select',
            'wc-address-i18n',
            'wc-password-strength-meter',
            'wc-credit-card-form',
            'jquery-payment',
            
            // i18n & Utils (3)
            'wp-i18n',
            'wp-hooks',
            'wp-polyfill',
            
            // Theme Core (2)
            'elessi-theme-js',
            
            // Validation (1)
            'jquery-validate'
        );
        
        // Add payment gateway scripts dynamically
        $chosen_gateway = WC()->session ? WC()->session->get('chosen_payment_method') : '';
        if ($chosen_gateway) {
            // Only add scripts for active payment method
            if (strpos($chosen_gateway, 'vietcombank') !== false) {
                $allowed_scripts[] = 'wc-vietcombank';
            } elseif (strpos($chosen_gateway, 'stripe') !== false) {
                $allowed_scripts[] = 'stripe';
                $allowed_scripts[] = 'wc-stripe-checkout';
            }
        }
        
        // Remove ALL scripts not in whitelist
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->removed_scripts[$handle] = $this->get_script_source($handle);
                $this->log_fix("Checkout removed script: $handle");
            }
        }
        
        // STRICT Checkout Styles Whitelist - Target: <10 styles
        $allowed_styles = array(
            // WooCommerce Core (3)
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            
            // Theme Core (2)
            'elessi-style',
            'elessi-style-css',
            
            // Select2 (1)
            'select2'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                $this->removed_styles[$handle] = $this->get_style_source($handle);
                $this->log_fix("Checkout removed style: $handle");
            }
        }
        
        // Record after counts
        $this->scripts_after_whitelist = count($allowed_scripts);
        $this->styles_after_whitelist = count($allowed_styles);
        
        $this->log_fix("Checkout whitelist applied - Scripts: {$this->scripts_before_whitelist} → {$this->scripts_after_whitelist}");
        $this->log_fix("Checkout whitelist applied - Styles: {$this->styles_before_whitelist} → {$this->styles_after_whitelist}");
        
        // Block common unnecessary
        $this->block_common_unnecessary();
    }
    
    /**
     * ULTIMATE ORDER-RECEIVED WHITELIST - New optimization
     */
    public function ultimate_order_received_whitelist() {
        global $wp_scripts, $wp_styles;
        
        $this->current_hook = 'wp_enqueue_scripts';
        $this->current_priority = 9999;
        $this->whitelist_applied = true;
        
        // Record before counts
        $this->scripts_before_whitelist = count($wp_scripts->queue);
        $this->styles_before_whitelist = count($wp_styles->queue);
        
        // STRICT Order-Received Scripts Whitelist - Target: <20 scripts
        $allowed_scripts = array(
            // Core (5)
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'js-cookie',
            'underscore',
            
            // WooCommerce Order (5)
            'woocommerce',
            'wc-cart-fragments',
            'wc-add-to-cart',
            'accounting',
            'round',
            
            // i18n (2)
            'wp-i18n',
            'wp-hooks',
            
            // Theme Core (1)
            'elessi-theme-js',
            
            // VCB-MH Payment Gateway (3)
            'vcb-mh-public',
            'sweetalert2',
            'sweetalert2-all',
            
            // Vidieu Compat (1)
            'vidieu-vcb-qr-compat'
        );
        
        // Remove ALL scripts not in whitelist
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->removed_scripts[$handle] = $this->get_script_source($handle);
                $this->log_fix("Order-received removed script: $handle");
            }
        }
        
        // STRICT Order-Received Styles Whitelist - Target: <8 styles
        $allowed_styles = array(
            // WooCommerce Core (3)
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            
            // Theme Core (2)
            'elessi-style',
            'elessi-style-css',
            
            // VCB-MH Payment Gateway (2)
            'vcb-mh-public',
            'sweetalert2',
            
            // Vidieu Compat (1)
            'vidieu-vcb-qr-compat'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                $this->removed_styles[$handle] = $this->get_style_source($handle);
                $this->log_fix("Order-received removed style: $handle");
            }
        }
        
        // Record after counts
        $this->scripts_after_whitelist = count($allowed_scripts);
        $this->styles_after_whitelist = count($allowed_styles);
        
        $this->log_fix("Order-received whitelist applied - Scripts: {$this->scripts_before_whitelist} → {$this->scripts_after_whitelist}");
        $this->log_fix("Order-received whitelist applied - Styles: {$this->styles_before_whitelist} → {$this->styles_after_whitelist}");
        
        // Block common unnecessary
        $this->block_common_unnecessary();
    }
    
    /**
     * Block common unnecessary scripts/styles
     */
    private function block_common_unnecessary() {
        // Remove emoji
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        
        // Remove embed
        wp_dequeue_script('wp-embed');
        wp_deregister_script('wp-embed');
        
        // Remove dashicons for non-logged in
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
            wp_deregister_style('dashicons');
        }
        
        // Remove comment-reply on cart/checkout
        if (is_cart() || is_checkout()) {
            wp_dequeue_script('comment-reply');
            wp_deregister_script('comment-reply');
        }
        
        // Block specific patterns - EXPANDED based on HAR analysis
        $block_patterns = array(
            'elementor', 'elementor-frontend', 'elementor-pro',
            'uael', 'ultimate-elementor',
            'revslider', 'tp-tools', 'kreaturamedia',
            'instagram', 'sbi_scripts',
            'yith-woocompare', 'yith-wcwl',
            'wp-mediaelement', 'mediaelement',
            'contact-form-7', 'wpcf7',
            'wpforms', 'wpforms-modern',
            'mailchimp', 'mc4wp',
            'popup', 'pum-', 'spu-',
            'font-awesome', 'fontawesome',
            'owl-carousel', 'slick-slider',
            'magnific-popup', 'fancybox',
            'addtoany', 'sharethis',
            'google-fonts', 'typekit'
        );
        
        global $wp_scripts, $wp_styles;
        
        foreach ($wp_scripts->registered as $handle => $script) {
            foreach ($block_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false || 
                    (isset($script->src) && strpos($script->src, $pattern) !== false)) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    $this->removed_scripts[$handle] = $this->get_script_source($handle);
                    $this->log_fix("Blocked pattern script: $handle (pattern: $pattern)");
                }
            }
        }
        
        foreach ($wp_styles->registered as $handle => $style) {
            foreach ($block_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false ||
                    (isset($style->src) && strpos($style->src, $pattern) !== false)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                    $this->removed_styles[$handle] = $this->get_style_source($handle);
                    $this->log_fix("Blocked pattern style: $handle (pattern: $pattern)");
                }
            }
        }
    }
    
    /**
     * Get script source/plugin
     */
    private function get_script_source($handle) {
        global $wp_scripts;
        if (!isset($wp_scripts->registered[$handle])) {
            return 'unknown';
        }
        
        $script = $wp_scripts->registered[$handle];
        $src = $script->src ?? '';
        
        if (strpos($src, '/wp-includes/') !== false) {
            return 'WordPress Core';
        } elseif (strpos($src, '/wp-content/plugins/woocommerce/') !== false) {
            return 'WooCommerce';
        } elseif (strpos($src, '/wp-content/themes/elessi') !== false) {
            return 'Elessi Theme';
        } elseif (strpos($src, '/wp-content/plugins/') !== false) {
            preg_match('/\/plugins\/([^\/]+)\//', $src, $matches);
            return 'Plugin: ' . ($matches[1] ?? 'unknown');
        }
        
        return 'Other';
    }
    
    /**
     * Get style source/plugin
     */
    private function get_style_source($handle) {
        global $wp_styles;
        if (!isset($wp_styles->registered[$handle])) {
            return 'unknown';
        }
        
        $style = $wp_styles->registered[$handle];
        $src = $style->src ?? '';
        
        if (strpos($src, '/wp-includes/') !== false) {
            return 'WordPress Core';
        } elseif (strpos($src, '/wp-content/plugins/woocommerce/') !== false) {
            return 'WooCommerce';
        } elseif (strpos($src, '/wp-content/themes/elessi') !== false) {
            return 'Elessi Theme';
        } elseif (strpos($src, '/wp-content/plugins/') !== false) {
            preg_match('/\/plugins\/([^\/]+)\//', $src, $matches);
            return 'Plugin: ' . ($matches[1] ?? 'unknown');
        }
        
        return 'Other';
    }
    
    /**
     * Late cleanup for scripts (Cart)
     */
    public function late_cart_cleanup() {
        $this->current_hook = 'wp_print_scripts';
        $this->current_priority = 1;
        $this->late_cleanup_scripts('cart');
    }
    
    public function late_checkout_cleanup() {
        $this->current_hook = 'wp_print_scripts';
        $this->current_priority = 1;
        $this->late_cleanup_scripts('checkout');
    }
    
    public function late_order_received_cleanup() {
        $this->current_hook = 'wp_print_scripts';
        $this->current_priority = 1;
        $this->late_cleanup_scripts('order-received');
    }
    
    /**
     * Late cleanup for styles
     */
    public function late_cart_style_cleanup() {
        $this->current_hook = 'wp_print_styles';
        $this->current_priority = 1;
        $this->late_cleanup_styles('cart');
    }
    
    public function late_checkout_style_cleanup() {
        $this->current_hook = 'wp_print_styles';
        $this->current_priority = 1;
        $this->late_cleanup_styles('checkout');
    }
    
    public function late_order_received_style_cleanup() {
        $this->current_hook = 'wp_print_styles';
        $this->current_priority = 1;
        $this->late_cleanup_styles('order-received');
    }
    
    /**
     * Final cleanup in footer (Cart/Checkout only)
     */
    public function final_cart_cleanup() {
        $this->current_hook = 'wp_print_footer_scripts';
        $this->current_priority = 9999;
        $this->final_cleanup_scripts('cart');
    }
    
    public function final_checkout_cleanup() {
        $this->current_hook = 'wp_print_footer_scripts';
        $this->current_priority = 9999;
        $this->final_cleanup_scripts('checkout');
    }
    
    /**
     * Generic late cleanup
     */
    private function late_cleanup_scripts($page) {
        global $wp_scripts;
        
        $count = 0;
        foreach ($wp_scripts->queue as $handle) {
            if ($this->should_block_handle($handle, $page)) {
                wp_dequeue_script($handle);
                $this->removed_scripts[$handle] = $this->get_script_source($handle);
                $count++;
                $this->log_fix("Late cleanup removed script: $handle on $page");
            }
        }
        
        if ($count > 0) {
            $this->log_fix("Late script cleanup on $page: $count removed in {$this->current_hook}");
        }
    }
    
    private function late_cleanup_styles($page) {
        global $wp_styles;
        
        $count = 0;
        foreach ($wp_styles->queue as $handle) {
            if ($this->should_block_handle($handle, $page)) {
                wp_dequeue_style($handle);
                $this->removed_styles[$handle] = $this->get_style_source($handle);
                $count++;
                $this->log_fix("Late cleanup removed style: $handle on $page");
            }
        }
        
        if ($count > 0) {
            $this->log_fix("Late style cleanup on $page: $count removed in {$this->current_hook}");
        }
    }
    
    private function final_cleanup_scripts($page) {
        global $wp_scripts;
        
        $count = 0;
        foreach ($wp_scripts->queue as $handle) {
            if ($this->should_final_block_handle($handle, $page)) {
                wp_dequeue_script($handle);
                $this->removed_scripts[$handle] = $this->get_script_source($handle);
                $count++;
                $this->log_fix("Final cleanup removed script: $handle on $page");
            }
        }
        
        if ($count > 0) {
            $this->log_fix("Final script cleanup on $page: $count removed in {$this->current_hook}");
        }
    }
    
    /**
     * Check if handle should be blocked
     */
    private function should_block_handle($handle, $page) {
        // Never block essentials
        $never_block = array('jquery', 'jquery-core', 'woocommerce', 'wc-cart', 'wc-checkout');
        if (in_array($handle, $never_block)) {
            return false;
        }
        
        // Always block these patterns
        $always_block = array(
            'elementor', 'uael', 'revslider', 'instagram',
            'yith', 'mediaelement', 'cf7', 'wpcf7',
            'font-awesome', 'popup', 'mailchimp'
        );
        
        foreach ($always_block as $pattern) {
            if (stripos($handle, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function should_final_block_handle($handle, $page) {
        // In final cleanup, be more aggressive
        $final_block = array(
            'addtoany', 'sharethis', 'google-fonts',
            'owl-carousel', 'slick', 'magnific'
        );
        
        foreach ($final_block as $pattern) {
            if (stripos($handle, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Setup output buffering
     */
    public function setup_output_buffering() {
        if (!is_cart() && !is_checkout() && !is_wc_endpoint_url('order-received')) {
            return;
        }
        
        ob_start(array($this, 'filter_output_buffer'));
        $this->log_fix('Output buffering started for ' . $this->get_current_page_type());
    }
    
    /**
     * Filter output buffer
     */
    public function filter_output_buffer($buffer) {
        if (!is_cart() && !is_checkout() && !is_wc_endpoint_url('order-received')) {
            return $buffer;
        }
        
        $removed_count = 0;
        
        // Domains to strip - EXPANDED based on HAR analysis
        $block_domains = array(
            // Page builders
            'elementor', 'uael', 'divi', 'beaver',
            
            // Sliders & galleries  
            'revslider', 'layerslider', 'masterslider', 'slider',
            
            // Social & tracking
            'instagram', 'facebook', 'twitter', 'pinterest',
            'tiktok', 'linkedin', 'snapchat',
            'analytics', 'googletagmanager', 'google-analytics',
            'gtag', 'hotjar', 'mixpanel', 'segment',
            'doubleclick', 'googlesyndication',
            
            // Extra plugins
            'yith', 'mailchimp', 'convertkit', 'activecampaign',
            'sendinblue', 'contact-form-7', 'wpforms',
            'gravityforms', 'ninja-forms', 'popup',
            'pum-', 'spu-', 'hustle',
            
            // CDNs & External
            'cdnjs.cloudflare.com', 'unpkg.com', 'jsdelivr.net',
            'maxcdn.bootstrapcdn.com', 'use.fontawesome.com',
            'fonts.googleapis.com', 'fonts.gstatic.com',
            'use.typekit.net'
        );
        
        // Remove script/link tags from blocked domains
        foreach ($block_domains as $domain) {
            // External resources
            $pattern = '/<(script|link)[^>]*(' . preg_quote($domain, '/') . ')[^>]*>/i';
            $matches = array();
            if (preg_match_all($pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($pattern, '<!-- Blocked: $2 -->', $buffer);
            }
            
            // Inline scripts containing blocked domain
            $inline_pattern = '/<script[^>]*>(?:(?!<\/script>).)*?' . preg_quote($domain, '/') . '.*?<\/script>/is';
            if (preg_match_all($inline_pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($inline_pattern, '<!-- Blocked inline: ' . $domain . ' -->', $buffer);
            }
        }
        
        // Remove specific patterns that load external resources
        $patterns_to_remove = array(
            // WP emoji
            '/window\._wpemojiSettings\s*=\s*\{[^}]*\};.*?<\/script>/is',
            '/<script[^>]*wp-emoji-release[^>]*>.*?<\/script>/is',
            
            // oEmbed
            '/<link[^>]*oembed[^>]*>/i',
            
            // DNS Prefetch for blocked domains
            '/<link[^>]*rel=[\'"]dns-prefetch[\'"][^>]*href=[\'"]\/\/(?:' . implode('|', array_map('preg_quote', $block_domains)) . ')[\'"][^>]*>/i',
            
            // Preconnect for blocked domains
            '/<link[^>]*rel=[\'"]preconnect[\'"][^>]*href=[\'"][^"\']*(?:' . implode('|', array_map('preg_quote', $block_domains)) . ')[^"\']*[\'"][^>]*>/i'
        );
        
        foreach ($patterns_to_remove as $pattern) {
            if (preg_match_all($pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($pattern, '', $buffer);
            }
        }
        
        $this->log_fix("Output buffer stripped $removed_count elements");
        $this->domain_requests['output_buffer_removed'] = $removed_count;
        
        return $buffer;
    }
    
    /**
     * Nuclear reCAPTCHA setup
     */
    private function setup_nuclear_recaptcha() {
        // Priority 1: Early prevention
        add_action('wp_enqueue_scripts', array($this, 'nuclear_remove_recaptcha'), 1);
        add_action('wp_print_scripts', array($this, 'nuclear_remove_recaptcha_late'), 999);
        add_filter('script_loader_tag', array($this, 'filter_recaptcha_tags'), 10, 3);
        add_action('wp_head', array($this, 'inject_recaptcha_blocker'), 1);
        
        // Track reCAPTCHA sources
        add_action('wp_footer', array($this, 'track_recaptcha_sources'), 1);
    }
    
    /**
     * Nuclear remove reCAPTCHA
     */
    public function nuclear_remove_recaptcha() {
        global $wp_scripts;
        
        // Patterns to match reCAPTCHA
        $recaptcha_patterns = array(
            'google-recaptcha', 'grecaptcha', 'recaptcha',
            'google.com/recaptcha', 'gstatic.com/recaptcha'
        );
        
        foreach ($wp_scripts->registered as $handle => $script) {
            foreach ($recaptcha_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false || 
                    (isset($script->src) && strpos($script->src, $pattern) !== false)) {
                    
                    // Track source
                    $this->recaptcha_sources[$handle] = $this->get_script_source($handle);
                    $this->recaptcha_count++;
                    
                    // If not on contact page, remove it
                    if (!is_page('contact') && !is_page('lien-he')) {
                        wp_dequeue_script($handle);
                        wp_deregister_script($handle);
                        $this->removed_scripts[$handle] = 'reCAPTCHA: ' . $this->get_script_source($handle);
                        $this->log_fix("Nuclear removed reCAPTCHA: $handle");
                    }
                }
            }
        }
    }
    
    /**
     * Nuclear remove reCAPTCHA late
     */
    public function nuclear_remove_recaptcha_late() {
        global $wp_scripts;
        
        if (is_page('contact') || is_page('lien-he')) {
            return; // Keep on contact page
        }
        
        foreach ($wp_scripts->queue as $handle) {
            if (strpos($handle, 'recaptcha') !== false || strpos($handle, 'grecaptcha') !== false) {
                wp_dequeue_script($handle);
                $this->removed_scripts[$handle] = 'reCAPTCHA Late: ' . $this->get_script_source($handle);
                $this->log_fix("Nuclear late removed reCAPTCHA: $handle");
            }
        }
    }
    
    /**
     * Filter reCAPTCHA tags
     */
    public function filter_recaptcha_tags($tag, $handle, $src) {
        if (is_page('contact') || is_page('lien-he')) {
            return $tag; // Keep on contact page
        }
        
        if (strpos($src, 'recaptcha') !== false || strpos($src, 'grecaptcha') !== false) {
            $this->log_fix("Nuclear filter blocked reCAPTCHA tag: $handle");
            return '<!-- Blocked reCAPTCHA: ' . esc_html($handle) . ' -->';
        }
        
        return $tag;
    }
    
    /**
     * Inject reCAPTCHA blocker JavaScript
     */
    public function inject_recaptcha_blocker() {
        if (is_page('contact') || is_page('lien-he')) {
            return; // Don't block on contact page
        }
        ?>
        <script id="vidieu-recaptcha-blocker">
        /* Vidieu reCAPTCHA Blocker - Allow only 1 instance */
        (function() {
            var recaptchaLoaded = false;
            var originalAppendChild = Element.prototype.appendChild;
            var originalInsertBefore = Element.prototype.insertBefore;
            
            function isRecaptchaScript(element) {
                if (element && element.tagName === 'SCRIPT') {
                    var src = element.src || '';
                    return src.includes('recaptcha') || src.includes('grecaptcha');
                }
                return false;
            }
            
            Element.prototype.appendChild = function(element) {
                if (isRecaptchaScript(element)) {
                    if (recaptchaLoaded) {
                        console.log('[Vidieu] Blocked duplicate reCAPTCHA:', element.src);
                        return element;
                    }
                    recaptchaLoaded = true;
                }
                return originalAppendChild.call(this, element);
            };
            
            Element.prototype.insertBefore = function(element, reference) {
                if (isRecaptchaScript(element)) {
                    if (recaptchaLoaded) {
                        console.log('[Vidieu] Blocked duplicate reCAPTCHA:', element.src);
                        return element;
                    }
                    recaptchaLoaded = true;
                }
                return originalInsertBefore.call(this, element, reference);
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Track reCAPTCHA sources
     */
    public function track_recaptcha_sources() {
        if (current_user_can('manage_options') && !empty($this->recaptcha_sources)) {
            echo "\n<!-- reCAPTCHA Sources Found:\n";
            foreach ($this->recaptcha_sources as $handle => $source) {
                echo "  - $handle: $source\n";
            }
            echo "Total reCAPTCHA instances: " . $this->recaptcha_count . "\n-->\n";
        }
    }
    
    /**
     * Setup basic optimization
     */
    private function setup_basic_optimization() {
        // Remove unnecessary WordPress features
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'rest_output_link_wp_head');
        
        // Remove oEmbed
        wp_deregister_script('wp-embed');
        
        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Remove jQuery Migrate on frontend
        add_action('wp_default_scripts', array($this, 'remove_jquery_migrate'));
    }
    
    /**
     * Remove jQuery Migrate
     */
    public function remove_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
    
    /**
     * Log helper
     */
    private function log_fix($message) {
        $this->fixes_log[] = array(
            'time' => current_time('H:i:s'),
            'hook' => $this->current_hook,
            'priority' => $this->current_priority,
            'message' => $message
        );
    }
    
    /**
     * Get current page type
     */
    private function get_current_page_type() {
        if (is_front_page()) return 'Home';
        if (is_cart()) return 'Cart';
        if (is_checkout() && !is_wc_endpoint_url('order-received')) return 'Checkout';
        if (is_wc_endpoint_url('order-received')) return 'Order-received';
        if (is_product()) return 'Product';
        if (is_shop()) return 'Shop';
        if (is_page('contact') || is_page('lien-he')) return 'Contact';
        if (is_single()) return 'Post';
        return 'Other';
    }
    
    /**
     * Output enhanced admin log
     */
    public function output_enhanced_admin_log() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $page_type = $this->get_current_page_type();
        
        // Only show detailed log on Cart/Checkout/Order-received
        if (!in_array($page_type, array('Cart', 'Checkout', 'Order-received'))) {
            return;
        }
        
        echo "\n<!-- ===== VIDIEU V2 ULTIMATE - ENHANCED ADMIN LOG =====\n";
        echo "Page Type: " . $page_type . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Feature Status
        echo "FEATURE STATUS:\n";
        echo " - Basic Optimization: " . (self::ENABLE_BASIC_OPTIMIZATION ? 'ON' : 'OFF') . "\n";
        echo " - Nuclear reCAPTCHA: " . (self::ENABLE_NUCLEAR_RECAPTCHA ? 'ON' : 'OFF') . "\n";
        echo " - Cart/Checkout Whitelist: " . (self::ENABLE_CART_CHECKOUT_WHITELIST ? 'ON' : 'OFF') . "\n";
        echo " - Output Buffering: " . (self::ENABLE_OUTPUT_BUFFERING ? 'ON' : 'OFF') . "\n";
        echo " - Order-Received Opt: " . (self::ENABLE_ORDER_RECEIVED_OPT ? 'ON' : 'OFF') . "\n\n";
        
        // Whitelist Application
        if ($this->whitelist_applied) {
            echo "WHITELIST APPLICATION:\n";
            echo " - Hook: {$this->current_hook}\n";
            echo " - Priority: {$this->current_priority}\n";
            echo " - Scripts Before: {$this->scripts_before_whitelist}\n";
            echo " - Scripts After: {$this->scripts_after_whitelist}\n";
            echo " - Scripts Removed: " . ($this->scripts_before_whitelist - $this->scripts_after_whitelist) . "\n";
            echo " - Styles Before: {$this->styles_before_whitelist}\n";
            echo " - Styles After: {$this->styles_after_whitelist}\n";
            echo " - Styles Removed: " . ($this->styles_before_whitelist - $this->styles_after_whitelist) . "\n\n";
        }
        
        // Removed Scripts Detail
        if (!empty($this->removed_scripts)) {
            echo "REMOVED SCRIPTS (" . count($this->removed_scripts) . " total):\n";
            
            // Group by source
            $grouped = array();
            foreach ($this->removed_scripts as $handle => $source) {
                if (!isset($grouped[$source])) {
                    $grouped[$source] = array();
                }
                $grouped[$source][] = $handle;
            }
            
            foreach ($grouped as $source => $handles) {
                echo "\n  [$source] (" . count($handles) . "):\n";
                foreach ($handles as $handle) {
                    echo "    - $handle\n";
                }
            }
            echo "\n";
        }
        
        // Removed Styles Detail
        if (!empty($this->removed_styles)) {
            echo "REMOVED STYLES (" . count($this->removed_styles) . " total):\n";
            
            // Group by source
            $grouped = array();
            foreach ($this->removed_styles as $handle => $source) {
                if (!isset($grouped[$source])) {
                    $grouped[$source] = array();
                }
                $grouped[$source][] = $handle;
            }
            
            foreach ($grouped as $source => $handles) {
                echo "\n  [$source] (" . count($handles) . "):\n";
                foreach ($handles as $handle) {
                    echo "    - $handle\n";
                }
            }
            echo "\n";
        }
        
        // reCAPTCHA Analysis
        if ($this->recaptcha_count > 0) {
            echo "RECAPTCHA ANALYSIS:\n";
            echo " - Total reCAPTCHA found: {$this->recaptcha_count}\n";
            if (!empty($this->recaptcha_sources)) {
                echo " - Sources:\n";
                foreach ($this->recaptcha_sources as $handle => $source) {
                    echo "   - $handle: $source\n";
                }
            }
            echo "\n";
        }
        
        // Domain Requests (if tracked via JS)
        if (!empty($this->domain_requests)) {
            echo "DOMAIN REQUESTS:\n";
            foreach ($this->domain_requests as $key => $value) {
                echo " - $key: $value\n";
            }
            echo "\n";
        }
        
        // Hook Execution Timeline
        echo "HOOK EXECUTION TIMELINE:\n";
        $hooks_used = array();
        foreach ($this->fixes_log as $log) {
            if (!empty($log['hook'])) {
                $hook_key = $log['hook'] . ':' . $log['priority'];
                if (!in_array($hook_key, $hooks_used)) {
                    $hooks_used[] = $hook_key;
                    echo " - {$log['time']} | {$log['hook']} (priority: {$log['priority']})\n";
                }
            }
        }
        echo "\n";
        
        // Summary Statistics
        $total_removed = count($this->removed_scripts) + count($this->removed_styles);
        echo "SUMMARY STATISTICS:\n";
        echo " - Total Elements Removed: $total_removed\n";
        echo " - Scripts Removed: " . count($this->removed_scripts) . "\n";
        echo " - Styles Removed: " . count($this->removed_styles) . "\n";
        echo " - Output Buffer Elements: " . ($this->domain_requests['output_buffer_removed'] ?? 0) . "\n";
        echo " - Kill Switch: " . (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION ? 'ACTIVE' : 'inactive') . "\n";
        
        // Performance Tips
        echo "\n[ TIP: Open browser console and run window.vidieuPerfDomains to see domain analysis ]\n";
        echo "===== END ADMIN LOG ===== -->\n";
        
        // Inject domain tracking script
        ?>
        <script id="vidieu-admin-domain-tracker">
        (function() {
            if (!window.vidieuAdminTracking) {
                window.vidieuAdminTracking = true;
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        var resources = performance.getEntriesByType('resource');
                        var domains = {};
                        
                        resources.forEach(function(r) {
                            var host = new URL(r.name).hostname;
                            domains[host] = (domains[host] || 0) + 1;
                        });
                        
                        window.vidieuPerfDomains = domains;
                        console.log('[Vidieu Admin] Total resources:', resources.length);
                        console.log('[Vidieu Admin] Domain breakdown:', domains);
                        
                        // Check for blocked domains still loading
                        var blocked = ['elementor', 'yith', 'revslider', 'instagram', 'facebook'];
                        var stillLoading = [];
                        
                        Object.keys(domains).forEach(function(domain) {
                            blocked.forEach(function(b) {
                                if (domain.includes(b)) {
                                    stillLoading.push(domain + ' (' + domains[domain] + ' requests)');
                                }
                            });
                        });
                        
                        if (stillLoading.length > 0) {
                            console.warn('[Vidieu Admin] Blocked domains still loading:', stillLoading);
                        }
                    }, 2000);
                });
            }
        })();
        </script>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2_Ultimate::get_instance();
}, 1);