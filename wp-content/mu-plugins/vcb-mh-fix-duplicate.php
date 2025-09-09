<?php
/**
 * Plugin Name: VCB-MH Fix Duplicate Display
 * Description: Ngăn VCB-MH hiển thị 2 lần và fix QR code
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Track if VCB-MH has been displayed
 */
global $vcb_mh_displayed;
$vcb_mh_displayed = false;

/**
 * Add critical CSS to hide left-col BEFORE it renders
 */
add_action('wp_head', function() {
    if (!is_order_received_page()) {
        return;
    }
    
    // Check if this is a VCB-MH order
    $order_id = get_query_var('order-received');
    if (!$order_id) {
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== 'vcb-gateway-mh') {
        return;
    }
    ?>
    <style id="vcb-mh-critical-css">
    /* Critical CSS - Hide left-col immediately to prevent flash */
    #vcb-gateway #left-col {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    /* Ensure right-col is ready */
    #vcb-gateway #right-col {
        width: 100% !important;
        max-width: 600px !important;
        margin: 0 auto !important;
    }
    
    /* Hide duplicate gateways */
    #vcb-gateway ~ #vcb-gateway {
        display: none !important;
    }
    </style>
    <?php
}, 1); // Very early priority

/**
 * Prevent duplicate VCB-MH display
 */
add_action('woocommerce_thankyou', function($order_id) {
    global $vcb_mh_displayed;
    
    // Mark as will be displayed
    if (!$vcb_mh_displayed) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_payment_method() === 'vcb-gateway-mh') {
            $vcb_mh_displayed = true;
        }
    }
}, 3); // Before VCB-MH priority 4

/**
 * Remove duplicate display via JavaScript
 */
