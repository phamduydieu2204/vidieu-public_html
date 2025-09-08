<?php
/**
 * Vidieu Duplicate Requests Guard
 * 
 * Xử lý và loại bỏ các request trùng lặp để tối ưu performance
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Dup_Requests_Guard {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Emergency kill switch
     */
    const DISABLE_OPTIMIZATION = 'VIDIEU_DISABLE_DUP_OPTIMIZATION';
    
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
        
        // Skip admin
        if (is_admin()) {
            return;
        }
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Phase 1: Critical fixes
        add_filter('style_loader_src', array($this, 'fix_font_paths'), 999, 2);
        add_action('wp_enqueue_scripts', array($this, 'fix_missing_styles'), 999);
        add_action('wp_enqueue_scripts', array($this, 'fix_elementor_fonts'), 998);
        
        // reCAPTCHA consolidation
        add_action('wp_print_scripts', array($this, 'consolidate_recaptcha'), 5);
        add_filter('script_loader_tag', array($this, 'remove_duplicate_recaptcha_inline'), 10, 3);
        
        // Phase 2: AJAX optimization
        add_action('wp_footer', array($this, 'optimize_ajax_calls'), 1);
        
        // CSS duplication fix
        add_action('wp_enqueue_scripts', array($this, 'fix_child_style_duplication'), 999);
        
        // Phase 3: Route-based optimization
        add_action('wp_enqueue_scripts', array($this, 'optimize_by_route'), 1000);
        
        // Logging
        add_action('wp_footer', array($this, 'log_optimizations'), 9999);
    }
    
    /**
     * Fix font paths (404 errors)
     */
    public function fix_font_paths($src, $handle) {
        if (strpos($src, 'main-font.woff2') !== false && strpos($src, 'elessi-theme/assets/fonts/') !== false) {
            // Check if file exists in child theme
            $child_font = get_stylesheet_directory() . '/assets/fonts/main-font.woff2';
            if (file_exists($child_font)) {
                $src = str_replace(
                    '/elessi-theme/assets/fonts/',
                    '/elessi-theme-child/assets/fonts/',
                    $src
                );
                $this->log_fix('Fixed font path for main-font.woff2');
            } else {
                // Fallback to parent theme non-minified
                $src = str_replace('main-font.woff2', 'main-font.woff', $src);
                $this->log_fix('Fallback font path for main-font');
            }
        }
        return $src;
    }
    
    /**
     * Fix missing theme styles
     */
    public function fix_missing_styles() {
        global $wp_styles;
        
        // Fix missing style.min.css
        foreach ($wp_styles->registered as $handle => $style) {
            if (strpos($style->src, 'elessi-theme/style.min.css') !== false) {
                // Check if minified exists
                $min_path = get_template_directory() . '/style.min.css';
                if (!file_exists($min_path)) {
                    // Use non-minified version
                    $style->src = str_replace('style.min.css', 'style.css', $style->src);
                    $this->log_fix('Fixed missing style.min.css');
                }
            }
        }
    }
    
    /**
     * Fix Elementor Google Fonts
     */
    public function fix_elementor_fonts() {
        // Option 1: Disable Elementor Google Fonts file generation
        add_filter('elementor/files/file_generataor/create_file', function($should_create, $file_data) {
            if (isset($file_data['name']) && strpos($file_data['name'], 'google-fonts') !== false) {
                $this->log_fix('Prevented Elementor Google Fonts file creation');
                return false;
            }
            return $should_create;
        }, 10, 2);
        
        // Option 2: Remove 404 font files from queue
        add_action('wp_print_styles', function() {
            global $wp_styles;
            foreach ($wp_styles->queue as $handle) {
                if (isset($wp_styles->registered[$handle])) {
                    $src = $wp_styles->registered[$handle]->src;
                    if (strpos($src, 'uploads/elementor/google-fonts') !== false) {
                        // Check if file exists
                        $path = str_replace(content_url(), WP_CONTENT_DIR, $src);
                        $path = strtok($path, '?'); // Remove query string
                        if (!file_exists($path)) {
                            wp_dequeue_style($handle);
                            $this->log_fix('Removed missing Elementor font: ' . $handle);
                        }
                    }
                }
            }
        }, 999);
    }
    
    /**
     * Consolidate reCAPTCHA scripts
     */
    public function consolidate_recaptcha() {
        global $wp_scripts;
        
        $recaptcha_handles = array();
        $valid_key = '6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ'; // WP Captcha key
        $found_valid = false;
        
        // Find all reCAPTCHA instances
        foreach ($wp_scripts->registered as $handle => $script) {
            $src = $script->src ?? '';
            
            if (strpos($src, 'google.com/recaptcha/api.js') !== false ||
                strpos($src, 'gstatic.com/recaptcha') !== false) {
                
                $recaptcha_handles[] = $handle;
                
                // Check if this is the valid one
                if (strpos($src, $valid_key) !== false) {
                    $found_valid = $handle;
                }
            }
        }
        
        // Remove all except the valid one
        foreach ($recaptcha_handles as $handle) {
            if ($handle !== $found_valid) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
                $this->log_fix('Removed duplicate reCAPTCHA: ' . $handle);
            }
        }
    }
    
    /**
     * Remove inline duplicate reCAPTCHA
     */
    public function remove_duplicate_recaptcha_inline($tag, $handle, $src) {
        // Remove invalid API key script
        if (strpos($src, '6LdAg_UqAAAAANiCMOwwgJ_I3We6DGQ3T5LohPX0') !== false) {
            $this->log_fix('Removed invalid reCAPTCHA API key script');
            return '';
        }
        
        // Remove duplicate gstatic loads
        static $recaptcha_loaded = false;
        if (strpos($src, 'gstatic.com/recaptcha') !== false) {
            if ($recaptcha_loaded) {
                $this->log_fix('Prevented duplicate reCAPTCHA gstatic load');
                return '';
            }
            $recaptcha_loaded = true;
        }
        
        return $tag;
    }
    
    /**
     * Optimize AJAX calls
     */
    public function optimize_ajax_calls() {
        ?>
        <script>
        /* Vidieu AJAX Deduplication */
        (function() {
            if (typeof jQuery === 'undefined') return;
            
            var ajaxQueue = {};
            var originalAjax = jQuery.ajax;
            
            jQuery.ajax = function(options) {
                if (!options || !options.url) return originalAjax.apply(this, arguments);
                
                // Only dedupe specific endpoints
                if (options.url.indexOf('admin-ajax.php') === -1) {
                    return originalAjax.apply(this, arguments);
                }
                
                var key = options.url + '?' + jQuery.param(options.data || {});
                
                // Skip if same request already pending
                if (ajaxQueue[key] && ajaxQueue[key].state && ajaxQueue[key].state() === 'pending') {
                    console.log('[Vidieu Perf] Prevented duplicate AJAX: ' + key);
                    return ajaxQueue[key];
                }
                
                ajaxQueue[key] = originalAjax.apply(this, arguments);
                
                // Clean up after completion
                if (ajaxQueue[key] && ajaxQueue[key].always) {
                    ajaxQueue[key].always(function() {
                        delete ajaxQueue[key];
                    });
                }
                
                return ajaxQueue[key];
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Fix child theme style duplication
     */
    public function fix_child_style_duplication() {
        global $wp_styles;
        
        $child_style_url = get_stylesheet_directory_uri() . '/style.css';
        $found_handles = array();
        
        // Find all instances
        foreach ($wp_styles->registered as $handle => $style) {
            if ($style->src && strpos($style->src, $child_style_url) !== false) {
                $found_handles[$handle] = $style->ver;
            }
        }
        
        // Keep only one with child theme version
        if (count($found_handles) > 1) {
            $keep_handle = null;
            
            // Prefer the one with version 1.0.0 (child theme version)
            foreach ($found_handles as $handle => $version) {
                if ($version === '1.0.0') {
                    $keep_handle = $handle;
                    break;
                }
            }
            
            // Fallback to first one
            if (!$keep_handle) {
                $keep_handle = array_key_first($found_handles);
            }
            
            // Remove others
            foreach ($found_handles as $handle => $version) {
                if ($handle !== $keep_handle) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                    $this->log_fix('Removed duplicate child style: ' . $handle . ' (ver: ' . $version . ')');
                }
            }
        }
    }
    
    /**
     * Route-based optimization
     */
    public function optimize_by_route() {
        // Skip if kill switch enabled
        if (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION)) {
            return;
        }
        
        // Contact page optimizations
        if (is_page('contact')) {
            // Remove WooCommerce scripts not needed on contact
            $woo_scripts = array(
                'wc-add-to-cart',
                'wc-cart-fragments', 
                'wc-single-product',
                'yith-woocompare-main'
            );
            
            foreach ($woo_scripts as $handle) {
                wp_dequeue_script($handle);
                $this->log_fix('Removed unnecessary script on contact: ' . $handle);
            }
        }
        
        // Non-product pages
        if (!is_shop() && !is_product_category() && !is_product() && !is_cart() && !is_checkout()) {
            // Remove YITH Compare
            wp_dequeue_script('yith-woocompare-main');
            wp_dequeue_style('yith-woocompare-widget');
            $this->log_fix('Removed YITH Compare on non-product page');
        }
        
        // Blog/Post pages
        if (is_single() && get_post_type() === 'post') {
            // Can remove some WooCommerce assets
            if (!has_shortcode(get_post()->post_content, 'products')) {
                wp_dequeue_script('wc-add-to-cart-variation');
                $this->log_fix('Removed WC variation script on blog post');
            }
        }
    }
    
    /**
     * Log optimizations
     */
    private $fixes_log = array();
    
    private function log_fix($message) {
        $this->fixes_log[] = $message;
    }
    
    public function log_optimizations() {
        if (!empty($this->fixes_log) && current_user_can('manage_options')) {
            echo "\n<!-- Vidieu Duplicate Requests Guard Log:\n";
            foreach ($this->fixes_log as $log) {
                echo " - " . esc_html($log) . "\n";
            }
            echo "-->\n";
        }
    }
}

// Initialize
add_action('init', function() {
    Vidieu_Dup_Requests_Guard::get_instance();
});