<?php
/**
 * Vidieu Duplicate Requests Guard V2 Enhanced
 * 
 * Nuclear options for reCAPTCHA and aggressive Cart/Checkout optimization
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2_Enhanced {
    
    /**
     * Instance
     */
    private static $instance = null;
    
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
        
        // Hook very early
        add_action('init', array($this, 'init_optimization'), 1);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        // Skip admin, AJAX, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Phase 1: Nuclear reCAPTCHA removal
        $this->setup_recaptcha_nuclear_removal();
        
        // Phase 2: Route-based optimization
        add_action('wp', array($this, 'setup_route_optimization'), 1);
        
        // Phase 3: Global optimizations
        add_action('wp_enqueue_scripts', array($this, 'global_optimizations'), 5);
        
        // Phase 4: Late cleanup
        add_action('wp_print_scripts', array($this, 'late_cleanup'), 9999);
        add_action('wp_print_styles', array($this, 'late_style_cleanup'), 9999);
        
        // Monitoring
        add_action('wp_footer', array($this, 'output_debug_info'), 9999);
    }
    
    /**
     * NUCLEAR RECAPTCHA REMOVAL
     */
    private function setup_recaptcha_nuclear_removal() {
        // 1. Remove ALL reCAPTCHA hooks from other plugins
        remove_all_filters('wp_head', 9);
        remove_all_filters('wp_footer', 9);
        remove_all_filters('woocommerce_checkout_fields');
        
        // 2. Prevent reCAPTCHA enqueue
        add_action('wp_enqueue_scripts', function() {
            global $wp_scripts, $wp_styles;
            
            // Track all reCAPTCHA sources for debugging
            foreach ($wp_scripts->registered as $handle => $script) {
                if (!empty($script->src) && $this->is_recaptcha_resource($script->src)) {
                    $this->recaptcha_sources[$handle] = $script->src;
                    
                    // Remove ALL except our valid key
                    if (strpos($script->src, self::VALID_RECAPTCHA_KEY) === false) {
                        wp_dequeue_script($handle);
                        wp_deregister_script($handle);
                        $this->removed_scripts[] = $handle;
                        $this->log_fix("Removed reCAPTCHA source: $handle");
                    }
                }
            }
        }, 1);
        
        // 3. JavaScript-based blocking
        add_action('wp_head', array($this, 'inject_recaptcha_blocker'), 1);
        
        // 4. Single reCAPTCHA injection
        add_action('wp_footer', array($this, 'inject_single_recaptcha'), 999);
        
        // 5. Output buffer filtering
        add_action('template_redirect', array($this, 'start_output_buffer'), 1);
    }
    
    /**
     * Check if resource is reCAPTCHA related
     */
    private function is_recaptcha_resource($src) {
        $patterns = array(
            'google.com/recaptcha',
            'gstatic.com/recaptcha',
            'grecaptcha',
            'recaptcha/api',
            'recaptcha__'
        );
        
        foreach ($patterns as $pattern) {
            if (stripos($src, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Inject JavaScript reCAPTCHA blocker
     */
    public function inject_recaptcha_blocker() {
        ?>
        <script id="vidieu-recaptcha-blocker">
        /* NUCLEAR RECAPTCHA BLOCKER */
        (function() {
            window.__recaptchaLoaded = false;
            
            // Override appendChild
            const originalAppend = Element.prototype.appendChild;
            Element.prototype.appendChild = function(child) {
                if (child && child.tagName === 'SCRIPT' && child.src) {
                    if (child.src.includes('recaptcha') || child.src.includes('gstatic')) {
                        if (window.__recaptchaLoaded) {
                            console.log('[Vidieu] Blocked duplicate reCAPTCHA:', child.src);
                            return child;
                        }
                        if (child.src.includes('<?php echo self::VALID_RECAPTCHA_KEY; ?>')) {
                            window.__recaptchaLoaded = true;
                        } else {
                            console.log('[Vidieu] Blocked invalid reCAPTCHA:', child.src);
                            return child;
                        }
                    }
                }
                return originalAppend.apply(this, arguments);
            };
            
            // Override document.write
            const originalWrite = document.write;
            document.write = function(content) {
                if (content.includes('recaptcha') && window.__recaptchaLoaded) {
                    console.log('[Vidieu] Blocked document.write reCAPTCHA');
                    return;
                }
                return originalWrite.apply(this, arguments);
            };
            
            // Block dynamic script creation
            const originalCreate = document.createElement;
            document.createElement = function(tagName) {
                const element = originalCreate.apply(this, arguments);
                if (tagName.toLowerCase() === 'script') {
                    const originalSrc = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src');
                    Object.defineProperty(element, 'src', {
                        set: function(value) {
                            if (value && value.includes('recaptcha') && window.__recaptchaLoaded) {
                                console.log('[Vidieu] Blocked dynamic reCAPTCHA:', value);
                                return;
                            }
                            originalSrc.set.call(this, value);
                        },
                        get: originalSrc.get
                    });
                }
                return element;
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Inject single valid reCAPTCHA
     */
    public function inject_single_recaptcha() {
        // Only inject on pages that need it
        if (!is_page('contact') && !is_checkout()) {
            return;
        }
        
        ?>
        <script id="vidieu-single-recaptcha">
        if (!window.__recaptchaLoaded) {
            var script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/api.js?render=<?php echo self::VALID_RECAPTCHA_KEY; ?>';
            script.async = true;
            script.defer = true;
            document.body.appendChild(script);
            window.__recaptchaLoaded = true;
        }
        </script>
        <?php
    }
    
    /**
     * Setup route-based optimization
     */
    public function setup_route_optimization() {
        if (is_cart()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_cart_whitelist'), 999);
        } elseif (is_checkout()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_checkout_whitelist'), 999);
        } elseif (is_page('contact')) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_contact_page'), 999);
        }
    }
    
    /**
     * CART WHITELIST OPTIMIZATION
     */
    public function optimize_cart_whitelist() {
        global $wp_scripts, $wp_styles;
        
        // Essential scripts for cart
        $allowed_scripts = array(
            'jquery',
            'jquery-migrate',
            'woocommerce',
            'wc-cart',
            'wc-cart-fragments',
            'wc-country-select',
            'wc-address-i18n',
            'selectWoo',
            'elessi-theme-js'
        );
        
        // Remove ALL scripts not in whitelist
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts)) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
                $this->log_fix("Cart whitelist removed script: $handle");
            }
        }
        
        // Essential styles for cart
        $allowed_styles = array(
            'woocommerce-layout',
            'woocommerce-general',
            'woocommerce-smallscreen',
            'elessi-style',
            'elessi-style-main',
            'elessi-style-responsive'
        );
        
        // Remove ALL styles not in whitelist
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles)) {
                wp_dequeue_style($handle);
                $this->removed_styles[] = $handle;
                $this->log_fix("Cart whitelist removed style: $handle");
            }
        }
        
        // Remove duplicate images via filter
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'dedupe_cart_images'), 10, 3);
    }
    
    /**
     * CHECKOUT WHITELIST OPTIMIZATION
     */
    public function optimize_checkout_whitelist() {
        global $wp_scripts, $wp_styles;
        
        // Essential scripts for checkout
        $allowed_scripts = array(
            'jquery',
            'jquery-migrate',
            'woocommerce',
            'wc-checkout',
            'wc-country-select',
            'wc-address-i18n',
            'selectWoo',
            'wc-cart-fragments',
            'jquery-payment',
            'elessi-theme-js'
        );
        
        // Add payment gateway scripts dynamically
        $chosen_gateway = WC()->session->get('chosen_payment_method');
        if ($chosen_gateway) {
            $allowed_scripts[] = 'wc-' . $chosen_gateway;
        }
        
        // Track payment scripts to prevent duplicates
        $payment_files = array();
        
        foreach ($wp_scripts->queue as $handle) {
            $keep = false;
            
            // Check whitelist
            if (in_array($handle, $allowed_scripts)) {
                $keep = true;
            }
            
            // Check payment gateways
            if (strpos($handle, 'payment') !== false || 
                strpos($handle, 'stripe') !== false ||
                strpos($handle, 'paypal') !== false) {
                
                // Prevent duplicate payment scripts
                if (isset($wp_scripts->registered[$handle]->src)) {
                    $filename = basename($wp_scripts->registered[$handle]->src);
                    if (!isset($payment_files[$filename])) {
                        $payment_files[$filename] = true;
                        $keep = true;
                    }
                }
            }
            
            if (!$keep) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
                $this->log_fix("Checkout whitelist removed script: $handle");
            }
        }
        
        // Limit reCAPTCHA on checkout
        add_filter('woocommerce_checkout_fields', function($fields) {
            // Ensure only one reCAPTCHA instance
            static $recaptcha_added = false;
            if (!$recaptcha_added) {
                $recaptcha_added = true;
            } else {
                // Remove additional reCAPTCHA fields
                foreach ($fields as $fieldset => &$fieldset_fields) {
                    foreach ($fieldset_fields as $key => $field) {
                        if (strpos($key, 'captcha') !== false || strpos($key, 'recaptcha') !== false) {
                            unset($fieldset_fields[$key]);
                        }
                    }
                }
            }
            return $fields;
        }, 999);
    }
    
    /**
     * Deduplicate cart images
     */
    public function dedupe_cart_images($thumbnail, $cart_item, $cart_item_key) {
        static $loaded_images = array();
        
        if (preg_match('/src="([^"]+)"/', $thumbnail, $matches)) {
            $src = $matches[1];
            $clean_src = strtok($src, '?');
            
            if (isset($loaded_images[$clean_src])) {
                // Return 1x1 transparent SVG for duplicates
                return '<img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'1\' height=\'1\'%3E%3C/svg%3E" 
                        data-src="' . esc_attr($src) . '" 
                        class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail lazy-duplicate" 
                        alt="" 
                        width="247" 
                        height="296">';
            }
            
            $loaded_images[$clean_src] = true;
        }
        
        return $thumbnail;
    }
    
    /**
     * Contact page optimization
     */
    public function optimize_contact_page() {
        global $wp_scripts;
        
        // Remove ALL WooCommerce scripts except fragments
        foreach ($wp_scripts->queue as $handle) {
            if (strpos($handle, 'wc-') === 0 || strpos($handle, 'woocommerce') === 0) {
                if ($handle !== 'wc-cart-fragments') {
                    wp_dequeue_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Contact page removed: $handle");
                }
            }
        }
    }
    
    /**
     * Global optimizations
     */
    public function global_optimizations() {
        // Remove admin bar on frontend
        if (!current_user_can('manage_options')) {
            add_filter('show_admin_bar', '__return_false');
        }
        
        // Disable emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Remove duplicate jQuery migrate
        add_action('wp_default_scripts', function($scripts) {
            if (!is_admin()) {
                $scripts->remove('jquery');
                $scripts->add('jquery', false, array('jquery-core'), '1.12.4');
            }
        });
    }
    
    /**
     * Late cleanup
     */
    public function late_cleanup() {
        global $wp_scripts;
        
        // Final pass to remove any missed duplicates
        $loaded_files = array();
        
        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle]->src)) continue;
            
            $src = $wp_scripts->registered[$handle]->src;
            $filename = basename(strtok($src, '?'));
            
            if (isset($loaded_files[$filename])) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
                $this->log_fix("Late cleanup removed duplicate: $handle ($filename)");
            } else {
                $loaded_files[$filename] = true;
            }
        }
    }
    
    /**
     * Late style cleanup
     */
    public function late_style_cleanup() {
        global $wp_styles;
        
        // Remove duplicate theme styles
        $theme_styles = array();
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle]->src)) {
                $src = $wp_styles->registered[$handle]->src;
                if (strpos($src, 'themes/elessi') !== false) {
                    $filename = basename($src);
                    if (isset($theme_styles[$filename])) {
                        wp_dequeue_style($handle);
                        $this->removed_styles[] = $handle;
                        $this->log_fix("Removed duplicate theme style: $handle");
                    } else {
                        $theme_styles[$filename] = true;
                    }
                }
            }
        }
    }
    
    /**
     * Start output buffer for final filtering
     */
    public function start_output_buffer() {
        ob_start(array($this, 'filter_final_output'));
    }
    
    /**
     * Filter final output
     */
    public function filter_final_output($buffer) {
        // Remove duplicate reCAPTCHA in output
        $recaptcha_pattern = '/<script[^>]*recaptcha[^>]*><\/script>/i';
        $matches = array();
        preg_match_all($recaptcha_pattern, $buffer, $matches);
        
        if (count($matches[0]) > 1) {
            // Keep only first, remove others
            $first = true;
            foreach ($matches[0] as $match) {
                if ($first) {
                    $first = false;
                } else {
                    $buffer = str_replace($match, '<!-- Removed duplicate reCAPTCHA -->', $buffer);
                    $this->log_fix("Removed duplicate reCAPTCHA from output buffer");
                }
            }
        }
        
        return $buffer;
    }
    
    /**
     * Log helper
     */
    private function log_fix($message) {
        $this->fixes_log[] = $message;
    }
    
    /**
     * Output debug info
     */
    public function output_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $total_removed = count($this->removed_scripts) + count($this->removed_styles);
        
        echo "\n<!-- Vidieu V2 Enhanced Optimization Report\n";
        echo "=====================================\n";
        echo "Total optimizations: " . count($this->fixes_log) . "\n";
        echo "Scripts removed: " . count($this->removed_scripts) . "\n";
        echo "Styles removed: " . count($this->removed_styles) . "\n";
        echo "Total removed: " . $total_removed . "\n\n";
        
        if (!empty($this->recaptcha_sources)) {
            echo "reCAPTCHA Sources Found:\n";
            foreach ($this->recaptcha_sources as $handle => $src) {
                echo " - $handle: " . basename($src) . "\n";
            }
            echo "\n";
        }
        
        echo "Detailed Log:\n";
        foreach ($this->fixes_log as $i => $log) {
            echo ($i + 1) . ". " . esc_html($log) . "\n";
        }
        
        echo "\nCurrent Page: " . $this->get_current_page_type() . "\n";
        echo "Kill Switch: " . (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION) ? 'ACTIVE' : 'inactive') . "\n";
        echo "-->\n";
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
        if (is_page('contact')) return 'Contact';
        if (is_single()) return 'Post';
        return 'Other';
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2_Enhanced::get_instance();
}, 1);