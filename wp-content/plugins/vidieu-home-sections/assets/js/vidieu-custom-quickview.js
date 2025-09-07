/**
 * Vidieu Custom Quickview Logic
 * 
 * Thay đổi logic hiển thị cho các phần tử:
 * - Phần tử A: Quickview icon
 * - Phần tử B: Bảng chọn thuộc tính
 * - Phần tử C: Quickview sidebar (không mở nữa)
 * 
 * 4 Trạng thái:
 * 1. Mặc định: A và B đều ẩn
 * 2. Hover: Chỉ A hiện
 * 3. Click A: B hiện
 * 4. Mouse leave: A và B ẩn
 * 
 * @package VidieuHomeSections
 * @version 1.1.0
 */

(function($) {
    'use strict';
    
    // Early exit if jQuery is not available
    if (typeof $ === 'undefined') {
        return;
    }
    
    var VidieuCustomQuickview = {
        
        /**
         * Initialize custom quickview logic
         */
        init: function() {
            var self = this;
            
            // Check if we have products sections
            if (!$('.vd-home-section.vd-home-products').length) {
                return;
            }
            
            // Initialize custom CSS
            self.injectCustomCSS();
            
            // Setup hover handlers for 4 states
            self.setupHoverHandlers();
            
            // Setup quickview click handler
            self.setupQuickviewClick();
            
            // Handle AJAX content updates
            self.handleAjaxUpdates();
            
            // Remove black-window overlay
            self.removeBlackWindow();
        },
        
        /**
         * Inject custom CSS to override default behavior
         */
        injectCustomCSS: function() {
            var customCSS = `
                <style id="vidieu-custom-quickview-css">
                    /* State 1: Default - Hide both A and B completely */
                    .vd-home-section.vd-home-products .product-item .quick-view,
                    .vd-home-section.vd-home-products .product-item .nasa-product-content-select-wrap {
                        opacity: 0 !important;
                        visibility: hidden !important;
                        pointer-events: none !important;
                        display: none !important;
                    }
                    
                    /* State 2: Hover - Show only A */
                    .vd-home-section.vd-home-products .product-item.vd-hover .quick-view {
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                    }
                    
                    /* State 3: Click A - Show B */
                    .vd-home-section.vd-home-products .product-item.vd-show-attributes .nasa-product-content-select-wrap {
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        transform: translate(3px, -5px) !important;
                        transition: none !important;
                        -webkit-transition: none !important;
                        -moz-transition: none !important;
                        -o-transition: none !important;
                        z-index: 9999 !important;
                    }
                    
                    /* Keep A visible when B is shown */
                    .vd-home-section.vd-home-products .product-item.vd-show-attributes .quick-view {
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        background-color: #333 !important;
                        color: #fff !important;
                    }
                    
                    /* Ensure attributes panel is above everything */
                    .vd-home-section.vd-home-products .nasa-product-content-select-wrap {
                        z-index: 9999 !important;
                        position: absolute !important;
                    }
                    
                    /* Remove all transitions for instant display */
                    .vd-home-section.vd-home-products .nasa-product-content-select-wrap,
                    .vd-home-section.vd-home-products .nasa-product-content-select-wrap * {
                        transition: none !important;
                        -webkit-transition: none !important;
                        -moz-transition: none !important;
                        -o-transition: none !important;
                        animation: none !important;
                    }
                    
                    /* Prevent quickview sidebar from opening */
                    body.vd-custom-quickview-active #nasa-quickview-sidebar {
                        display: none !important;
                    }
                    
                    /* Hide black window overlay when custom quickview is active */
                    body.vd-custom-quickview-active .black-window {
                        display: none !important;
                        visibility: hidden !important;
                        opacity: 0 !important;
                        pointer-events: none !important;
                        z-index: -1 !important;
                    }
                    
                    /* Hide NASA loader in our sections during quickview */
                    .vd-home-section.vd-home-products .nasa-loader {
                        display: none !important;
                        visibility: hidden !important;
                        opacity: 0 !important;
                    }
                    
                    /* Ensure no loader appears when clicking quickview */
                    .vd-home-section.vd-home-products .product-item .nasa-loader,
                    .vd-home-section.vd-home-products .product-item .nasa-light-fog,
                    .vd-home-section.vd-home-products .product-item .nasa-dark-fog {
                        display: none !important;
                        visibility: hidden !important;
                        opacity: 0 !important;
                    }
                    
                    /* Mobile specific adjustments */
                    @media (max-width: 767px) {
                        /* On mobile, always show quickview icon (no hover) */
                        .vd-home-section.vd-home-products .product-item .quick-view {
                            display: block !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                            pointer-events: auto !important;
                        }
                        
                        .vd-home-section.vd-home-products .product-item.vd-show-attributes .nasa-product-content-select-wrap {
                            max-width: 90%;
                            left: 5%;
                            right: 5%;
                        }
                    }
                </style>
            `;
            
            // Remove existing custom CSS if any
            $('#vidieu-custom-quickview-css').remove();
            
            // Append new CSS to head
            $('head').append(customCSS);
        },
        
        /**
         * Setup hover handlers for 4 states
         */
        setupHoverHandlers: function() {
            var self = this;
            
            // Check if mobile device
            var isMobile = window.matchMedia('(max-width: 767px)').matches;
            
            // Remove default hover classes on init
            $('.vd-home-section.vd-home-products .product-item').removeClass('vd-hover vd-show-attributes');
            
            // For desktop only - hover events
            if (!isMobile) {
                // State 2: Mouse enter - Show only A
                $(document).on('mouseenter.vdcustom', '.vd-home-section.vd-home-products .product-item', function() {
                    var $product = $(this);
                    
                    // Add hover class to show quickview icon
                    $product.addClass('vd-hover');
                    
                    // Make sure attributes are not shown unless clicked
                    if (!$product.hasClass('vd-show-attributes')) {
                        $product.find('.nasa-product-content-select-wrap').css({
                            'display': 'none',
                            'opacity': '0',
                            'visibility': 'hidden'
                        });
                    }
                });
                
                // State 4: Mouse leave - Hide both A and B
                $(document).on('mouseleave.vdcustom', '.vd-home-section.vd-home-products .product-item', function() {
                    var $product = $(this);
                    
                    // Remove all states - back to default
                    $product.removeClass('vd-hover vd-show-attributes');
                    
                    // Ensure both elements are hidden
                    $product.find('.quick-view').css({
                        'display': 'none',
                        'opacity': '0',
                        'visibility': 'hidden'
                    });
                    
                    $product.find('.nasa-product-content-select-wrap').css({
                        'display': 'none',
                        'opacity': '0',
                        'visibility': 'hidden'
                    });
                    
                    // Reset quickview icon text
                    $product.find('.quick-view').attr('data-icon-text', 'Quick View');
                });
            }
            
            // For mobile - handle tap outside to close
            if (isMobile) {
                $(document).on('touchstart.vdcustom', function(e) {
                    var $target = $(e.target);
                    
                    // If tap is outside product item with open attributes
                    if (!$target.closest('.product-item.vd-show-attributes').length) {
                        // Close all open attribute panels
                        $('.vd-home-section.vd-home-products .product-item.vd-show-attributes').each(function() {
                            var $product = $(this);
                            $product.removeClass('vd-show-attributes');
                            $product.find('.nasa-product-content-select-wrap').css({
                                'display': 'none',
                                'opacity': '0',
                                'visibility': 'hidden'
                            });
                            $product.find('.quick-view').attr('data-icon-text', 'Quick View');
                        });
                    }
                });
            }
            
            // Handle window resize to update behavior
            $(window).on('resize.vdcustom', function() {
                var wasMobile = isMobile;
                isMobile = window.matchMedia('(max-width: 767px)').matches;
                
                // If switched from mobile to desktop or vice versa
                if (wasMobile !== isMobile) {
                    // Remove all event handlers
                    $(document).off('mouseenter.vdcustom');
                    $(document).off('mouseleave.vdcustom');
                    $(document).off('touchstart.vdcustom');
                    
                    // Re-setup handlers
                    self.setupHoverHandlers();
                }
            });
        },
        
        /**
         * Setup quickview click handler
         */
        setupQuickviewClick: function() {
            var self = this;
            
            // Remove any existing handlers and add our custom one
            $(document).off('click.vdcustom', '.vd-home-section.vd-home-products .quick-view');
            $(document).on('click.vdcustom', '.vd-home-section.vd-home-products .quick-view', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var $quickview = $(this);
                var $product = $quickview.closest('.product-item');
                
                // Force remove any black-window immediately
                $('.black-window').remove();
                
                // Also remove any loader elements from theme
                $product.find('.nasa-loader, .nasa-light-fog, .nasa-dark-fog').remove();
                
                // State 3: Toggle attribute visibility
                if ($product.hasClass('vd-show-attributes')) {
                    // Hide attributes but keep hover state
                    $product.removeClass('vd-show-attributes');
                    
                    // Reset quickview icon state
                    $quickview.attr('data-icon-text', 'Quick View');
                } else {
                    // Show attributes for this product
                    $product.addClass('vd-show-attributes');
                    
                    // Force display the attributes panel right away
                    var $attrPanel = $product.find('.nasa-product-content-select-wrap');
                    if ($attrPanel.length) {
                        // Force immediate display
                        $attrPanel.css({
                            'display': 'block',
                            'opacity': '1',
                            'visibility': 'visible',
                            'pointer-events': 'auto'
                        });
                        
                        // Ensure all child elements are also visible
                        $attrPanel.find('.nasa-product-content-child').css('display', 'block');
                        $attrPanel.find('.nasa-toggle-content-attr-select').addClass('nasa-show').css('display', 'block');
                    }
                    
                    // Update quickview icon state
                    $quickview.attr('data-icon-text', 'Close');
                    
                    // Add flag to prevent sidebar
                    $('body').addClass('vd-custom-quickview-active');
                }
                
                // Prevent default quickview sidebar from opening
                return false;
            });
            
            // Prevent clicks on attribute selection from triggering mouseleave
            $(document).on('click', '.vd-home-section.vd-home-products .nasa-product-content-select-wrap', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Remove black-window overlay
         */
        removeBlackWindow: function() {
            // Initial removal
            $('.black-window').remove();
            
            // Monitor and remove black-window overlay and nasa-loader
            var overlayMonitor = setInterval(function() {
                var $blackWindow = $('.black-window');
                if ($blackWindow.length) {
                    $blackWindow.remove();
                }
                
                // Also remove any nasa-loader in our sections
                $('.vd-home-section.vd-home-products .nasa-loader').remove();
                $('.vd-home-section.vd-home-products .nasa-light-fog').remove();
                $('.vd-home-section.vd-home-products .nasa-dark-fog').remove();
            }, 100);
            
            // Stop monitoring after 10 seconds to save resources
            setTimeout(function() {
                clearInterval(overlayMonitor);
            }, 10000);
            
            // Monitor for dynamic black-window and loader creation
            $(document).on('DOMNodeInserted', function(e) {
                if ($(e.target).hasClass('black-window')) {
                    $(e.target).remove();
                }
                
                // Remove nasa-loader if inserted in our sections
                if ($(e.target).hasClass('nasa-loader') && $(e.target).closest('.vd-home-section.vd-home-products').length) {
                    $(e.target).remove();
                }
                
                // Remove light/dark fog overlays in our sections
                if (($(e.target).hasClass('nasa-light-fog') || $(e.target).hasClass('nasa-dark-fog')) && 
                    $(e.target).closest('.vd-home-section.vd-home-products').length) {
                    $(e.target).remove();
                }
            });
        },
        
        /**
         * Handle AJAX content updates
         */
        handleAjaxUpdates: function() {
            var self = this;
            
            // Re-initialize after AJAX loads
            $(document).on('vidieu_products_filtered vidieu_products_page_loaded vidieu_items_loaded', function() {
                // Re-inject CSS if needed
                if (!$('#vidieu-custom-quickview-css').length) {
                    self.injectCustomCSS();
                }
                
                // Reset all product states
                $('.vd-home-section.vd-home-products .product-item').removeClass('vd-hover vd-show-attributes');
                
                // Ensure body flag is set
                $('body').addClass('vd-custom-quickview-active');
                
                // Re-setup handlers
                self.setupHoverHandlers();
                self.setupQuickviewClick();
            });
            
            // Also handle legacy events
            $(document).on('vd_products_filtered vd_products_page_loaded vd_items_loaded', function() {
                // Re-inject CSS if needed
                if (!$('#vidieu-custom-quickview-css').length) {
                    self.injectCustomCSS();
                }
                
                // Reset all product states
                $('.vd-home-section.vd-home-products .product-item').removeClass('vd-hover vd-show-attributes');
                
                // Ensure body flag is set
                $('body').addClass('vd-custom-quickview-active');
            });
            
            // Handle NASA theme events
            $(document).on('nasa_after_load nasa_refresh_shop', function() {
                setTimeout(function() {
                    // Ensure our handlers are still active
                    self.setupHoverHandlers();
                    self.setupQuickviewClick();
                    $('body').addClass('vd-custom-quickview-active');
                }, 100);
            });
        },
        
        /**
         * Public method to enable/disable custom quickview logic
         */
        toggle: function(enable) {
            if (enable) {
                $('body').addClass('vd-custom-quickview-active');
                this.init();
            } else {
                $('body').removeClass('vd-custom-quickview-active');
                $('#vidieu-custom-quickview-css').remove();
                $('.vd-home-section.vd-home-products .product-item').removeClass('vd-hover vd-show-attributes');
                $(document).off('click.vdcustom');
                $(document).off('mouseenter.vdcustom');
                $(document).off('mouseleave.vdcustom');
                $(document).off('touchstart.vdcustom');
                $(window).off('resize.vdcustom');
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        VidieuCustomQuickview.init();
        
        // Set body flag to prevent quickview sidebar
        $('body').addClass('vd-custom-quickview-active');
    });
    
    // Also initialize on window load as fallback
    $(window).on('load', function() {
        if (!$('body').hasClass('vd-custom-quickview-active')) {
            VidieuCustomQuickview.init();
            $('body').addClass('vd-custom-quickview-active');
        }
    });
    
    
})(jQuery);