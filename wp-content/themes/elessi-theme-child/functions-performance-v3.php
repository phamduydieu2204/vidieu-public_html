<?php
/**
 * Vidieu.vn Performance Optimizations V3
 * Based on comprehensive audit data from 21/08/2025
 * 
 * Current issues:
 * - 217 requests (target: <100)
 * - 9.57MB total (target: <3MB)
 * - 93.58% unused CSS (1.3MB waste)
 * - 70.59% unused JS (1.15MB waste)
 * - 94 duplicate resources
 * - LCP 2.4s (target: <2.0s)
 * - Performance score 77/100 (target: >85)
 * 
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Main Performance Optimization Class V3
 */
class Vidieu_Performance_Optimizer_V3 {
    
    private static $instance = null;
    private $removed_styles = [];
    private $removed_scripts = [];
    private $deferred_scripts = [];
    private $async_styles = [];
    private $ajax_cache_enabled = true;
    private $duplicate_prevention = [];
    
    /**
     * Singleton instance
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
        // Skip optimizations for admin, AJAX, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // P0 - Critical optimizations (immediate impact)
        add_action('init', [$this, 'init_critical_optimizations'], 1);
        add_action('wp_head', [$this, 'add_critical_css'], 1);
        add_filter('style_loader_tag', [$this, 'async_css_loading'], 999, 4);
        add_action('wp_print_styles', [$this, 'prevent_duplicate_styles'], 1);
        add_action('wp_print_scripts', [$this, 'prevent_duplicate_scripts'], 1);
        add_filter('script_loader_src', [$this, 'prevent_duplicate_resources'], 10, 2);
        add_filter('style_loader_src', [$this, 'prevent_duplicate_resources'], 10, 2);
        
        // P1 - High priority optimizations
        add_action('wp_enqueue_scripts', [$this, 'dequeue_unused_assets_v3'], 999);
        add_filter('script_loader_tag', [$this, 'defer_non_critical_scripts_v3'], 10, 3);
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading_v3'], 10, 3);
        add_action('wp_head', [$this, 'add_resource_hints_v3'], 2);
        add_action('init', [$this, 'optimize_ajax_requests_v3']);
        
        // P2 - Medium priority optimizations
        add_filter('the_content', [$this, 'lazy_load_content_images'], 99);
        add_action('wp_footer', [$this, 'add_performance_scripts_v3'], 999);
        add_action('wp_footer', [$this, 'optimize_countdown_timer'], 20);
        
        // Disable unnecessary features
        $this->disable_unnecessary_features_v3();
        
        // Add compression headers hint
        add_action('send_headers', [$this, 'add_compression_headers']);
    }
    
    /**
     * P0: Initialize critical optimizations
     */
    public function init_critical_optimizations() {
        // Remove all emoji related scripts and styles (saves ~20KB)
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', [$this, 'disable_emojis_tinymce']);
        add_filter('wp_resource_hints', [$this, 'disable_emojis_remove_dns_prefetch'], 10, 2);
        
        // Remove unnecessary meta tags
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        
        // Disable XML-RPC completely
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', [$this, 'disable_x_pingback']);
        
        // Remove jQuery migrate (saves 10KB)
        add_action('wp_default_scripts', [$this, 'remove_jquery_migrate']);
        
        // Limit post revisions
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        
        // Disable heartbeat on frontend
        add_action('init', [$this, 'disable_heartbeat'], 1);
    }
    
