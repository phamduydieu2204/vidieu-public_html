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
            this.unbindExistingHandlers();
            this.bindBuyNowButton();
        },
        
        /**
         * Unbind existing NASA theme handlers
         */
        unbindExistingHandlers: function() {
            // Remove all existing click handlers from NASA theme
            $('.nasa-buy-now, button.nasa-buy-now, .single_add_to_cart_button.nasa-buy-now').each(function() {
                var $button = $(this);
                // Clone and replace to remove all event handlers
                var $newButton = $button.clone(true, false);
                $button.replaceWith($newButton);
            });
            
            console.log('SingleProductBuyNow: Removed existing handlers');
        },
        
        /**
         * Bind click handler to NASA Buy Now button
         */
        bindBuyNowButton: function() {
            var self = this;
            
            // Use capturing phase to intercept before NASA theme
            if (document.addEventListener) {
                document.addEventListener('click', function(e) {
                    var $target = $(e.target);
                    
                    // Check if clicked element is buy now button
                    if ($target.hasClass('nasa-buy-now') || 
                        $target.closest('.nasa-buy-now').length ||
                        ($target.hasClass('single_add_to_cart_button') && $target.hasClass('nasa-buy-now'))) {
                        
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        console.log('SingleProductBuyNow: Intercepted buy now click in capture phase');
                        
                        // Call our handler
                        self.handleBuyNowClick($target.hasClass('nasa-buy-now') ? $target : $target.closest('.nasa-buy-now'));
                        
                        return false;
                    }
                }, true); // true = use capture phase
            }
            
            // Also bind normally as fallback
            $(document).off('click.vdBuyNow').on('click.vdBuyNow', '.nasa-buy-now, button.nasa-buy-now, .single_add_to_cart_button.nasa-buy-now', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('SingleProductBuyNow: Buy Now clicked via jQuery handler');
                
                self.handleBuyNowClick($(this));
                
                return false;
            });
        },
        
        /**
         * Handle buy now button click
         */
        handleBuyNowClick: function($button) {
            var self = this;
            
            console.log('SingleProductBuyNow: Processing buy now click');
            
            // Ensure we have the button element
            if (!$button || !$button.length) {
                console.error('SingleProductBuyNow: Button not found');
                return;
            }
            
            var $form = $button.closest('form.cart');
            console.log('SingleProductBuyNow: Form found:', $form.length > 0, $form);
            
            // Prevent double clicks
            if ($button.hasClass('loading') || $button.hasClass('vd-processing')) {
                console.log('SingleProductBuyNow: Button already processing');
                return false;
            }
            
            // Handle variable products
            if ($form.hasClass('variations_form')) {
                var variationId = $form.find('.variation_id').val();
                console.log('SingleProductBuyNow: Variable product, variation ID:', variationId);
                
                if (!variationId || variationId === '0' || variationId === '') {
                    alert('Please select product options.');
                    return false;
                }
                
                // Process variable product buy now
                self.processBuyNow($form, 'variable');
            } else {
                console.log('SingleProductBuyNow: Simple product');
                // Process simple product buy now
                self.processBuyNow($form, 'simple');
            }
        },
        
        /**
         * Process Buy Now action
         */
        processBuyNow: function($form, productType) {
            var self = this;
            var $button = $form.find('.nasa-buy-now');
            
            console.log('SingleProductBuyNow: Processing buy now for', productType, 'product');
            
            // Add loading state
            $button.addClass('loading vd-processing');
            
            // Get form data
            var productId = $form.find('[name="product_id"]').val() || $form.find('[name="add-to-cart"]').val();
            var quantity = self.getValidQuantity($form);
            var variationId = 0;
            var variationData = {};
            
            console.log('SingleProductBuyNow: Product ID:', productId, 'Quantity:', quantity);
            
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
            
            console.log('SingleProductBuyNow: Sending AJAX request with data:', ajaxData);
            
            // Send AJAX request
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('SingleProductBuyNow: AJAX success response:', response);
                    
                    $button.removeClass('loading vd-processing nasa-waiting');
                    
                    if (response.success && response.data) {
                        if (response.data.action === 'redirect' && response.data.redirect_url) {
                            console.log('SingleProductBuyNow: Redirecting to:', response.data.redirect_url);
                            
                            // Force immediate redirect
                            window.location.href = response.data.redirect_url;
                        } else {
                            console.error('SingleProductBuyNow: Unexpected response action:', response.data.action);
                        }
                    } else {
                        // Show error message
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                        console.error('SingleProductBuyNow: Error response:', response);
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SingleProductBuyNow: AJAX error:', status, error, xhr);
                    
                    $button.removeClass('loading vd-processing nasa-waiting');
                    
                    var errorMessage = vd_home_ajax.error_text || 'An error occurred. Please try again.';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    alert(errorMessage);
                },
                complete: function() {
                    console.log('SingleProductBuyNow: AJAX request completed');
                    
                    // Clean up any NASA theme states
                    var $form = $button.closest('form.cart');
                    if ($form.find('input[name="nasa_buy_now"]').length) {
                        $form.find('input[name="nasa_buy_now"]').val('0');
                    }
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
            console.log('SingleProductBuyNow: Initializing on single product page');
            
            // Wait a bit for NASA theme to initialize
            setTimeout(function() {
                SingleProductBuyNow.init();
                
                // Debug: Check if button exists
                var $buyNowBtn = $('.nasa-buy-now, button.nasa-buy-now');
                if ($buyNowBtn.length) {
                    console.log('SingleProductBuyNow: Found NASA buy now button(s):', $buyNowBtn.length, $buyNowBtn);
                    
                    // Check for existing event handlers
                    var events = $._data($buyNowBtn[0], 'events');
                    console.log('SingleProductBuyNow: Existing jQuery events on button:', events);
                } else {
                    console.log('SingleProductBuyNow: NASA buy now button not found');
                }
            }, 500);
        }
    });
    
    // Also initialize on AJAX complete (for dynamic content)
    $(document).on('ajaxComplete', function(event, xhr, settings) {
        if ($('body').hasClass('single-product') && $('.nasa-buy-now').length) {
            console.log('SingleProductBuyNow: Reinitializing after AJAX');
            SingleProductBuyNow.unbindExistingHandlers();
            SingleProductBuyNow.bindBuyNowButton();
        }
    });
    
})(jQuery);