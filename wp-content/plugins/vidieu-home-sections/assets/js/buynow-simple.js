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
            namespace: '.vdBuyNowSimple', // Changed to unique namespace
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
            this.cleanupOldHandlers();
            this.bindEvents();
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
            
            // Always off before on - be more specific
            $(document).off('click.vdBuyNowSimple', '.vd-buy-now-button.vd-buy-now-simple');
            
            // Single delegated handler - NO DEBOUNCE for immediate response
            $(document).on('click.vdBuyNowSimple', '.vd-buy-now-button.vd-buy-now-simple', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Check if already processing inline
                var $button = $(this);
                if ($button.attr('data-processing') === 'true') {
                    return false;
                }
                
                self.handleClick($button);
            });
        },
        
        /**
         * Handle button click
         */
        handleClick: function($button) {
            // Set loading state IMMEDIATELY
            if (!$button.attr(this.config.originalTextAttr)) {
                $button.attr(this.config.originalTextAttr, $button.text());
            }
            this.setButtonState($button, 'loading');
            
            // Get button data
            var productId = $button.data('product-id');
            var nonce = (window.vd_home_ajax ? vd_home_ajax.nonce : '');
            var quantity = parseInt($button.data('qty') || 1);
            var redirect = $button.data('redirect') || 'checkout';
            
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
                    self.handleSuccess(response, $button, data);
                },
                error: function(xhr, status, error) {
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
                    // No redirect - reset immediately
                    this.setButtonState($button, 'idle');
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
            // Set error state briefly then reset
            this.setButtonState($button, 'error');
            
            // Small delay for UX only when not redirecting
            if (this.timeouts.length === 0) {
                var timeout = setTimeout(() => {
                    this.setButtonState($button, 'idle');
                }, 300);
                this.timeouts.push(timeout);
            }
        },
        
        /**
         * Set button state
         */
        setButtonState: function($button, state) {
            
            var originalText = $button.attr(this.config.originalTextAttr) || $button.text();
            var classes = this.config.classes;
            
            switch (state) {
                case 'loading':
                    $button
                        .removeClass([classes.loading, classes.success, classes.error].join(' '))
                        .attr({
                            [this.config.processingAttr]: 'true',
                            'aria-busy': 'true',
                            'disabled': 'disabled'
                        })
                        .prop('disabled', true)
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
        VDBuyNowSimple.init();
    });
    
    // Re-initialize after AJAX loads
    $(document).on('vidieu_products_filtered vidieu_products_page_loaded nasa_after_load', function() {
        VDBuyNowSimple.bindEvents();
    });
    
    // Expose for debugging
    window.VDBuyNowSimple = VDBuyNowSimple;
    
})(jQuery);