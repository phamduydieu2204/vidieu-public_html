<?php
/**
 * Fix Checkout Account Creation - WooCommerce Block Checkout Version
 * Ensures user accounts are automatically created during Block Checkout
 *
 * @package Elessi-theme-child
 * @since 2025-09-20
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook into WooCommerce Store API checkout to create accounts
 * This handles WooCommerce Block Checkout specifically
 */
add_action('woocommerce_store_api_checkout_order_processed', 'elessi_child_block_checkout_create_account', 10, 1);
function elessi_child_block_checkout_create_account($order) {
    // Only for guest orders
    if ($order->get_customer_id() > 0) {
        return;
    }

    // Get email from order
    $email = $order->get_billing_email();

    // Try to get password from various sources
    $password = '';

    // Check session for password
    if (WC()->session) {
        $password = WC()->session->get('checkout_password', '');
    }

    // Check if user already exists
    if (empty($email) || empty($password) || email_exists($email)) {
        return;
    }

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

        // Clear password from session
        if (WC()->session) {
            WC()->session->set('checkout_password', '');
            WC()->session->set('account_created_notice', true);
        }
    }
}

/**
 * Legacy fallback for classic checkout
 */
add_action('woocommerce_checkout_process', 'elessi_child_force_account_creation', 5);
function elessi_child_force_account_creation() {
    // Only for logged out users
    if (is_user_logged_in()) {
        return;
    }

    // Check if we have email and password
    $email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';
    $password = isset($_POST['textinput-1']) ? $_POST['textinput-1'] : '';

    // If both email and password are provided, force account creation
    if (!empty($email) && !empty($password)) {
        $_POST['createaccount'] = '1';

        // Also set account password field for WooCommerce
        $_POST['account_password'] = $password;

        // Set account email (though billing_email should work)
        $_POST['account_email'] = $email;

        // Store password in session for Store API
        if (WC()->session) {
            WC()->session->set('checkout_password', $password);
        }

        // Enable account creation in WooCommerce settings temporarily
        add_filter('pre_option_woocommerce_enable_checkout_signup', '__return_yes');
        add_filter('woocommerce_enable_checkout_signup', '__return_true');
    }
}

/**
 * Alternative approach: Hook into order creation to create account
 */
add_action('woocommerce_checkout_order_processed', 'elessi_child_create_account_after_order', 10, 3);
function elessi_child_create_account_after_order($order_id, $posted_data, $order) {
    // Only for guest orders
    if ($order->get_customer_id() > 0) {
        return;
    }

    $email = $order->get_billing_email();
    $password = isset($_POST['textinput-1']) ? $_POST['textinput-1'] : '';

    // If we have email and password but no account was created
    if (!empty($email) && !empty($password)) {

        // Check if user already exists
        if (email_exists($email)) {
            return;
        }

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

            // Add success notice for next page load
            WC()->session->set('account_created_notice', true);
        }
    }
}

/**
 * Show success notice when account is created
 */
add_action('woocommerce_before_single_order_summary', 'elessi_child_show_account_created_notice');
add_action('woocommerce_thankyou', 'elessi_child_show_account_created_notice');
function elessi_child_show_account_created_notice() {
    if (WC()->session && WC()->session->get('account_created_notice')) {
        wc_add_notice(__('Tài khoản của bạn đã được tạo thành công! Bạn đã được đăng nhập tự động.', 'woocommerce'), 'success');
        WC()->session->set('account_created_notice', null);
    }
}

/**
 * Enable customer registration during checkout
 */
add_filter('woocommerce_checkout_registration_enabled', '__return_true');
add_filter('woocommerce_checkout_registration_required', '__return_false');

/**
 * Modify checkout fields to include password field properly
 */
add_filter('woocommerce_checkout_fields', 'elessi_child_add_password_field_block_checkout', 999);
function elessi_child_add_password_field_block_checkout($fields) {
    // Only add if not logged in
    if (is_user_logged_in()) {
        return $fields;
    }

    // Add password field to account section
    if (!isset($fields['account'])) {
        $fields['account'] = array();
    }

    $fields['account']['account_password'] = array(
        'type'        => 'password',
        'label'       => __('Tạo mật khẩu', 'woocommerce'),
        'required'    => false, // We'll handle this in JavaScript
        'class'       => array('form-row-wide', 'wc-block-components-text-input'),
        'priority'    => 20,
    );

    return $fields;
}

/**
 * JavaScript to handle WooCommerce Block Checkout account creation
 */
add_action('wp_footer', 'elessi_child_checkout_account_creation_js');
function elessi_child_checkout_account_creation_js() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function() {
        'use strict';

        // Store password when user enters it
        function storePasswordInSession() {
            const passwordField = document.querySelector('#textinput-1, input[type="password"]');
            const emailField = document.querySelector('#email, input[type="email"]');

            if (passwordField && emailField && passwordField.value && emailField.value) {
                // Send AJAX request to store password in session
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'store_checkout_password',
                        password: passwordField.value,
                        email: emailField.value,
                        nonce: '<?php echo wp_create_nonce("store_checkout_password"); ?>'
                    })
                }).catch(function(error) {
                    console.log('Error storing password:', error);
                });
            }
        }

        // Store password on input change
        document.addEventListener('input', function(e) {
            if (e.target && (e.target.id === 'textinput-1' || e.target.type === 'password')) {
                setTimeout(storePasswordInSession, 500);
            }
        });

        // Store password before checkout
        document.addEventListener('click', function(e) {
            if (e.target && (
                e.target.textContent === 'Đặt hàng' ||
                e.target.classList.contains('wc-block-components-checkout-place-order-button') ||
                e.target.closest('.wc-block-components-checkout-place-order-button')
            )) {
                storePasswordInSession();
            }
        }, true);

        // Initial store
        setTimeout(storePasswordInSession, 2000);

        // Store periodically
        setInterval(storePasswordInSession, 5000);

    })();
    </script>
    <?php
}

/**
 * AJAX handler to store checkout password in session
 */
add_action('wp_ajax_store_checkout_password', 'elessi_child_store_checkout_password');
add_action('wp_ajax_nopriv_store_checkout_password', 'elessi_child_store_checkout_password');
function elessi_child_store_checkout_password() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'store_checkout_password')) {
        wp_die('Security check failed');
    }

    $password = sanitize_text_field($_POST['password']);
    $email = sanitize_email($_POST['email']);

    if (!empty($password) && !empty($email)) {
        if (WC()->session) {
            WC()->session->set('checkout_password', $password);
            WC()->session->set('checkout_email', $email);
        }
        wp_send_json_success('Password stored');
    } else {
        wp_send_json_error('Invalid data');
    }
}

/**
 * Force enable registration on checkout page
 */
add_action('wp', 'elessi_child_enable_checkout_registration');
function elessi_child_enable_checkout_registration() {
    if (is_checkout()) {
        add_filter('pre_option_woocommerce_enable_signup_and_login_from_checkout', '__return_yes');
        add_filter('pre_option_woocommerce_enable_checkout_signup', '__return_yes');
    }
}