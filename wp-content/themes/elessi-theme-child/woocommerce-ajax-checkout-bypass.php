<?php
/**
 * WooCommerce AJAX Checkout Bypass
 * Handle checkout via custom AJAX endpoint
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom AJAX endpoint for checkout
 */
add_action('wp_ajax_elessi_simple_checkout', 'elessi_handle_simple_checkout');
add_action('wp_ajax_nopriv_elessi_simple_checkout', 'elessi_handle_simple_checkout');

function elessi_handle_simple_checkout() {
    // Skip nonce verification for this custom endpoint
    // The checkout form may not have the correct nonce for our custom AJAX action
    
    // Log the request for debugging
    error_log('elessi_simple_checkout called with data: ' . print_r($_POST, true));
    
    // Initialize WooCommerce session if needed
    if (!WC()->session->has_session()) {
        WC()->session->set_customer_session_cookie(true);
    }
    
    // Check cart has contents
    if (WC()->cart->is_empty()) {
        wp_send_json_error('Cart is empty');
        return;
    }
    
    // Get posted data
    $email = sanitize_email($_POST['billing_email']);
    $first_name = sanitize_text_field($_POST['billing_first_name']);
    $last_name = sanitize_text_field($_POST['billing_last_name']);
    $phone = sanitize_text_field($_POST['billing_phone']);
    $order_comments = isset($_POST['order_comments']) ? sanitize_textarea_field($_POST['order_comments']) : '';
    
    // Validate required fields
    if (empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        wp_send_json_error('Vui lòng điền đầy đủ thông tin bắt buộc');
        return;
    }
    
    // Validate email format
    if (!is_email($email)) {
        wp_send_json_error('Vui lòng nhập địa chỉ email hợp lệ');
        return;
    }
    
    // Create order data
    $data = array(
        'status' => 'pending',
        'customer_id' => get_current_user_id(),
        'customer_ip_address' => WC_Geolocation::get_ip_address(),
        'customer_user_agent' => wc_get_user_agent(),
        'created_via' => 'checkout',
        'cart_hash' => WC()->cart->get_cart_hash(),
        'billing' => array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'country' => 'VN',
            'address_1' => 'N/A',
            'address_2' => '',
            'city' => 'Ho Chi Minh',
            'state' => 'VN',
            'postcode' => '700000',
        ),
        'shipping' => array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'country' => 'VN',
            'address_1' => 'N/A',
            'address_2' => '',
            'city' => 'Ho Chi Minh', 
            'state' => 'VN',
            'postcode' => '700000',
        ),
        'payment_method' => 'bacs',
        'payment_method_title' => 'Direct Bank Transfer',
        'set_paid' => false,
    );
    
    // Create the order
    $order = wc_create_order($data);
    
    if (is_wp_error($order)) {
        wp_send_json_error($order->get_error_message());
        return;
    }
    
    // Set billing email separately to ensure it's saved
    $order->set_billing_email($email);
    $order->set_billing_first_name($first_name);
    $order->set_billing_last_name($last_name);
    $order->set_billing_phone($phone);
    $order->set_billing_country('VN');
    $order->set_billing_address_1('N/A');
    $order->set_billing_city('Ho Chi Minh');
    $order->set_billing_state('VN');
    $order->set_billing_postcode('700000');
    
    // Set shipping info
    $order->set_shipping_first_name($first_name);
    $order->set_shipping_last_name($last_name);
    $order->set_shipping_country('VN');
    $order->set_shipping_address_1('N/A');
    $order->set_shipping_city('Ho Chi Minh');
    $order->set_shipping_state('VN');
    $order->set_shipping_postcode('700000');
    
    // Add items from cart
    foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
        $product = $values['data'];
        $order->add_product($product, $values['quantity']);
    }
    
    // Calculate totals
    $order->calculate_totals();
    
    // Set payment method
    $order->set_payment_method('bacs');
    
    // Set customer note if provided
    if (!empty($order_comments)) {
        $order->set_customer_note($order_comments);
    }
    
    // Add order note about source
    $order->add_order_note('Đơn hàng được tạo từ Simple Checkout (chỉ yêu cầu 4 trường thông tin)');
    
    // Save the order
    $order->save();
    
    // Set order status to pending payment
    $order->update_status('pending', __('Awaiting bank transfer payment', 'woocommerce'));
    
    // Trigger new order actions
    do_action('woocommerce_new_order', $order->get_id(), $order);
    do_action('woocommerce_checkout_order_processed', $order->get_id(), array(), $order);
    
    // Clear cart
    WC()->cart->empty_cart();
    
    // Clear any checkout sessions
    WC()->session->set('reload_checkout', null);
    
    // Return success with redirect URL
    wp_send_json_success(array(
        'redirect' => $order->get_checkout_order_received_url(),
        'order_id' => $order->get_id()
    ));
}

/**
 * Add JavaScript to handle custom checkout
 */