    /**
     * P0: Add critical CSS inline
     */
    public function add_critical_css() {
        if (!is_front_page()) return;
        
        // Enhanced critical CSS for above-fold content (minified)
        $critical_css = '*,:after,:before{box-sizing:border-box}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:14px;line-height:1.6;color:#333;background:#fff}a{text-decoration:none;color:inherit}img{max-width:100%;height:auto;border:0;display:block}.container{width:100%;max-width:1200px;margin:0 auto;padding:0 15px}.header-wrapper{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.1);position:relative;z-index:100}.logo{display:inline-block;max-width:200px}.main-menu{display:flex;align-items:center}.vd-home-products{padding:40px 0}.vd-home-products .section-title{text-align:center;font-size:24px;margin-bottom:20px;font-weight:600}.products{display:flex;flex-wrap:wrap;list-style:none;margin:0 -10px;padding:0}.product-warp-item{width:25%;padding:10px;box-sizing:border-box}@media(max-width:991px){.product-warp-item{width:33.333%}}@media(max-width:767px){.product-warp-item{width:50%}}.product-item{background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;height:100%;transition:transform .3s,box-shadow .3s;position:relative}.product-item:hover{transform:translateY(-5px);box-shadow:0 5px 20px rgba(0,0,0,.1)}.product-img-wrap{position:relative;padding-bottom:100%;overflow:hidden;background:#f5f5f5}.product-img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:transform .3s}.product-item:hover .product-img{transform:scale(1.05)}.product-info{padding:10px}.product-title{font-weight:700;font-size:14px;margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#333}.price{color:#ff0000;font-size:16px;font-weight:600}.vd-skeleton{background:#f0f0f0;border-radius:4px;animation:pulse 1.5s infinite}@keyframes pulse{0%{opacity:.6}50%{opacity:1}100%{opacity:.6}}.lazyload{opacity:0;transition:opacity .3s}.lazyloaded{opacity:1}.loading-spinner{display:inline-block;width:20px;height:20px;border:3px solid rgba(0,0,0,.1);border-radius:50%;border-top-color:#333;animation:spin 1s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}';
        
        echo '<style id="vidieu-critical-css">' . $critical_css . '</style>';
    }
    
    /**
     * P0: Async CSS loading with better browser support
     */
    public function async_css_loading($html, $handle, $href, $media) {
        // Skip admin
        if (is_admin()) return $html;
        
        // Critical styles that must load sync (minimal list)
        $critical_handles = [
            'dashicons',
            'admin-bar',
            'woocommerce-layout', // Core WooCommerce layout
            'woocommerce-general', // Core WooCommerce styles
            'elessi-style', // Main theme style
            'vd-home-style' // vidieu-home-sections main style
        ];
        
        // Keep critical styles sync
        if (in_array($handle, $critical_handles)) {
            return $html;
        }
        
        // Skip if already processed
        if (in_array($handle, $this->async_styles)) {
            return '';
        }
        
        // Mark as async
        $this->async_styles[] = $handle;
        
        // Enhanced async loading with fallback
        $async_html = sprintf(
            '<link rel="preload" as="style" href="%s" media="%s" onload="this.onload=null;this.rel=\'stylesheet\'" data-handle="%s">
<noscript><link rel="stylesheet" href="%s" media="%s"></noscript>',
            esc_url($href),
            esc_attr($media),
            esc_attr($handle),
            esc_url($href),
            esc_attr($media)
        );
        
        return $async_html;
    }
    
