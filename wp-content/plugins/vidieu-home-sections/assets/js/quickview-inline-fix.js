/**
 * QuickView Inline Scroll Fix
 * Prevents auto-scroll to top when selecting attributes in inline quickview
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */
(function($) {
    'use strict';
    
    if (typeof $ === 'undefined') {
        return;
    }
    
    var QuickViewInlineFix = {
        init: function() {
            this.preventAttributeScrolling();
            this.disableLegacySidebarTriggers();
            this.handleAjaxUpdates();
        },
        
        /**
         * Prevent hash anchor scrolling when clicking attribute selectors
         */
        preventAttributeScrolling: function() {
            // Handle clicks on attribute selector links
            $(document).on('click.qvfix', '.nasa-attr-ux-item, .nasa-attr-ux-select', function(e) {
                // Prevent default anchor behavior
                e.preventDefault();
                
                // Store current scroll position
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
                
                // Restore scroll position immediately
                window.scrollTo(scrollLeft, scrollTop);
                
                // Additional safeguard: prevent hash change
                if (this.getAttribute('href') === '#') {
                    return false;
                }
            });
            
            // Handle hashchange events that might be triggered
            $(window).on('hashchange.qvfix', function(e) {
                // If hash is empty or just #, prevent scrolling
                if (window.location.hash === '#' || window.location.hash === '') {
                    e.preventDefault();
                    
                    // Restore scroll position
                    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    window.scrollTo(0, scrollTop);
                    
                    return false;
                }
            });
            
            // Capture and prevent scroll events during attribute selection
            var isSelectingAttribute = false;
            
            $(document).on('mousedown.qvfix touchstart.qvfix', '.nasa-attr-ux-item, .nasa-attr-ux-select', function() {
                isSelectingAttribute = true;
                
                // Reset flag after a short delay
                setTimeout(function() {
                    isSelectingAttribute = false;
                }, 500);
            });
            
            // Prevent scroll during attribute selection
            $(window).on('scroll.qvfix', function(e) {
                if (isSelectingAttribute) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        },
        
        /**
         * Disable any remaining legacy quickview sidebar triggers
         */
        disableLegacySidebarTriggers: function() {
            // Prevent nasa-quickview-sidebar from being triggered
            $(document).on('click.qvfix', '[data-prod="0"], [rel*="quickview"], .quick-view', function(e) {
                var $this = $(this);
                
                // Only prevent if within our home sections
                if ($this.closest('.vd-home-section.vd-home-products').length) {
                    // Check if it's trying to open the sidebar
                    if ($this.attr('href') === '#' || $this.data('prod') === 0) {
                        e.stopImmediatePropagation();
                    }
                }
            });
            
            // Block sidebar close link if somehow triggered
            $(document).on('click.qvfix', '#nasa-quickview-sidebar .quickview-close a', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Monitor for focus changes that might trigger scrolling
            $(document).on('focus.qvfix', '#nasa-quickview-sidebar *', function(e) {
                // Prevent focus on sidebar elements
                $(this).blur();
                e.preventDefault();
            });
        },
        
        /**
         * Re-initialize after AJAX content loads
         */
        handleAjaxUpdates: function() {
            var self = this;
            
            // Re-initialize on various AJAX events
            var ajaxEvents = [
                'vidieu_products_filtered',
                'vidieu_products_page_loaded', 
                'vidieu_items_loaded',
                'vd_products_filtered',
                'vd_products_page_loaded',
                'vd_items_loaded',
                'nasa_after_load',
                'nasa_refresh_shop'
            ];
            
            $(document).on(ajaxEvents.join(' '), function() {
                // Small delay to ensure DOM is updated
                setTimeout(function() {
                    self.preventAttributeScrolling();
                }, 100);
            });
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        QuickViewInlineFix.init();
    });
    
    // Also initialize on window load as fallback
    $(window).on('load', function() {
        QuickViewInlineFix.init();
    });
    
    
})(jQuery);