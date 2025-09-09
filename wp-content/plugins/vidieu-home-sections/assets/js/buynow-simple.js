/**
 * Buy Now Simple Product Handler
 * Standardized handler for simple product Buy Now buttons
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 * @since 2025-09-09
 */

(function($) {
    'use strict';
    
    // Check jQuery dependency
    if (typeof $ === 'undefined') {
        return;
    }
    
    /**
     * Buy Now Simple Handler Module
     */
    var VDBuyNowSimple = {
        
        // Store timeout references for cleanup
        timeouts: [],
        
        // Configuration
        config: {
            debounceDelay: 300,
            successDuration: 1500,
            selector: '.vd-buy-now-button.vd-buy-now-simple',
            namespace: '.vdBuyNow',
            processingAttr: 'data-processing',
            originalTextAttr: 'data-original-text',
            
            // Labels
            labels: {
                processing: 'Đang xử lý...',
                success: 'Đã thêm',
                error: 'Có lỗi',
                defaultError: 'Có lỗi xảy ra. Vui lòng thử lại.'
            },
            
            // Classes
            classes: {
                loading: 'is-loading',
                success: 'is-success',
                error: 'is-error',
                disabled: 'disabled'
            }
        },
        
        /**
         * Initialize the module
         */
        init: function() {
            console.log('[VDBuyNowSimple] Initializing...');
            this.cleanupOldHandlers();
            this.bindEvents();
            console.log('[VDBuyNowSimple] Initialization complete');
        },
        
        /**
         * Clean up any old handlers
         */
        cleanupOldHandlers: function() {
            // Remove any existing handlers without namespace
            $(document).off('click', this.config.selector);
            
            // Remove handlers from buy-now-no-scroll.js if any remain
            $(document).off('click.vd-buy-now', this.config.selector);
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            console.log('[VDBuyNowSimple] Binding events...');
            console.log('[VDBuyNowSimple] Selector:', this.config.selector);
            console.log('[VDBuyNowSimple] Found buttons:', $(this.config.selector).length);
            
            // Always off before on
            $(document).off('click' + this.config.namespace, this.config.selector);
            
            // Single delegated handler with namespace and debounce
            var debouncedHandler = this.debounce(function(e) {
                console.log('[VDBuyNowSimple] Button clicked!');
                console.log('[VDBuyNowSimple] Button data:', {
                    productId: $(this).data('product-id'),
                    productType: $(this).data('product-type'),
                    action: $(this).data('action')
                });
                e.preventDefault();
                e.stopImmediatePropagation();
                self.handleClick($(this));
            }, this.config.debounceDelay);
            
            console.log('[VDBuyNowSimple] Debounced handler:', typeof debouncedHandler);
            
            $(document).on('click' + this.config.namespace, this.config.selector, debouncedHandler);
            
            // Verify event was bound
            var events = $._data(document, 'events');
            var boundCorrectly = false;
            if (events && events.click) {
                events.click.forEach(function(e) {
                    if (e.namespace === 'vdBuyNow' && e.selector === '.vd-buy-now-button.vd-buy-now-simple') {
                        boundCorrectly = true;
                    }
                });
            }
            console.log('[VDBuyNowSimple] Event bound correctly:', boundCorrectly);
            console.log('[VDBuyNowSimple] Events bound successfully');
        },
        
        /**
         * Handle button click
         */
        handleClick: function($button) {
            console.log('[VDBuyNowSimple] handleClick called');
            
            // Check if already processing
            if ($button.attr(this.config.processingAttr) === 'true') {
                console.log('[VDBuyNowSimple] Already processing, skipping...');
                return false;
            }
            
            // Get button data
            var productId = $button.data('product-id');
            var nonce = $button.data('nonce') || (window.vd_home_ajax ? vd_home_ajax.nonce : '');
            var quantity = parseInt($button.data('qty') || 1);
            var redirect = $button.data('redirect') || 'checkout';
            
            console.log('[VDBuyNowSimple] Button data extracted:', {
                productId: productId,
                nonce: nonce,
                quantity: quantity,
                redirect: redirect
            });
            
            // Save original text
            if (!$button.attr(this.config.originalTextAttr)) {
                $button.attr(this.config.originalTextAttr, $button.text());
            }
            
            // Set processing state
            this.setButtonState($button, 'loading');
            
            // Make AJAX request
            this.processBuyNow($button, {
                product_id: productId,
                quantity: quantity,
                nonce: nonce,
                redirect: redirect
            });
        },
        
        /**
         * Process Buy Now request
         */
        processBuyNow: function($button, data) {
            var self = this;
            
            var ajaxUrl = (window.vd_home_ajax ? vd_home_ajax.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php'));
            
            console.log('[VDBuyNowSimple] Making AJAX request to:', ajaxUrl);
            console.log('[VDBuyNowSimple] AJAX data:', {
                action: 'vidieu_buy_now',
                nonce: data.nonce,
                product_id: data.product_id,
                quantity: data.quantity,
                action_type: 'buy-now'
            });
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vidieu_buy_now',
                    nonce: data.nonce,
                    product_id: data.product_id,
                    quantity: data.quantity,
                    action_type: 'buy-now'
                },
                timeout: 30000,
                success: function(response) {
                    console.log('[VDBuyNowSimple] AJAX success:', response);
                    self.handleSuccess(response, $button, data);
                },
                error: function(xhr, status, error) {
                    console.error('[VDBuyNowSimple] AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    self.handleError(xhr, status, error, $button);
                }
            });
        },
        
        /**
         * Handle successful response
         */
        handleSuccess: function(response, $button, data) {
            if (response.success && response.data) {
                // Show success state briefly
                this.setButtonState($button, 'success');
                
                // Handle redirect
                if (response.data.redirect_url && data.redirect !== 'none') {
                    // Redirect immediately - no delay
                    window.location.href = response.data.redirect_url;
                } else {
                    // No redirect - reset button after success duration
                    var timeout = setTimeout(() => {
                        this.setButtonState($button, 'idle');
                    }, this.config.successDuration);
                    this.timeouts.push(timeout);
                }
            } else {
                // Server returned error
                var message = response.data && response.data.message || this.config.labels.defaultError;
                this.showError(message, $button);
            }
        },
        
        /**
         * Handle error response
         */
        handleError: function(xhr, status, error, $button) {
            var message = this.config.labels.defaultError;
            
            if (status === 'timeout') {
                message = 'Yêu cầu quá thời gian chờ. Vui lòng thử lại.';
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data.message || message;
            }
            
            this.showError(message, $button);
        },
        
        /**
         * Show error message
         */
        showError: function(message, $button) {
            // Set error state
            this.setButtonState($button, 'error');
            
            // Show toast notification
            this.showToast(message, 'error');
            
            // Reset button after delay
            var timeout = setTimeout(() => {
                this.setButtonState($button, 'idle');
            }, this.config.successDuration);
            this.timeouts.push(timeout);
        },
        
        /**
         * Set button state
         */
        setButtonState: function($button, state) {
            console.log('[VDBuyNowSimple] Setting button state:', state);
            
            var originalText = $button.attr(this.config.originalTextAttr) || $button.text();
            var classes = this.config.classes;
            
            // Batch DOM updates with requestAnimationFrame
            requestAnimationFrame(() => {
                // Remove all state classes
                $button.removeClass([classes.loading, classes.success, classes.error].join(' '));
            });
            
            switch (state) {
                case 'loading':
                    $button
                        .attr({
                            [this.config.processingAttr]: 'true',
                            'aria-busy': 'true',
                            'disabled': true
                        })
                        .addClass(classes.loading)
                        .html('<span class="spinner" aria-hidden="true"></span> ' + this.config.labels.processing);
                    break;
                    
                case 'success':
                    $button
                        .attr('aria-busy', 'false')
                        .removeClass(classes.loading)
                        .addClass(classes.success)
                        .html('<span class="checkmark" aria-hidden="true">✓</span> ' + this.config.labels.success);
                    break;
                    
                case 'error':
                    $button
                        .attr('aria-busy', 'false')
                        .removeClass(classes.loading)
                        .addClass(classes.error)
                        .html('<span class="error-icon" aria-hidden="true">✕</span> ' + this.config.labels.error);
                    break;
                    
                default: // idle
                    $button
                        .attr({
                            [this.config.processingAttr]: 'false',
                            'aria-busy': 'false'
                        })
                        .prop('disabled', false)
                        .removeAttr('disabled')
                        .html(originalText);
            }
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            // Remove any existing toasts
            $('.vd-toast').remove();
            
            // Create toast element
            var $toast = $('<div>', {
                'class': 'vd-toast vd-toast-' + type,
                'role': 'alert',
                'aria-live': 'polite',
                'text': message
            });
            
            // Add to body
            $('body').append($toast);
            
            // Animate in with requestAnimationFrame for performance
            requestAnimationFrame(() => {
                $toast.addClass('show');
            });
            
            // Remove after delay
            var timeout1 = setTimeout(() => {
                $toast.removeClass('show');
                var timeout2 = setTimeout(() => {
                    $toast.remove();
                }, 300);
                this.timeouts.push(timeout2);
            }, 3000);
            this.timeouts.push(timeout1);
        },
        
        /**
         * Debounce utility
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Destroy and cleanup
         */
        destroy: function() {
            // Remove event handlers
            $(document).off('click' + this.config.namespace);
            
            // Remove any toasts
            $('.vd-toast').remove();
            
            // Clear any pending timeouts
            this.timeouts.forEach(clearTimeout);
            this.timeouts = [];
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        console.log('[VDBuyNowSimple] Document ready, jQuery version:', $.fn.jquery);
        console.log('[VDBuyNowSimple] vd_home_ajax available:', typeof window.vd_home_ajax !== 'undefined');
        if (window.vd_home_ajax) {
            console.log('[VDBuyNowSimple] vd_home_ajax data:', window.vd_home_ajax);
        }
        VDBuyNowSimple.init();
    });
    
    // Re-initialize after AJAX loads - but only bind once
    $(document).on('vidieu_products_filtered vidieu_products_page_loaded nasa_after_load', function() {
        console.log('[VDBuyNowSimple] AJAX content loaded, re-binding events...');
        // Use requestAnimationFrame to avoid blocking
        requestAnimationFrame(() => {
            VDBuyNowSimple.bindEvents();
        });
    });
    
    // Skip fragment refresh for Buy Now Simple (they redirect)
    // No need to listen to wc_fragments_refreshed
    
    // Expose for debugging
    window.VDBuyNowSimple = VDBuyNowSimple;
    
})(jQuery);