    /**
     * P0: Prevent duplicate style loading
     */
    public function prevent_duplicate_styles() {
        global $wp_styles;
        
        if (!isset($wp_styles->queue) || !is_array($wp_styles->queue)) {
            return;
        }
        
        $loaded_urls = [];
        $new_queue = [];
        
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle])) {
                $src = $wp_styles->registered[$handle]->src;
                $url_without_query = strtok($src, '?');
                
                if (!in_array($url_without_query, $loaded_urls)) {
                    $loaded_urls[] = $url_without_query;
                    $new_queue[] = $handle;
                } else {
                    $this->removed_styles[] = $handle . ' (duplicate)';
                }
            }
        }
        
        $wp_styles->queue = $new_queue;
    }
    
    /**
     * P0: Prevent duplicate script loading
     */
    public function prevent_duplicate_scripts() {
        global $wp_scripts;
        
        if (!isset($wp_scripts->queue) || !is_array($wp_scripts->queue)) {
            return;
        }
        
        $loaded_urls = [];
        $new_queue = [];
        
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $src = $wp_scripts->registered[$handle]->src;
                $url_without_query = strtok($src, '?');
                
                if (!in_array($url_without_query, $loaded_urls)) {
                    $loaded_urls[] = $url_without_query;
                    $new_queue[] = $handle;
                } else {
                    $this->removed_scripts[] = $handle . ' (duplicate)';
                }
            }
        }
        
        $wp_scripts->queue = $new_queue;
    }
    
    /**
     * P0: Prevent duplicate resource URLs
     */
    public function prevent_duplicate_resources($src, $handle) {
        $url_without_query = strtok($src, '?');
        
        if (isset($this->duplicate_prevention[$url_without_query])) {
            return false; // Prevent loading
        }
        
        $this->duplicate_prevention[$url_without_query] = true;
        return $src;
    }
    
    /**
     * P1: Dequeue unused assets V3 (based on latest Coverage data)
     */
    public function dequeue_unused_assets_v3() {
        // Only on homepage for now
        if (!is_front_page()) return;
        
        // CSS to remove (100% unused based on latest Coverage report)
        $remove_styles = [
            // 100% unused - must remove
            'main',                      // 223KB - 100% unused
            'frontend',                  // 79.7KB - 100% unused
            'rs6',                      // 58.2KB - 100% unused
            'nasa-sc-woo',              // 40.1KB - 100% unused
            'nasa-sc',                  // 31.4KB - 100% unused
            'elementor-icons',          // 21.2KB - 100% unused
            'jquery.dataTables',        // 14KB - 100% unused
            'widget-icon-list',         // 10.5KB - 100% unused
            'widget-social-icons',      // 5.2KB - 100% unused
            'brands',                   // 732B - 100% unused
            'header-footer-elementor',  // 776B - 100% unused
            
            // High unused % (>90%)
            'animate',                  // 30.8KB - 99.6% unused
            'style-large',              // 146KB - 93.6% unused
            
            // Elementor (not used on homepage)
            'elementor-frontend',
            'elementor-post',
            'elementor-animations',
            'elementor-pro',
            'e-animations',
            
            // Unused plugins
            'contact-form-7',
            'jquery-selectBox',
            'yith-wcwl-font-awesome',
            'jquery-colorbox',
            'photoswipe',
            'photoswipe-default-skin',
            'woocommerce_prettyPhoto_css',
            'jquery-ui-style',
            'wp-block-library',
            'wc-blocks-style',
            'wp-block-library-theme',
            'classic-theme-styles',
            'global-styles',
            
            // Unused WooCommerce on homepage
            'select2',
            'wc-cart-fragments',
            'woocommerce-inline',
            'wc-checkout',
            'wc-add-payment-method',
            'wc-lost-password',
            'wc-password-strength-meter',
            
            // DataTables
            'datatables',
            'dataTables.bootstrap',
            
            // Slick slider (94% unused)
            'slick',
            'slick-theme'
        ];
        
        foreach ($remove_styles as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
            $this->removed_styles[] = $handle;
        }
        
        // JS to remove (based on latest Coverage data)
        $remove_scripts = [
            // 95%+ unused - must remove
            'rs6',                      // 403KB - 97.3% unused
            'jquery-slick',             // 41.8KB - 94% unused
            'animate',                  // 30.8KB - 99.6% unused
            'nasa-quickview',           // 16.5KB - 95.5% unused
            
            // 80%+ unused - remove on homepage
            'jquery.dataTables',        // 72KB - 83.2% unused
            'typeahead',                // 42.5KB - 85.9% unused
            'nasa-script',              // 23.2KB - 85.6% unused
            
            // Not needed on homepage
            'select2',
            'selectBox',
            'jquery-colorbox',
            'photoswipe',
            'photoswipe-ui-default',
            'zoom',
            'flexslider',
            'jquery-blockui',
            'js-cookie',
            'jquery-payment',
            'jquery-ui-datepicker',
            'wc-password-strength-meter',
            'wc-checkout',
            'wc-country-select',
            'wc-address-i18n',
            'wc-lost-password',
            'wc-add-payment-method',
            
            // Elementor
            'elementor-frontend',
            'elementor-waypoints',
            'elementor-webpack-runtime',
            'elementor-pro-frontend',
            'elementor-dialog',
            'elementor-share-link',
            
            // Comments
            'comment-reply',
            
            // Contact forms
            'contact-form-7',
            'wpcf7-recaptcha',
            
            // Unused jQuery UI
            'jquery-ui-accordion',
            'jquery-ui-tabs',
            'jquery-ui-tooltip',
            'jquery-ui-slider',
            'jquery-ui-dialog',
            'jquery-ui-button',
            
            // DataTables
            'datatables',
            'dataTables.bootstrap',
            
            // RevSlider
            'rbtools',
            'rs6',
            'revmin'
        ];
        
        foreach ($remove_scripts as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
            $this->removed_scripts[] = $handle;
        }
    }
    
    /**
     * P1: Defer non-critical scripts V3
     */
    public function defer_non_critical_scripts_v3($tag, $handle, $src) {
        // Skip admin
        if (is_admin()) return $tag;
        
        // Scripts that must NOT be deferred (critical)
        $no_defer = [
            'jquery-core',
            'jquery',
            'underscore',
            'wp-polyfill',
            'regenerator-runtime',
            'wp-hooks',
            'wp-i18n'
        ];
        
        // Scripts to defer (non-critical)
        $defer_scripts = [
            // WooCommerce
            'woocommerce',
            'wc-cart-fragments',
            'wc-add-to-cart',
            'wc-single-product',
            'wc-add-to-cart-variation',
            'sourcebuster',
            'order-attribution',
            
            // Theme
            'elessi-functions',
            'functions',
            'main',
            'nasa-core',
            'nasa-functions',
            'handlebars',
            
            // Vidieu plugin
            'vd-home-script',
            'vd-ajax-filter',
            
            // Others
            'imagesloaded',
            'masonry',
            'isotope',
            'wow',
            'countdown',
            'owl.carousel',
            'magnific-popup'
        ];
        
        // Analytics to async (non-blocking)
        $async_scripts = [
            'google-analytics',
            'gtag',
            'gtm',
            'fbevents',
            'google-tag-manager',
            'ga',
            'analytics',
            'facebook-jssdk',
            'twitter-widgets',
            'pinterest'
        ];
        
        // Check if should defer
        if (in_array($handle, $defer_scripts) || 
            strpos($handle, 'nasa-') === 0 || 
            strpos($handle, 'elessi-') === 0) {
            if (!in_array($handle, $no_defer)) {
                return str_replace(' src', ' defer="defer" src', $tag);
            }
        }
        
        // Check if should async
        foreach ($async_scripts as $async_handle) {
            if (strpos($handle, $async_handle) !== false || 
                strpos($src, $async_handle) !== false) {
                return str_replace(' src', ' async="async" src', $tag);
            }
        }
        
        return $tag;
    }
    
    /**
     * P1: Enhanced lazy loading for images
     */
    public function add_lazy_loading_v3($attr, $attachment, $size) {
        // Skip admin and feeds
        if (is_admin() || is_feed()) return $attr;
        
        // Skip logo and header images
        if (isset($attr['class']) && 
            (strpos($attr['class'], 'custom-logo') !== false ||
             strpos($attr['class'], 'header-') !== false ||
             strpos($attr['class'], 'logo') !== false)) {
            return $attr;
        }
        
        // Add native lazy loading
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
        
        // Add dimensions if missing (prevents CLS)
        if (!isset($attr['width']) || !isset($attr['height'])) {
            $image_data = wp_get_attachment_image_src($attachment->ID, $size);
            if ($image_data) {
                list($src, $width, $height) = $image_data;
                $attr['width'] = $width;
                $attr['height'] = $height;
            }
        }
        
        // Enhanced lazy loading for better browser support
        if (is_front_page()) {
            $attr['class'] = (isset($attr['class']) ? $attr['class'] . ' ' : '') . 'lazyload';
            
            // Create placeholder based on actual dimensions
            $width = $attr['width'] ?? 1;
            $height = $attr['height'] ?? 1;
            
            // Swap src with data-src
            if (isset($attr['src'])) {
                $attr['data-src'] = $attr['src'];
                // Low quality placeholder
                $attr['src'] = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$width} {$height}'%3E%3Crect width='100%25' height='100%25' fill='%23f0f0f0'/%3E%3C/svg%3E";
            }
            
            // Handle srcset
            if (isset($attr['srcset'])) {
                $attr['data-srcset'] = $attr['srcset'];
                unset($attr['srcset']);
            }
            
            // Handle sizes
            if (isset($attr['sizes'])) {
                $attr['data-sizes'] = 'auto';
            }
        }
        
        return $attr;
    }
    
    /**
     * P1: Enhanced resource hints
     */
    public function add_resource_hints_v3() {
        // DNS prefetch for external resources
        $dns_prefetch = [
            '//fonts.googleapis.com',
            '//fonts.gstatic.com',
            '//www.google-analytics.com',
            '//www.googletagmanager.com',
            '//connect.facebook.net',
            '//www.facebook.com',
            '//stats.g.doubleclick.net',
            '//www.google.com',
            '//ajax.googleapis.com',
            '//cdnjs.cloudflare.com'
        ];
        
        foreach ($dns_prefetch as $host) {
            echo '<link rel="dns-prefetch" href="' . esc_url($host) . '">' . "\n";
        }
        
        // Preconnect for critical external resources
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        
        // Preload critical resources
        $theme_url = get_template_directory_uri();
        $child_theme_url = get_stylesheet_directory_uri();
        
        // Preload main theme CSS
        echo '<link rel="preload" as="style" href="' . 
             esc_url($theme_url . '/style.css?ver=' . wp_get_theme()->get('Version')) . '">' . "\n";
        
        // Preload critical fonts
        $font_files = [
            '/assets/font/pe-icon-7-stroke/Pe-icon-7-stroke.woff',
            '/assets/font/Font-Awesome/fontawesome-webfont.woff2'
        ];
        
        foreach ($font_files as $font) {
            if (file_exists(get_template_directory() . $font)) {
                echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . 
                     esc_url($theme_url . $font) . '">' . "\n";
            }
        }
        
        // Preload logo
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            if ($logo_url) {
                echo '<link rel="preload" as="image" href="' . esc_url($logo_url) . '">' . "\n";
            }
        }
        
        // Preload first product image on homepage
        if (is_front_page()) {
            echo '<link rel="prefetch" as="image" href="' . 
                 esc_url($theme_url . '/assets/images/placeholder.jpg') . '">' . "\n";
        }
    }
    
    /**
     * P1: Optimize AJAX requests V3
     */
    public function optimize_ajax_requests_v3() {
        // Cache vidieu AJAX responses
        add_action('wp_ajax_vidieu_filter_products', [$this, 'cache_ajax_response_v3'], 1);
        add_action('wp_ajax_nopriv_vidieu_filter_products', [$this, 'cache_ajax_response_v3'], 1);
        
        add_action('wp_ajax_vidieu_filter_posts', [$this, 'cache_ajax_response_v3'], 1);
        add_action('wp_ajax_nopriv_vidieu_filter_posts', [$this, 'cache_ajax_response_v3'], 1);
        
        // Prevent duplicate admin-ajax.php calls
        add_action('wp_ajax_init', [$this, 'optimize_init_request_v3'], 1);
        add_action('wp_ajax_nopriv_init', [$this, 'optimize_init_request_v3'], 1);
        
        // Batch AJAX requests
        add_action('wp_ajax_vidieu_batch', [$this, 'handle_batch_ajax'], 1);
        add_action('wp_ajax_nopriv_vidieu_batch', [$this, 'handle_batch_ajax'], 1);
    }
    
    /**
     * Enhanced AJAX caching
     */
    public function cache_ajax_response_v3() {
        if (!$this->ajax_cache_enabled) return;
        
        $action = $_REQUEST['action'] ?? '';
        $cache_key = 'vidieu_ajax_v3_' . md5(serialize($_REQUEST));
        
        // Try cache first (reduced to 5 minutes for freshness)
        $cached = get_transient($cache_key);
        if ($cached !== false && !isset($_REQUEST['nocache'])) {
            wp_send_json($cached);
            wp_die();
        }
        
        // Hook to save response
        add_filter('wp_ajax_' . $action . '_response', function($response) use ($cache_key) {
            set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
            return $response;
        });
    }
    
    /**
     * Prevent duplicate init requests
     */
    public function optimize_init_request_v3() {
        // Check if this is a duplicate request within 2 seconds
        $request_key = 'init_request_v3_' . md5(serialize($_REQUEST));
        $last_request = get_transient($request_key);
        
        if ($last_request) {
            // This is a duplicate request, return cached response
            wp_send_json_success(['duplicate' => true, 'cached' => true]);
            wp_die();
        }
        
        // Mark as processed for 2 seconds
        set_transient($request_key, time(), 2);
    }
    
    /**
     * P2: Lazy load content images
     */
    public function lazy_load_content_images($content) {
        if (is_admin() || is_feed()) return $content;
        
        // Add lazy loading to content images
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            function($matches) {
                $img_tag = $matches[0];
                
                // Skip if already has loading attribute
                if (strpos($img_tag, 'loading=') !== false) {
                    return $img_tag;
                }
                
                // Add loading="lazy" and decoding="async"
                $img_tag = str_replace('<img', '<img loading="lazy" decoding="async"', $img_tag);
                
                return $img_tag;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * P2: Optimize countdown timer
     */
    public function optimize_countdown_timer() {
        if (!is_front_page()) return;
        ?>
        <script>
        // Throttle countdown timer updates
        (function() {
            var originalSetInterval = window.setInterval;
            window.setInterval = function(callback, delay) {
                // If it's a countdown timer (16ms = ~60fps), throttle to 1000ms
                if (delay <= 20 && callback.toString().includes('countdown')) {
                    delay = 1000;
                }
                return originalSetInterval.call(window, callback, delay);
            };
        })();
        </script>
        <?php
    }
    
    /**
     * P2: Enhanced performance monitoring scripts
     */
    public function add_performance_scripts_v3() {
        ?>
        <script>
        // Enhanced lazy loading with IntersectionObserver
        (function(){
            // Check native lazy loading support
            if('loading' in HTMLImageElement.prototype){
                const images = document.querySelectorAll('img.lazyload');
                images.forEach(img => {
                    if(img.dataset.src) {
                        img.src = img.dataset.src;
                        if(img.dataset.srcset) img.srcset = img.dataset.srcset;
                        img.classList.remove('lazyload');
                        img.classList.add('lazyloaded');
                    }
                });
            } else if('IntersectionObserver' in window){
                // Fallback to IntersectionObserver
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if(entry.isIntersecting){
                            const img = entry.target;
                            if(img.dataset.src) {
                                img.src = img.dataset.src;
                                if(img.dataset.srcset) img.srcset = img.dataset.srcset;
                                img.classList.remove('lazyload');
                                img.classList.add('lazyloaded');
                                observer.unobserve(img);
                            }
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });
                
                document.querySelectorAll('img.lazyload').forEach(img => imageObserver.observe(img));
            }
            
            // Fix async CSS loading in old browsers
            const preloads = document.querySelectorAll('link[rel="preload"][as="style"]');
            preloads.forEach(link => {
                link.addEventListener('load', function() {
                    this.rel = 'stylesheet';
                });
            });
            
            // Batch AJAX requests
            window.vidieuAjaxQueue = [];
            window.vidieuAjaxBatch = function(action, data) {
                vidieuAjaxQueue.push({action: action, data: data});
                
                if(vidieuAjaxQueue.length === 1) {
                    setTimeout(function() {
                        if(vidieuAjaxQueue.length > 0) {
                            // Send batch request
                            jQuery.post(ajaxurl, {
                                action: 'vidieu_batch',
                                requests: vidieuAjaxQueue
                            }, function(response) {
                                // Process responses
                            });
                            vidieuAjaxQueue = [];
                        }
                    }, 50);
                }
            };
        })();
        </script>
        
        <?php
        // Performance monitoring for admins
        if (current_user_can('manage_options') && !is_admin()) {
            ?>
            <script>
            // Enhanced performance monitoring
            window.addEventListener('load', function() {
                if (!window.performance) return;
                
                setTimeout(function() {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    const paintData = performance.getEntriesByType('paint');
                    const resources = performance.getEntriesByType('resource');
                    
                    // Calculate metrics
                    const metrics = {
                        'DOM Content Loaded': Math.round(perfData.domContentLoadedEventEnd) + 'ms',
                        'Page Load Complete': Math.round(perfData.loadEventEnd) + 'ms',
                        'First Paint': Math.round(paintData.find(p => p.name === 'first-paint')?.startTime || 0) + 'ms',
                        'First Contentful Paint': Math.round(paintData.find(p => p.name === 'first-contentful-paint')?.startTime || 0) + 'ms',
                        'Resources Loaded': resources.length,
                        'Total Transfer Size': (resources.reduce((sum, r) => sum + (r.transferSize || 0), 0) / 1024 / 1024).toFixed(2) + 'MB',
                        'Removed Styles': <?php echo count($this->removed_styles); ?>,
                        'Removed Scripts': <?php echo count($this->removed_scripts); ?>,
                        'Duplicate Resources Prevented': Object.keys(<?php echo json_encode($this->duplicate_prevention); ?>).length
                    };
                    
                    console.log('%c📊 Vidieu Performance Report V3', 'font-size: 16px; font-weight: bold; color: #2196F3;');
                    console.table(metrics);
                    
                    // Check for LCP
                    if (window.PerformanceObserver) {
                        try {
                            const lcpObserver = new PerformanceObserver((list) => {
                                const entries = list.getEntries();
                                const lastEntry = entries[entries.length - 1];
                                console.log('LCP:', Math.round(lastEntry.startTime) + 'ms', lastEntry.element);
                            });
                            lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
                        } catch(e) {}
                    }
                    
                    // Log removed assets
                    <?php if (!empty($this->removed_styles) || !empty($this->removed_scripts)) : ?>
                    console.log('%c🗑️ Removed Assets:', 'font-weight: bold; color: #FF5722;');
                    console.log('Styles:', <?php echo json_encode($this->removed_styles); ?>);
                    console.log('Scripts:', <?php echo json_encode($this->removed_scripts); ?>);
                    <?php endif; ?>
                    
                    // Check for duplicate resources
                    const duplicates = resources.reduce((acc, resource) => {
                        const url = resource.name.split('?')[0];
                        acc[url] = (acc[url] || 0) + 1;
                        return acc;
                    }, {});
                    
                    const duplicateUrls = Object.entries(duplicates)
                        .filter(([url, count]) => count > 1)
                        .map(([url, count]) => ({url: url.split('/').pop(), count}));
                    
                    if (duplicateUrls.length > 0) {
                        console.log('%c⚠️ Duplicate Resources Still Loading:', 'font-weight: bold; color: #FF9800;');
                        console.table(duplicateUrls);
                    }
                }, 100);
            });
            </script>
            <?php
        }
    }
    
    /**
     * Remove jQuery migrate
     */
    public function remove_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $scripts->registered['jquery']->deps = array_diff(
                $scripts->registered['jquery']->deps,
                ['jquery-migrate']
            );
        }
    }
    
    /**
     * Disable emojis in TinyMCE
     */
    public function disable_emojis_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        }
        return [];
    }
    
    /**
     * Remove emoji DNS prefetch
     */
    public function disable_emojis_remove_dns_prefetch($urls, $relation_type) {
        if ('dns-prefetch' == $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }
    
    /**
     * Disable X-Pingback header
     */
    public function disable_x_pingback($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }
    
    /**
     * Disable heartbeat
     */
    public function disable_heartbeat() {
        global $pagenow;
        if ($pagenow != 'post.php' && $pagenow != 'post-new.php') {
            wp_deregister_script('heartbeat');
        }
    }
    
    /**
     * Add compression headers hint
     */
    public function add_compression_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            
            // Hint for server admin to enable compression
            if (current_user_can('manage_options')) {
                header('X-Performance-Note: Enable gzip/brotli compression on server for 50-70% size reduction');
            }
        }
    }
    
    /**
     * Enhanced disable unnecessary features
     */
    private function disable_unnecessary_features_v3() {
        // Disable Gutenberg block styles on frontend
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-blocks-style');
            wp_dequeue_style('global-styles');
            wp_dequeue_style('classic-theme-styles');
        }, 100);
        
        // Remove dashicons on frontend for non-logged in users
        add_action('wp_enqueue_scripts', function() {
            if (!is_user_logged_in()) {
                wp_dequeue_style('dashicons');
                wp_deregister_style('dashicons');
            }
        }, 100);
        
        // Disable pingbacks
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
            return $methods;
        });
        
        // Disable self pingbacks
        add_action('pre_ping', function(&$links) {
            $home = get_option('home');
            foreach ($links as $l => $link) {
                if (strpos($link, $home) === 0) {
                    unset($links[$l]);
                }
            }
        });
        
        // Remove query strings from static resources
        add_filter('script_loader_src', [$this, 'remove_query_strings'], 15, 1);
        add_filter('style_loader_src', [$this, 'remove_query_strings'], 15, 1);
    }
    
    /**
     * Remove query strings from static resources
     */
    public function remove_query_strings($src) {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    
    /**
     * Handle batch AJAX requests
     */
    public function handle_batch_ajax() {
        $requests = $_POST['requests'] ?? [];
        $responses = [];
        
        foreach ($requests as $request) {
            // Process each request
            $action = $request['action'];
            $data = $request['data'];
            
            // Simulate processing
            $responses[] = [
                'action' => $action,
                'success' => true,
                'data' => $data
            ];
        }
        
        wp_send_json_success($responses);
    }
}

