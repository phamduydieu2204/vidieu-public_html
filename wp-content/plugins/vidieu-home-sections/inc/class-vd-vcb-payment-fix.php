<?php
/**
 * VD VCB Payment Fix
 * 
 * Fixes VCB payment confirmation issues without modifying the original plugin:
 * 1. Adds missing AJAX action handler for payment checking
 * 2. Prevents infinite polling loops
 * 3. Fixes mobile QR code display
 * 
 * @package Vidieu_Home_Sections
 * @since 2.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_VCB_Payment_Fix {
    
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
        // Hook into VCB payment AJAX - add the missing action handler
        add_action('wp_ajax_check-payment', array($this, 'handle_payment_check'));
        add_action('wp_ajax_nopriv_check-payment', array($this, 'handle_payment_check'));
        
        // Fix mobile QR display
        add_action('wp_head', array($this, 'fix_mobile_qr_display'), 999);
        
        // Override VCB JavaScript to fix polling
        add_action('wp_footer', array($this, 'fix_vcb_polling'), 999);
        
        // Add debug logging for admins
        add_action('wp_footer', array($this, 'add_debug_info'), 1000);
    }
    
    /**
     * Handle the missing payment check AJAX action
     */
    public function handle_payment_check() {
        // Verify nonce
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }
        
        // Verify nonce - format from VCB plugin
        if (!wp_verify_nonce($nonce, 'check-payment_' . $order_id)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found');
            return;
        }
        
        // Check if VCB payment
        if ($order->get_payment_method() !== 'vcb-gateway-mh') {
            wp_send_json_error('Not a VCB payment');
            return;
        }
        
        // Check payment status from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vcb_gateway_transactions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            wp_send_json_error('Payment table not found');
            return;
        }
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE order_id = %d AND is_paid = 1",
            $order_id
        ));
        
        if ($payment) {
            // Payment found - update order status if needed
            if ($order->get_status() === 'pending' || $order->get_status() === 'on-hold') {
                $order->payment_complete();
                $order->add_order_note(__('Payment confirmed via VCB Gateway', 'woocommerce'));
            }
            
            // Return success with payment info
            wp_send_json_success(array(
                'paid' => true,
                'amount' => $payment->amount,
                'comment' => sprintf(
                    __('Đơn hàng #%d đã được thanh toán thành công. Số tiền: %s VNĐ', 'woocommerce'),
                    $order_id,
                    number_format($payment->amount, 0, ',', '.')
                ),
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->created_at
            ));
        } else {
            // No payment yet - but don't return error to avoid stopping the polling
            wp_send_json_error(array(
                'paid' => false,
                'message' => 'Payment not received yet'
            ));
        }
    }
    
    /**
     * Fix mobile QR code display
     */
    public function fix_mobile_qr_display() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        ?>
        <style id="vd-vcb-mobile-fix">
        /* Fix QR code display on mobile */
        @media (max-width: 768px) {
            #vcb-gateway {
                display: block !important;
                visibility: visible !important;
            }
            
            .vcb-mh-box {
                display: block !important;
                padding: 15px !important;
            }
            
            .vcb-mh-qr-code,
            .qrVietqr {
                display: block !important;
                width: 100% !important;
                max-width: 300px !important;
                height: auto !important;
                margin: 0 auto !important;
            }
            
            .vcb-mh-info {
                text-align: center !important;
            }
            
            .vcb-gw-row {
                display: block !important;
                margin-bottom: 15px !important;
            }
            
            .vcb-gw-row > div {
                width: 100% !important;
                margin-bottom: 10px !important;
            }
            
            /* Fix buttons on mobile */
            .download-btn,
            .copy-btn {
                width: 100% !important;
                margin: 5px 0 !important;
                padding: 12px !important;
                font-size: 16px !important;
            }
            
            /* Fix text size for mobile */
            .vcb-mh-content {
                font-size: 14px !important;
            }
            
            .vcb-mh-amount {
                font-size: 18px !important;
                font-weight: bold !important;
            }
        }
        
        /* Ensure QR is always visible */
        #vcb-gateway .vcb-mh-qr-code {
            opacity: 1 !important;
            visibility: visible !important;
        }
        </style>
        <?php
    }
    
    /**
     * Fix VCB polling issues
     */
    public function fix_vcb_polling() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        ?>
        <script id="vd-vcb-polling-fix">
        (function($) {
            'use strict';
            
            // Wait for VCB script to load
            $(document).ready(function() {
                // Add missing action parameter to AJAX data
                var originalAjax = $.ajax;
                $.ajax = function(options) {
                    // Intercept VCB payment check requests
                    if (options.url && options.url.includes('admin-ajax.php') && options.data && options.data.order_id) {
                        // Add missing action
                        if (!options.data.action) {
                            options.data.action = 'check-payment';
                            console.log('[VD Fix] Added missing action to VCB payment check');
                        }
                    }
                    
                    return originalAjax.apply(this, arguments);
                };
                
                // Monitor for payment success to prevent loops
                var paymentCompleted = false;
                $(document).ajaxSuccess(function(event, xhr, settings) {
                    if (settings.url && settings.url.includes('admin-ajax.php')) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data && response.data.paid) {
                                paymentCompleted = true;
                                console.log('[VD Fix] Payment confirmed, preventing further polling');
                                
                                // Clear any running intervals
                                for (var i = 1; i < 9999; i++) {
                                    window.clearInterval(i);
                                }
                            }
                        } catch (e) {}
                    }
                });
                
                // Prevent page reload loops
                var reloadCount = sessionStorage.getItem('vd_vcb_reload_count') || 0;
                if (reloadCount > 2) {
                    console.warn('[VD Fix] Preventing reload loop, already reloaded', reloadCount, 'times');
                    sessionStorage.removeItem('vd_vcb_reload_count');
                    
                    // Override reload function
                    window.location.reload = function() {
                        console.log('[VD Fix] Reload blocked to prevent loop');
                    };
                } else {
                    // Increment reload counter
                    $(window).on('beforeunload', function() {
                        if (paymentCompleted) {
                            sessionStorage.setItem('vd_vcb_reload_count', parseInt(reloadCount) + 1);
                        }
                    });
                }
                
                // Fix mobile QR visibility
                if ($(window).width() <= 768) {
                    $('#vcb-gateway').show();
                    $('.vcb-mh-qr-code').show();
                    console.log('[VD Fix] Forced QR code visibility on mobile');
                }
            });
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Add debug info for admins
     */
    public function add_debug_info() {
        if (!current_user_can('manage_options') || !is_wc_endpoint_url('order-received')) {
            return;
        }
        
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
        if (!$order_id) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        ?>
        <script id="vd-vcb-debug">
        console.group('[VD VCB Debug]');
        console.log('Order ID:', <?php echo $order_id; ?>);
        console.log('Payment Method:', '<?php echo $order->get_payment_method(); ?>');
        console.log('Order Status:', '<?php echo $order->get_status(); ?>');
        console.log('Mobile Device:', window.innerWidth <= 768);
        console.log('VCB Elements Found:', {
            gateway: $('#vcb-gateway').length,
            qrCode: $('.vcb-mh-qr-code, .qrVietqr').length,
            nonce: $('#vcb-gateway-nonce').length
        });
        console.groupEnd();
        </script>
        <?php
    }
}

// Initialize
add_action('init', function() {
    VD_VCB_Payment_Fix::get_instance();
});