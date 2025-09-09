/**
 * Buy Now No-Scroll Enhancement
 * Prevents unwanted scrolling when Buy Now button is clicked
 * Adds visual feedback for button states
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // Only run on frontend
    if (typeof vd_home_ajax === 'undefined') {
        return;
    }
    
    var VDBuyNow = {
        // Track current action to prevent scroll only for Buy Now
        currentAction: null,
        savedScrollPosition: null,
        isProcessing: false,
        
        /**
         * Initialize the module
         */
        init: function() {
            this.preventDefaultBehavior();
            this.interceptScrollEvents();
            this.enhanceButtonFeedback();
            this.handleWooCommerceEvents();
            this.addStyles();
        },
        
        /**
         * Prevent default behavior on Buy Now buttons
         */
        preventDefaultBehavior: function() {
            var self = this;
            
            // Handle clicks on Buy Now buttons
            $(document).on('click', '.vd-buy-now-button', function(e) {
                var $btn = $(this);
                var action = $btn.attr('data-action');
                var href = $btn.attr('href');
                
                // Prevent navigation for # or empty hrefs
                if (href === '#' || href === '' || !href) {
                    e.preventDefault();
                }
                
                // Track if this is a buy-now action
                if (action === 'buy-now') {
                    self.currentAction = 'buy-now';
                    self.savedScrollPosition = window.scrollY;
                    
                    // Clear action tracking after 3 seconds
                    setTimeout(function() {
                        self.currentAction = null;
                        self.savedScrollPosition = null;
                    }, 3000);
                }
            });
            
            // Prevent quickview icons from scrolling in our sections
            $(document).on('click', '.vd-home-section .quick-view', function(e) {
                e.preventDefault();
                self.savedScrollPosition = window.scrollY;
            });
        },
        
        /**
         * Intercept scroll events during Buy Now action
         */
        interceptScrollEvents: function() {
            var self = this;
            
            // Store original scroll methods
            var originalScrollTo = window.scrollTo;
            var originalScrollBy = window.scrollBy;
            var originalScrollIntoView = Element.prototype.scrollIntoView;
            
            // Override scrollTo
            window.scrollTo = function() {
                // Only block if we're in a buy-now action
                if (self.currentAction === 'buy-now' && self.savedScrollPosition !== null) {
                    var args = Array.prototype.slice.call(arguments);
                    var targetY = 0;
                    
                    // Parse different argument formats
                    if (typeof args[0] === 'object' && args[0] !== null) {
                        targetY = args[0].top || 0;
                    } else if (typeof args[0] === 'number') {
                        targetY = args[1] || 0;
                    }
                    
                    // Block scroll to top
                    if (targetY === 0 || targetY < 100) {
                        return;
                    }
                }
                
                return originalScrollTo.apply(window, arguments);
            };
            
            // Override scrollIntoView
            Element.prototype.scrollIntoView = function() {
                // Block scrolling to notices during buy-now
                if (self.currentAction === 'buy-now') {
                    var element = this;
                    if (element.classList && (
                        element.classList.contains('woocommerce-notices-wrapper') ||
                        element.classList.contains('woocommerce-error') ||
                        element.classList.contains('woocommerce-message') ||
                        element.classList.contains('woocommerce-info')
                    )) {
                        return;
                    }
                }
                
                return originalScrollIntoView.apply(this, arguments);
            };
            
            // Monitor scroll position changes
            var lastScrollY = window.scrollY;
            setInterval(function() {
                if (self.currentAction === 'buy-now' && self.savedScrollPosition !== null) {
                    var currentScrollY = window.scrollY;
                    
                    // If scrolled to top (less than 100px), restore position
                    if (currentScrollY < 100 && lastScrollY >= 100) {
                        window.scrollTo({
                            top: self.savedScrollPosition,
                            left: 0,
                            behavior: 'instant'
                        });
                    }
                    
                    lastScrollY = currentScrollY;
                }
            }, 100);
        },
        
        /**
         * Enhance button feedback
         */
        enhanceButtonFeedback: function() {
            var self = this;
            
            // Override the original Buy Now handler to add feedback
            $(document).on('click', '.vd-buy-now-button', function(e) {
                var $btn = $(this);
                var action = $btn.attr('data-action');
                
                // Skip if already processing
                if (self.isProcessing || $btn.hasClass('vd-is-busy')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
                
                // For buy-now action, add visual feedback
                if (action === 'buy-now') {
                    self.isProcessing = true;
                    var originalText = $btn.text();
                    
                    // Add busy state
                    $btn.addClass('vd-is-busy')
                        .prop('disabled', true)
                        .html('<span class="vd-spinner"></span> Đang xử lý...');
                    
                    // Handle success after a delay (will be replaced by actual AJAX callback)
                    setTimeout(function() {
                        $btn.removeClass('vd-is-busy')
                            .addClass('vd-success')
                            .html('<span class="vd-checkmark">✓</span> Đã thêm')
                            .prop('disabled', false);
                        
                        // Restore original text after 1.5s
                        setTimeout(function() {
                            $btn.removeClass('vd-success')
                                .html(originalText);
                            self.isProcessing = false;
                        }, 1500);
                    }, 1000);
                }
                
                // For variable products without selection
                if (action === 'select-options' && $btn.hasClass('vd-buy-now-variable')) {
                    var $product = $btn.closest('.product');
                    var hasSelection = $product.find('input[name^="attribute_"]:checked').length > 0 ||
                                     $product.find('select[name^="attribute_"] option:selected[value!=""]').length > 0;
                    
                    if (!hasSelection && $btn.text().toLowerCase().includes('mua ngay')) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        
                        // Add shake effect
                        $btn.addClass('vd-shake');
                        setTimeout(function() {
                            $btn.removeClass('vd-shake');
                        }, 500);
                        
                        // Show tooltip
                        self.showTooltip($btn, 'Vui lòng chọn thuộc tính');
                        return false;
                    }
                }
            });
        },
        
        /**
         * Handle WooCommerce events
         */
        handleWooCommerceEvents: function() {
            var self = this;
            
            // Block scroll after add to cart
            $(document).on('added_to_cart', function(e, fragments, cart_hash, $btn) {
                if (self.currentAction === 'buy-now' && self.savedScrollPosition !== null) {
                    // Ensure we stay at saved position
                    setTimeout(function() {
                        window.scrollTo({
                            top: self.savedScrollPosition,
                            left: 0,
                            behavior: 'instant'
                        });
                    }, 50);
                }
            });
            
            // Handle fragments refresh
            $(document).on('wc_fragments_refreshed', function() {
                if (self.currentAction === 'buy-now' && self.savedScrollPosition !== null) {
                    window.scrollTo({
                        top: self.savedScrollPosition,
                        left: 0,
                        behavior: 'instant'
                    });
                }
            });
            
            // NASA theme specific
            $(document).on('nasa_after_add_to_cart', function() {
                if (self.currentAction === 'buy-now' && self.savedScrollPosition !== null) {
                    setTimeout(function() {
                        window.scrollTo({
                            top: self.savedScrollPosition,
                            left: 0,
                            behavior: 'instant'
                        });
                    }, 100);
                }
            });
        },
        
        /**
         * Show tooltip near button
         */
        showTooltip: function($btn, message) {
            var $tooltip = $('<div class="vd-tooltip">' + message + '</div>');
            
            $('body').append($tooltip);
            
            var btnOffset = $btn.offset();
            var btnWidth = $btn.outerWidth();
            
            $tooltip.css({
                top: btnOffset.top - $tooltip.outerHeight() - 10,
                left: btnOffset.left + (btnWidth / 2) - ($tooltip.outerWidth() / 2)
            }).addClass('vd-show');
            
            setTimeout(function() {
                $tooltip.removeClass('vd-show');
                setTimeout(function() {
                    $tooltip.remove();
                }, 300);
            }, 2000);
        },
        
        /**
         * Add required styles
         */
        addStyles: function() {
            var styles = `
                <style id="vd-buy-now-styles">
                    /* Busy state */
                    .vd-buy-now-button.vd-is-busy {
                        position: relative;
                        color: transparent !important;
                        pointer-events: none;
                        opacity: 0.7;
                    }
                    
                    .vd-buy-now-button.vd-is-busy .vd-spinner {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: 16px;
                        height: 16px;
                        border: 2px solid #fff;
                        border-top-color: transparent;
                        border-radius: 50%;
                        animation: vd-spin 0.8s linear infinite;
                    }
                    
                    @keyframes vd-spin {
                        to { transform: translate(-50%, -50%) rotate(360deg); }
                    }
                    
                    /* Success state */
                    .vd-buy-now-button.vd-success {
                        background-color: #4CAF50 !important;
                        color: #fff !important;
                    }
                    
                    .vd-checkmark {
                        margin-right: 5px;
                    }
                    
                    /* Shake animation */
                    .vd-shake {
                        animation: vd-shake 0.5s;
                    }
                    
                    @keyframes vd-shake {
                        0%, 100% { transform: translateX(0); }
                        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                        20%, 40%, 60%, 80% { transform: translateX(5px); }
                    }
                    
                    /* Tooltip */
                    .vd-tooltip {
                        position: absolute;
                        background: #333;
                        color: #fff;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-size: 13px;
                        white-space: nowrap;
                        z-index: 9999;
                        opacity: 0;
                        transform: translateY(5px);
                        transition: all 0.3s;
                        pointer-events: none;
                    }
                    
                    .vd-tooltip:after {
                        content: '';
                        position: absolute;
                        top: 100%;
                        left: 50%;
                        transform: translateX(-50%);
                        border: 5px solid transparent;
                        border-top-color: #333;
                    }
                    
                    .vd-tooltip.vd-show {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    
                    /* Ensure buttons in product cards stay consistent */
                    .vd-home-section .product .button {
                        transition: all 0.3s ease;
                    }
                </style>
            `;
            
            $('head').append(styles);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        VDBuyNow.init();
    });
    
    // Re-initialize after AJAX updates
    $(document).on('vidieu_products_filtered vidieu_products_page_loaded vidieu_items_loaded', function() {
        // Just ensure our event handlers are still active
        VDBuyNow.currentAction = null;
        VDBuyNow.savedScrollPosition = null;
    });
    
})(jQuery);