/**
 * WooCommerce Block Checkout - Fix Validation for Hidden Fields
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

(function() {
    'use strict';


    // Wait for WooCommerce blocks to be ready
    function waitForWc(callback) {
        if (window.wc && window.wc.blocksCheckout && window.wp && window.wp.data) {
            callback();
        } else {
            setTimeout(() => waitForWc(callback), 100);
        }
    }

    waitForWc(function() {

        // Fill hidden fields with default values
        function fillHiddenFields() {
            const defaults = {
                'billing-country': 'VN',
                'billing-address_1': 'N/A',
                'billing-city': 'Ho Chi Minh',
                'billing-state': 'VN', 
                'billing-postcode': '700000'
            };

            // Fill DOM fields
            Object.keys(defaults).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && (!field.value || field.value === '')) {
                    field.value = defaults[fieldId];
                    
                    // Trigger React events
                    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                        field.tagName === 'SELECT' ? HTMLSelectElement.prototype : HTMLInputElement.prototype, 
                        'value'
                    ).set;
                    nativeInputValueSetter.call(field, defaults[fieldId]);
                    
                    const inputEvent = new Event('input', { bubbles: true });
                    const changeEvent = new Event('change', { bubbles: true });
                    field.dispatchEvent(inputEvent);
                    field.dispatchEvent(changeEvent);
                }
            });

            // Force payment method
            const bankTransferRadio = document.querySelector('#radio-control-wc-payment-method-options-bacs');
            if (bankTransferRadio && !bankTransferRadio.checked) {
                bankTransferRadio.checked = true;
                bankTransferRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Override validation handling
        if (window.wp && window.wp.data) {
            const { dispatch, select } = window.wp.data;
            
            // Try different store names that WooCommerce might use
            const storeNames = ['wc/store/checkout', 'wc/store/validation', 'wc/checkout'];
            
            storeNames.forEach(storeName => {
                try {
                    const store = select(storeName);
                    if (store) {
                        
                        // Override any validation methods we find
                        const methods = Object.getOwnPropertyNames(store);
                        methods.forEach(method => {
                            if (method.toLowerCase().includes('valid') || method.toLowerCase().includes('error')) {
                                const original = store[method];
                                if (typeof original === 'function') {
                                    store[method] = function(...args) {
                                        const result = original.apply(this, args);
                                        
                                        // If it's returning validation errors, filter them
                                        if (result && typeof result === 'object') {
                                            const filtered = filterValidationErrors(result);
                                            return filtered;
                                        }
                                        
                                        return result;
                                    };
                                }
                            }
                        });
                    }
                } catch (e) {
                    // Store doesn't exist, continue
                }
            });
        }

        // Filter validation errors
        function filterValidationErrors(errors) {
            if (!errors) return errors;
            
            const fieldsToIgnore = [
                'billing_country',
                'billing_address_1',
                'billing_city', 
                'billing_state',
                'billing_postcode',
                'shipping_country',
                'shipping_address_1',
                'shipping_city',
                'shipping_state', 
                'shipping_postcode'
            ];
            
            if (Array.isArray(errors)) {
                return errors.filter(error => {
                    return !fieldsToIgnore.some(field => 
                        error && error.field && error.field.includes(field)
                    );
                });
            } else if (typeof errors === 'object') {
                const filtered = {};
                Object.keys(errors).forEach(key => {
                    if (!fieldsToIgnore.some(field => key.includes(field))) {
                        filtered[key] = errors[key];
                    }
                });
                return filtered;
            }
            
            return errors;
        }

        // Fill fields initially
        setTimeout(fillHiddenFields, 1000);

        // Fill fields before checkout submission
        document.addEventListener('click', function(e) {
            if (e.target && (
                e.target.textContent === 'Đặt hàng' || 
                e.target.classList.contains('wc-block-components-checkout-place-order-button') ||
                e.target.closest('.wc-block-components-checkout-place-order-button')
            )) {
                fillHiddenFields();
                
                // Remove validation errors from DOM
                setTimeout(() => {
                    document.querySelectorAll('.wc-block-components-validation-error').forEach(error => {
                        const text = error.textContent.toLowerCase();
                        if (text.includes('country') || 
                            text.includes('address') || 
                            text.includes('city') || 
                            text.includes('state') || 
                            text.includes('postcode') ||
                            text.includes('quốc gia') ||
                            text.includes('địa chỉ') ||
                            text.includes('thành phố') ||
                            text.includes('tiểu bang') ||
                            text.includes('mã bưu')) {
                            error.style.display = 'none';
                            const parent = error.closest('.has-error');
                            if (parent) {
                                parent.classList.remove('has-error');
                            }
                        }
                    });
                }, 100);
            }
        }, true);

        // Monitor for changes and auto-fill
        const observer = new MutationObserver(function(mutations) {
            // Check if any hidden fields are empty
            const needsFill = ['billing-country', 'billing-address_1', 'billing-city', 'billing-state', 'billing-postcode']
                .some(id => {
                    const field = document.getElementById(id);
                    return field && (!field.value || field.value === '');
                });
            
            if (needsFill) {
                fillHiddenFields();
            }
        });

        // Start observing
        const checkoutForm = document.querySelector('.wc-block-checkout__form');
        if (checkoutForm) {
            observer.observe(checkoutForm, {
                childList: true,
                subtree: true
            });
        }
    });
})();