<?php
/**
 * VCB-MH Compatibility Layer
 * 
 * Ensures VCB-MH QR code displays properly on mobile devices
 * without modifying the third-party plugin code
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Compatibility
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_VCB_MH_Compat {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * VCB-MH script/style handles that must be whitelisted
     */
    private $vcb_handles = array(
        'scripts' => array(
            'vcb-mh-public',
            'sweetalert2',
            'sweetalert2-all'
        ),
        'styles' => array(
            'vcb-mh-public',
            'sweetalert2',
            'sweetalert2-theme'
        )
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
        // Initialize on appropriate hooks
        add_action('init', array($this, 'init_compat'), 5);
        
        // Ensure VCB-MH scripts are whitelisted in performance optimization
        add_filter('vidieu_allowed_scripts', array($this, 'whitelist_vcb_scripts'), 10);
        add_filter('vidieu_allowed_styles', array($this, 'whitelist_vcb_styles'), 10);
    }
    
    /**
     * Initialize compatibility
     */
    public function init_compat() {
        // Only run on frontend
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Hook into appropriate actions
        add_action('wp', array($this, 'setup_vcb_compat'), 20);
    }
    
    /**
     * Setup VCB compatibility based on page context
     */
    public function setup_vcb_compat() {
        // Only apply on checkout and order-received pages
        if (!is_checkout() && !is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Enqueue compatibility assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_compat_assets'), 25);
        
        // Add inline critical CSS for immediate effect
        add_action('wp_head', array($this, 'add_critical_css'), 5);
        
        // Ensure VCB-MH scripts load properly
        add_filter('script_loader_tag', array($this, 'ensure_vcb_script_loading'), 10, 3);
    }
    
    /**
     * Enqueue compatibility assets
     */
    public function enqueue_compat_assets() {
        // Only on relevant pages
        if (!is_checkout() && !is_wc_endpoint_url('order-received')) {
            return;
        }
        
        $plugin_url = plugin_dir_url(dirname(__DIR__));
        $version = '1.0.0';
        
        // Enqueue compatibility CSS
        wp_enqueue_style(
            'vidieu-vcb-qr-compat',
            $plugin_url . 'assets/css/vcb-qr-compat.css',
            array(),
            $version
        );
        
        // Enqueue compatibility JS
        wp_enqueue_script(
            'vidieu-vcb-qr-compat',
            $plugin_url . 'assets/js/vcb-qr-compat.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize script with necessary data
        wp_localize_script('vidieu-vcb-qr-compat', 'vidieuVCBCompat', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isOrderReceived' => is_wc_endpoint_url('order-received'),
            'isCheckout' => is_checkout() && !is_wc_endpoint_url('order-received'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }
    
    /**
     * Add critical CSS to ensure QR visibility on mobile
     */
    public function add_critical_css() {
        ?>
        <style id="vidieu-vcb-qr-critical">
            /* Ensure VCB-MH QR code is visible on mobile */
            @media screen and (max-width: 768px) {
                /* QR container visibility */
                .qrVietqr,
                #qrVietqr,
                .vcb-qr-code,
                #vcb-qr-code,
                [class*="vcb"][class*="qr"],
                [id*="vcb"][id*="qr"] {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    position: relative !important;
                    z-index: 10 !important;
                }
                
                /* QR image sizing */
                .qrVietqr img,
                #qrVietqr img,
                .vcb-qr-code img,
                #vcb-qr-code img,
                [class*="vcb"][class*="qr"] img,
                [id*="vcb"][id*="qr"] img {
                    max-width: 100% !important;
                    width: auto !important;
                    height: auto !important;
                    display: block !important;
                    margin: 0 auto !important;
                }
                
                /* Payment info container adjustments */
                #payment-info {
                    overflow: visible !important;
                    min-height: auto !important;
                }
                
                /* Column adjustments for mobile */
                #left-col,
                #right-col {
                    width: 100% !important;
                    float: none !important;
                    display: block !important;
                }
                
                /* Ensure parent containers don't hide QR */
                .woocommerce-order-details,
                .woocommerce-thankyou-order-details,
                .order_details,
                .bacs_details {
                    overflow: visible !important;
                }
            }
            
            /* General QR visibility fix */
            .qrVietqr {
                max-width: 300px !important;
                margin: 15px auto !important;
            }
            
            /* SweetAlert2 mobile adjustments */
            @media screen and (max-width: 540px) {
                .swal2-popup {
                    width: 90% !important;
                    font-size: 14px !important;
                }
                
                .swal2-popup .qrVietqr {
                    max-width: 250px !important;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Whitelist VCB-MH scripts in performance optimization
     */
    public function whitelist_vcb_scripts($allowed_scripts) {
        if (is_checkout() || is_wc_endpoint_url('order-received')) {
            $allowed_scripts = array_merge($allowed_scripts, $this->vcb_handles['scripts']);
        }
        return $allowed_scripts;
    }
    
    /**
     * Whitelist VCB-MH styles in performance optimization
     */
    public function whitelist_vcb_styles($allowed_styles) {
        if (is_checkout() || is_wc_endpoint_url('order-received')) {
            $allowed_styles = array_merge($allowed_styles, $this->vcb_handles['styles']);
        }
        return $allowed_styles;
    }
    
    /**
     * Ensure VCB scripts load with proper attributes
     */
    public function ensure_vcb_script_loading($tag, $handle, $src) {
        // Check if this is a VCB-MH or SweetAlert script
        if (in_array($handle, $this->vcb_handles['scripts']) || 
            strpos($src, 'vcb-mh') !== false || 
            strpos($src, 'sweetalert') !== false) {
            
            // Ensure script loads properly (remove async/defer if present)
            $tag = str_replace(' async', '', $tag);
            $tag = str_replace(' defer', '', $tag);
            
            // Add data attribute to identify VCB scripts
            $tag = str_replace(' src=', ' data-vcb-compat="true" src=', $tag);
        }
        
        return $tag;
    }
}

// Initialize the compatibility layer
Vidieu_VCB_MH_Compat::get_instance();