/**
 * Buy Now Scroll Debug Script
 * Temporary script to diagnose scroll issues
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    console.log('[VD Debug] Scroll debug script loaded');
    
    // Only run on frontend, not admin
    if (typeof vd_home_ajax === 'undefined') {
        return;
    }
    
    // Store original functions
    var originalScrollTo = window.scrollTo;
    var originalScrollBy = window.scrollBy;
    var originalScrollIntoView = Element.prototype.scrollIntoView;
    var originalFocus = HTMLElement.prototype.focus;
    var originalLocationHash = Object.getOwnPropertyDescriptor(window.location, 'hash');
    
    // Track scroll events
    var scrollEvents = [];
    var maxEvents = 20;
    
    function logScrollEvent(type, details, stack) {
        var event = {
            type: type,
            details: details,
            timestamp: new Date().toISOString(),
            stack: stack || (new Error()).stack
        };
        
        scrollEvents.push(event);
        if (scrollEvents.length > maxEvents) {
            scrollEvents.shift();
        }
        
        console.log('[VD Debug] Scroll event:', type, details);
        console.trace();
    }
    
    // Override window.scrollTo
    window.scrollTo = function() {
        var args = Array.prototype.slice.call(arguments);
        logScrollEvent('window.scrollTo', {
            args: args,
            currentY: window.scrollY
        });
        
        // Check if this is from WooCommerce/theme trying to scroll to top
        var stack = (new Error()).stack;
        if (stack.includes('woocommerce') || stack.includes('nasa') || stack.includes('elessi')) {
            console.log('[VD Debug] Blocked scroll from:', stack.split('\n')[2]);
            // Uncomment to block: return;
        }
        
        return originalScrollTo.apply(window, arguments);
    };
    
    // Override window.scrollBy
    window.scrollBy = function() {
        var args = Array.prototype.slice.call(arguments);
        logScrollEvent('window.scrollBy', {
            args: args,
            currentY: window.scrollY
        });
        return originalScrollBy.apply(window, arguments);
    };
    
    // Override Element.scrollIntoView
    Element.prototype.scrollIntoView = function() {
        var element = this;
        logScrollEvent('scrollIntoView', {
            element: element.tagName + (element.id ? '#' + element.id : '') + (element.className ? '.' + element.className.split(' ')[0] : ''),
            currentY: window.scrollY
        });
        
        // Check if scrolling to notices or messages
        if (element.classList && (element.classList.contains('woocommerce-notices-wrapper') || 
            element.classList.contains('woocommerce-error') ||
            element.classList.contains('woocommerce-message'))) {
            console.log('[VD Debug] Blocked scroll to WooCommerce notices');
            // Uncomment to block: return;
        }
        
        return originalScrollIntoView.apply(element, arguments);
    };
    
    // Override focus to catch focus-related scrolls
    HTMLElement.prototype.focus = function() {
        var element = this;
        var preventScroll = false;
        
        // Check if focus options prevent scroll
        if (arguments[0] && typeof arguments[0] === 'object' && arguments[0].preventScroll) {
            preventScroll = true;
        }
        
        if (!preventScroll) {
            logScrollEvent('element.focus', {
                element: element.tagName + (element.id ? '#' + element.id : ''),
                currentY: window.scrollY
            });
        }
        
        return originalFocus.apply(element, arguments);
    };
    
    // Monitor hash changes
    if (originalLocationHash && originalLocationHash.set) {
        Object.defineProperty(window.location, 'hash', {
            get: originalLocationHash.get,
            set: function(hash) {
                logScrollEvent('location.hash', {
                    oldHash: window.location.hash,
                    newHash: hash,
                    currentY: window.scrollY
                });
                return originalLocationHash.set.call(window.location, hash);
            }
        });
    }
    
    // Monitor Buy Now button clicks
    $(document).on('click', '.vd-buy-now-button', function(e) {
        var $btn = $(this);
        var action = $btn.attr('data-action');
        
        console.log('[VD Debug] Buy Now clicked:', {
            action: action,
            href: $btn.attr('href'),
            productId: $btn.attr('data-product-id'),
            scrollY: window.scrollY
        });
        
        // Store initial scroll position
        window.vdDebugInitialScroll = window.scrollY;
        
        // Monitor for scroll changes after click
        var scrollCheckInterval = setInterval(function() {
            if (window.scrollY !== window.vdDebugInitialScroll) {
                console.log('[VD Debug] Scroll changed after Buy Now click:', {
                    from: window.vdDebugInitialScroll,
                    to: window.scrollY,
                    diff: window.scrollY - window.vdDebugInitialScroll
                });
                clearInterval(scrollCheckInterval);
            }
        }, 100);
        
        // Stop monitoring after 3 seconds
        setTimeout(function() {
            clearInterval(scrollCheckInterval);
        }, 3000);
    });
    
    // Monitor WooCommerce AJAX events
    $(document).on('adding_to_cart', function(e, $btn, data) {
        console.log('[VD Debug] WooCommerce adding_to_cart event', {
            scrollY: window.scrollY
        });
    });
    
    $(document).on('added_to_cart', function(e, fragments, cart_hash, $btn) {
        console.log('[VD Debug] WooCommerce added_to_cart event', {
            scrollY: window.scrollY,
            fragments: Object.keys(fragments || {})
        });
    });
    
    $(document).on('wc_fragments_refreshed', function() {
        console.log('[VD Debug] WooCommerce fragments refreshed', {
            scrollY: window.scrollY
        });
    });
    
    // Monitor NASA theme events
    $(document).on('nasa_after_add_to_cart', function() {
        console.log('[VD Debug] NASA after_add_to_cart event', {
            scrollY: window.scrollY
        });
    });
    
    // Add debug button to show collected events
    $(document).ready(function() {
        $('body').append(`
            <div id="vd-debug-panel" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999; background: #333; color: #fff; padding: 10px; border-radius: 5px; font-size: 12px; display: none;">
                <div style="margin-bottom: 10px; font-weight: bold;">Scroll Debug Panel</div>
                <div id="vd-debug-events" style="max-height: 300px; overflow-y: auto;"></div>
                <button id="vd-debug-clear" style="margin-top: 10px;">Clear</button>
                <button id="vd-debug-close" style="margin-top: 10px; margin-left: 5px;">Close</button>
            </div>
            <button id="vd-debug-toggle" style="position: fixed; bottom: 20px; right: 20px; z-index: 99998; background: #333; color: #fff; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                Debug
            </button>
        `);
        
        $('#vd-debug-toggle').on('click', function() {
            $('#vd-debug-panel').toggle();
            $(this).hide();
            updateDebugPanel();
        });
        
        $('#vd-debug-close').on('click', function() {
            $('#vd-debug-panel').hide();
            $('#vd-debug-toggle').show();
        });
        
        $('#vd-debug-clear').on('click', function() {
            scrollEvents = [];
            updateDebugPanel();
        });
    });
    
    function updateDebugPanel() {
        var html = '';
        scrollEvents.forEach(function(event) {
            html += '<div style="margin-bottom: 10px; padding: 5px; background: #444; border-radius: 3px;">';
            html += '<strong>' + event.type + '</strong><br>';
            html += '<small>' + event.timestamp + '</small><br>';
            html += '<pre style="margin: 5px 0; font-size: 11px;">' + JSON.stringify(event.details, null, 2) + '</pre>';
            html += '</div>';
        });
        $('#vd-debug-events').html(html || '<em>No events captured</em>');
    }
    
})(jQuery);