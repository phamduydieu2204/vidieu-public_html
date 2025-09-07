<?php
/**
 * QuickView Inline Fix - Emergency fallback
 * 
 * This file provides an inline CSS/JS solution to fix QuickView scroll issue
 * when asset loading fails due to caching or path issues
 * 
 * @package Vidieu_Home_Sections
 * @since 1.6.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_QuickView_Inline {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp', array($this, 'init_inline_fix'), 30);
    }
    
    public function init_inline_fix() {
        // Only on frontend pages with products
        if (is_admin() || (!is_front_page() && !is_home() && !is_shop() && !is_product_category())) {
            return;
        }
        
        // Add inline styles and scripts
        add_action('wp_head', array($this, 'output_inline_css'), 999);
        add_action('wp_footer', array($this, 'output_inline_js'), 999);
    }
    
    public function output_inline_css() {
        ?>
        <style id="vidieu-qv-inline-css">
        /* QuickView Scroll Lock - Inline Fallback */
        html.qv-open,
        body.qv-open {
            overflow: hidden !important;
            touch-action: none !important;
            overscroll-behavior: contain !important;
            position: relative !important;
        }
        
        /* Ensure QuickView modal is scrollable */
        .qv-open .nasa-quickview-popup,
        .qv-open .mfp-content {
            overflow-y: auto !important;
            max-height: 100vh !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        /* Mobile specific fixes */
        @media (max-width: 768px) {
            .qv-open .mfp-wrap {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }
        }
        </style>
        <?php
    }
    
    public function output_inline_js() {
        ?>
        <script id="vidieu-qv-inline-js">
        (function() {
            'use strict';
            
            // Prevent multiple init
            if (window.__vidieuQVInline) return;
            window.__vidieuQVInline = true;
            
            var scrollPos = 0;
            var isQVOpen = false;
            
            // Lock scroll
            function lockScroll() {
                scrollPos = window.pageYOffset || document.documentElement.scrollTop || 0;
                isQVOpen = true;
                document.documentElement.classList.add('qv-open');
                document.body.classList.add('qv-open');
                if ('scrollRestoration' in history) {
                    history.scrollRestoration = 'manual';
                }
            }
            
            // Unlock scroll
            function unlockScroll() {
                isQVOpen = false;
                document.documentElement.classList.remove('qv-open');
                document.body.classList.remove('qv-open');
                if ('scrollRestoration' in history) {
                    history.scrollRestoration = 'auto';
                }
            }
            
            // Maintain position
            function maintainPos() {
                if (!isQVOpen) return;
                var currentPos = window.pageYOffset || document.documentElement.scrollTop || 0;
                if (Math.abs(currentPos - scrollPos) > 10) {
                    window.scrollTo(0, scrollPos);
                }
            }
            
            // Prevent anchor scrolling
            document.addEventListener('click', function(e) {
                var anchor = e.target.closest('a');
                if (!anchor || !isQVOpen) return;
                
                var inQV = anchor.closest('.nasa-quick-view, .quick-view, .mfp-content');
                if (!inQV) return;
                
                var href = anchor.getAttribute('href') || '';
                if (href === '#' || href === '#!' || href === '' || href.indexOf('#') === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
            
            // Monitor QuickView
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Check for QuickView open
                        for (var i = 0; i < mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && node.classList) {
                                if (node.classList.contains('nasa-quick-view') || 
                                    node.classList.contains('mfp-wrap') ||
                                    node.id === 'nasa-quickview-popup') {
                                    if (!isQVOpen) lockScroll();
                                }
                            }
                        }
                        // Check for QuickView close
                        for (var j = 0; j < mutation.removedNodes.length; j++) {
                            var removed = mutation.removedNodes[j];
                            if (removed.nodeType === 1 && removed.classList) {
                                if (removed.classList.contains('nasa-quick-view') || 
                                    removed.classList.contains('mfp-wrap')) {
                                    if (isQVOpen) unlockScroll();
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
            
            // Handle variation changes
            document.addEventListener('change', function(e) {
                if (!isQVOpen) return;
                var form = e.target.closest('.variations_form');
                if (!form) return;
                var inQV = form.closest('.nasa-quick-view, .quick-view');
                if (!inQV) return;
                setTimeout(maintainPos, 0);
                setTimeout(maintainPos, 50);
            }, true);
            
            // Prevent hashchange
            window.addEventListener('hashchange', function(e) {
                if (!isQVOpen) return;
                e.stopImmediatePropagation();
                if (window.location.hash) {
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                }
            }, true);
        })();
        </script>
        <?php
    }
}

// Initialize
Vidieu_QuickView_Inline::get_instance();