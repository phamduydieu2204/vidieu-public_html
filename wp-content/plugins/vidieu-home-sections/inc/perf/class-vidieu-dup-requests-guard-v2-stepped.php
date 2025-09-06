<?php
/**
 * Vidieu Duplicate Requests Guard V2 - Stepped Implementation
 * 
 * Enable features step by step for safer deployment
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard_V2_Stepped {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Feature flags - Enable step by step
     */
    const ENABLE_BASIC_OPTIMIZATION = true;        // Step 1: Basic deduplication
    const ENABLE_NUCLEAR_RECAPTCHA = true;         // Step 2: Nuclear reCAPTCHA
    const ENABLE_CART_CHECKOUT_WHITELIST = true;   // Step 3: Cart/Checkout whitelist
    
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
        // Check kill switch first
        if (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION)) {
            $this->log_fix('Kill switch active - optimization disabled');
            return;
        }
        
        // Initialize on proper hook
        add_action('init', array($this, 'init_optimization'), 1);
    }
    
    /**
     * Initialize optimization
     */
    public function init_optimization() {
        // Skip admin, AJAX, cron, CLI
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        
        $this->log_fix('V2 Stepped initialization started');
        
        // STEP 1: Basic optimization (always enabled)
        if (self::ENABLE_BASIC_OPTIMIZATION) {
            $this->setup_basic_optimization();
            $this->log_fix('Step 1: Basic optimization enabled');
        }
        
        // STEP 2: Nuclear reCAPTCHA
        if (self::ENABLE_NUCLEAR_RECAPTCHA) {
            $this->setup_nuclear_recaptcha();
            $this->log_fix('Step 2: Nuclear reCAPTCHA enabled');
        }
        
        // STEP 3: Cart/Checkout whitelist
        if (self::ENABLE_CART_CHECKOUT_WHITELIST) {
            add_action('wp', array($this, 'setup_route_optimization'), 1);
            $this->log_fix('Step 3: Cart/Checkout whitelist enabled');
        }
        
        // Always enable monitoring
        add_action('wp_footer', array($this, 'output_debug_info'), 9999);
    }
    
    /**
     * STEP 1: Basic optimization
     */
    private function setup_basic_optimization() {
        // Remove duplicate scripts/styles
        add_action('wp_enqueue_scripts', array($this, 'remove_basic_duplicates'), 999);
        
        // Fix child theme CSS duplication
        add_action('wp_print_styles', array($this, 'fix_theme_style_duplicates'), 999);
        
        // Disable emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Remove unnecessary meta tags
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
    }
    
    /**
     * Remove basic duplicates
     */
    public function remove_basic_duplicates() {
        global $wp_scripts, $wp_styles;
        
        // Track loaded files to prevent duplicates
        $loaded_scripts = array();
        $loaded_styles = array();
        
        // Check scripts
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!empty($script->src)) {
                $filename = basename(strtok($script->src, '?'));
                
                if (isset($loaded_scripts[$filename])) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Removed duplicate script: $handle ($filename)");
                } else {
                    $loaded_scripts[$filename] = $handle;
                }
            }
        }
        
        // Check styles
        foreach ($wp_styles->registered as $handle => $style) {
            if (!empty($style->src)) {
                $filename = basename(strtok($style->src, '?'));
                
                if (isset($loaded_styles[$filename])) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                    $this->removed_styles[] = $handle;
                    $this->log_fix("Removed duplicate style: $handle ($filename)");
                } else {
                    $loaded_styles[$filename] = $handle;
                }
            }
        }
    }
    
    /**
     * Fix theme style duplicates
     */
    public function fix_theme_style_duplicates() {
        global $wp_styles;
        
        $theme_styles = array();
        
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle]->src)) {
                $src = $wp_styles->registered[$handle]->src;
                
                // Check for theme styles
                if (strpos($src, 'themes/elessi') !== false) {
                    $clean_url = strtok($src, '?');
                    
                    if (isset($theme_styles[$clean_url])) {
                        wp_dequeue_style($handle);
                        $this->removed_styles[] = $handle;
                        $this->log_fix("Removed duplicate theme style: $handle");
                    } else {
                        $theme_styles[$clean_url] = $handle;
                    }
                }
            }
        }
    }
    
    /**
     * STEP 2: Nuclear reCAPTCHA removal
     */
    private function setup_nuclear_recaptcha() {
        // Early removal
        add_action('wp_enqueue_scripts', array($this, 'nuclear_remove_recaptcha'), 1);
        
        // Late removal
        add_action('wp_print_scripts', array($this, 'nuclear_remove_recaptcha_late'), 999);
        
        // JavaScript blocking
        add_action('wp_head', array($this, 'inject_recaptcha_blocker'), 1);
        
        // Single injection
        add_action('wp_footer', array($this, 'inject_single_recaptcha'), 999);
        
        // Tag filtering
        add_filter('script_loader_tag', array($this, 'filter_recaptcha_tags'), 10, 3);
    }
    
    /**
     * Nuclear remove reCAPTCHA - Early
     */
    public function nuclear_remove_recaptcha() {
        global $wp_scripts;
        
        $kept_recaptcha = false;
        
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!empty($script->src) && $this->is_recaptcha_resource($script->src)) {
                $this->recaptcha_sources[$handle] = $script->src;
                
                // Keep only one with valid key
                if (!$kept_recaptcha && strpos($script->src, self::VALID_RECAPTCHA_KEY) !== false) {
                    $kept_recaptcha = true;
                    $this->log_fix("Keeping valid reCAPTCHA: $handle");
                } else {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    $this->removed_scripts[] = $handle;
                    $this->log_fix("Removed reCAPTCHA: $handle");
                }
            }
        }
    }
    
    /**
     * Nuclear remove reCAPTCHA - Late
     */
    public function nuclear_remove_recaptcha_late() {
        global $wp_scripts;
        
        $recaptcha_count = 0;
        
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle]->src)) {
                $src = $wp_scripts->registered[$handle]->src;
                
                if ($this->is_recaptcha_resource($src)) {
                    $recaptcha_count++;
                    
                    if ($recaptcha_count > 1) {
                        wp_dequeue_script($handle);
                        $this->removed_scripts[] = $handle;
                        $this->log_fix("Late removal reCAPTCHA: $handle");
                    }
                }
            }
        }
    }
    
    /**
     * Check if resource is reCAPTCHA
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
     * Inject reCAPTCHA blocker JavaScript
     */
    public function inject_recaptcha_blocker() {
        ?>
        <script id="vidieu-recaptcha-blocker">
        (function() {
            window.__vidieuRecaptchaLoaded = false;
            var originalAppend = Element.prototype.appendChild;
            
            Element.prototype.appendChild = function(child) {
                if (child && child.tagName === 'SCRIPT' && child.src && 
                    (child.src.includes('recaptcha') || child.src.includes('gstatic'))) {
                    
                    if (window.__vidieuRecaptchaLoaded) {
                        console.log('[Vidieu] Blocked duplicate reCAPTCHA:', child.src);
                        return child;
                    }
                    
                    if (child.src.includes('<?php echo self::VALID_RECAPTCHA_KEY; ?>')) {
                        window.__vidieuRecaptchaLoaded = true;
                        console.log('[Vidieu] Allowed valid reCAPTCHA');
                    } else {
                        console.log('[Vidieu] Blocked invalid reCAPTCHA:', child.src);
                        return child;
                    }
                }
                return originalAppend.apply(this, arguments);
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Inject single reCAPTCHA
     */
    public function inject_single_recaptcha() {
        // Only on pages that need it
        if (!is_page('contact') && !is_checkout() && !is_page('lien-he')) {
            return;
        }
        
        ?>
        <script id="vidieu-single-recaptcha">
        if (!window.__vidieuRecaptchaLoaded && !window.grecaptcha) {
            var script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/api.js?render=<?php echo self::VALID_RECAPTCHA_KEY; ?>';
            script.async = true;
            script.defer = true;
            document.body.appendChild(script);
            window.__vidieuRecaptchaLoaded = true;
            console.log('[Vidieu] Injected single reCAPTCHA');
        }
        </script>
        <?php
    }
    
    /**
     * Filter reCAPTCHA tags
     */
    public function filter_recaptcha_tags($tag, $handle, $src) {
        static $recaptcha_rendered = false;
        
        if ($this->is_recaptcha_resource($src)) {
            if ($recaptcha_rendered) {
                $this->log_fix("Tag filter blocked: $handle");
                return '<!-- Blocked by tag filter: ' . esc_html($handle) . ' -->';
            }
            $recaptcha_rendered = true;
        }
        
        return $tag;
    }
    
    /**
     * STEP 3: Setup route optimization
     */
    public function setup_route_optimization() {
        if (is_cart()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_cart_whitelist'), 999);
            $this->log_fix('Cart whitelist optimization triggered');
        } elseif (is_checkout()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_checkout_whitelist'), 999);
            $this->log_fix('Checkout whitelist optimization triggered');
        } elseif (is_page('contact') || is_page('lien-he')) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_contact_page'), 999);
            $this->log_fix('Contact optimization triggered');
        }
    }
    
    /**
     * Cart whitelist optimization
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
            'elessi-theme-js',
            'elessi-js-ajax-load'
        );
        
        // Remove non-whitelisted scripts
        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $allowed_scripts) && strpos($handle, 'wc-') !== 0) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
            }
        }
        
        // Essential styles for cart
        $allowed_styles = array(
            'woocommerce-layout',
            'woocommerce-general',
            'woocommerce-smallscreen',
            'elessi-style',
            'elessi-style-main',
            'elessi-style-responsive',
            'elessi-style-dynamic'
        );
        
        // Remove non-whitelisted styles  
        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $allowed_styles) && strpos($handle, 'wc-') !== 0) {
                wp_dequeue_style($handle);
                $this->removed_styles[] = $handle;
            }
        }
        
        $this->log_fix('Cart whitelist applied: ' . count($this->removed_scripts) . ' scripts, ' . count($this->removed_styles) . ' styles removed');
    }
    
    /**
     * Checkout whitelist optimization
     */
    public function optimize_checkout_whitelist() {
        global $wp_scripts;
        
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
        
        // Add payment gateway scripts
        $payment_methods = array('stripe', 'paypal', 'square', 'authorize');
        foreach ($payment_methods as $method) {
            $allowed_scripts[] = 'wc-' . $method;
            $allowed_scripts[] = 'woocommerce_' . $method;
        }
        
        // Remove non-whitelisted scripts
        foreach ($wp_scripts->queue as $handle) {
            $keep = false;
            
            // Check whitelist
            if (in_array($handle, $allowed_scripts)) {
                $keep = true;
            }
            
            // Keep payment-related scripts
            if (strpos($handle, 'payment') !== false || 
                strpos($handle, 'stripe') !== false ||
                strpos($handle, 'paypal') !== false) {
                $keep = true;
            }
            
            if (!$keep) {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
            }
        }
        
        $this->log_fix('Checkout whitelist applied: ' . count($this->removed_scripts) . ' scripts removed');
    }
    
    /**
     * Contact page optimization
     */
    public function optimize_contact_page() {
        global $wp_scripts;
        
        // Remove WooCommerce scripts except cart fragments
        foreach ($wp_scripts->queue as $handle) {
            if ((strpos($handle, 'wc-') === 0 || strpos($handle, 'woocommerce') === 0) 
                && $handle !== 'wc-cart-fragments') {
                wp_dequeue_script($handle);
                $this->removed_scripts[] = $handle;
            }
        }
        
        $this->log_fix('Contact page optimization: removed ' . count($this->removed_scripts) . ' WooCommerce scripts');
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
        if (is_archive()) return 'Archive';
        return 'Other';
    }
    
    /**
     * Output debug info
     */
    public function output_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $total_removed = count($this->removed_scripts) + count($this->removed_styles);
        
        echo "\n<!-- Vidieu V2 Stepped Optimization Report\n";
        echo "=====================================\n";
        echo "Page Type: " . $this->get_current_page_type() . "\n";
        echo "Features Enabled:\n";
        echo " - Basic Optimization: " . (self::ENABLE_BASIC_OPTIMIZATION ? 'YES' : 'NO') . "\n";
        echo " - Nuclear reCAPTCHA: " . (self::ENABLE_NUCLEAR_RECAPTCHA ? 'YES' : 'NO') . "\n";
        echo " - Cart/Checkout Whitelist: " . (self::ENABLE_CART_CHECKOUT_WHITELIST ? 'YES' : 'NO') . "\n";
        echo "\nResults:\n";
        echo " - Total optimizations: " . count($this->fixes_log) . "\n";
        echo " - Scripts removed: " . count($this->removed_scripts) . "\n";
        echo " - Styles removed: " . count($this->removed_styles) . "\n";
        echo " - Total removed: " . $total_removed . "\n";
        
        if (!empty($this->recaptcha_sources)) {
            echo "\nreCAPTCHA Sources Found:\n";
            foreach ($this->recaptcha_sources as $handle => $src) {
                echo " - $handle: " . basename($src) . "\n";
            }
        }
        
        echo "\nDetailed Log:\n";
        foreach ($this->fixes_log as $i => $log) {
            echo ($i + 1) . ". " . esc_html($log) . "\n";
        }
        
        echo "\nKill Switch: " . (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION) ? 'ACTIVE' : 'inactive') . "\n";
        echo "-->\n";
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_Dup_Requests_Guard_V2_Stepped::get_instance();
}, 1);