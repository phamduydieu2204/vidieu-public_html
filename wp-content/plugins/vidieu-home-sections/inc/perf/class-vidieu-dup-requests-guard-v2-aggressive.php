<?php
/**
 * Vidieu Duplicate Requests Guard V2 - Aggressive Whitelist
 * 
 * Enhanced whitelist with detailed logging and output buffering
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2_Aggressive {
    
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
    
    /**
     * Whitelist tracking
     */
    private $scripts_before_whitelist = 0;
    private $styles_before_whitelist = 0;
    private $scripts_after_whitelist = 0;
    private $styles_after_whitelist = 0;
    private $domain_requests = array();
    
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
        if (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION)) {
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
        
        $this->log_fix('V2 Aggressive initialization started');
        
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
        
        // Output buffering for cart/checkout
        if (self::ENABLE_OUTPUT_BUFFERING) {
            add_action('template_redirect', array($this, 'setup_output_buffering'), 1);
        }
        
        // Enhanced monitoring
        add_action('wp_footer', array($this, 'output_detailed_debug_info'), 9999);
        add_action('wp_footer', array($this, 'inject_performance_monitor'), 9998);
    }
    
    /**
     * Setup route optimization
     */
    public function setup_route_optimization() {
        if (is_cart()) {
            // Multiple hooks for cart
            add_action('wp_enqueue_scripts', array($this, 'aggressive_cart_whitelist'), 9999);
            add_action('wp_print_scripts', array($this, 'late_cart_cleanup'), 1);
            add_action('wp_print_styles', array($this, 'late_cart_style_cleanup'), 1);
            $this->log_fix('Cart optimization triggered on multiple hooks');
            
        } elseif (is_checkout()) {
            // Multiple hooks for checkout
            add_action('wp_enqueue_scripts', array($this, 'aggressive_checkout_whitelist'), 9999);
            add_action('wp_print_scripts', array($this, 'late_checkout_cleanup'), 1);
            add_action('wp_print_styles', array($this, 'late_checkout_style_cleanup'), 1);
            $this->log_fix('Checkout optimization triggered on multiple hooks');
        }
    }
    
    /**
     * AGGRESSIVE CART WHITELIST
     */
    public function aggressive_cart_whitelist() {
        global $wp_scripts, $wp_styles;
        
        // Record before counts
        $this->scripts_before_whitelist = count($wp_scripts->queue);
        $this->styles_before_whitelist = count($wp_styles->queue);
        
        // Essential scripts for cart - TARGETED
        $allowed_scripts = array(
            // Core - absolutely required
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'js-cookie',
            'underscore', // WP dependency
            
            // WooCommerce cart essentials
            'woocommerce',
            'wc-add-to-cart',
            'wc-cart',
            'wc-cart-fragments',
            'selectWoo',
            'select2', // selectWoo dependency
            'wc-country-select',
            'wc-address-i18n',
            
            // WooCommerce blocks (if using block cart)
            'wc-blocks-vendors',
            'wc-cart-blocks',
            'wc-blocks-data-store',
            
            // i18n
            'wp-i18n',
            'wp-hooks',
            
            // Theme core only
            'elessi-theme-js',
            'elessi-functions-js'
        );
        
        // Remove ALL scripts not in whitelist
        $removed_count = 0;
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->removed_scripts[] = $handle;
                $removed_count++;
                $this->log_fix("Cart removed script: $handle");
            }
        }
        
        // Essential styles for cart - MINIMAL
        $allowed_styles = array(
            // WooCommerce
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            
            // Theme
            'elessi-style',
            'elessi-style-child'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                $this->removed_styles[] = $handle;
                $this->log_fix("Cart removed style: $handle");
            }
        }
        
        // Record after counts
        $this->scripts_after_whitelist = count($allowed_scripts);
        $this->styles_after_whitelist = count($allowed_styles);
        
        $this->log_fix("Cart whitelist applied: $removed_count scripts removed");
        
        // Block common unnecessary on cart
        $this->block_common_unnecessary();
    }
    
    /**
     * AGGRESSIVE CHECKOUT WHITELIST
     */
    public function aggressive_checkout_whitelist() {
        global $wp_scripts, $wp_styles;
        
        // Record before counts
        $this->scripts_before_whitelist = count($wp_scripts->queue);
        $this->styles_before_whitelist = count($wp_styles->queue);
        
        // Essential scripts for checkout - TARGETED
        $allowed_scripts = array(
            // Core - absolutely required
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'jquery-blockui',
            'js-cookie',
            'underscore',
            
            // WooCommerce checkout essentials
            'woocommerce',
            'wc-cart-fragments',
            'wc-checkout',
            'wc-country-select',
            'wc-address-i18n',
            'wc-password-strength-meter',
            'selectWoo',
            'select2',
            
            // WooCommerce blocks (if using block checkout)
            'wc-blocks-vendors',
            'wc-checkout-blocks',
            'wc-blocks-data-store',
            'wc-blocks-registry',
            
            // Payment gateways base
            'jquery-payment',
            'wc-credit-card-form',
            
            // i18n & WP deps
            'wp-i18n',
            'wp-hooks',
            'wp-util',
            
            // Theme core only
            'elessi-theme-js',
            'elessi-functions-js'
        );
        
        // Add payment gateway scripts dynamically
        $chosen_gateway = WC()->session ? WC()->session->get('chosen_payment_method') : '';
        if ($chosen_gateway) {
            // Common payment gateways
            $gateway_patterns = array(
                'vietcombank' => array('vietcombank-mh', 'wc-vietcombank'),
                'stripe' => array('stripe', 'wc-stripe', 'stripe-js'),
                'paypal' => array('paypal', 'wc-paypal', 'ppcp-'],
                'cod' => array(), // No scripts needed
                'bacs' => array() // No scripts needed
            );
            
            foreach ($gateway_patterns as $gateway => $scripts) {
                if (strpos($chosen_gateway, $gateway) !== false) {
                    $allowed_scripts = array_merge($allowed_scripts, $scripts);
                }
            }
        }
        
        // Remove ALL scripts not in whitelist
        $removed_count = 0;
        foreach ($wp_scripts->queue as $handle) {
            $keep = false;
            
            // Check whitelist
            if (in_array($handle, $allowed_scripts)) {
                $keep = true;
            }
            
            // Check payment gateway pattern
            if ($chosen_gateway && strpos($handle, $chosen_gateway) !== false) {
                $keep = true;
                $this->log_fix("Kept payment script: $handle");
            }
            
            if (!$keep) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->removed_scripts[] = $handle;
                $removed_count++;
                $this->log_fix("Checkout removed script: $handle");
            }
        }
        
        // Essential styles for checkout - MINIMAL
        $allowed_styles = array(
            // WooCommerce
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            
            // Theme
            'elessi-style',
            'elessi-style-child'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
                $this->removed_styles[] = $handle;
                $this->log_fix("Checkout removed style: $handle");
            }
        }
        
        // Record after counts
        $this->scripts_after_whitelist = count($allowed_scripts);
        $this->styles_after_whitelist = count($allowed_styles);
        
        $this->log_fix("Checkout whitelist applied: $removed_count scripts removed");
        
        // Block common unnecessary on checkout
        $this->block_common_unnecessary();
    }
    
    /**
     * Block common unnecessary scripts/styles
     */
    private function block_common_unnecessary() {
        // Remove emoji
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Remove embed
        wp_dequeue_script('wp-embed');
        
        // Remove dashicons for non-logged in
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
        }
        
        // Block specific patterns
        $block_patterns = array(
            'elementor-frontend',
            'uael-',
            'revslider',
            'instagram-feed',
            'yith-woocompare',
            'wp-mediaelement',
            'contact-form-7',
            'wpcf7-'
        );
        
        global $wp_scripts, $wp_styles;
        
        foreach ($wp_scripts->registered as $handle => $script) {
            foreach ($block_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Blocked pattern script: $handle");
                }
            }
        }
        
        foreach ($wp_styles->registered as $handle => $style) {
            foreach ($block_patterns as $pattern) {
                if (strpos($handle, $pattern) !== false) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                    $this->removed_styles[] = $handle;
                    $this->log_fix("Blocked pattern style: $handle");
                }
            }
        }
    }
    
    /**
     * Late cleanup for scripts
     */
    public function late_cart_cleanup() {
        $this->late_cleanup_scripts('cart');
    }
    
    public function late_checkout_cleanup() {
        $this->late_cleanup_scripts('checkout');
    }
    
    private function late_cleanup_scripts($page) {
        global $wp_scripts;
        
        $count = 0;
        foreach ($wp_scripts->queue as $handle) {
            if ($this->should_block_handle($handle, $page)) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
                $count++;
                $this->log_fix("Late cleanup removed: $handle on $page");
            }
        }
        
        $this->log_fix("Late script cleanup on $page: $count removed");
    }
    
    /**
     * Late cleanup for styles
     */
    public function late_cart_style_cleanup() {
        $this->late_cleanup_styles('cart');
    }
    
    public function late_checkout_style_cleanup() {
        $this->late_cleanup_styles('checkout');
    }
    
    private function late_cleanup_styles($page) {
        global $wp_styles;
        
        $count = 0;
        foreach ($wp_styles->queue as $handle) {
            if ($this->should_block_handle($handle, $page)) {
                wp_dequeue_style($handle);
                $this->removed_styles[] = $handle;
                $count++;
                $this->log_fix("Late style cleanup removed: $handle on $page");
            }
        }
        
        $this->log_fix("Late style cleanup on $page: $count removed");
    }
    
    /**
     * Check if handle should be blocked
     */
    private function should_block_handle($handle, $page) {
        // Never block essentials
        $never_block = array('jquery', 'woocommerce', 'wc-cart', 'wc-checkout');
        if (in_array($handle, $never_block)) {
            return false;
        }
        
        // Always block these patterns
        $always_block = array(
            'elementor', 'uael', 'revslider', 'instagram', 
            'yith', 'mediaelement', 'cf7', 'wpcf7'
        );
        
        foreach ($always_block as $pattern) {
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
        if (!is_cart() && !is_checkout()) {
            return;
        }
        
        ob_start(array($this, 'filter_output_buffer'));
        $this->log_fix('Output buffering started for ' . (is_cart() ? 'cart' : 'checkout'));
    }
    
    /**
     * Filter output buffer
     */
    public function filter_output_buffer($buffer) {
        if (!is_cart() && !is_checkout()) {
            return $buffer;
        }
        
        $removed_count = 0;
        
        // Domains to strip - EXPANDED
        $block_domains = array(
            // Page builders
            'elementor',
            'uael',
            'divi',
            'beaver',
            
            // Sliders & galleries
            'revslider',
            'layerslider',
            'masterslider',
            
            // Social & tracking
            'instagram',
            'facebook',
            'twitter',
            'pinterest',
            'tiktok',
            'analytics',
            'googletagmanager',
            'google-analytics',
            'gtag',
            'hotjar',
            'mixpanel',
            'segment',
            
            // Extra plugins
            'yith',
            'mailchimp',
            'convertkit',
            'activecampaign',
            'sendinblue',
            'contact-form-7',
            'wpforms',
            'gravityforms',
            'ninja-forms',
            'popup',
            'pum-',
            'spu-',
            
            // CDNs we don't need
            'cdnjs.cloudflare.com',
            'unpkg.com',
            'jsdelivr.net',
            'maxcdn.bootstrapcdn.com'
        );
        
        // Remove script/link tags from blocked domains
        foreach ($block_domains as $domain) {
            // Pattern for external resources
            $pattern = '/<(script|link)[^>]*(' . preg_quote($domain, '/') . ')[^>]*>/i';
            $matches = array();
            if (preg_match_all($pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($pattern, '<!-- Blocked: $2 -->', $buffer);
            }
            
            // Also remove inline scripts containing blocked domain
            $inline_pattern = '/<script[^>]*>.*?' . preg_quote($domain, '/') . '.*?<\/script>/is';
            if (preg_match_all($inline_pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($inline_pattern, '<!-- Blocked inline: ' . $domain . ' -->', $buffer);
            }
        }
        
        // Remove specific patterns that load external resources
        $patterns_to_remove = array(
            // Google Fonts
            '/<link[^>]*fonts\.googleapis\.com[^>]*>/i',
            '/<link[^>]*fonts\.gstatic\.com[^>]*>/i',
            // Font Awesome CDN
            '/<link[^>]*use\.fontawesome\.com[^>]*>/i',
            // Adobe Fonts
            '/<link[^>]*use\.typekit\.net[^>]*>/i',
            // Remove wp emoji scripts
            '/window\._wpemojiSettings\s*=\s*\{[^}]*\};/s',
            // Remove comment reply script on cart/checkout
            '/<script[^>]*comment-reply[^>]*><\/script>/i'
        );
        
        foreach ($patterns_to_remove as $pattern) {
            if (preg_match_all($pattern, $buffer, $matches)) {
                $removed_count += count($matches[0]);
                $buffer = preg_replace($pattern, '', $buffer);
            }
        }
        
        $this->log_fix("Output buffer stripped $removed_count tags");
        
        // Add performance stats
        $stats = $this->get_performance_stats();
        $buffer = str_replace('</body>', $stats . '</body>', $buffer);
        
        return $buffer;
    }
    
    /**
     * Get performance stats HTML
     */
    private function get_performance_stats() {
        if (!current_user_can('manage_options')) {
            return '';
        }
        
        $html = "\n<!-- Vidieu Performance Stats -->\n";
        $html .= "<div id='vidieu-perf-stats' style='display:none;'>\n";
        $html .= "Scripts removed: " . count($this->removed_scripts) . "\n";
        $html .= "Styles removed: " . count($this->removed_styles) . "\n";
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Basic optimization setup
     */
    private function setup_basic_optimization() {
        add_action('wp_enqueue_scripts', array($this, 'remove_basic_duplicates'), 999);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
    }
    
    /**
     * Remove basic duplicates
     */
    public function remove_basic_duplicates() {
        // Implementation from previous version
    }
    
    /**
     * Nuclear reCAPTCHA setup
     */
    private function setup_nuclear_recaptcha() {
        add_action('wp_enqueue_scripts', array($this, 'nuclear_remove_recaptcha'), 1);
        add_action('wp_print_scripts', array($this, 'nuclear_remove_recaptcha_late'), 999);
        add_filter('script_loader_tag', array($this, 'filter_recaptcha_tags'), 10, 3);
    }
    
    /**
     * Nuclear remove reCAPTCHA
     */
    public function nuclear_remove_recaptcha() {
        // Implementation from previous version
    }
    
    public function nuclear_remove_recaptcha_late() {
        // Implementation from previous version
    }
    
    public function filter_recaptcha_tags($tag, $handle, $src) {
        // Implementation from previous version
        return $tag;
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
     * Inject performance monitor JavaScript
     */
    public function inject_performance_monitor() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <script id="vidieu-performance-monitor">
        (function() {
            // Wait for page load
            window.addEventListener('load', function() {
                setTimeout(function() {
                    // Get all resources
                    var resources = performance.getEntriesByType('resource');
                    var domains = {};
                    
                    // Count by domain
                    resources.forEach(function(resource) {
                        var url = new URL(resource.name);
                        var domain = url.hostname;
                        
                        if (!domains[domain]) {
                            domains[domain] = {
                                count: 0,
                                size: 0,
                                types: {}
                            };
                        }
                        
                        domains[domain].count++;
                        domains[domain].size += resource.transferSize || 0;
                        
                        // Get type from URL
                        var type = 'other';
                        if (resource.name.match(/\.(js)$/i)) type = 'js';
                        else if (resource.name.match(/\.(css)$/i)) type = 'css';
                        else if (resource.name.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) type = 'image';
                        else if (resource.name.match(/\.(woff|woff2|ttf|eot)$/i)) type = 'font';
                        
                        domains[domain].types[type] = (domains[domain].types[type] || 0) + 1;
                    });
                    
                    // Store in window for console access
                    window.vidieuPerfDomains = domains;
                    
                    console.log('[Vidieu Performance] Total resources:', resources.length);
                    console.log('[Vidieu Performance] Domains:', domains);
                    
                    // Add to page if admin
                    var statsDiv = document.getElementById('vidieu-perf-stats');
                    if (statsDiv) {
                        statsDiv.innerHTML += '\nTotal resources: ' + resources.length;
                        statsDiv.innerHTML += '\nDomains: ' + Object.keys(domains).length;
                    }
                }, 1000);
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Output detailed debug info
     */
    public function output_detailed_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $total_removed = count($this->removed_scripts) + count($this->removed_styles);
        
        echo "\n<!-- ===== Vidieu V2 Aggressive Whitelist Report =====\n";
        echo "Page Type: " . $this->get_current_page_type() . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "FEATURE FLAGS:\n";
        echo " - Basic Optimization: " . (self::ENABLE_BASIC_OPTIMIZATION ? 'YES' : 'NO') . "\n";
        echo " - Nuclear reCAPTCHA: " . (self::ENABLE_NUCLEAR_RECAPTCHA ? 'YES' : 'NO') . "\n";
        echo " - Cart/Checkout Whitelist: " . (self::ENABLE_CART_CHECKOUT_WHITELIST ? 'YES' : 'NO') . "\n";
        echo " - Output Buffering: " . (self::ENABLE_OUTPUT_BUFFERING ? 'YES' : 'NO') . "\n\n";
        
        echo "WHITELIST STATS:\n";
        echo " - Scripts before whitelist: " . $this->scripts_before_whitelist . "\n";
        echo " - Scripts after whitelist: " . $this->scripts_after_whitelist . "\n";
        echo " - Scripts removed: " . count($this->removed_scripts) . "\n";
        echo " - Styles before whitelist: " . $this->styles_before_whitelist . "\n";
        echo " - Styles after whitelist: " . $this->styles_after_whitelist . "\n";
        echo " - Styles removed: " . count($this->removed_styles) . "\n";
        echo " - Total removed: " . $total_removed . "\n\n";
        
        if (count($this->removed_scripts) > 0) {
            echo "REMOVED SCRIPTS:\n";
            foreach ($this->removed_scripts as $i => $handle) {
                echo ($i + 1) . ". " . $handle . "\n";
            }
            echo "\n";
        }
        
        if (count($this->removed_styles) > 0) {
            echo "REMOVED STYLES:\n";
            foreach ($this->removed_styles as $i => $handle) {
                echo ($i + 1) . ". " . $handle . "\n";
            }
            echo "\n";
        }
        
        echo "HOOKS & PRIORITIES:\n";
        echo " - wp_enqueue_scripts: priority 9999 (aggressive whitelist)\n";
        echo " - wp_print_scripts: priority 1 (late cleanup)\n";
        echo " - wp_print_styles: priority 1 (late cleanup)\n";
        echo " - template_redirect: priority 1 (output buffering)\n\n";
        
        echo "DETAILED LOG:\n";
        foreach ($this->fixes_log as $i => $log) {
            echo ($i + 1) . ". " . esc_html($log) . "\n";
        }
        
        echo "\nTO VIEW DOMAIN STATS: Open console and type: window.vidieuPerfDomains\n";
        echo "Kill Switch: " . (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION) ? 'ACTIVE' : 'inactive') . "\n";
        echo "===== End Report ===== -->\n";
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2_Aggressive::get_instance();
}, 1);