<?php
/**
 * Assets management class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Assets class
 */
class VD_Assets {
    
    /**
     * Instance
     */
    private static $instance = null;
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Check if we should load assets
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'vd-home-style',
            VD_HOME_PLUGIN_URL . 'assets/css/vidieu-home.css',
            array(),
            VD_HOME_VERSION
        );
        
        // Enqueue Tree Menu CSS
        wp_enqueue_style(
            'vd-tree-menu-style',
            VD_HOME_PLUGIN_URL . 'assets/css/vidieu-tree-menu.css',
            array('vd-home-style'),
            VD_HOME_VERSION
        );
        
        // Enqueue JS in footer
        wp_enqueue_script(
            'vd-home-script',
            VD_HOME_PLUGIN_URL . 'assets/js/vidieu-home.js',
            array('jquery'),
            VD_HOME_VERSION,
            true
        );
        
        // Enqueue custom quickview script if enabled
        $enable_custom_quickview = VD_Admin::get_option('enable_custom_quickview', false);
        if ($enable_custom_quickview) {
            wp_enqueue_script(
                'vd-custom-quickview',
                VD_HOME_PLUGIN_URL . 'assets/js/vidieu-custom-quickview.js',
                array('jquery', 'vd-home-script'),
                VD_HOME_VERSION,
                true
            );
        }
        
        // Enqueue select options open quickview script
        wp_enqueue_script(
            'vd-select-options-open-qv',
            VD_HOME_PLUGIN_URL . 'assets/js/vd-select-options-open-qv.js',
            array('jquery', 'vd-home-script'),
            VD_HOME_VERSION,
            true
        );
        
        // Enqueue AJAX optimized script
        wp_enqueue_script(
            'vd-ajax-optimized',
            VD_HOME_PLUGIN_URL . 'assets/js/vidieu-ajax-optimized.js',
            array('jquery', 'vd-home-script'),
            VD_HOME_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vd-home-script', 'vd_home_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vd_home_nonce'),
            'loading_text' => __('Loading...', VD_HOME_TEXT_DOMAIN),
            'error_text' => __('Error loading content. Please try again.', VD_HOME_TEXT_DOMAIN),
            'no_results_text' => __('No items found.', VD_HOME_TEXT_DOMAIN),
            'i18n' => array(
                'all' => __('All', VD_HOME_TEXT_DOMAIN)
            )
        ));
        
        // Localize AJAX optimized script
        wp_localize_script('vd-ajax-optimized', 'vidieu_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vidieu_ajax_nonce')
        ));
        
        // Enqueue contact form assets if contact shortcode is present
        if ($this->has_contact_shortcode()) {
            wp_enqueue_style(
                'vd-contact-style',
                VD_HOME_PLUGIN_URL . 'assets/css/contact.css',
                array('vd-home-style'),
                VD_HOME_VERSION
            );
            
            wp_enqueue_script(
                'vd-contact-script',
                VD_HOME_PLUGIN_URL . 'assets/js/contact.js',
                array('jquery'),
                VD_HOME_VERSION,
                true
            );
            
            wp_localize_script('vd-contact-script', 'vd_ajax_object', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'error_message' => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
            ));
        }
        
        // Enqueue Buy Now Simple standardized handler
        $enable_buy_now = VD_Admin::get_option('enable_buy_now', false);
        if (!is_admin() && $enable_buy_now) {
            // Buy Now Simple styles
            wp_enqueue_style(
                'vd-buynow-simple',
                VD_HOME_PLUGIN_URL . 'assets/css/buynow-simple.css',
                array('vd-home-style'),
                VD_HOME_VERSION
            );
            
            // Buy Now Simple script
            wp_enqueue_script(
                'vd-buynow-simple',
                VD_HOME_PLUGIN_URL . 'assets/js/buynow-simple.js',
                array('jquery', 'vd-home-script'),
                VD_HOME_VERSION,
                true
            );
        }
        
        // Legacy Buy Now No-Scroll fix (kept for variable products)
        if (!is_admin() && defined('VD_FIX_BUY_NOW_NOSCROLL') && VD_FIX_BUY_NOW_NOSCROLL) {
            wp_enqueue_script(
                'vd-buy-now-no-scroll',
                VD_HOME_PLUGIN_URL . 'assets/js/buy-now-no-scroll.js',
                array('jquery', 'vd-home-script'),
                VD_HOME_VERSION,
                true
            );
        }
        
        // Enqueue debug script if in debug mode (temporary)
        if (!is_admin() && defined('VD_DEBUG_SCROLL') && VD_DEBUG_SCROLL) {
            wp_enqueue_script(
                'vd-buy-now-scroll-debug',
                VD_HOME_PLUGIN_URL . 'assets/js/buy-now-scroll-debug.js',
                array('jquery'),
                VD_HOME_VERSION,
                true
            );
        }
    }
    
    /**
     * Check if we should load assets based on page content
     */
    private function should_load_assets() {
        // Always load in admin for Elementor/WPBakery editing
        if (is_admin()) {
            return true;
        }
        
        // Load for Elementor preview modes
        if (isset($_GET['elementor-preview']) || isset($_GET['preview']) || isset($_GET['elementor_library'])) {
            return true;
        }
        
        // Load for WPBakery frontend editor
        if (function_exists('vc_is_frontend_editor') && vc_is_frontend_editor()) {
            return true;
        }
        
        // Check for Elementor contexts
        if (class_exists('\Elementor\Plugin')) {
            $elementor = \Elementor\Plugin::$instance;
            
            // Elementor preview mode
            if (isset($elementor->preview) && $elementor->preview->is_preview_mode()) {
                return true;
            }
            
            // Elementor editor mode
            if (isset($elementor->editor) && $elementor->editor->is_edit_mode()) {
                return true;
            }
        }
        
        global $post;
        if (!$post) {
            return false;
        }
        
        // Check if this is front page or contains shortcodes anywhere
        $should_load = false;
        
        // Check for shortcodes in post content
        if (has_shortcode($post->post_content, 'vidieu_home_products') || 
            has_shortcode($post->post_content, 'vidieu_home_posts') || 
            has_shortcode($post->post_content, 'vd_contact')) {
            $should_load = true;
        }
        
        // Check if this page has sidebar mapping
        if (is_page()) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vd_page_sidebar_mappings';
            $has_mapping = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE page_id = %d",
                get_the_ID()
            ));
            if ($has_mapping) {
                $should_load = true;
            }
        }
        
        // Check for shortcodes in meta fields (for page builders)
        if (!$should_load) {
            $meta_values = get_post_meta($post->ID);
            foreach ($meta_values as $meta_key => $meta_array) {
                if (is_array($meta_array)) {
                    foreach ($meta_array as $meta_value) {
                        if (is_string($meta_value)) {
                            if (strpos($meta_value, '[vidieu_home_products') !== false || 
                                strpos($meta_value, '[vidieu_home_posts') !== false ||
                                strpos($meta_value, '[vd_contact') !== false) {
                                $should_load = true;
                                break 2;
                            }
                            
                            // Check for Elementor data
                            if ($meta_key === '_elementor_data' && strpos($meta_value, 'vidieu_home_') !== false) {
                                $should_load = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        
        // For front page, load only if shortcodes are actually found
        if (is_front_page() && $should_load) {
            return true;
        }
        
        return $should_load;
    }
    
    /**
     * Static method for backward compatibility
     */
    public static function should_load_assets_static() {
        $instance = self::get_instance();
        return $instance->should_load_assets();
    }
    
    /**
     * Check if contact shortcode is present
     */
    private function has_contact_shortcode() {
        global $post;
        if (!$post) {
            return false;
        }
        
        // Check in post content
        if (has_shortcode($post->post_content, 'vd_contact')) {
            return true;
        }
        
        // Check in meta fields
        $meta_values = get_post_meta($post->ID);
        foreach ($meta_values as $meta_key => $meta_array) {
            if (is_array($meta_array)) {
                foreach ($meta_array as $meta_value) {
                    if (is_string($meta_value) && strpos($meta_value, '[vd_contact') !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
}