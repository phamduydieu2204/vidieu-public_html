<?php
/**
 * VD VCB Payment Fix V2 - Enhanced Performance
 * 
 * Fixes critical performance issues:
 * 1. 7.8s server delay on order-received page
 * 2. Endless polling loop after payment confirmation  
 * 3. Multiple page reloads causing poor UX
 * 4. Kaspersky scripts blocking for 100+ seconds
 * 
 * @package Vidieu_Home_Sections
 * @since 2.5.3
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_VCB_Payment_Fix_V2 {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Payment confirmed flag
     */
    private $payment_confirmed = false;
    
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
        // High priority to run before VCB plugin
        add_action('init', array($this, 'init_fixes'), 1);
        
        // Optimize order-received page load
        add_action('template_redirect', array($this, 'optimize_order_received'), 1);
        
        // Fix AJAX handlers
        add_action('wp_ajax_vcb_gw_waiting_payment', array($this, 'handle_payment_check_v2'), 5);
        add_action('wp_ajax_nopriv_vcb_gw_waiting_payment', array($this, 'handle_payment_check_v2'), 5);
        
        // Block Kaspersky on order-received
        add_action('wp_enqueue_scripts', array($this, 'block_slow_scripts'), 1);
        
        // Enhanced mobile QR fix
        add_action('wp_head', array($this, 'enhanced_mobile_styles'), 999);
        
        // JavaScript fixes
        add_action('wp_footer', array($this, 'enhanced_js_fixes'), 999);
        
        // Prevent duplicate loads
        add_action('wp_loaded', array($this, 'prevent_duplicates'), 1);
    }
    
    /**
     * Initialize fixes
     */
    public function init_fixes() {
        // Remove original VCB hooks if they exist
        remove_action('wp_ajax_check-payment', array('VD_VCB_Payment_Fix', 'handle_payment_check'));
        remove_action('wp_ajax_nopriv_check-payment', array('VD_VCB_Payment_Fix', 'handle_payment_check'));
    }
    
    /**
     * Optimize order-received page load time
     */
    public function optimize_order_received() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Enable page caching for order-received
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', false);
        }
        
        // Set proper cache headers
        header('Cache-Control: private, max-age=60');
        header('X-VD-Optimized: true');
        
        // Get order ID
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
        
        if ($order_id) {
            // Check if payment already confirmed
            $this->payment_confirmed = get_post_meta($order_id, '_vcb_payment_confirmed', true) === 'yes';
            
            // If confirmed, prevent polling
            if ($this->payment_confirmed) {
                add_filter('wp_footer', array($this, 'disable_polling_for_confirmed'), 1);
            }
        }
    }
    
    /**
     * Enhanced payment check handler
     */
    public function handle_payment_check_v2() {
        // Set shorter execution time to prevent long waits
        @set_time_limit(5);
        
        $order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order'));
            return;
        }
        
        // Check if already confirmed to short-circuit
        $confirmed = get_post_meta($order_id, '_vcb_payment_confirmed', true);
        if ($confirmed === 'yes') {
            wp_send_json_success(array(
                'status' => 'completed',
                'message' => 'Payment already confirmed',
                'stop_polling' => true
            ));
            return;
        }
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        // Check payment in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vcb_gateway_transactions';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE order_id = %d AND is_paid = 1 LIMIT 1",
            $order_id
        ));
        
        if ($payment) {
            // Payment found - update order
            if (in_array($order->get_status(), array('pending', 'on-hold'))) {
                $order->payment_complete();
                $order->add_order_note(__('Payment confirmed via VCB Gateway (Optimized)', 'woocommerce'));
            }
            
            // Mark as confirmed to prevent future polling
            update_post_meta($order_id, '_vcb_payment_confirmed', 'yes');
            update_post_meta($order_id, '_vcb_payment_time', current_time('mysql'));
            
            wp_send_json_success(array(
                'status' => 'completed',
                'message' => sprintf('Đơn hàng #%d đã thanh toán thành công', $order_id),
                'amount' => $payment->amount,
                'stop_polling' => true,
                'reload_page' => false // Prevent reload loops
            ));
        } else {
            // Return quickly to avoid timeout
            wp_send_json_error(array(
                'status' => 'waiting',
                'message' => 'Waiting for payment',
                'stop_polling' => false
            ));
        }
    }
    
    /**
     * Block slow scripts on order-received
     */
    public function block_slow_scripts() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Dequeue Kaspersky and other slow scripts
        $blocked_scripts = array(
            'gc-kis-v2', // Kaspersky
            'gc-kis-v2-check',
            'kaspersky-script',
            'tp-tools',
            'revmin',
            'rs-plugin-settings'
        );
        
        foreach ($blocked_scripts as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
        
        // Block by source URL pattern
        add_filter('script_loader_src', array($this, 'block_slow_script_urls'), 10, 2);
    }
    
    /**
     * Block slow script URLs
     */
    public function block_slow_script_urls($src, $handle) {
        if (!is_wc_endpoint_url('order-received')) {
            return $src;
        }
        
        $blocked_patterns = array(
            'gc.kis.v2',
            'kaspersky',
            'k.kis',
            'tp-tools',
            'revolution'
        );
        
        foreach ($blocked_patterns as $pattern) {
            if (stripos($src, $pattern) !== false) {
                return false; // Block the script
            }
        }
        
        return $src;
    }
    
    /**
     * Enhanced mobile styles
     */
    public function enhanced_mobile_styles() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        ?>
        <style id="vd-vcb-fix-v2">
        /* Block Kaspersky iframe/scripts */
        iframe[src*="kis.v2"],
        iframe[src*="kaspersky"] {
            display: none !important;
        }
        
        /* Mobile QR optimization */
        @media (max-width: 768px) {
            #vcb-gateway {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .vcb-mh-box {
                padding: 10px !important;
                background: #fff !important;
                border-radius: 8px !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }
            
            .qrVietqr,
            .vcb-mh-qr-code img {
                width: 280px !important;
                height: 280px !important;
                max-width: 100% !important;
                margin: 10px auto !important;
                display: block !important;
            }
            
            /* Improve button visibility */
            .download-btn,
            .copy-btn,
            .vcb-gw-coppy {
                display: inline-block !important;
                width: 48% !important;
                padding: 12px 8px !important;
                margin: 1% !important;
                font-size: 14px !important;
                background: #2271b1 !important;
                color: white !important;
                border: none !important;
                border-radius: 4px !important;
                cursor: pointer !important;
                text-align: center !important;
            }
        }
        
        /* Loading state optimization */
        .vcb-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 9999;
        }
        </style>
        <?php
    }
    
    /**
     * Enhanced JavaScript fixes
     */
    public function enhanced_js_fixes() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
        ?>
        <script id="vd-vcb-fix-v2-js">
        (function($) {
            'use strict';
            
            console.log('[VD V2] Enhanced VCB fixes loading...');
            
            // Configuration
            var config = {
                orderId: <?php echo $order_id; ?>,
                paymentConfirmed: <?php echo $this->payment_confirmed ? 'true' : 'false'; ?>,
                maxPolls: 60, // Max 4 minutes
                pollCount: 0,
                pollInterval: 4000,
                reloadCount: parseInt(sessionStorage.getItem('vd_reload_count') || '0')
            };
            
            // Prevent reload loops
            if (config.reloadCount > 2) {
                console.warn('[VD V2] Preventing reload loop, count:', config.reloadCount);
                sessionStorage.removeItem('vd_reload_count');
                
                // Override reload
                var originalReload = window.location.reload;
                window.location.reload = function() {
                    console.log('[VD V2] Reload blocked');
                    return false;
                };
            }
            
            // Increment reload count on page unload
            $(window).on('beforeunload', function() {
                sessionStorage.setItem('vd_reload_count', config.reloadCount + 1);
            });
            
            // Block Kaspersky scripts
            $(document).ready(function() {
                // Remove Kaspersky elements
                $('script[src*="gc.kis.v2"]').remove();
                $('iframe[src*="kis.v2"]').remove();
                
                // Prevent new Kaspersky scripts
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.tagName === 'SCRIPT' && node.src && node.src.includes('kis.v2')) {
                                console.log('[VD V2] Blocked Kaspersky script:', node.src);
                                node.remove();
                            }
                            if (node.tagName === 'IFRAME' && node.src && node.src.includes('kis.v2')) {
                                console.log('[VD V2] Blocked Kaspersky iframe:', node.src);
                                node.remove();
                            }
                        });
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
            
            // Override AJAX for payment polling
            if (!config.paymentConfirmed) {
                var originalAjax = $.ajax;
                $.ajax = function(options) {
                    // Intercept VCB payment polling
                    if (options.url && options.url.includes('vcb_gw_waiting_payment')) {
                        config.pollCount++;
                        
                        console.log('[VD V2] Payment poll #' + config.pollCount);
                        
                        // Stop after max polls
                        if (config.pollCount > config.maxPolls) {
                            console.warn('[VD V2] Max polls reached, stopping');
                            clearAllIntervals();
                            return $.Deferred().reject('Max polls reached');
                        }
                        
                        // Add success handler to check for completion
                        var originalSuccess = options.success;
                        options.success = function(response) {
                            console.log('[VD V2] Poll response:', response);
                            
                            if (response.success && response.data) {
                                if (response.data.stop_polling || response.data.status === 'completed') {
                                    console.log('[VD V2] Payment confirmed, stopping polls');
                                    clearAllIntervals();
                                    config.paymentConfirmed = true;
                                    
                                    // Prevent reloads
                                    if (response.data.reload_page === false) {
                                        console.log('[VD V2] Preventing page reload');
                                        window.location.reload = function() { return false; };
                                    }
                                }
                            }
                            
                            if (originalSuccess) {
                                originalSuccess.apply(this, arguments);
                            }
                        };
                        
                        // Add shorter timeout
                        options.timeout = 5000; // 5 seconds max
                    }
                    
                    return originalAjax.apply(this, arguments);
                };
            }
            
            // Helper to clear all intervals
            function clearAllIntervals() {
                for (var i = 1; i < 9999; i++) {
                    window.clearInterval(i);
                }
            }
            
            // If payment already confirmed, stop all polling
            if (config.paymentConfirmed) {
                console.log('[VD V2] Payment already confirmed, stopping all polling');
                setTimeout(clearAllIntervals, 1000);
            }
            
            // Mobile QR visibility
            if ($(window).width() <= 768) {
                $('#vcb-gateway, .vcb-mh-box, .qrVietqr').show();
                console.log('[VD V2] Mobile QR forced visible');
            }
            
            // Add loading indicator
            var $loadingDiv = $('<div class="vcb-loading" style="display:none;">Đang kiểm tra thanh toán...</div>');
            $('body').append($loadingDiv);
            
            $(document).ajaxStart(function() {
                if (!config.paymentConfirmed) {
                    $loadingDiv.show();
                }
            }).ajaxStop(function() {
                $loadingDiv.hide();
            });
            
            console.log('[VD V2] Enhanced fixes active');
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Disable polling for confirmed payments
     */
    public function disable_polling_for_confirmed() {
        ?>
        <script>
        // Disable all polling for confirmed payment
        (function() {
            console.log('[VD V2] Payment confirmed - disabling all polling');
            for (var i = 1; i < 9999; i++) {
                window.clearInterval(i);
            }
            
            // Hide payment waiting elements
            jQuery('.vcb-gw-waiting, .vcb-gateway-waiting').hide();
            jQuery('.vcb-gw-success, .vcb-gateway-success').show();
        })();
        </script>
        <?php
    }
    
    /**
     * Prevent duplicate resource loads
     */
    public function prevent_duplicates() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Track loaded resources
        add_filter('script_loader_tag', array($this, 'track_scripts'), 10, 3);
        add_filter('style_loader_tag', array($this, 'track_styles'), 10, 3);
    }
    
    /**
     * Track loaded scripts
     */
    public function track_scripts($tag, $handle, $src) {
        static $loaded = array();
        
        if (isset($loaded[$src])) {
            return '<!-- Duplicate blocked: ' . $handle . ' -->';
        }
        
        $loaded[$src] = true;
        return $tag;
    }
    
    /**
     * Track loaded styles
     */
    public function track_styles($tag, $handle, $src) {
        static $loaded = array();
        
        if (isset($loaded[$src])) {
            return '<!-- Duplicate blocked: ' . $handle . ' -->';
        }
        
        $loaded[$src] = true;
        return $tag;
    }
}

// Initialize with high priority
add_action('plugins_loaded', function() {
    VD_VCB_Payment_Fix_V2::get_instance();
}, 1);