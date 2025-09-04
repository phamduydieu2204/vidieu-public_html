/**
 * WooCommerce Block Checkout - Hide Fields and Bypass Validation
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

(function() {
    'use strict';


    // Wait for DOM to be ready
    function domReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    // Main function to hide fields and bypass validation
    function simplifyBlockCheckout() {
        
        // Check if we're on checkout page
        if (!document.querySelector('.wc-block-checkout')) {
            return;
        }

        // Function to hide billing section title and description
        function hideBillingTitleAndDescription() {
            
            // Find all elements with the description text
            const allParagraphs = document.querySelectorAll('p');
            allParagraphs.forEach(p => {
                if (p.textContent.includes('Nhập địa chỉ thanh toán khớp với phương thức thanh toán của bạn')) {
                    p.style.display = 'none';
                    p.style.setProperty('display', 'none', 'important');
                }
            });
            
            // Also hide by class
            const descriptions = document.querySelectorAll('.wc-block-components-checkout-step__description');
            descriptions.forEach(desc => {
                desc.style.display = 'none';
                desc.style.setProperty('display', 'none', 'important');
            });
            
            // Hide billing titles
            const titles = document.querySelectorAll('h2.wc-block-components-title');
            titles.forEach(title => {
                if (title.textContent.includes('Địa chỉ thanh toán')) {
                    title.style.display = 'none';
                    title.style.setProperty('display', 'none', 'important');
                }
            });
        }
        
        // Function to add h1 title above email field
        function addContactInfoTitle() {
            const emailField = document.querySelector('#email');
            if (emailField && !document.querySelector('#contact-info-h1')) {
                // Find the parent container of email field
                const emailContainer = emailField.closest('.wc-block-components-text-input');
                if (emailContainer) {
                    // Check if h1 already exists
                    const existingH1 = emailContainer.parentElement.querySelector('h1#contact-info-h1');
                    if (!existingH1) {
                        // Create h1 element
                        const h1 = document.createElement('h1');
                        h1.id = 'contact-info-h1';
                        h1.textContent = 'Thông tin liên hệ';
                        h1.style.fontSize = '24px';
                        h1.style.fontWeight = 'bold';
                        h1.style.marginTop = '0';
                        h1.style.marginBottom = '15px';
                        
                        // Insert h1 before email container
                        emailContainer.parentElement.insertBefore(h1, emailContainer);
                    }
                }
            }
        }

        // Function to hide fields and fill default values
        function hideAndFillFields() {
            
            // First hide billing title and description
            hideBillingTitleAndDescription();
            
            // Add contact info title
            addContactInfoTitle();
            
            // Find and process all address fields
            const fieldsToHide = [
                { 
                    selector: '#billing-country', 
                    value: 'VN',
                    type: 'select'
                },
                { 
                    selector: '#billing-address_1', 
                    value: 'N/A',
                    type: 'input'
                },
                { 
                    selector: '#billing-city', 
                    value: 'Ho Chi Minh',
                    type: 'input'
                },
                { 
                    selector: '#billing-state', 
                    value: 'VN',
                    type: 'input'
                },
                { 
                    selector: '#billing-postcode', 
                    value: '700000',
                    type: 'input'
                }
            ];

            fieldsToHide.forEach(field => {
                const element = document.querySelector(field.selector);
                if (element) {
                    // Fill value
                    if (element.value !== field.value) {
                        element.value = field.value;
                        
                        // Trigger events to notify React
                        const inputEvent = new Event('input', { bubbles: true });
                        const changeEvent = new Event('change', { bubbles: true });
                        element.dispatchEvent(inputEvent);
                        element.dispatchEvent(changeEvent);
                        
                        // For React 16+
                        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                            field.type === 'select' ? HTMLSelectElement.prototype : HTMLInputElement.prototype, 
                            'value'
                        ).set;
                        nativeInputValueSetter.call(element, field.value);
                        element.dispatchEvent(inputEvent);
                    }
                    
                    // Remove required attribute
                    element.removeAttribute('required');
                    element.setAttribute('aria-required', 'false');
                    
                    // Remove error state
                    element.setAttribute('aria-invalid', 'false');
                    element.classList.remove('has-error');
                }
            });

            // Force bank transfer selection
            const bankTransferRadio = document.querySelector('#radio-control-wc-payment-method-options-bacs');
            if (bankTransferRadio && !bankTransferRadio.checked) {
                bankTransferRadio.checked = true;
                bankTransferRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Remove validation from hidden fields
            document.querySelectorAll('.wc-block-components-text-input').forEach(wrapper => {
                const input = wrapper.querySelector('input, select');
                if (input && (input.id && fieldsToHide.some(f => f.selector === `#${input.id}`))) {
                    wrapper.classList.remove('has-error');
                    const validationError = wrapper.querySelector('.wc-block-components-validation-error');
                    if (validationError) {
                        validationError.style.display = 'none';
                    }
                }
            });
        }

        // Initial hide and fill
        setTimeout(hideAndFillFields, 500);

        // Monitor for React re-renders and reapply
        const observer = new MutationObserver(function(mutations) {
            // Check for any new paragraphs or descriptions added
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            // Check if it's the description paragraph
                            if (node.tagName === 'P' && node.textContent.includes('Nhập địa chỉ thanh toán')) {
                                node.style.display = 'none';
                                node.style.setProperty('display', 'none', 'important');
                            }
                            // Check children too
                            const descElements = node.querySelectorAll ? node.querySelectorAll('.wc-block-components-checkout-step__description, p') : [];
                            descElements.forEach(el => {
                                if (el.textContent.includes('Nhập địa chỉ thanh toán')) {
                                    el.style.display = 'none';
                                    el.style.setProperty('display', 'none', 'important');
                                }
                            });
                        }
                    });
                }
            });
            
            // Only process if checkout form changed
            const hasRelevantChange = mutations.some(mutation => {
                return mutation.target.classList && (
                    mutation.target.classList.contains('wc-block-checkout') ||
                    mutation.target.classList.contains('wc-block-components-address-form')
                );
            });
            
            if (hasRelevantChange) {
                hideAndFillFields();
            }
        });

        // Start observing
        const checkoutForm = document.querySelector('.wc-block-checkout__form');
        if (checkoutForm) {
            observer.observe(checkoutForm, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'aria-invalid']
            });
        }

        // Intercept form submission
        document.addEventListener('click', function(e) {
            if (e.target && (
                e.target.textContent === 'Đặt hàng' || 
                e.target.classList.contains('wc-block-components-checkout-place-order-button') ||
                e.target.closest('.wc-block-components-checkout-place-order-button')
            )) {
                
                // Fill all fields one more time
                hideAndFillFields();
                
                // Force remove all validation errors for hidden fields
                setTimeout(() => {
                    document.querySelectorAll('.wc-block-components-validation-error').forEach(error => {
                        const parent = error.closest('.wc-block-components-text-input');
                        if (parent) {
                            const input = parent.querySelector('input, select');
                            if (input && ['billing-country', 'billing-address_1', 'billing-city', 'billing-state', 'billing-postcode'].includes(input.id)) {
                                error.style.display = 'none';
                                parent.classList.remove('has-error');
                            }
                        }
                    });
                }, 100);
            }
        }, true);

        // Additional validation bypass
        if (window.wp && window.wp.data) {
            
            // Try to access checkout store
            const checkoutStore = window.wp.data.select('wc/store/checkout');
            if (checkoutStore) {
                
                // Override validation
                const originalHasError = checkoutStore.hasError;
                if (originalHasError) {
                    checkoutStore.hasError = function() {
                        const result = originalHasError.apply(this, arguments);
                        return false; // Always return no error
                    };
                }
            }
        }

        // Log any validation errors and monitor button state
        setInterval(() => {
            // Also check for billing description periodically
            hideBillingTitleAndDescription();
            
            // Make sure contact info title is present
            addContactInfoTitle();
            
            const errors = document.querySelectorAll('.wc-block-components-validation-error:not([style*="display: none"])');
            const orderButton = document.querySelector('.wc-block-components-checkout-place-order-button');
            const buttonContainer = document.querySelector('.wc-block-checkout__actions');
            
            if (errors.length > 0) {
            }
            
            // Check button visibility
            if (orderButton) {
                const buttonStyle = window.getComputedStyle(orderButton);
                const containerStyle = buttonContainer ? window.getComputedStyle(buttonContainer) : null;
                
                if (buttonStyle.display === 'none' || (containerStyle && containerStyle.display === 'none')) {
                    
                    // Force show button
                    orderButton.style.setProperty('display', 'block', 'important');
                    orderButton.style.setProperty('visibility', 'visible', 'important');
                    orderButton.style.setProperty('opacity', '1', 'important');
                    if (buttonContainer) {
                        buttonContainer.style.setProperty('display', 'block', 'important');
                        buttonContainer.style.setProperty('visibility', 'visible', 'important');
                    }
                }
            }
        }, 500);
    }

    // Initialize when DOM is ready
    domReady(simplifyBlockCheckout);
    
    // Also try after a delay in case React hasn't rendered yet
    setTimeout(simplifyBlockCheckout, 1000);
})();