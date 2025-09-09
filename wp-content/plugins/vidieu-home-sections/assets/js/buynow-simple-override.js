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
            // Remove ALL other handlers for buy now buttons
            $(document).off('click', '.vd-buy-now-button');
            $(document).off('click', '.vd-buy-now-button.vd-buy-now-simple');
            $(document).off('click', '.vd-buy-now-button:not(.vd-buy-now-simple)');
            $(document).off('click.vd-buy-now', '.vd-buy-now-button.vd-buy-now-simple');
            
            // Remove namespaced handlers except our own
            var events = $._data(document, 'events');
            if (events && events.click) {
                var toRemove = [];
                events.click.forEach(function(event, index) {
                    if (event.selector && event.selector.includes('.vd-buy-now-button')) {
                        // Keep only vdBuyNowSimple for simple products
                        // and vdBuyNow for variable products
                        if (event.namespace !== 'vdBuyNowSimple' && event.namespace !== 'vdBuyNow') {
                            toRemove.push(index);
                        }
                    }
                });
                
                // Remove in reverse order to maintain indices
                toRemove.reverse().forEach(function(index) {
                    events.click.splice(index, 1);
                });
            }
            
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
            
            // Log final state
            console.log('[BuyNowOverride] Cleanup complete');
            
            // Check final handler count
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
            
            // Count setTimeout calls
            var setTimeoutCount = 0;
            var originalSetTimeout = window.setTimeout;
            window.setTimeout = function() {
                setTimeoutCount++;
                console.log('[BuyNowOverride] setTimeout called, total:', setTimeoutCount);
                return originalSetTimeout.apply(window, arguments);
            };
        }, 500);
    });
    
})(jQuery);