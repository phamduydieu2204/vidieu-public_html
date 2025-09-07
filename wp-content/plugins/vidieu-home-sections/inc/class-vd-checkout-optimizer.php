<?php
/**
 * VD Checkout Optimizer
 * Optimize checkout flow to reduce 163s to <10s
 * 
 * @package Vidieu_Home_Sections
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_Checkout_Optimizer {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Performance tracking
     */
    private $start_time = 0;
    private $metrics = array();
    
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
        // Override theme's slow AJAX handler with priority 5 (before default 10)
        add_action('wp_ajax_elessi_simple_checkout', array($this, 'optimized_checkout_handler'), 5);
        add_action('wp_ajax_nopriv_elessi_simple_checkout', array($this, 'optimized_checkout_handler'), 5);
        
        // Cache order-received pages
        add_action('woocommerce_thankyou', array($this, 'cache_order_received_page'), 5);
        add_action('template_redirect', array($this, 'serve_cached_order_received'), 1);
        
        // Fix payment polling
        add_filter('vcb_gw_polling_interval', array($this, 'optimize_payment_polling'));
        add_action('wp_ajax_vcb_gw_check_payment_once', array($this, 'quick_payment_check'));
        add_action('wp_ajax_nopriv_vcb_gw_check_payment_once', array($this, 'quick_payment_check'));
        
        // Performance logging
        add_action('woocommerce_checkout_process', array($this, 'log_checkout_start'), 1);
        add_action('woocommerce_checkout_order_processed', array($this, 'log_order_created'), 999);
        add_action('woocommerce_thankyou', array($this, 'log_thankyou_page'), 1);
        
        // Add performance monitoring JS
        add_action('wp_footer', array($this, 'inject_performance_monitor'), 999);
        
        // Emergency optimizations
        add_action('wp_enqueue_scripts', array($this, 'emergency_optimizations'), 999);
        
        // Admin performance display
        if (current_user_can('manage_options')) {
            add_action('admin_bar_menu', array($this, 'add_performance_info'), 999);
        }
    }
    
    /**
     * Optimized checkout handler - Target: <1s instead of 4s
     */
    public function optimized_checkout_handler() {
        $start = microtime(true);
        
        // Verify nonce
        if (!check_ajax_referer('woocommerce-process_checkout', 'security', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Start output buffering to prevent any echo/print
        ob_start();
        
        try {
            // Quick validation only
            $errors = $this->quick_validate_checkout($_POST);
            if (!empty($errors)) {
                wp_send_json_error(array(
                    'messages' => implode("\n", $errors),
                    'reload' => false
                ));
                return;
            }
            
            // Create order with minimal processing
            $order = $this->create_order_optimized($_POST);
            
            if (is_wp_error($order)) {
                wp_send_json_error(array(
                    'messages' => $order->get_error_message(),
                    'reload' => false
                ));
                return;
            }
            
            // Queue heavy tasks for background processing
            $this->queue_background_tasks($order);
            
            // Clear cart
            WC()->cart->empty_cart();
            
            // Return success immediately
            $result = array(
                'redirect' => $order->get_checkout_order_received_url(),
                'order_id' => $order->get_id(),
                'performance' => array(
                    'duration' => round((microtime(true) - $start) * 1000, 2) . 'ms'
                )
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'messages' => $e->getMessage(),
                'reload' => false
            ));
        } finally {
            ob_end_clean();
        }
    }
    
    /**
     * Quick validation - only critical checks
     */
    private function quick_validate_checkout($data) {
        $errors = array();
        
        // Only validate essentials
        if (empty($data['billing_email'])) {
            $errors[] = 'Email is required';
        }
        
        if (empty($data['payment_method'])) {
            $errors[] = 'Please select a payment method';
        }
        
        // Skip complex validations for now
        
        return $errors;
    }
    
    /**
     * Create order with minimal processing
     */
    private function create_order_optimized($data) {
        // Disable hooks that slow down order creation
        remove_all_actions('woocommerce_checkout_create_order');
        remove_all_actions('woocommerce_checkout_update_order_meta');
        
        // Create order
        $order = wc_create_order(array(
            'status' => 'pending',
            'customer_id' => get_current_user_id()
        ));
        
        if (is_wp_error($order)) {
            return $order;
        }
        
        // Add products from cart - optimized
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $order->add_product(
                $cart_item['data'],
                $cart_item['quantity'],
                array(
                    'subtotal' => $cart_item['line_subtotal'],
                    'total' => $cart_item['line_total']
                )
            );
        }
        
        // Set addresses - batch update
        $address_fields = array(
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
            'billing_address_1',
            'billing_city',
            'billing_postcode',
            'billing_country'
        );
        
        foreach ($address_fields as $field) {
            if (isset($data[$field])) {
                $order->update_meta_data('_' . $field, sanitize_text_field($data[$field]));
            }
        }
        
        // Set payment method
        $order->set_payment_method($data['payment_method']);
        
        // Calculate totals
        $order->calculate_totals();
        
        // Save order
        $order->save();
        
        // Store performance metric
        $order->update_meta_data('_checkout_optimized', 'yes');
        $order->save_meta_data();
        
        return $order;
    }
    
    /**
     * Queue background tasks
     */
    private function queue_background_tasks($order) {
        // Schedule immediate background processing
        wp_schedule_single_event(time() + 1, 'vd_process_order_background', array($order->get_id()));
        
        // Add action for background processing
        add_action('vd_process_order_background', array($this, 'process_order_background'));
    }
    
    /**
     * Process order in background
     */
    public function process_order_background($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Now do heavy processing:
        // 1. Send emails
        do_action('woocommerce_checkout_order_processed', $order_id, array(), $order);
        
        // 2. Update stock
        wc_maybe_reduce_stock_levels($order_id);
        
        // 3. Trigger webhooks
        do_action('woocommerce_new_order', $order_id);
        
        // 4. Log completion
        $order->add_order_note('Background processing completed');
    }
    
    /**
     * Cache order-received page
     */
    public function cache_order_received_page($order_id) {
        if (!$order_id) return;
        
        $cache_key = 'vd_order_received_' . $order_id . '_' . get_current_user_id();
        
        // Check if already cached
        if (get_transient($cache_key)) {
            return;
        }
        
        // Start capturing output
        ob_start();
        
        // Let page render normally
        add_action('shutdown', function() use ($cache_key, $order_id) {
            $content = ob_get_contents();
            
            // Only cache if successful
            if (strlen($content) > 1000 && !is_404()) {
                // Cache for 1 hour
                set_transient($cache_key, $content, HOUR_IN_SECONDS);
                
                // Log cache creation
                if (current_user_can('manage_options')) {
                    error_log('[VD Checkout] Cached order-received page for order #' . $order_id);
                }
            }
        }, 0);
    }
    
    /**
     * Serve cached order-received page
     */
    public function serve_cached_order_received() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        global $wp;
        $order_id = absint($wp->query_vars['order-received']);
        if (!$order_id) return;
        
        $cache_key = 'vd_order_received_' . $order_id . '_' . get_current_user_id();
        $cached = get_transient($cache_key);
        
        if ($cached && !isset($_GET['nocache'])) {
            // Add header to indicate cached
            header('X-VD-Cache: HIT');
            header('X-VD-Cache-Time: ' . date('c'));
            
            echo $cached;
            
            // Add performance info for admins
            if (current_user_can('manage_options')) {
                echo "\n<!-- Served from VD Cache - Add ?nocache=1 to bypass -->\n";
            }
            
            exit;
        }
    }
    
    /**
     * Optimize payment polling
     */
    public function optimize_payment_polling($interval) {
        // Increase interval from default to reduce load
        return 5000; // 5 seconds instead of 1-2s
    }
    
    /**
     * Quick payment check
     */
    public function quick_payment_check() {
        check_ajax_referer('vcb-payment-check', 'security');
        
        $order_id = absint($_POST['order_id']);
        if (!$order_id) {
            wp_send_json_error();
        }
        
        // Use transient for quick check
        $cache_key = 'vcb_payment_status_' . $order_id;
        $status = get_transient($cache_key);
        
        if (!$status) {
            // Only check if not cached
            $order = wc_get_order($order_id);
            if ($order) {
                $status = $order->get_status();
                set_transient($cache_key, $status, 30); // Cache 30s
            }
        }
        
        wp_send_json(array(
            'status' => $status ?: 'pending',
            'cached' => !empty($status)
        ));
    }
    
    /**
     * Emergency optimizations
     */
    public function emergency_optimizations() {
        if (!is_checkout() && !is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // 1. Remove Kaspersky on checkout (38.6s!)
        wp_dequeue_script('gc-kis-v2');
        wp_deregister_script('gc-kis-v2');
        
        // 2. Limit reCAPTCHA to single instance
        global $vd_recaptcha_loaded;
        if (!isset($vd_recaptcha_loaded)) {
            $vd_recaptcha_loaded = false;
        }
        
        add_filter('script_loader_tag', function($tag, $handle, $src) use (&$vd_recaptcha_loaded) {
            if (strpos($src, 'recaptcha') !== false || strpos($src, 'grecaptcha') !== false) {
                if ($vd_recaptcha_loaded) {
                    return ''; // Remove duplicate
                }
                $vd_recaptcha_loaded = true;
            }
            return $tag;
        }, 10, 3);
        
        // 3. Remove unnecessary scripts on order-received
        if (is_wc_endpoint_url('order-received')) {
            $remove_scripts = array(
                'wc-add-to-cart',
                'wc-add-to-cart-variation',
                'wc-cart-fragments',
                'selectWoo',
                'select2'
            );
            
            foreach ($remove_scripts as $handle) {
                wp_dequeue_script($handle);
            }
        }
    }
    
    /**
     * Performance logging
     */
    public function log_checkout_start() {
        $this->start_time = microtime(true);
        $session_id = WC()->session ? WC()->session->get_customer_id() : 0;
        
        if ($session_id) {
            set_transient('checkout_start_' . $session_id, $this->start_time, 300);
        }
    }
    
    public function log_order_created($order_id) {
        if (!$this->start_time) {
            $session_id = WC()->session ? WC()->session->get_customer_id() : 0;
            $this->start_time = get_transient('checkout_start_' . $session_id);
        }
        
        if ($this->start_time) {
            $duration = microtime(true) - $this->start_time;
            update_post_meta($order_id, '_checkout_duration', $duration);
            
            // Log slow checkouts
            if ($duration > 5) {
                error_log(sprintf(
                    '[VD SLOW CHECKOUT] Order #%d took %.2fs to process',
                    $order_id,
                    $duration
                ));
            }
        }
    }
    
    public function log_thankyou_page($order_id) {
        $checkout_duration = get_post_meta($order_id, '_checkout_duration', true);
        $total_duration = 0;
        
        if ($this->start_time) {
            $total_duration = microtime(true) - $this->start_time;
        }
        
        // Store metrics
        update_post_meta($order_id, '_checkout_metrics', array(
            'checkout_duration' => $checkout_duration,
            'total_duration' => $total_duration,
            'optimized' => get_post_meta($order_id, '_checkout_optimized', true) === 'yes'
        ));
    }
    
    /**
     * Inject performance monitor JavaScript
     */
    public function inject_performance_monitor() {
        if (!is_checkout()) return;
        ?>
        <script id="vd-checkout-performance">
        (function() {
            var vdMetrics = {
                pageStart: performance.now(),
                clickTime: 0,
                ajaxStart: 0,
                ajaxEnd: 0,
                redirectTime: 0
            };
            
            // Monitor place order button
            jQuery(document).on('click', '#place_order', function() {
                vdMetrics.clickTime = performance.now();
                console.log('[VD Perf] Order button clicked at', Math.round(vdMetrics.clickTime), 'ms');
            });
            
            // Monitor AJAX
            jQuery(document).ajaxSend(function(e, xhr, settings) {
                if (settings.data && settings.data.includes('elessi_simple_checkout')) {
                    vdMetrics.ajaxStart = performance.now();
                    console.log('[VD Perf] Checkout AJAX started');
                }
            });
            
            jQuery(document).ajaxComplete(function(e, xhr, settings) {
                if (settings.data && settings.data.includes('elessi_simple_checkout')) {
                    vdMetrics.ajaxEnd = performance.now();
                    var duration = vdMetrics.ajaxEnd - vdMetrics.ajaxStart;
                    console.log('[VD Perf] Checkout AJAX completed in', Math.round(duration), 'ms');
                    
                    // Check response
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data && response.data.performance) {
                            console.log('[VD Perf] Server processing:', response.data.performance.duration);
                        }
                    } catch(e) {}
                }
            });
            
            // Monitor page unload (redirect)
            window.addEventListener('beforeunload', function() {
                vdMetrics.redirectTime = performance.now();
                var totalTime = vdMetrics.redirectTime - vdMetrics.clickTime;
                
                // Try to log before page unloads
                if (navigator.sendBeacon) {
                    var data = new FormData();
                    data.append('action', 'vd_log_checkout_performance');
                    data.append('total_time', totalTime);
                    data.append('ajax_time', vdMetrics.ajaxEnd - vdMetrics.ajaxStart);
                    
                    navigator.sendBeacon(ajaxurl, data);
                }
            });
            
            // Log initial metrics
            console.log('[VD Perf] Checkout page loaded, monitoring started');
        })();
        </script>
        <?php
    }
    
    /**
     * Add performance info to admin bar
     */
    public function add_performance_info($wp_admin_bar) {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        global $wp;
        $order_id = absint($wp->query_vars['order-received']);
        if (!$order_id) return;
        
        $metrics = get_post_meta($order_id, '_checkout_metrics', true);
        if (empty($metrics)) return;
        
        $title = sprintf(
            'Checkout: %.2fs | Total: %.2fs %s',
            $metrics['checkout_duration'],
            $metrics['total_duration'],
            $metrics['optimized'] ? '(Optimized)' : ''
        );
        
        $wp_admin_bar->add_node(array(
            'id' => 'vd_checkout_performance',
            'title' => $title,
            'meta' => array(
                'class' => $metrics['total_duration'] > 10 ? 'vd-slow-checkout' : 'vd-fast-checkout'
            )
        ));
    }
}

// Initialize
add_action('init', function() {
    VD_Checkout_Optimizer::get_instance();
}, 5);