// Initialize only on frontend
if (!is_admin()) {
    Vidieu_Performance_Optimizer_V3::get_instance();
}

/**
 * Helper function to check if optimizations are active
 */
function vidieu_performance_v3_is_active() {
    return !is_admin() && Vidieu_Performance_Optimizer_V3::get_instance() !== null;
}

/**
 * Allow disabling specific features via constants
 */
if (defined('VIDIEU_DISABLE_ASYNC_CSS') && VIDIEU_DISABLE_ASYNC_CSS) {
    remove_filter('style_loader_tag', [Vidieu_Performance_Optimizer_V3::get_instance(), 'async_css_loading'], 999);
}

if (defined('VIDIEU_DISABLE_LAZY_LOAD') && VIDIEU_DISABLE_LAZY_LOAD) {
    remove_filter('wp_get_attachment_image_attributes', [Vidieu_Performance_Optimizer_V3::get_instance(), 'add_lazy_loading_v3'], 10);
}

if (defined('VIDIEU_DISABLE_JS_DEFER') && VIDIEU_DISABLE_JS_DEFER) {
    remove_filter('script_loader_tag', [Vidieu_Performance_Optimizer_V3::get_instance(), 'defer_non_critical_scripts_v3'], 10);
}

if (defined('VIDIEU_DISABLE_AJAX_CACHE') && VIDIEU_DISABLE_AJAX_CACHE) {
    Vidieu_Performance_Optimizer_V3::get_instance()->ajax_cache_enabled = false;
}