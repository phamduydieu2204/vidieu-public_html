<?php
/**
 * WooCommerce Checkout - Simple Fix for Virtual Products
 * Remove all unnecessary fields and shipping
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mark all products as virtual to disable shipping
 */
add_filter('woocommerce_product_needs_shipping', '__return_false', 999);
add_filter('woocommerce_cart_needs_shipping', '__return_false', 999);
add_filter('woocommerce_cart_needs_shipping_address', '__return_false', 999);

/**
 * Disable shipping calculations
 */
add_filter('woocommerce_shipping_enabled', '__return_false', 999);
add_filter('wc_shipping_enabled', '__return_false', 999);

/**
 * Remove shipping from checkout
 */
add_action('init', function() {
    remove_action('woocommerce_checkout_shipping', array(WC()->checkout(), 'checkout_form_shipping'));
});

/**
 * Simplify checkout fields - only keep essential fields
 */
add_filter('woocommerce_checkout_fields', 'elessi_child_simple_checkout_fields', 9999);
function elessi_child_simple_checkout_fields($fields) {
    // Create minimal billing fields
    $minimal_fields = array(
        'billing' => array(
            'billing_email' => array(
                'label' => __('Email', 'woocommerce'),
                'required' => true,
                'type' => 'email',
                'class' => array('form-row-wide'),
                'priority' => 10,
            ),
            'billing_first_name' => array(
                'label' => __('Tên', 'woocommerce'),
                'required' => true,
                'class' => array('form-row-first'),
                'priority' => 20,
            ),
            'billing_last_name' => array(
                'label' => __('Họ', 'woocommerce'),
                'required' => true,
                'class' => array('form-row-last'),
                'priority' => 30,
            ),
            'billing_phone' => array(
                'label' => __('Số điện thoại', 'woocommerce'),
                'required' => true,
                'type' => 'tel',
                'class' => array('form-row-wide'),
                'priority' => 40,
            ),
        ),
        'shipping' => array(), // Empty shipping
        'order' => isset($fields['order']) ? $fields['order'] : array(),
        'account' => isset($fields['account']) ? $fields['account'] : array(),
    );
    
    return $minimal_fields;
}

/**
 * Set default country to Vietnam
 */
add_filter('default_checkout_billing_country', function() {
    return 'VN';
});

/**
 * Fill hidden required fields with default values
 */
add_action('woocommerce_checkout_process', 'elessi_child_fill_hidden_fields', 5);
function elessi_child_fill_hidden_fields() {
    if (empty($_POST['billing_country'])) {
        $_POST['billing_country'] = 'VN';
    }
    if (empty($_POST['billing_address_1'])) {
        $_POST['billing_address_1'] = 'N/A';
    }
    if (empty($_POST['billing_city'])) {
        $_POST['billing_city'] = 'Ho Chi Minh';
    }
    if (empty($_POST['billing_state'])) {
        $_POST['billing_state'] = 'VN';
    }
    if (empty($_POST['billing_postcode'])) {
        $_POST['billing_postcode'] = '700000';
    }
    
    // Set payment method
    if (empty($_POST['payment_method'])) {
        $_POST['payment_method'] = 'bacs';
    }
}

/**
 * Remove validation for hidden fields
 */
add_filter('woocommerce_checkout_required_field_notice', 'elessi_child_remove_hidden_field_notices', 10, 2);
function elessi_child_remove_hidden_field_notices($notice, $field_label) {
    $hidden_fields = array('Địa chỉ', 'Thị trấn / Thành phố', 'Tiểu bang / Hạt', 'Mã bưu điện', 'Country', 'Address', 'City', 'State', 'Postcode');
    
    foreach ($hidden_fields as $hidden) {
        if (stripos($field_label, $hidden) !== false) {
            return '';
        }
    }
    
    return $notice;
}

/**
 * Remove validation errors for hidden fields  
 */
