/**
 * Force submit checkout by overriding validation
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

(function() {
    'use strict';


    // Wait for WooCommerce to be ready
    function waitForWc(callback) {
        if (window.wp && window.wp.data && window.wc && window.wc.wcBlocksData) {
            callback();
        } else {
            setTimeout(() => waitForWc(callback), 100);
        }
    }

    waitForWc(function() {

        // Override checkout submission
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.textContent === 'Đặt hàng' || e.target.classList.contains('wc-block-components-checkout-place-order-button'))) {
                
                // Check if we have required fields filled
                const email = document.querySelector('#email')?.value;
                const firstName = document.querySelector('#billing-first_name')?.value;
                const lastName = document.querySelector('#billing-last_name')?.value; 
                const phone = document.querySelector('#billing-phone')?.value;
                
                
                if (email && firstName && lastName && phone) {
                    
                    // Try to find the checkout form and submit it directly
                    setTimeout(() => {
                        // Clear all validation errors from store
                        if (window.wp && window.wp.data && window.wp.data.dispatch) {
                            const validationStore = window.wp.data.dispatch('wc/store/validation');
                            if (validationStore && validationStore.clearAllValidationErrors) {
                                validationStore.clearAllValidationErrors();
                            }
                            
                            // Also try to set validation errors to empty
                            if (validationStore && validationStore.setValidationErrors) {
                                validationStore.setValidationErrors({});
                            }
                        }
                        
                        // Try to submit via checkout actions
                        if (window.wp && window.wp.data && window.wp.data.dispatch) {
                            const checkoutActions = window.wp.data.dispatch('wc/store/checkout');
                            if (checkoutActions) {
                                
                                // Set customer data with all required fields
                                if (checkoutActions.__internalSetCustomerData) {
                                    checkoutActions.__internalSetCustomerData({
                                        billingAddress: {
                                            first_name: firstName,
                                            last_name: lastName,
                                            email: email,
                                            phone: phone,
                                            country: 'VN',
                                            address_1: 'N/A',
                                            city: 'Ho Chi Minh',
                                            state: 'VN',
                                            postcode: '700000'
                                        },
                                        shippingAddress: {
                                            first_name: firstName,
                                            last_name: lastName,
                                            country: 'VN',
                                            address_1: 'N/A',
                                            city: 'Ho Chi Minh',
                                            state: 'VN',
                                            postcode: '700000'
                                        }
                                    });
                                }
                                
                                // Try to submit order
                                if (checkoutActions.__internalSubmitCheckout) {
                                    checkoutActions.__internalSubmitCheckout();
                                }
                            }
                        }
                    }, 100);
                } else {
                }
            }
        }, true);
    });
})();