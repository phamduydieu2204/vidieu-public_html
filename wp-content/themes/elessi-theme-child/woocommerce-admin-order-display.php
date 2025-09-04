<?php
/**
 * WooCommerce Admin Order Display Enhancements
 * Ensure customer information is properly displayed in admin
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom column to show source of order
 */
add_filter('manage_edit-shop_order_columns', 'elessi_add_order_source_column');
function elessi_add_order_source_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        
        // Add source column after order number
        if ($key === 'order_number') {
            $new_columns['order_source'] = __('Nguồn', 'woocommerce');
        }
    }
    
    return $new_columns;
}

/**
 * Display order source in the custom column
 */
add_action('manage_shop_order_posts_custom_column', 'elessi_display_order_source_column', 10, 2);
function elessi_display_order_source_column($column, $post_id) {
    if ($column === 'order_source') {
        $order = wc_get_order($post_id);
        if ($order) {
            $created_via = $order->get_created_via();
            
            // Check if order was created by our simple checkout
            $notes = wc_get_order_notes(array(
                'order_id' => $post_id,
                'type' => 'internal',
            ));
            
            $is_simple_checkout = false;
            foreach ($notes as $note) {
                if (strpos($note->content, 'Simple Checkout') !== false) {
                    $is_simple_checkout = true;
                    break;
                }
            }
            
            if ($is_simple_checkout) {
                echo '<span style="color: #2271b1;">Simple Checkout</span>';
            } else {
                echo ucfirst($created_via);
            }
        }
    }
}

/**
 * Ensure billing information is displayed in order details
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'elessi_display_additional_billing_info');
function elessi_display_additional_billing_info($order) {
    // Get billing information
    $billing_email = $order->get_billing_email();
    $billing_phone = $order->get_billing_phone();
    
    if ($billing_email || $billing_phone) {
        echo '<div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
        echo '<h4 style="margin: 0 0 10px 0;">Thông tin liên hệ:</h4>';
        
        if ($billing_email) {
            echo '<p><strong>Email:</strong> ' . esc_html($billing_email) . '</p>';
        }
        
        if ($billing_phone) {
            echo '<p><strong>Điện thoại:</strong> ' . esc_html($billing_phone) . '</p>';
        }
        
        echo '</div>';
    }
}

/**
 * Display customer note in order details
 */
add_action('woocommerce_admin_order_data_after_order_details', 'elessi_display_customer_note');
function elessi_display_customer_note($order) {
    // Get customer note
    $customer_note = $order->get_customer_note();
    
    if (!empty($customer_note)) {
        echo '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
        echo '<h4 style="margin: 0 0 10px 0; color: #856404;">Ghi chú của khách hàng:</h4>';
        echo '<p style="margin: 0; color: #856404;">' . wp_kses_post(nl2br($customer_note)) . '</p>';
        echo '</div>';
    }
}

/**
 * Also display customer note in the order meta box
 */
add_action('woocommerce_admin_order_data_after_shipping_address', 'elessi_display_customer_note_in_metabox');
function elessi_display_customer_note_in_metabox($order) {
    $customer_note = $order->get_customer_note();
    
    if (!empty($customer_note)) {
        echo '<div style="clear: both; margin-top: 10px;">';
        echo '<h3>Ghi chú của khách hàng</h3>';
        echo '<div style="padding: 10px; background: #fffbf0; border: 1px solid #f0b849; border-radius: 4px;">';
        echo wp_kses_post(nl2br($customer_note));
        echo '</div>';
        echo '</div>';
    }
}

/**
 * Fix display of customer name in order list
 */
add_filter('woocommerce_admin_order_buyer_name', 'elessi_fix_order_buyer_name', 10, 2);
function elessi_fix_order_buyer_name($buyer, $order) {
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name = $order->get_billing_last_name();
    $billing_email = $order->get_billing_email();
    
    if ($billing_first_name || $billing_last_name) {
        $name = trim($billing_first_name . ' ' . $billing_last_name);
        if ($billing_email) {
            return $name . ' (' . $billing_email . ')';
        }
        return $name;
    }
    
    return $buyer;
}

/**
 * Translate order source/origin values to Vietnamese
 */
add_filter('woocommerce_order_get_created_via', 'elessi_translate_order_source');
function elessi_translate_order_source($source) {
    $translations = array(
        'checkout'     => 'Trang thanh toán',
        'admin'        => 'Quản trị viên',
        'rest-api'     => 'REST API',
        'store-api'    => 'Store API',
        'subscription' => 'Đăng ký định kỳ',
        'manual'       => 'Thủ công',
    );
    
    return isset($translations[$source]) ? $translations[$source] : ucfirst($source);
}