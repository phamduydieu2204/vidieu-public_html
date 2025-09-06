<?php
/**
 * Vidieu Performance Optimization for HOME
 * 
 * @package VidieuHomeSections
 * @version 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Perf_Home {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only run on frontend HOME page with master flag enabled
        if (!is_admin() && defined('VIDIEU_PERF_HOME_ENABLE') && VIDIEU_PERF_HOME_ENABLE) {
            add_action('init', array($this, 'init'));
        }
    }
    
    /**
     * Initialize performance optimizations
     */
    public function init() {
        // Only apply to HOME page
        add_action('wp', array($this, 'setup_home_optimizations'));
    }
    
    /**
     * Setup optimizations for HOME page only
     */
    public function setup_home_optimizations() {
        if (!is_front_page()) {
            return;
        }
        
        // H1.2 - Fix 404 resources
        if (defined('VIDIEU_PERF_HOME_FIX_404') && VIDIEU_PERF_HOME_FIX_404) {
            $this->fix_missing_resources();
        }
        
        // H1.2b - Disable reCAPTCHA on HOME
        if (defined('VIDIEU_PERF_HOME_DISABLE_RECAPTCHA_ON_HOME') && VIDIEU_PERF_HOME_DISABLE_RECAPTCHA_ON_HOME) {
            $this->disable_recaptcha_on_home();
        }
        
        // H2.1 - Defer JS (future implementation)
        if (defined('VIDIEU_PERF_HOME_DEFER_JS') && VIDIEU_PERF_HOME_DEFER_JS) {
            add_filter('script_loader_tag', array($this, 'defer_non_critical_scripts'), 10, 3);
        }
        
        // H2.2 - Critical CSS (future implementation)
        if (defined('VIDIEU_PERF_HOME_CRITICAL_CSS') && VIDIEU_PERF_HOME_CRITICAL_CSS) {
            add_action('wp_head', array($this, 'inline_critical_css'), 5);
        }
        
        // H2.4 - Font optimization (future implementation)
        if (defined('VIDIEU_PERF_HOME_FONT_OPTIMIZE') && VIDIEU_PERF_HOME_FONT_OPTIMIZE) {
            add_action('wp_head', array($this, 'optimize_font_loading'), 2);
        }
        
        // H3.1 - Lazy images (future implementation)
        if (defined('VIDIEU_PERF_HOME_LAZY_IMAGES') && VIDIEU_PERF_HOME_LAZY_IMAGES) {
            add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'), 10, 3);
        }
    }
    
    /**
     * H1.2 - Fix missing resources and 404 errors
     */
    private function fix_missing_resources() {
        // Remove preloads for non-existent files
        remove_action('wp_head', array($this, 'remove_broken_preloads'), 1);
        add_action('wp_head', array($this, 'fix_preloads'), 1);
        
        // Fix Elementor Google Fonts
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'fix_elementor_fonts'), 20);
        
        // Remove broken resource references
        add_action('wp_enqueue_scripts', array($this, 'clean_broken_resources'), 999);
        
        // H1.2a - Remove any preload hints for non-existent resources
        add_filter('wp_resource_hints', array($this, 'clean_resource_hints'), 10, 2);
        
        // H1.2a - Filter out broken stylesheet links
        add_filter('style_loader_tag', array($this, 'filter_broken_stylesheets'), 999, 4);
    }
    
    /**
     * Fix preload tags
     */
    public function fix_preloads() {
        // Only preload resources that actually exist
        // Remove the broken preloads from functions-performance.php
        ?>
        <!-- Performance: Fixed preloads for HOME -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <?php
        
        // Only preload existing parent theme style
        $parent_style = get_template_directory_uri() . '/style.css';
        echo '<link rel="preload" href="' . esc_url($parent_style) . '" as="style">' . "\n";
    }
    
    /**
     * Fix Elementor Google Fonts 404
     */
    public function fix_elementor_fonts() {
        global $wp_styles;
        
        // H1.2a - Remove all Elementor local Google Fonts that may cause 404
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                // Check for Elementor Google Fonts patterns
                if (strpos($style->src, '/uploads/elementor/google-fonts/') !== false ||
                    strpos($style->src, 'elementor-gf-') !== false ||
                    preg_match('/google-fonts-\d+/', $handle)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
        
        // Re-register Jost font from Google CDN with display=swap
        if ($this->is_jost_font_needed()) {
            wp_enqueue_style(
                'vidieu-google-fonts-jost',
                'https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600&display=swap',
                array(),
                null
            );
        }
    }
    
    /**
     * Clean up broken resource references
     */
    public function clean_broken_resources() {
        global $wp_styles;
        
        // Remove any style that references style.min.css
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                if (strpos($style->src, 'style.min.css') !== false) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
    }
    
    /**
     * Check if Jost font is needed
     */
    private function is_jost_font_needed() {
        // You can add logic here to detect if Jost font is actually used
        // For now, return true to maintain current design
        return true;
    }
    
    // Placeholder methods for future implementations
    
    public function defer_non_critical_scripts($tag, $handle, $src) {
        // H2.1 implementation will go here
        return $tag;
    }
    
    public function inline_critical_css() {
        // H2.2 implementation will go here
    }
    
    public function optimize_font_loading() {
        // H2.4 implementation will go here
    }
    
    public function add_lazy_loading($attr, $attachment, $size) {
        // H3.1 implementation will go here
        return $attr;
    }
    
    /**
     * H1.2a - Clean resource hints to prevent preloading non-existent files
     */
    public function clean_resource_hints($urls, $relation_type) {
        if ($relation_type === 'preload') {
            $filtered_urls = array();
            foreach ($urls as $url) {
                // Skip URLs that contain elementor google fonts uploads
                if (strpos($url, '/uploads/elementor/google-fonts/') === false &&
                    strpos($url, 'jost.css') === false) {
                    $filtered_urls[] = $url;
                }
            }
            return $filtered_urls;
        }
        return $urls;
    }
    
    /**
     * H1.2a - Filter stylesheet tags to prevent loading broken CSS files
     */
    public function filter_broken_stylesheets($html, $handle, $href, $media) {
        // Remove any link tag that references non-existent Elementor Google Fonts
        if (strpos($href, '/uploads/elementor/google-fonts/') !== false ||
            strpos($href, 'jost.css') !== false) {
            // Return empty to prevent rendering
            return '';
        }
        
        // Also check for preload links
        if (strpos($html, 'rel="preload"') !== false || 
            strpos($html, "rel='preload'") !== false) {
            if (strpos($href, '/uploads/elementor/google-fonts/') !== false ||
                strpos($href, 'jost.css') !== false ||
                strpos($href, 'main-font.woff2') !== false ||
                strpos($href, 'style.min.css') !== false) {
                return '';
            }
        }
        
        return $html;
    }
    
    /**
     * H1.2b - Disable reCAPTCHA on HOME page only
     */
    private function disable_recaptcha_on_home() {
        // Remove reCAPTCHA scripts
        add_action('wp_enqueue_scripts', array($this, 'dequeue_recaptcha_scripts'), 999);
        
        // Add guard code for inline scripts
        add_action('wp_footer', array($this, 'add_recaptcha_guard_code'), 1);
        
        // Remove reCAPTCHA from script loader
        add_filter('script_loader_src', array($this, 'filter_recaptcha_scripts'), 999, 2);
    }
    
    /**
     * Dequeue all reCAPTCHA scripts on HOME
     */
    public function dequeue_recaptcha_scripts() {
        // Contact Form 7 reCAPTCHA
        wp_dequeue_script('wpcf7-recaptcha');
        wp_deregister_script('wpcf7-recaptcha');
        wp_dequeue_script('google-recaptcha');
        wp_deregister_script('google-recaptcha');
        
        // Advanced Google reCAPTCHA plugin
        wp_dequeue_script('wpcaptcha-recaptcha');
        wp_deregister_script('wpcaptcha-recaptcha');
        
        // Other possible handles
        wp_dequeue_script('recaptcha');
        wp_deregister_script('recaptcha');
        wp_dequeue_script('google-recaptcha-js');
        wp_deregister_script('google-recaptcha-js');
    }
    
    /**
     * Filter out reCAPTCHA scripts via URL
     */
    public function filter_recaptcha_scripts($src, $handle) {
        // Block any script from google.com/recaptcha
        if (strpos($src, 'google.com/recaptcha') !== false || 
            strpos($src, 'recaptcha/api.js') !== false ||
            strpos($src, 'grecaptcha') !== false) {
            return '';
        }
        return $src;
    }
    
    /**
     * Add guard code to prevent grecaptcha errors
     */
    public function add_recaptcha_guard_code() {
        ?>
        <script id="vidieu-recaptcha-guard">
        // H1.2b - Guard code for reCAPTCHA on HOME page
        (function() {
            // Create dummy grecaptcha object to prevent errors
            if (typeof window.grecaptcha === 'undefined') {
                window.grecaptcha = {
                    ready: function(callback) {
                        // Do nothing on HOME page
                    },
                    execute: function() {
                        // Return dummy promise
                        return Promise.resolve('home-page-no-recaptcha');
                    },
                    render: function() {
                        // Do nothing
                        return null;
                    },
                    reset: function() {
                        // Do nothing
                    },
                    getResponse: function() {
                        return '';
                    }
                };
            }
            
            // Override any inline code that tries to load reCAPTCHA
            if (typeof window.wpcaptcha_captcha === 'function') {
                window.wpcaptcha_captcha = function() {
                    // Do nothing on HOME
                };
            }
        })();
        </script>
        <?php
    }
}

// Initialize the class
new Vidieu_Perf_Home();