add_action('wp_footer', function() {
    if (!is_order_received_page()) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Remove duplicate VCB gateway
        var vcbGateways = $('#vcb-gateway');
        if (vcbGateways.length > 1) {
            // Keep only the first one
            vcbGateways.not(':first').remove();
        }
        
        // Also remove duplicate scripts/styles
        $('script[src*="sweetalert2.all.min.js"]').not(':first').remove();
        $('link[href*="sweetalert2.css"]').not(':first').remove();
        
        // Fix QR code after removing duplicates
        setTimeout(function() {
            fixMissingQR();
        }, 100);
    });
    
    // Function to fix missing QR
    function fixMissingQR() {
        var $ = jQuery;
        var orderId = $('#vcb-gateway-order_id').val();
        
        if (!orderId) return;
        
        // Get order info from PHP
        var orderInfo = <?php
            $order_id = get_query_var('order-received');
            $order = wc_get_order($order_id);
            $vcb_settings = get_option('vcb_gw_settings', []);
            
            echo json_encode([
                'order_id' => $order_id,
                'total' => $order ? intval($order->get_total()) : 0,
                'phone' => $vcb_settings['account']['phone'] ?? '0821000013390',
                'prefix' => $vcb_settings['prefix'] ?? 'VIDIEUVN',
                'suffix' => $vcb_settings['subfix'] ?? ''
            ]);
        ?>;
        
        var note = orderInfo.prefix + orderInfo.order_id + orderInfo.suffix;
        var qrUrl = 'https://api.vietqr.io/970436/' + orderInfo.phone + '/' + orderInfo.total + '/' + note + '/qr_only.jpg';
        
        
        // Check if QR exists
        var $qrImages = $('#vcb-gateway .qrVietqr');
        
        if ($qrImages.length === 0) {
            // No QR found, add them
            
            // Add to left column
            $('#left-col .flex-center-box').after('<img class="qrVietqr" src="' + qrUrl + '" alt="QR Code chuyển khoản" />');
            
            // Add to right column (PC view)
            $('.anPc > div:first').after('<img class="qrVietqr" src="' + qrUrl + '" alt="QR Code chuyển khoản" />');
        } else {
            // QR exists but might not have src
            $qrImages.each(function() {
                if (!$(this).attr('src')) {
                    $(this).attr('src', qrUrl);
                }
            });
        }
        
        // Ensure QR is visible
        $('.qrVietqr').css({
            'display': 'block',
            'max-width': '280px',
            'margin': '20px auto',
            'border': '2px solid #ddd',
            'padding': '10px',
            'background': '#fff'
        });
        
        // Fix download buttons
        $('.download-btn').off('click').on('click', function(e) {
            e.preventDefault();
            var link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'QR-Vidieu-' + orderInfo.order_id + '.jpg';
            link.click();
        });
    }
    </script>
    
    <style>
    /* Hide duplicate gateways during load */
    #vcb-gateway ~ #vcb-gateway {
        display: none !important;
    }
    
    /* Hide left column - we only need right column */
    #vcb-gateway #left-col {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Make right column full width */
    #vcb-gateway #right-col {
        width: 100% !important;
        max-width: 600px !important;
        margin: 0 auto !important;
    }
    
    /* Center the payment info */
    #vcb-gateway #payment-info {
        justify-content: center !important;
    }
    
    /* Ensure proper QR display */
    #vcb-gateway .qrVietqr {
        display: block !important;
        max-width: 280px !important;
        height: auto !important;
        margin: 20px auto !important;
        border: 2px solid #ddd !important;
        padding: 10px !important;
        background: #fff !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    }
    
    /* Fix layout */
    #vcb-gateway {
        clear: both;
        margin: 20px 0;
    }
    
    /* Style the table */
    #vcb-gateway table {
        margin: 20px 0;
        width: 100%;
    }
    
    #vcb-gateway table th {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        #vcb-gateway #right-col {
            padding: 15px !important;
        }
        
        .anPc {
            display: none !important;
        }
        
        .anMoblie {
            display: block !important;
        }
    }
    
    @media (min-width: 769px) {
        .anMoblie {
            display: none !important;
        }
        
        .anPc {
            display: block !important;
        }
    }
    
    /* Center align QR code section */
    #vcb-gateway .anPc > div:first-child {
        text-align: center;
        margin-bottom: 20px;
    }
    
    /* Fix download button */
    #vcb-gateway .download-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        margin: 10px auto !important;
        padding: 10px 20px !important;
        background: #0066cc !important;
        color: #fff !important;
        border: none !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        text-decoration: none !important;
    }
    
    #vcb-gateway .download-btn:hover {
        background: #0052a3 !important;
    }
    
    #vcb-gateway .download-btn img {
        filter: brightness(0) invert(1) !important;
    }
    </style>
    <?php
}, 999);

/**
 * Alternative: Modify VCB-MH output to prevent duplicate
 */
add_filter('woocommerce_thankyou_order_received_text', function($text, $order) {
    if ($order && $order->get_payment_method() === 'vcb-gateway-mh') {
        // Add marker to prevent duplicate
        $text .= '<div id="vcb-mh-marker" style="display:none"></div>';
    }
    return $text;
}, 10, 2);

/**
 * Disable duplicate hooks from VCB-MH if possible
 */
add_action('init', function() {
    // Try to remove duplicate actions
    global $wp_filter;
    
    if (isset($wp_filter['woocommerce_thankyou'])) {
        $hooks = $wp_filter['woocommerce_thankyou'];
        $vcb_count = 0;
        
        foreach ($hooks as $priority => $hook_list) {
            foreach ($hook_list as $hook) {
                if (is_array($hook['function']) && is_object($hook['function'][0])) {
                    $class_name = get_class($hook['function'][0]);
                    if (strpos($class_name, 'Vcb_Gateway_MH') !== false) {
                        $vcb_count++;
                        // If more than one, could remove extras
                        if ($vcb_count > 1) {
                            // Duplicate hook found
                        }
                    }
                }
            }
        }
    }
}, 999);