/**
 * QuickView Compatibility JavaScript
 * Prevents auto-scroll to top when selecting variations in QuickView
 * 
 * @package Vidieu_Home_Sections
 * @since 1.6.0
 */

(function($, window, document) {
    'use strict';

    // Prevent multiple initializations
    if (window.__vidieuQVInitialized) {
        return;
    }
    window.__vidieuQVInitialized = true;

    // Configuration
    const config = window.vidieuQVCompat || {
        debug: '0'
    };

    // State
    let isQuickViewOpen = false;
    let scrollPositionBeforeOpen = 0;

    // Debug logging (only when enabled)
    function log(message, data) {
        if (config.debug === '1' && window.console) {
            console.log('[Vidieu QV] ' + message, data || '');
        }
    }

    /**
     * Check if element is inside QuickView modal
     */
    function isInQuickView(element) {
        if (!element) return false;
        return !!(element.closest('.nasa-quick-view, .quick-view, .quickview, .nasa-quickview-popup, #nasa-quickview-popup, .mfp-content, .mfp-wrap'));
    }

    /**
     * Get current scroll position
     */
    function getScrollY() {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    /**
     * Lock scroll position
     */
    function lockScroll() {
        scrollPositionBeforeOpen = getScrollY();
        isQuickViewOpen = true;
        
        document.documentElement.classList.add('qv-open');
        document.body.classList.add('qv-open');
        
        // Disable scroll restoration
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        
        log('QuickView opened, scroll locked at', scrollPositionBeforeOpen);
    }

    /**
     * Unlock scroll position
     */
    function unlockScroll() {
        isQuickViewOpen = false;
        
        document.documentElement.classList.remove('qv-open');
        document.body.classList.remove('qv-open');
        
        // Re-enable scroll restoration
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'auto';
        }
        
        log('QuickView closed, scroll unlocked');
    }

    /**
     * Maintain scroll position
     */
    function maintainScrollPosition() {
        if (!isQuickViewOpen) return;
        
        const currentScroll = getScrollY();
        
        if (Math.abs(currentScroll - scrollPositionBeforeOpen) > 10) {
            log('Restoring scroll position from', currentScroll, 'to', scrollPositionBeforeOpen);
            
            window.scrollTo({
                top: scrollPositionBeforeOpen,
                left: 0,
                behavior: 'instant'
            });
        }
    }

    /**
     * Initialize all event handlers
     */
    function init() {
        log('Initializing QuickView scroll fix');

        // 1. Prevent anchor default behavior (using capture phase)
        document.addEventListener('click', function(e) {
            const anchor = e.target.closest('a');
            if (!anchor || !isInQuickView(anchor)) return;
            
            const href = anchor.getAttribute('href') || '';
            
            // Prevent scroll for problematic hrefs
            if (href === '#' || 
                href === '#!' || 
                href === '' || 
                href.indexOf('#') === 0 || 
                href === 'javascript:void(0)' || 
                href === 'javascript:;') {
                
                log('Preventing anchor scroll:', href);
                e.preventDefault();
                e.stopPropagation();
                anchor.blur();
            }
            
            // Special handling for reset variations
            if (anchor.classList.contains('reset_variations')) {
                log('Preventing reset variations scroll');
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        // 2. Monitor for QuickView open/close via DOM changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check for QuickView being added
                if (mutation.type === 'childList' && mutation.addedNodes.length) {
                    for (let node of mutation.addedNodes) {
                        if (node.nodeType === 1) { // Element node
                            const isQV = node.classList && (
                                node.classList.contains('nasa-quick-view') ||
                                node.classList.contains('quick-view') ||
                                node.classList.contains('mfp-wrap') ||
                                node.id === 'nasa-quickview-popup'
                            );
                            
                            const hasQV = node.querySelector && node.querySelector('.nasa-quick-view, .quick-view');
                            
                            if ((isQV || hasQV) && !isQuickViewOpen) {
                                lockScroll();
                            }
                        }
                    }
                }
                
                // Check for QuickView being removed
                if (mutation.type === 'childList' && mutation.removedNodes.length) {
                    for (let node of mutation.removedNodes) {
                        if (node.nodeType === 1) {
                            const isQV = node.classList && (
                                node.classList.contains('nasa-quick-view') ||
                                node.classList.contains('quick-view') ||
                                node.classList.contains('mfp-wrap')
                            );
                            
                            if (isQV && isQuickViewOpen) {
                                unlockScroll();
                            }
                        }
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // 3. Listen for theme-specific events
        $(document)
            .on('nasa_quick_view_open nasa_before_quickview quickview_open mfpOpen', function(e) {
                if (!isQuickViewOpen) {
                    lockScroll();
                }
            })
            .on('nasa_quick_view_close nasa_after_quickview quickview_close mfpClose', function(e) {
                if (isQuickViewOpen) {
                    unlockScroll();
                }
            });

        // 4. Handle variation changes
        document.addEventListener('change', function(e) {
            if (!isQuickViewOpen) return;
            if (!isInQuickView(e.target)) return;
            
            const form = e.target.closest('.variations_form');
            if (!form) return;
            
            log('Variation changed, maintaining scroll position');
            
            // Use both immediate and delayed checks
            setTimeout(maintainScrollPosition, 0);
            setTimeout(maintainScrollPosition, 50);
        }, true);

        // 5. WooCommerce variation events
        $(document).on('found_variation reset_data update_variation_values', '.variations_form', function(e) {
            if (!isQuickViewOpen) return;
            if (!isInQuickView(e.target)) return;
            
            log('WooCommerce variation event:', e.type);
            
            setTimeout(maintainScrollPosition, 0);
            setTimeout(maintainScrollPosition, 50);
        });

        // 6. Prevent hashchange scrolling
        window.addEventListener('hashchange', function(e) {
            if (!isQuickViewOpen) return;
            
            log('Preventing hashchange scroll');
            e.stopImmediatePropagation();
            
            // Remove hash without scrolling
            if (window.location.hash) {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        }, true);

        // 7. Focus management
        document.addEventListener('focusin', function(e) {
            if (!isQuickViewOpen) return;
            if (!isInQuickView(e.target)) return;
            
            // Prevent scroll on focus
            setTimeout(maintainScrollPosition, 0);
        }, true);

        // 8. Handle backdrop clicks (close QuickView)
        $(document).on('click', '.mfp-bg, .mfp-close', function() {
            if (isQuickViewOpen) {
                unlockScroll();
            }
        });
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        init();
    });

})(jQuery, window, document);