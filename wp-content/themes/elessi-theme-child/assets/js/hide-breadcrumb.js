/**
 * Hide/Remove NASA Breadcrumb Elements
 * Targets: Static Blocks breadcrumb in admin/frontend
 */

(function() {
    'use strict';
    
    /**
     * Remove breadcrumb elements
     */
    function removeBreadcrumbs() {
        // Select all matching breadcrumb elements
        var breadcrumbs = document.querySelectorAll('span.nasa-flex.jc');
        
        breadcrumbs.forEach(function(breadcrumb) {
            // Check if it contains the specific text pattern
            var text = breadcrumb.textContent || breadcrumb.innerText;
            
            if (text.includes('Dashboard') && text.includes('Static Blocks')) {
                // Remove the entire element from DOM
                breadcrumb.remove();
                
                // Also check if parent is empty and remove it
                var parent = breadcrumb.parentElement;
                if (parent && parent.children.length === 0) {
                    parent.remove();
                }
            }
        });
        
        // Alternative method for browsers without :has() support
        try {
            // Try using :has() selector
            var specificBreadcrumbs = document.querySelectorAll('span.nasa-flex.jc:has(i.pe-7s-news-paper)');
            specificBreadcrumbs.forEach(function(el) {
                el.remove();
            });
        } catch (e) {
            // Fallback for browsers without :has() support
            var allNasaFlex = document.querySelectorAll('span.nasa-flex.jc');
            allNasaFlex.forEach(function(span) {
                // Check if contains the newspaper icon
                if (span.querySelector('i.pe-7s-news-paper') || 
                    span.querySelector('i.pe7-icon.pe-7s-news-paper')) {
                    span.remove();
                }
            });
        }
    }
    
    /**
     * Observer for dynamic content
     */
    function setupObserver() {
        // Create observer to watch for dynamically added elements
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    removeBreadcrumbs();
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Initialize on various load events
     */
    function init() {
        // Remove on initial load
        removeBreadcrumbs();
        
        // Setup observer for dynamic content
        setupObserver();
        
        // Also run after a short delay to catch late-loading elements
        setTimeout(removeBreadcrumbs, 500);
        setTimeout(removeBreadcrumbs, 1000);
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run on window load
    window.addEventListener('load', function() {
        removeBreadcrumbs();
    });
    
})();