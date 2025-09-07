<?php
/**
 * VD VCB Root Cause Fix
 * 
 * Fixes root causes without modifying VCB plugin:
 * 1. Prevents vcb_gw_sync_transactions from running on page load
 * 2. Caches payment status to avoid repeated database queries
 * 3. Stops polling immediately when payment detected
 * 4. Prevents reload loops
 * 
 * @package Vidieu_Home_Sections
 * @since 2.5.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_VCB_Root_Cause_Fix {
    
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
        // Override VCB AJAX handler with higher priority
        add_action('wp_ajax_vcb_gw_waiting_payment', array($this, 'optimized_payment_check'), 1);
        add_action('wp_ajax_nopriv_vcb_gw_waiting_payment', array($this, 'optimized_payment_check'), 1);
        
        // Cache payment status on thankyou page
        add_action('woocommerce_thankyou', array($this, 'cache_payment_status'), 5);
        
        // Inject optimized JavaScript
        add_action('wp_footer', array($this, 'inject_optimized_script'), 998);
        
        // Prevent sync on page load
        add_filter('pre_option_vcb_gw_login', array($this, 'prevent_sync_on_page_load'), 10, 2);
    }
    
    /**
     * Optimized payment check - avoids calling sync_transactions
     */
    public function optimized_payment_check() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        
        if (!$order_id) {
            wp_send_json_error('Invalid order');
            exit;
        }
        
        // Check cached payment status first
        $cache_key = 'vd_vcb_payment_' . $order_id;
        $cached_status = get_transient($cache_key);
        
        if ($cached_status === 'paid') {
            wp_send_json_success(array(
                'msg' => 'Thanh toán thành công',
                'data' => array('comment' => 'Cached payment confirmed'),
                '_cached' => true
            ));
            exit;
        }
        
        // Check order status
        $order = wc_get_order($order_id);
        if ($order && in_array($order->get_status(), array('processing', 'completed'))) {
            set_transient($cache_key, 'paid', HOUR_IN_SECONDS);
            wp_send_json_success(array(
                'msg' => 'Thanh toán thành công',
                'data' => array('comment' => 'Order already paid')
            ));
            exit;
        }
        
        // Check database directly without triggering sync
        global $wpdb;
        $table = $wpdb->prefix . 'vcb_gateway_transactions';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE order_id = %d AND is_paid = 1 LIMIT 1",
            $order_id
        ), ARRAY_A);
        
        if ($payment) {
            // Update order status
            if ($order && in_array($order->get_status(), array('pending', 'on-hold'))) {
                $order->payment_complete();
                $order->add_order_note(__('Payment confirmed via VCB Gateway (Optimized)', 'woocommerce'));
            }
            
            // Cache the result
            set_transient($cache_key, 'paid', HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'msg' => 'Thanh toán thành công',
                'data' => $payment
            ));
            exit;
        }
        
        // Only sync if explicitly requested or last sync was > 30 seconds ago
        $should_sync = false;
        
        if (isset($_GET['force_sync'])) {
            $should_sync = true;
        } else {
            $last_sync_key = 'vd_vcb_last_sync_' . $order_id;
            $last_sync = get_transient($last_sync_key);
            
            if (!$last_sync) {
                $should_sync = true;
                set_transient($last_sync_key, time(), 30);
            }
        }
        
        if ($should_sync && isset($_GET['cron'])) {
            // Let original handler run but only for cron requests
            return;
        }
        
        // Return not paid without triggering sync
        wp_send_json_error(array(
            'msg' => 'Waiting for payment',
            '_optimized' => true,
            '_should_sync' => $should_sync
        ));
        exit;
    }
    
    /**
     * Cache payment status on order completion
     */
    public function cache_payment_status($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_payment_method() === 'vcb-gateway-mh') {
            $cache_key = 'vd_vcb_payment_' . $order_id;
            
            if (in_array($order->get_status(), array('processing', 'completed'))) {
                set_transient($cache_key, 'paid', HOUR_IN_SECONDS);
            }
        }
    }
    
    /**
     * Prevent sync on page load by temporarily hiding credentials
     */
    public function prevent_sync_on_page_load($pre_option, $option) {
        // Only on order-received page
        if (!is_wc_endpoint_url('order-received')) {
            return $pre_option;
        }
        
        // Check if this is AJAX request
        if (wp_doing_ajax()) {
            // Allow for cron requests only
            if (isset($_GET['cron'])) {
                return $pre_option;
            }
        }
        
        // For page loads, return false to prevent sync
        if (!wp_doing_ajax()) {
            return false;
        }
        
        return $pre_option;
    }
    
    /**
     * Inject optimized JavaScript
     */
    public function inject_optimized_script() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
        ?>
        <script id="vd-vcb-root-fix">
        (function($) {
            'use strict';
            
            console.log('[VD Root Fix] Optimizing VCB payment check...');
            
            // Configuration
            var config = {
                orderId: <?php echo $order_id; ?>,
                pollCount: 0,
                maxPolls: 30, // 2 minutes max
                syncRequested: false,
                paymentConfirmed: false
            };
            
            // Check if payment already confirmed
            var orderStatus = $('#vcb-gateway-order_status').val();
            if (orderStatus === 'completed' || orderStatus === 'processing') {
                console.log('[VD Root Fix] Payment already confirmed, stopping polling');
                config.paymentConfirmed = true;
            }
            
            // Override setInterval to control polling
            var originalSetInterval = window.setInterval;
            window.setInterval = function(callback, delay) {
                // Intercept VCB polling
                if (delay === 4000 && callback.toString().includes('vcb_gw_waiting_payment')) {
                    console.log('[VD Root Fix] Intercepting VCB polling');
                    
                    // Don't start if already paid
                    if (config.paymentConfirmed) {
                        console.log('[VD Root Fix] Payment confirmed, not starting poll');
                        return 0;
                    }
                    
                    // Modified polling with better control
                    return originalSetInterval(function() {
                        config.pollCount++;
                        
                        // Stop after max polls
                        if (config.pollCount > config.maxPolls) {
                            console.log('[VD Root Fix] Max polls reached');
                            clearInterval(this);
                            return;
                        }
                        
                        // Add force_sync parameter every 10th request
                        if (config.pollCount % 10 === 0 && !config.syncRequested) {
                            console.log('[VD Root Fix] Requesting sync on poll', config.pollCount);
                            
                            // Modify AJAX data to include cron parameter
                            var originalAjax = $.ajax;
                            $.ajax = function(options) {
                                if (options.data && options.data.action === 'vcb_gw_waiting_payment') {
                                    options.data.cron = 'true';
                                    config.syncRequested = true;
                                    
                                    // Restore after this request
                                    setTimeout(function() {
                                        $.ajax = originalAjax;
                                        config.syncRequested = false;
                                    }, 100);
                                }
                                return originalAjax.apply(this, arguments);
                            };
                        }
                        
                        // Call original callback
                        callback.apply(this, arguments);
                    }, delay);
                }
                
                return originalSetInterval.apply(this, arguments);
            };
            
            // Override reload to prevent loops
            var reloadCount = parseInt(sessionStorage.getItem('vd_reload_count') || '0');
            
            if (reloadCount > 1) {
                console.log('[VD Root Fix] Preventing reload loop, count:', reloadCount);
                
                var originalReload = window.location.reload;
                window.location.reload = function() {
                    console.log('[VD Root Fix] Reload blocked');
                    
                    // Instead of reload, just update UI
                    $('#vcb-gateway').html(`
                        <div class="vcb-gateway-result">
                            <div class="success-animation">
                                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                                </svg>
                            </div>
                            <div class="text-center">Thanh toán thành công!</div>
                        </div>
                    `);
                    
                    // Clear all intervals
                    for (var i = 1; i < 9999; i++) {
                        clearInterval(i);
                    }
                    
                    sessionStorage.removeItem('vd_reload_count');
                    return false;
                };
            }
            
            // Track reloads
            $(window).on('beforeunload', function() {
                if (config.paymentConfirmed || $('#vcb-gateway .vcb-gateway-result').length > 0) {
                    sessionStorage.setItem('vd_reload_count', reloadCount + 1);
                }
            });
            
            // Monitor AJAX responses
            $(document).ajaxSuccess(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('vcb_gw_waiting_payment')) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('[VD Root Fix] Payment confirmed!');
                            config.paymentConfirmed = true;
                            
                            // Clear all intervals immediately
                            setTimeout(function() {
                                for (var i = 1; i < 9999; i++) {
                                    clearInterval(i);
                                }
                            }, 100);
                        }
                    } catch (e) {}
                }
            });
            
            console.log('[VD Root Fix] Optimization active');
        })(jQuery);
        </script>
        <?php
    }
}

// Initialize
add_action('init', function() {
    VD_VCB_Root_Cause_Fix::get_instance();
}, 1);