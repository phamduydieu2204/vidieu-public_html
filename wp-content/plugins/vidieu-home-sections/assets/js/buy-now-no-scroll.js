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
            
            // Handle clicks on Buy Now buttons - DISABLED for Simple Products
            // Simple products now use standardized handler
            // REMOVED: Duplicate handler - already handled in vidieu-home.js with namespace .vdBuyNow
            
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
            
            // Monitor scroll position changes - DISABLED for Simple Products
            // Simple products use direct redirect, no need for scroll monitoring
            var lastScrollY = window.scrollY;
            var scrollMonitor = setInterval(function() {
                // Only monitor for variable/complex products
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
            
            // Store interval reference for cleanup
            self.scrollMonitorInterval = scrollMonitor;
        },
        
        /**
         * Enhance button feedback - Simplified for variable products only
         */
        enhanceButtonFeedback: function() {
            // All button feedback now handled by respective specialized handlers
            // This function is kept for potential future enhancements
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
         * Cleanup and destroy
         */
        destroy: function() {
            // Clear scroll monitor interval
            if (this.scrollMonitorInterval) {
                clearInterval(this.scrollMonitorInterval);
                this.scrollMonitorInterval = null;
            }
            
            // Reset state
            this.currentAction = null;
            this.savedScrollPosition = null;
            this.isProcessing = false;
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