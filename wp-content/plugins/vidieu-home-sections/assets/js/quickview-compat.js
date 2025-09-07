/**
 * QuickView Compatibility JavaScript
 * Prevents auto-scroll to top when selecting variations in QuickView
 * 
 * @package Vidieu_Home_Sections
 * @since 1.6.0
 */

(function($, window, document) {
    'use strict';

    // Configuration and state
    const config = window.vidieuQVCompat || {
        debug: '0',
        isHome: 0,
        isShop: 0,
        isMobile: 0
    };

    // State tracking
    let isQuickViewOpen = false;
    let scrollPositionBeforeOpen = 0;
    let initialized = false;

    // Debug logging (only when enabled)
    function log(message, data) {
        if (config.debug === '1' && window.console && window.console.log) {
            console.log('[QV Compat] ' + message, data || '');
        }
    }

    /**
     * Get current scroll position
     */
    function getCurrentScrollY() {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    /**
     * Prevent default anchor behavior
     */
    function preventAnchorScroll(e) {
        const target = e.target;
        const anchor = target.closest('a');
        
        if (!anchor) return;
        
        // Check if within QuickView modal
        const inQuickView = anchor.closest('.nasa-quick-view, .quick-view, .quickview, .nasa-quickview-popup, #nasa-quickview-popup, .mfp-content');
        if (!inQuickView) return;
        
        const href = anchor.getAttribute('href') || '';
        
        // Prevent scroll for hash links, empty hrefs, javascript:void, etc
        if (href === '#' || 
            href === '#!' || 
            href === '' || 
            href.indexOf('#') === 0 ||
            href === 'javascript:void(0)' || 
            href === 'javascript:;') {
            
            log('Preventing anchor scroll:', href);
            e.preventDefault();
            e.stopPropagation();
            
            // Remove focus to prevent any focus-related scrolling
            anchor.blur();
            
            return false;
        }
    }

    /**
     * Handle reset variations link
     */
    function handleResetVariations(e) {
        const resetLink = e.target.closest('.reset_variations');
        if (!resetLink) return;
        
        // Check if within QuickView
        const inQuickView = resetLink.closest('.nasa-quick-view, .quick-view, .quickview, .nasa-quickview-popup, #nasa-quickview-popup, .mfp-content');
        if (!inQuickView) return;
        
        log('Preventing reset variations scroll');
        e.preventDefault();
        e.stopPropagation();
        
        // Let WooCommerce handle the actual reset via its own handler
        // We just prevent the default anchor behavior
    }

    /**
     * Maintain scroll position after variation change
     */
    function maintainScrollPosition() {
        if (!isQuickViewOpen) return;
        
        const currentScroll = getCurrentScrollY();
        
        // Use requestAnimationFrame for smoother correction
        requestAnimationFrame(function() {
            const afterScroll = getCurrentScrollY();
            
            // If scroll position changed significantly, restore it
            if (Math.abs(afterScroll - scrollPositionBeforeOpen) > 10) {
                log('Restoring scroll position from', afterScroll, 'to', scrollPositionBeforeOpen);
                window.scrollTo({
                    top: scrollPositionBeforeOpen,
                    left: 0,
                    behavior: 'instant'
                });
            }
        });
    }

    /**
     * Handle variation form changes
     */
    function handleVariationChange(e) {
        const target = e.target;
        
        // Check if change is within QuickView
        const form = target.closest('.variations_form');
        if (!form) return;
        
        const inQuickView = form.closest('.nasa-quick-view, .quick-view, .quickview, .nasa-quickview-popup, #nasa-quickview-popup, .mfp-content');
        if (!inQuickView) return;
        
        log('Variation changed, maintaining position');
        
        // Maintain position after DOM updates
        setTimeout(maintainScrollPosition, 0);
        setTimeout(maintainScrollPosition, 50); // Double-check after animations
    }

    /**
     * Lock body scroll when QuickView opens
     */
    function lockBodyScroll() {
        scrollPositionBeforeOpen = getCurrentScrollY();
        isQuickViewOpen = true;
        
        // Add class to HTML element
        document.documentElement.classList.add('qv-open');
        document.body.classList.add('qv-open');
        
        // Set scroll restoration to manual
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        
        log('QuickView opened, scroll locked at', scrollPositionBeforeOpen);
    }

    /**
     * Unlock body scroll when QuickView closes
     */
    function unlockBodyScroll() {
        isQuickViewOpen = false;
        
        // Remove class from HTML element
        document.documentElement.classList.remove('qv-open');
        document.body.classList.remove('qv-open');
        
        // Restore scroll restoration
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'auto';
        }
        
        log('QuickView closed, scroll unlocked');
    }

    /**
     * Prevent hashchange scrolling during QuickView
     */
    function preventHashScroll(e) {
        if (!isQuickViewOpen) return;
        
        log('Preventing hashchange scroll');
        e.stopImmediatePropagation();
        
        // Remove hash without scrolling
        if (window.location.hash) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    }

    /**
     * Monitor QuickView open/close
     */
    function monitorQuickView() {
        // NASA Theme QuickView events
        $(document).on('nasa_quick_view_open nasa_before_quickview quickview_open', function() {
            lockBodyScroll();
        });
        
        $(document).on('nasa_quick_view_close nasa_after_quickview quickview_close', function() {
            unlockBodyScroll();
        });
        
        // MagnificPopup events (often used for QuickView)
        $(document).on('mfpOpen', function(e, mfp) {
            if (mfp && mfp.content && mfp.content.hasClass('quickview')) {
                lockBodyScroll();
            }
        });
        
        $(document).on('mfpClose', function(e, mfp) {
            if (isQuickViewOpen) {
                unlockBodyScroll();
            }
        });
        
        // Mutation observer as fallback
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check for QuickView modal appearance
                const quickviewAdded = Array.from(mutation.addedNodes).some(node => {
                    if (node.nodeType === 1) { // Element node
                        return node.classList && (
                            node.classList.contains('nasa-quick-view') ||
                            node.classList.contains('quickview') ||
                            node.id === 'nasa-quickview-popup'
                        );
                    }
                    return false;
                });
                
                if (quickviewAdded && !isQuickViewOpen) {
                    lockBodyScroll();
                }
                
                // Check for removal
                const quickviewRemoved = Array.from(mutation.removedNodes).some(node => {
                    if (node.nodeType === 1) {
                        return node.classList && (
                            node.classList.contains('nasa-quick-view') ||
                            node.classList.contains('quickview') ||
                            node.id === 'nasa-quickview-popup'
                        );
                    }
                    return false;
                });
                
                if (quickviewRemoved && isQuickViewOpen) {
                    unlockBodyScroll();
                }
            });
        });
        
        // Start observing body for QuickView modals
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Initialize all event handlers
     */
    function init() {
        if (initialized) return;
        initialized = true;
        
        log('Initializing QuickView compatibility');
        
        // Prevent anchor scrolling (using capture phase)
        document.addEventListener('click', preventAnchorScroll, true);
        document.addEventListener('click', handleResetVariations, true);
        
        // Handle variation changes
        document.addEventListener('change', handleVariationChange, true);
        
        // Handle select/swatch clicks
        $(document).on('click', '.variations_form .swatch, .variations_form .select-option', function(e) {
            if (isQuickViewOpen) {
                log('Swatch/option clicked, maintaining position');
                setTimeout(maintainScrollPosition, 0);
            }
        });
        
        // Prevent hashchange scrolling
        window.addEventListener('hashchange', preventHashScroll, true);
        
        // Monitor QuickView state
        monitorQuickView();
        
        // Handle focus changes that might cause scrolling
        document.addEventListener('focusin', function(e) {
            if (!isQuickViewOpen) return;
            
            const target = e.target;
            const inQuickView = target.closest('.nasa-quick-view, .quick-view, .quickview, .nasa-quickview-popup, #nasa-quickview-popup, .mfp-content');
            
            if (inQuickView) {
                // Prevent focus from scrolling the main page
                setTimeout(maintainScrollPosition, 0);
            }
        }, true);
        
        // Additional WooCommerce variation hooks
        $(document).on('found_variation', '.variations_form', function() {
            if (isQuickViewOpen) {
                log('Variation found, maintaining position');
                setTimeout(maintainScrollPosition, 0);
            }
        });
        
        $(document).on('reset_data', '.variations_form', function() {
            if (isQuickViewOpen) {
                log('Variation reset, maintaining position');
                setTimeout(maintainScrollPosition, 0);
            }
        });
    }

    // Initialize when DOM is ready
    $(document).ready(init);
    
    // Also initialize on window load (for late-loaded content)
    $(window).on('load', function() {
        if (!initialized) init();
    });

})(jQuery, window, document);