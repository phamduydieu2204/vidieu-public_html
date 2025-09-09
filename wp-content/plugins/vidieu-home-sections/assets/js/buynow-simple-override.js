/**
 * Buy Now Simple Override
 * Ensures only proper namespaced handlers for buy now buttons
 * 
 * @package VidieuHomeSections
 * @version 1.1.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Wait for other scripts to initialize
        setTimeout(function() {
            setTimeout(function() {
                // Remove non-namespaced handlers
                $(document).off('click', '.vd-buy-now-button');
                
                // Remove old handlers from buy-now-no-scroll.js for simple products
                $(document).off('click.vd-buy-now', '.vd-buy-now-button.vd-buy-now-simple');
                
                // Clean up improper handlers
                var events = $._data(document, 'events');
                if (events && events.click) {
                    var toRemove = [];
                    
                    events.click.forEach(function(event, index) {
                        if (event.selector && event.selector.includes('.vd-buy-now-button')) {
                            // Keep only properly namespaced handlers
                            if (!event.namespace || 
                                (event.namespace !== 'vdBuyNowSimple' && 
                                 event.namespace !== 'vdBuyNow' && 
                                 event.namespace !== 'vdSelectOptions')) {
                                toRemove.push(index);
                            }
                        }
                    });
                    
                    // Remove unwanted handlers in reverse order
                    toRemove.reverse().forEach(function(index) {
                        events.click.splice(index, 1);
                    });
                }
                
                // Re-initialize if needed
                if (window.VDBuyNowSimple && typeof window.VDBuyNowSimple.bindEvents === 'function') {
                    window.VDBuyNowSimple.bindEvents();
                }
            }, 200);
        }, 500);
        
        // Override fragment refresh to skip simple products
        var originalFragmentRefresh = $.fn.wc_fragment_refresh;
        if (originalFragmentRefresh) {
            $.fn.wc_fragment_refresh = function() {
                var triggerElement = $(document.activeElement);
                if (triggerElement.hasClass('vd-buy-now-simple')) {
                    return this; // Skip fragment refresh for simple products
                }
                return originalFragmentRefresh.apply(this, arguments);
            };
        }
        
        // Remove fragment event handlers for simple products
        $(document).off('wc_fragments_refreshed.buyNowSimple');
        $(document).off('wc_fragments_loaded.buyNowSimple');
    });
    
})(jQuery);