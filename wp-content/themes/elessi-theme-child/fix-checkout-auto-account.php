<?php
/**
 * Auto Account Creation for WooCommerce Checkout
 * Creates accounts automatically with auto-generated passwords when email is provided
 *
 * @package Elessi-theme-child
 * @since 2025-09-20
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto-create account for any guest checkout with email
 * This is a fallback solution when user doesn't provide password
 */
add_action('woocommerce_checkout_order_processed', 'elessi_child_auto_create_account_fallback', 20, 3);
function elessi_child_auto_create_account_fallback($order_id, $posted_data, $order) {
    // Only for guest orders
    if ($order->get_customer_id() > 0) {
        return;
    }

    $email = $order->get_billing_email();

    // Skip if no email or user already exists
    if (empty($email) || email_exists($email)) {
        return;
    }

    // Generate random password
    $password = wp_generate_password(12, false);

    // Create new customer
    $customer_id = wc_create_new_customer(
        $email,
        '', // Let WooCommerce generate username
        $password,
        array(
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        )
    );

    if (!is_wp_error($customer_id)) {
        // Update order with customer ID
        $order->set_customer_id($customer_id);
        $order->save();

        // Log the user in
        wc_set_customer_auth_cookie($customer_id);

        // Send password email
        $user = get_user_by('id', $customer_id);
        if ($user) {
            // Store password for password reset
            update_user_meta($customer_id, 'auto_generated_password', $password);

            // Send welcome email with password
            elessi_child_send_welcome_email($user, $password);
        }

        // Add success notice for next page load
        if (WC()->session) {
            WC()->session->set('auto_account_created', true);
            WC()->session->set('auto_account_password', $password);
        }
    }
}

/**
 * Send welcome email with login credentials
 */
function elessi_child_send_welcome_email($user, $password) {
    $to = $user->user_email;
    $subject = 'Tài khoản của bạn đã được tạo - ' . get_bloginfo('name');

    $message = sprintf(
        'Chào %s,

Tài khoản của bạn đã được tạo thành công tại %s!

Thông tin đăng nhập:
- Email: %s
- Mật khẩu: %s

Bạn có thể đăng nhập tại: %s

Để thay đổi mật khẩu, vui lòng truy cập trang tài khoản của bạn.

Cảm ơn bạn đã mua hàng!

---
%s',
        $user->display_name,
        get_bloginfo('name'),
        $user->user_email,
        $password,
        wp_login_url(),
        get_bloginfo('name')
    );

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}

/**
 * Show account created notice on thank you page
 */
add_action('woocommerce_thankyou', 'elessi_child_show_auto_account_notice', 5);
function elessi_child_show_auto_account_notice($order_id) {
    if (WC()->session && WC()->session->get('auto_account_created')) {
        $password = WC()->session->get('auto_account_password', '');

        echo '<div class="woocommerce-message" style="background: #f0f8ff; border: 2px solid #007cba; padding: 15px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0; color: #007cba;">🎉 Tài khoản đã được tạo thành công!</h3>';
        echo '<p><strong>Bạn đã được đăng nhập tự động.</strong></p>';

        if (!empty($password)) {
            echo '<p>Thông tin đăng nhập đã được gửi qua email. Mật khẩu tạm thời của bạn là: <code style="background: #f9f9f9; padding: 2px 5px; border-radius: 3px;"><strong>' . esc_html($password) . '</strong></code></p>';
            echo '<p style="font-size: 14px; color: #666;">💡 Khuyến nghị: Hãy thay đổi mật khẩu này trong <a href="' . esc_url(wc_get_account_endpoint_url('edit-account')) . '">trang tài khoản</a> của bạn.</p>';
        }

        echo '</div>';

        // Clear session
        WC()->session->set('auto_account_created', null);
        WC()->session->set('auto_account_password', null);
    }
}

/**
 * Always enable account creation for checkout
 */
add_filter('woocommerce_checkout_registration_enabled', '__return_true', 999);
add_filter('pre_option_woocommerce_enable_checkout_signup', '__return_yes', 999);
add_filter('pre_option_woocommerce_enable_signup_and_login_from_checkout', '__return_yes', 999);

/**
 * Make account creation not required (we'll handle it automatically)
 */
add_filter('woocommerce_checkout_registration_required', '__return_false', 999);

/**
 * Hook into WooCommerce Store API for Block Checkout
 */
add_action('woocommerce_store_api_checkout_order_processed', 'elessi_child_block_checkout_auto_account', 15, 1);
function elessi_child_block_checkout_auto_account($order) {
    // This will trigger the auto account creation
    elessi_child_auto_create_account_fallback($order->get_id(), array(), $order);
}