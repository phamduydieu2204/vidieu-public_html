/**
 * Single Product Buy Now Handler
 * Handles Buy Now functionality on single product pages
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */
(function($) {
    'use strict';
    
    if (typeof $ === 'undefined') {
        return;
    }
    
    var SingleProductBuyNow = {
        init: function() {
            this.bindBuyNowButton();
        },
        
        /**
         * Bind click handler to NASA Buy Now button
         */
        bindBuyNowButton: function() {
            var self = this;
            
            // Handle Buy Now button click on single product page
            $(document).on('click', '.single_add_to_cart_button.nasa-buy-now', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $form = $button.closest('form.cart');
                
                // Prevent double clicks
                if ($button.hasClass('loading') || $button.hasClass('vd-processing')) {
                    return false;
                }
                
                // Handle variable products
                if ($form.hasClass('variations_form')) {
                    var variationId = $form.find('.variation_id').val();
                    
                    if (!variationId || variationId === '0' || variationId === '') {
                        alert('Please select product options.');
                        return false;
                    }
                    
                    // Process variable product buy now
                    self.processBuyNow($form, 'variable');
                } else {
                    // Process simple product buy now
                    self.processBuyNow($form, 'simple');
                }
            });
        },
        
        /**
         * Process Buy Now action
         */
        processBuyNow: function($form, productType) {
            var self = this;
            var $button = $form.find('.nasa-buy-now');
            
            // Add loading state
            $button.addClass('loading vd-processing');
            
            // Get form data
            var productId = $form.find('[name="product_id"]').val() || $form.find('[name="add-to-cart"]').val();
            var quantity = self.getValidQuantity($form);
            var variationId = 0;
            var variationData = {};
            
            // For variable products, get variation data
            if (productType === 'variable') {
                variationId = $form.find('.variation_id').val();
                
                // Collect variation attributes
                $form.find('.variations select').each(function() {
                    var $select = $(this);
                    var attrName = $select.attr('name');
                    var attrValue = $select.val();
                    
                    if (attrName && attrValue) {
                        variationData[attrName] = attrValue;
                    }
                });
            }
            
            // Prepare AJAX data
            var ajaxData = {
                action: 'vidieu_buy_now',
                nonce: vd_home_ajax.nonce, // Use global nonce
                product_id: productId,
                quantity: quantity,
                action_type: 'buy-now'
            };
            
            // Add variation data if applicable
            if (variationId) {
                ajaxData.variation_id = variationId;
                
                // Add variation attributes
                $.each(variationData, function(key, value) {
                    ajaxData[key] = value;
                });
            }
            
            // Send AJAX request
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    $button.removeClass('loading vd-processing');
                    
                    if (response.success && response.data) {
                        if (response.data.action === 'redirect' && response.data.redirect_url) {
                            // Redirect to cart/checkout
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        // Show error message
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    $button.removeClass('loading vd-processing');
                    
                    var errorMessage = vd_home_ajax.error_text || 'An error occurred. Please try again.';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    alert(errorMessage);
                }
            });
        },
        
        /**
         * Get valid quantity from form input
         */
        getValidQuantity: function($form) {
            var $qtyInput = $form.find('.qty');
            var quantity = 1;
            
            if ($qtyInput.length) {
                quantity = parseInt($qtyInput.val(), 10);
                
                // Validate quantity
                if (isNaN(quantity) || quantity < 1) {
                    quantity = 1;
                }
                
                // Check min attribute
                var min = parseInt($qtyInput.attr('min'), 10);
                if (!isNaN(min) && quantity < min) {
                    quantity = min;
                }
                
                // Check max attribute
                var max = parseInt($qtyInput.attr('max'), 10);
                if (!isNaN(max) && max > 0 && quantity > max) {
                    quantity = max;
                    $qtyInput.val(max); // Update input to max value
                }
                
                // Ensure it's an integer
                quantity = Math.floor(quantity);
            }
            
            return quantity;
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        // Only initialize on single product pages
        if ($('body').hasClass('single-product')) {
            SingleProductBuyNow.init();
        }
    });
    
})(jQuery);