add_action('wp_footer', 'elessi_custom_checkout_script', 999);
function elessi_custom_checkout_script() {
    if (!is_checkout() || is_wc_endpoint_url('order-received')) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('Custom checkout script loaded');
        
        // Flag to prevent multiple submissions
        var isProcessing = false;
        
        // Track WooCommerce events that might disable button
        $(document).on('checkout_error', function() {
            console.log('=== WC EVENT: checkout_error ===');
            var $button = $('.wc-block-components-checkout-place-order-button');
            console.log('Button disabled after checkout_error:', $button.prop('disabled'));
            // Re-enable button
            setTimeout(function() {
                $button.prop('disabled', false)
                       .removeAttr('aria-disabled')
                       .removeClass('is-disabled')
                       .css({
                           'pointer-events': 'auto',
                           'cursor': 'pointer',
                           'opacity': '1'
                       });
                console.log('Button re-enabled after checkout_error');
            }, 100);
        });
        
        $(document).on('invalid_checkout', function() {
            console.log('=== WC EVENT: invalid_checkout ===');
            var $button = $('.wc-block-components-checkout-place-order-button');
            console.log('Button disabled after invalid_checkout:', $button.prop('disabled'));
            // Re-enable button
            setTimeout(function() {
                $button.prop('disabled', false)
                       .removeAttr('aria-disabled')
                       .removeClass('is-disabled')
                       .css({
                           'pointer-events': 'auto',
                           'cursor': 'pointer',
                           'opacity': '1'
                       });
                console.log('Button re-enabled after invalid_checkout');
            }, 100);
        });
        
        // Override checkout submission
        $(document).on('click', '.wc-block-components-checkout-place-order-button', function(e) {
            console.log('Custom checkout triggered');
            console.log('Button visible?', $(this).is(':visible'));
            console.log('Button display:', $(this).css('display'));
            console.log('Container visible?', $('.wc-block-checkout__actions').is(':visible'));
            
            // Prevent multiple submissions
            if (isProcessing) {
                console.log('Already processing, skipping');
                return false;
            }
            
            // Get form values
            var email = $('#email').val();
            var firstName = $('#billing-first_name').val();
            var lastName = $('#billing-last_name').val();
            var phone = $('#billing-phone').val();
            
            // Get order comments/notes
            var orderComments = '';
            // Try multiple selectors for order notes textarea
            var $notesTextarea = $('textarea[placeholder*="Ghi chú"], textarea.wc-block-components-textarea, #order_comments');
            if ($notesTextarea.length > 0) {
                orderComments = $notesTextarea.val();
                console.log('Found order comments:', orderComments);
            }
            
            console.log('Form values:', {email, firstName, lastName, phone, orderComments});
            
            // Check if all required fields are filled
            if (email && firstName && lastName && phone) {
                // Validate email format
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    console.log('Email validation failed');
                    alert('Vui lòng nhập địa chỉ email hợp lệ');
                    // Keep button enabled
                    var $button = $('.wc-block-components-checkout-place-order-button');
                    console.log('Email validation - Button disabled:', $button.prop('disabled'));
                    $button.prop('disabled', false)
                           .removeAttr('aria-disabled')
                           .removeClass('is-disabled')
                           .text('Đặt hàng')
                           .css({
                               'display': 'block',
                               'visibility': 'visible',
                               'opacity': '1',
                               'pointer-events': 'auto',
                               'cursor': 'pointer',
                               'text-align': 'center'
                           });
                    console.log('Email validation - Button enabled again');
                    return false;
                }
                
                // Validate phone format - more lenient
                var cleanPhone = phone.replace(/[\s\-\(\)\.]/g, ''); // Remove spaces, dashes, parentheses, dots
                if (cleanPhone.length < 9 || cleanPhone.length > 15 || !/^[\d\+]+$/.test(cleanPhone)) {
                    console.log('Phone validation failed');
                    alert('Vui lòng nhập số điện thoại hợp lệ (9-15 số)');
                    // Keep button enabled
                    var $button = $('.wc-block-components-checkout-place-order-button');
                    console.log('Before enabling - Button disabled:', $button.prop('disabled'));
                    $button.prop('disabled', false)
                           .removeAttr('aria-disabled')
                           .removeClass('is-disabled')
                           .text('Đặt hàng')
                           .css({
                               'display': 'block',
                               'visibility': 'visible',
                               'opacity': '1',
                               'pointer-events': 'auto',
                               'cursor': 'pointer',
                               'text-align': 'center'
                           });
                    console.log('After enabling - Button disabled:', $button.prop('disabled'));
                    return false;
                }
                
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('Sending custom AJAX request');
                
                // Set processing flag
                isProcessing = true;
                
                // Show processing state
                var $button = $('.wc-block-components-checkout-place-order-button');
                var originalText = $button.text();
                $button.addClass('is-processing processing')
                       .prop('disabled', true)
                       .text('Đang xử lý...');
                
                // Send AJAX request without nonce
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'elessi_simple_checkout',
                        billing_email: email,
                        billing_first_name: firstName,
                        billing_last_name: lastName,
                        billing_phone: phone,
                        order_comments: orderComments
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        if (response.success && response.data.redirect) {
                            console.log('Redirecting to:', response.data.redirect);
                            window.location.href = response.data.redirect;
                        } else {
                            console.error('Checkout failed:', response.data);
                            alert(response.data || 'Có lỗi xảy ra');
                            $button.removeClass('is-processing processing')
                                   .prop('disabled', false)
                                   .text(originalText);
                            isProcessing = false;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('Có lỗi xảy ra khi xử lý đơn hàng');
                        $button.removeClass('is-processing processing')
                               .prop('disabled', false)
                               .text(originalText);
                        isProcessing = false;
                    }
                });
                
                return false;
            } else {
                // Show which fields are missing
                var missing = [];
                if (!email) missing.push('Email');
                if (!firstName) missing.push('Tên');
                if (!lastName) missing.push('Họ');
                if (!phone) missing.push('Số điện thoại');
                
                if (missing.length > 0) {
                    console.log('Missing fields:', missing.join(', '));
                }
            }
        });
        
        // Prevent button from disappearing
        var buttonObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                var $button = $('.wc-block-components-checkout-place-order-button');
                var $container = $('.wc-block-checkout__actions');
                
                // Check if button or container was hidden
                if ($button.is(':hidden') || $container.is(':hidden')) {
                    console.log('=== BUTTON HIDDEN DETECTED ===');
                    console.log('Button hidden?', $button.is(':hidden'));
                    console.log('Container hidden?', $container.is(':hidden'));
                    console.log('Button display:', $button.css('display'));
                    console.log('Container display:', $container.css('display'));
                    console.log('Mutation type:', mutation.type);
                    console.log('Mutation attribute:', mutation.attributeName);
                    console.log('Target element:', mutation.target);
                    
                    $button.show().css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    }).prop('disabled', false);
                    $container.show().css({
                        'display': 'block',
                        'visibility': 'visible'
                    });
                    console.log('After fix - Button display:', $button.css('display'));
                    console.log('After fix - Container display:', $container.css('display'));
                }
            });
        });
        
        // Observe the button and its container
        var checkoutButton = document.querySelector('.wc-block-components-checkout-place-order-button');
        var checkoutActions = document.querySelector('.wc-block-checkout__actions');
        
        if (checkoutButton) {
            buttonObserver.observe(checkoutButton, {
                attributes: true,
                attributeFilter: ['style', 'class', 'disabled']
            });
        }
        
        if (checkoutActions) {
            buttonObserver.observe(checkoutActions, {
                attributes: true,
                childList: true,
                subtree: true
            });
        }
        
        // Check periodically to ensure button stays visible and enabled
        var checkCount = 0;
        var originalButtonText = 'Đặt hàng'; // Store original text
        
        setInterval(function() {
            var $button = $('.wc-block-components-checkout-place-order-button');
            var $container = $('.wc-block-checkout__actions');
            
            // Check if button is hidden
            if ($button.length && ($button.is(':hidden') || $button.css('display') === 'none')) {
                checkCount++;
                console.log('=== PERIODIC CHECK #' + checkCount + ' - BUTTON HIDDEN ===');
                console.log('Button display:', $button.css('display'));
                console.log('Button visibility:', $button.css('visibility'));
                console.log('Button opacity:', $button.css('opacity'));
                console.log('Button classes:', $button.attr('class'));
                
                $button.show().css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                console.log('After periodic fix - display:', $button.css('display'));
            }
            
            // Check if button is disabled or text changed
            if ($button.length) {
                var currentText = $button.text().trim();
                var needsFix = false;
                
                // Check if disabled
                if ($button.prop('disabled') || $button.attr('aria-disabled') === 'true') {
                    console.log('=== PERIODIC CHECK - BUTTON DISABLED ===');
                    console.log('Button disabled prop:', $button.prop('disabled'));
                    console.log('Button aria-disabled:', $button.attr('aria-disabled'));
                    console.log('Button cursor:', $button.css('cursor'));
                    needsFix = true;
                }
                
                // Check if text changed to checkmark or other text
                if (currentText !== originalButtonText && (currentText === '✓' || currentText === '✔' || currentText.length < 3)) {
                    console.log('=== BUTTON TEXT CHANGED ===');
                    console.log('Current text:', currentText);
                    needsFix = true;
                }
                
                if (needsFix) {
                    $button.prop('disabled', false)
                           .removeAttr('aria-disabled')
                           .removeClass('is-disabled')
                           .text(originalButtonText)
                           .css({
                               'pointer-events': 'auto',
                               'cursor': 'pointer',
                               'opacity': '1',
                               'text-align': 'center',
                               'display': 'block',
                               'width': '100%'
                           });
                    console.log('Button fixed - enabled and text restored');
                }
            }
            
            if ($container.length && ($container.is(':hidden') || $container.css('display') === 'none')) {
                console.log('=== PERIODIC CHECK - CONTAINER HIDDEN ===');
                $container.show().css({
                    'display': 'block',
                    'visibility': 'visible'
                });
            }
        }, 500);
    });
    </script>
    <?php
}