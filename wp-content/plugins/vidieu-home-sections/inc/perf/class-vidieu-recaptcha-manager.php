<?php
/**
 * Vidieu ReCAPTCHA Manager
 * 
 * Centralized ReCAPTCHA management to prevent duplicate loading
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_ReCAPTCHA_Manager {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * ReCAPTCHA tracking
     */
    private $recaptcha_loaded = false;
    private $recaptcha_sources = array();
    private $allowed_handles = array();
    private $site_key = null;
    private $loaded_by = null;
    
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
        // Very early hook to intercept all ReCAPTCHA attempts
        add_action('init', array($this, 'init'), 1);
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Determine which ReCAPTCHA implementation to use
        $this->determine_recaptcha_source();
        
        // Hook into script registration/enqueue
        add_action('wp_enqueue_scripts', array($this, 'manage_recaptcha_loading'), 1);
        add_action('wp_print_scripts', array($this, 'late_recaptcha_check'), 999);
        add_filter('script_loader_tag', array($this, 'filter_duplicate_recaptcha'), 10, 3);
        
        // Remove inline ReCAPTCHA scripts
        add_action('wp_head', array($this, 'block_inline_recaptcha'), 1);
        add_action('wp_footer', array($this, 'block_inline_recaptcha'), 1);
        
        // Hook into plugin-specific actions
        $this->hook_into_plugins();
    }
    
    /**
     * Determine which ReCAPTCHA source to use
     */
    private function determine_recaptcha_source() {
        // Priority order:
        // 1. Contact Form 7 (if contact page)
        // 2. Advanced Google reCAPTCHA (if enabled globally)
        // 3. WooCommerce (if checkout)
        
        if ((is_page('contact') || is_page('lien-he')) && class_exists('WPCF7')) {
            $this->allowed_handles = array('google-recaptcha', 'wpcf7-recaptcha');
            $this->loaded_by = 'contact-form-7';
        } elseif (class_exists('WPCaptcha_Functions')) {
            // Check if Advanced Google reCAPTCHA is configured
            $options = get_option('advanced-google-recaptcha', array());
            if (!empty($options['captcha_site_key'])) {
                $this->site_key = $options['captcha_site_key'];
                $this->allowed_handles = array('wpcaptcha-recaptcha');
                $this->loaded_by = 'advanced-google-recaptcha';
            }
        }
    }
    
    /**
     * Hook into plugins to prevent their ReCAPTCHA loading
     */
    private function hook_into_plugins() {
        // Advanced Google reCAPTCHA
        if (class_exists('WPCaptcha_Functions') && $this->loaded_by !== 'advanced-google-recaptcha') {
            remove_action('login_enqueue_scripts', array('WPCaptcha_Functions', 'enqueue_scripts'));
            remove_action('wp_enqueue_scripts', array('WPCaptcha_Functions', 'enqueue_scripts'));
            
            // Remove from specific forms
            remove_filter('login_form', array('WPCaptcha_Functions', 'login_form_captcha'));
            remove_filter('register_form', array('WPCaptcha_Functions', 'register_form_captcha'));
            remove_filter('comment_form_after_fields', array('WPCaptcha_Functions', 'comment_form_captcha'));
            remove_filter('woocommerce_checkout_after_customer_details', array('WPCaptcha_Functions', 'woo_checkout_form_captcha'));
        }
        
        // Contact Form 7
        if (class_exists('WPCF7') && $this->loaded_by !== 'contact-form-7') {
            add_filter('wpcf7_load_js', '__return_false');
            add_filter('wpcf7_load_css', '__return_false');
            
            // Only load on contact pages
            add_action('wp_enqueue_scripts', function() {
                if (!is_page('contact') && !is_page('lien-he')) {
                    wp_dequeue_script('google-recaptcha');
                    wp_dequeue_script('wpcf7-recaptcha');
                    wp_deregister_script('google-recaptcha');
                    wp_deregister_script('wpcf7-recaptcha');
                }
            }, 100);
        }
    }
    
    /**
     * Manage ReCAPTCHA loading
     */
    public function manage_recaptcha_loading() {
        global $wp_scripts;
        
        // Track all ReCAPTCHA attempts
        foreach ($wp_scripts->registered as $handle => $script) {
            if ($this->is_recaptcha_script($handle, $script)) {
                $this->recaptcha_sources[$handle] = array(
                    'src' => $script->src ?? '',
                    'deps' => $script->deps ?? array(),
                    'ver' => $script->ver ?? '',
                );
                
                // If not in allowed list, remove it
                if (!in_array($handle, $this->allowed_handles)) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
        }
        
        // Ensure only one ReCAPTCHA loads
        if ($this->loaded_by && !$this->recaptcha_loaded) {
            $this->load_single_recaptcha();
        }
    }
    
    /**
     * Check if script is ReCAPTCHA related
     */
    private function is_recaptcha_script($handle, $script) {
        $patterns = array(
            'recaptcha', 'grecaptcha', 'google.com/recaptcha', 'gstatic.com/recaptcha'
        );
        
        // Check handle
        foreach ($patterns as $pattern) {
            if (stripos($handle, $pattern) !== false) {
                return true;
            }
        }
        
        // Check source
        if (!empty($script->src)) {
            foreach ($patterns as $pattern) {
                if (stripos($script->src, $pattern) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Load single ReCAPTCHA instance
     */
    private function load_single_recaptcha() {
        if ($this->recaptcha_loaded) {
            return;
        }
        
        // Conditional loading based on page
        if ($this->should_load_recaptcha()) {
            if ($this->loaded_by === 'contact-form-7') {
                // Let CF7 handle it on contact pages
                add_filter('wpcf7_load_js', '__return_true');
            } elseif ($this->loaded_by === 'advanced-google-recaptcha' && $this->site_key) {
                // Load single instance
                wp_enqueue_script(
                    'vidieu-unified-recaptcha',
                    'https://www.google.com/recaptcha/api.js?render=' . esc_attr($this->site_key),
                    array(),
                    null,
                    true
                );
                $this->recaptcha_loaded = true;
            }
        }
    }
    
    /**
     * Check if ReCAPTCHA should load on current page
     */
    private function should_load_recaptcha() {
        // Contact pages
        if (is_page('contact') || is_page('lien-he')) {
            return true;
        }
        
        // Login/Register pages
        if (is_account_page() || is_page('login') || is_page('register')) {
            return true;
        }
        
        // Checkout page (if configured)
        if (is_checkout() && $this->loaded_by === 'advanced-google-recaptcha') {
            return true;
        }
        
        // Comment forms (if configured)
        if (is_single() && comments_open() && $this->loaded_by === 'advanced-google-recaptcha') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Late check for ReCAPTCHA scripts
     */
    public function late_recaptcha_check() {
        global $wp_scripts;
        
        $removed = 0;
        foreach ($wp_scripts->queue as $handle) {
            if ($this->is_recaptcha_script($handle, $wp_scripts->registered[$handle] ?? null)) {
                if (!in_array($handle, $this->allowed_handles)) {
                    wp_dequeue_script($handle);
                    $removed++;
                }
            }
        }
        
        if ($removed > 0 && current_user_can('manage_options')) {
            echo "\n<!-- Vidieu ReCAPTCHA Manager: Removed $removed duplicate ReCAPTCHA scripts -->\n";
        }
    }
    
    /**
     * Filter duplicate ReCAPTCHA tags
     */
    public function filter_duplicate_recaptcha($tag, $handle, $src) {
        // If ReCAPTCHA already loaded, block subsequent attempts
        if ($this->is_recaptcha_url($src)) {
            if ($this->recaptcha_loaded && !in_array($handle, $this->allowed_handles)) {
                return '<!-- Blocked duplicate ReCAPTCHA: ' . esc_html($handle) . ' -->';
            }
            $this->recaptcha_loaded = true;
        }
        
        return $tag;
    }
    
    /**
     * Check if URL is ReCAPTCHA
     */
    private function is_recaptcha_url($url) {
        $patterns = array(
            'google.com/recaptcha',
            'gstatic.com/recaptcha',
            'recaptcha/api.js'
        );
        
        foreach ($patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Block inline ReCAPTCHA scripts
     */
    public function block_inline_recaptcha() {
        if (!$this->should_load_recaptcha()) {
            ?>
            <script id="vidieu-recaptcha-inline-blocker">
            (function() {
                // Block grecaptcha object creation
                Object.defineProperty(window, 'grecaptcha', {
                    get: function() { return undefined; },
                    set: function() { /* Blocked grecaptcha assignment */ },
                    configurable: false
                });
            })();
            </script>
            <?php
        }
    }
    
    /**
     * Get debug info
     */
    public function get_debug_info() {
        return array(
            'loaded_by' => $this->loaded_by,
            'allowed_handles' => $this->allowed_handles,
            'sources_found' => array_keys($this->recaptcha_sources),
            'recaptcha_loaded' => $this->recaptcha_loaded,
            'should_load' => $this->should_load_recaptcha(),
        );
    }
}

// Initialize
add_action('plugins_loaded', function() {
    Vidieu_ReCAPTCHA_Manager::get_instance();
}, 1);