add_action('woocommerce_after_checkout_validation', 'elessi_child_clear_hidden_field_errors', 10, 2);
function elessi_child_clear_hidden_field_errors($data, $errors) {
    if (!$errors || !is_object($errors)) {
        return;
    }
    
    $error_codes = $errors->get_error_codes();
    foreach ($error_codes as $code) {
        if (strpos($code, 'billing_country') !== false ||
            strpos($code, 'billing_address') !== false ||
            strpos($code, 'billing_city') !== false ||
            strpos($code, 'billing_state') !== false ||
            strpos($code, 'billing_postcode') !== false ||
            strpos($code, 'shipping_') !== false) {
            $errors->remove($code);
        }
    }
}

/**
 * Add hidden fields for Block Checkout
 */
add_action('woocommerce_review_order_after_payment', 'elessi_child_add_hidden_fields_block_checkout');
function elessi_child_add_hidden_fields_block_checkout() {
    ?>
    <div style="display: none !important;">
        <input type="hidden" name="billing_country" value="VN" />
        <input type="hidden" name="billing_address_1" value="N/A" />
        <input type="hidden" name="billing_city" value="Ho Chi Minh" />
        <input type="hidden" name="billing_state" value="VN" />
        <input type="hidden" name="billing_postcode" value="700000" />
        <input type="hidden" name="shipping_country" value="VN" />
        <input type="hidden" name="shipping_address_1" value="N/A" />
        <input type="hidden" name="shipping_city" value="Ho Chi Minh" />
        <input type="hidden" name="shipping_state" value="VN" />
        <input type="hidden" name="shipping_postcode" value="700000" />
        <input type="hidden" name="ship_to_different_address" value="0" />
    </div>
    <?php
}

/**
 * JavaScript for Block Checkout
 */
add_action('wp_footer', 'elessi_child_block_checkout_js');
function elessi_child_block_checkout_js() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function() {
        
        // Function to fill all hidden fields
        function fillHiddenFields() {
            const fieldsToFill = {
                'billing_country': 'VN',
                'billing-country': 'VN',
                'billing_address_1': 'N/A',
                'billing-address_1': 'N/A',
                'billing_city': 'Ho Chi Minh',
                'billing-city': 'Ho Chi Minh',
                'billing_state': 'VN',
                'billing-state': 'VN',
                'billing_postcode': '700000',
                'billing-postcode': '700000',
                'shipping_country': 'VN',
                'shipping-country': 'VN',
                'shipping_address_1': 'N/A',
                'shipping-address_1': 'N/A',
                'shipping_city': 'Ho Chi Minh',
                'shipping-city': 'Ho Chi Minh',
                'shipping_state': 'VN',
                'shipping-state': 'VN',
                'shipping_postcode': '700000',
                'shipping-postcode': '700000'
            };
            
            Object.keys(fieldsToFill).forEach(fieldId => {
                // Try by ID
                let field = document.getElementById(fieldId);
                // Try by name
                if (!field) {
                    field = document.querySelector(`[name="${fieldId}"]`);
                }
                
                if (field && field.value !== fieldsToFill[fieldId]) {
                    field.value = fieldsToFill[fieldId];
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            
            // Force payment method
            const paymentRadio = document.querySelector('[value="bacs"][type="radio"]');
            if (paymentRadio && !paymentRadio.checked) {
                paymentRadio.checked = true;
                paymentRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        
        // Fill on load
        setTimeout(fillHiddenFields, 1000);
        
        // Fill on any change
        document.addEventListener('change', fillHiddenFields);
        
        // Fill before submit
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.textContent === 'Đặt hàng' || e.target.classList.contains('wc-block-components-checkout-place-order-button'))) {
                fillHiddenFields();
                
                // Create hidden inputs if they don't exist
                const form = e.target.closest('form') || document.querySelector('.wc-block-checkout__form');
                if (form) {
                    const requiredFields = {
                        'billing_country': 'VN',
                        'billing_address_1': 'N/A',
                        'billing_city': 'Ho Chi Minh',
                        'billing_state': 'VN',
                        'billing_postcode': '700000'
                    };
                    
                    Object.keys(requiredFields).forEach(name => {
                        if (!form.querySelector(`[name="${name}"]`)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = requiredFields[name];
                            form.appendChild(input);
                        }
                    });
                }
            }
        });
    })();
    </script>
    <?php
}