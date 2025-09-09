/**
 * Buy Now Simple Override
 * Ensures only 1 handler for simple products
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Wait a bit to ensure all other scripts have loaded
        setTimeout(function() {
            console.log('[BuyNowOverride] Starting handler cleanup...');
            
            // First, let our handlers initialize properly by waiting longer
            setTimeout(function() {
                // Remove non-namespaced handlers only
                $(document).off('click', '.vd-buy-now-button');
                
                // Remove old namespaced handlers from buy-now-no-scroll.js for simple products
                $(document).off('click.vd-buy-now', '.vd-buy-now-button.vd-buy-now-simple');
                
                // Check current handlers
                var events = $._data(document, 'events');
                if (events && events.click) {
                    var toRemove = [];
                    var currentHandlers = [];
                    
                    events.click.forEach(function(event, index) {
                        if (event.selector && event.selector.includes('.vd-buy-now-button')) {
                            currentHandlers.push({
                                index: index,
                                selector: event.selector,
                                namespace: event.namespace || 'none'
                            });
                            
                            // Remove handlers without proper namespace (except our desired ones)
                            if (!event.namespace || 
                                (event.namespace !== 'vdBuyNowSimple' && 
                                 event.namespace !== 'vdBuyNow' && 
                                 event.namespace !== 'vdSelectOptions')) {
                                toRemove.push(index);
                            }
                        }
                    });
                    
                    console.log('[BuyNowOverride] Found handlers before cleanup:', currentHandlers);
                    
                    // Remove unwanted handlers in reverse order
                    toRemove.reverse().forEach(function(index) {
                        console.log('[BuyNowOverride] Removing handler at index:', index);
                        events.click.splice(index, 1);
                    });
                }
                
                // Re-initialize our handler if it was removed
                if (window.VDBuyNowSimple && typeof window.VDBuyNowSimple.bindEvents === 'function') {
                    window.VDBuyNowSimple.bindEvents();
                    console.log('[BuyNowOverride] Re-initialized VDBuyNowSimple handlers');
                }
                
                // Final verification
                setTimeout(function() {
                    var events = $._data(document, 'events');
                    var finalHandlers = [];
                    if (events && events.click) {
                        events.click.forEach(function(e) {
                            if (e.selector && e.selector.includes('.vd-buy-now')) {
                                finalHandlers.push({
                                    selector: e.selector,
                                    namespace: e.namespace || 'none'
                                });
                            }
                        });
                    }
                    console.log('[BuyNowOverride] Final handlers:', finalHandlers);
                    console.log('[BuyNowOverride] Cleanup complete');
                }, 100);
            }, 200);
        }, 500);
        
        // Override fragment refresh to skip simple products
        var originalFragmentRefresh = $.fn.wc_fragment_refresh;
        if (originalFragmentRefresh) {
            $.fn.wc_fragment_refresh = function() {
                // Check if this is triggered by simple product
                var triggerElement = $(document.activeElement);
                if (triggerElement.hasClass('vd-buy-now-simple')) {
                    console.log('[BuyNowOverride] Skipping fragment refresh for simple product');
                    return this;
                }
                return originalFragmentRefresh.apply(this, arguments);
            };
        }
        
        // Remove fragment event handlers for simple products
        $(document).off('wc_fragments_refreshed.buyNowSimple');
        $(document).off('wc_fragments_loaded.buyNowSimple');
        
        // Monitor setTimeout calls after page load (but don't intercept all)
        setTimeout(function() {
            var setTimeoutCount = 0;
            var originalSetTimeout = window.setTimeout;
            
            // Only monitor for 10 seconds to avoid infinite logging
            var stopMonitoring = false;
            setTimeout(function() {
                stopMonitoring = true;
                console.log('[BuyNowOverride] setTimeout monitoring stopped at:', setTimeoutCount);
            }, 10000);
            
            window.setTimeout = function() {
                if (!stopMonitoring) {
                    setTimeoutCount++;
                    // Only log every 5th call to reduce noise
                    if (setTimeoutCount % 5 === 0) {
                        console.log('[BuyNowOverride] setTimeout called, total:', setTimeoutCount);
                    }
                }
                return originalSetTimeout.apply(window, arguments);
            };
        }, 2000);
    });
    
})(jQuery);