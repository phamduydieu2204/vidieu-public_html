<?php
/**
 * Fix Checkout Account Creation
 * Ensures user accounts are automatically created during checkout
 *
 * @package Elessi-theme-child
 * @since 2025-09-20
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Force account creation for guests with email and password
 * This runs during checkout processing to ensure accounts are created
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
 * JavaScript to handle checkout block account creation
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

        // Function to ensure account creation
        function ensureAccountCreation() {
            // Find email and password fields
            const emailField = document.querySelector('#email, input[type="email"]');
            const passwordField = document.querySelector('#textinput-1, input[type="password"]');

            if (emailField && passwordField && emailField.value && passwordField.value) {
                // Create hidden input to force account creation
                const form = emailField.closest('form') || document.querySelector('.wc-block-checkout__form');
                if (form) {
                    // Remove existing createaccount field if any
                    const existingCreateAccount = form.querySelector('input[name="createaccount"]');
                    if (existingCreateAccount) {
                        existingCreateAccount.remove();
                    }

                    // Add createaccount field
                    const createAccountInput = document.createElement('input');
                    createAccountInput.type = 'hidden';
                    createAccountInput.name = 'createaccount';
                    createAccountInput.value = '1';
                    form.appendChild(createAccountInput);

                    // Also add account_password field
                    const existingAccountPassword = form.querySelector('input[name="account_password"]');
                    if (existingAccountPassword) {
                        existingAccountPassword.remove();
                    }

                    const accountPasswordInput = document.createElement('input');
                    accountPasswordInput.type = 'hidden';
                    accountPasswordInput.name = 'account_password';
                    accountPasswordInput.value = passwordField.value;
                    form.appendChild(accountPasswordInput);
                }
            }
        }

        // Run before form submission
        document.addEventListener('click', function(e) {
            if (e.target && (
                e.target.textContent === 'Đặt hàng' ||
                e.target.classList.contains('wc-block-components-checkout-place-order-button') ||
                e.target.closest('.wc-block-components-checkout-place-order-button')
            )) {
                ensureAccountCreation();
            }
        }, true);

        // Also run on any form submission
        document.addEventListener('submit', function(e) {
            if (e.target && e.target.classList.contains('wc-block-checkout__form')) {
                ensureAccountCreation();
            }
        }, true);

        // Run periodically to ensure it's set
        setInterval(ensureAccountCreation, 2000);

    })();
    </script>
    <?php
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