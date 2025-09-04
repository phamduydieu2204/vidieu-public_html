/**
 * Vidieu Select Options Open Quickview Sidebar
 * 
 * Opens NASA quickview sidebar when "Tùy chọn" button is clicked
 * in plugin product sections
 * 
 * @package VidieuHomeSections
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    if (typeof $ === 'undefined') {
        return;
    }
    
    var VDHome = window.VDHome || {};
    
    /**
     * Initialize select options to open quickview
     */
    VDHome.initSelectOptionsOpenQuickview = function(root) {
        root = root || document;
        var self = this;
        
        // Find container for event delegation
        var $containers = $(root).find('div[id^="vd-products-"]');
        if (!$containers.length && $(root).is('div[id^="vd-products-"]')) {
            $containers = $(root);
        }
        
        if (!$containers.length) {
            return;
        }
        
        // Remove previous handlers to prevent double-binding
        $containers.off('click.vdhs');
        
        // Attach delegated event handler
        $containers.on('click.vdhs', '.vd-buy-now-button', function(e) {
            var $btn = $(this);
            
            // Check if this is truly a select-options button
            if (!self.isSelectOptionsButton($btn)) {
                // Don't prevent default for "Mua ngay" buttons
                return;
            }
            
            // For select options, prevent default
            e.preventDefault();
            e.stopPropagation();
            
            // Get product info
            var $product = $btn.closest('li.product, li.product-item, .product-item, .item-product, .type-product');
            if (!$product.length) {
                $product = $btn.closest('li');
            }
            if (!$product.length) {
                return;
            }
            
            // Get product ID
            var productId = self.getProductId($product, $btn);
            if (!productId) {
                return;
            }
            
            // Open NASA quickview sidebar
            self.openNasaQuickviewSidebar(productId, $product);
        });
    };
    
    /**
     * Check if button is a select-options button
     */
    VDHome.isSelectOptionsButton = function($btn) {
        // Check data-action first - this is the most reliable
        var dataAction = $btn.attr('data-action');
        if (dataAction === 'buy-now' || dataAction === 'add-to-cart') {
            return false;
        }
        
        if (dataAction === 'select-options') {
            return true;
        }
        
        // Check by text content
        var text = $btn.text().trim().toLowerCase();
        if (text === 'mua ngay' || text === 'buy now' || text.includes('mua')) {
            return false;
        }
        
        if (text === 'tùy chọn' || text === 'select options' || text.includes('chọn')) {
            return true;
        }
        
        // Check by class as last resort
        if ($btn.hasClass('vd-buy-now-variable')) {
            if (text !== 'mua ngay' && text !== 'buy now') {
                return true;
            }
        }
        
        return false;
    };
    
    /**
     * Get product ID from various sources
     */
    VDHome.getProductId = function($product, $btn) {
        var productId = null;
        
        // Try 1: From .nasa-product-content-variable-warp
        var $varWrap = $product.find('.nasa-product-content-variable-warp[data-product_id]');
        if ($varWrap.length) {
            productId = $varWrap.attr('data-product_id');
            if (productId) return productId;
        }
        
        // Try 2: From button data attributes
        productId = $btn.attr('data-product_id') || $btn.data('product_id') || $btn.attr('data-product-id');
        if (productId) return productId;
        
        // Try 3: From product container
        productId = $product.attr('data-product_id') || $product.data('product-id') || $product.attr('data-product-id');
        if (productId) return productId;
        
        // Try 4: From quickview icon in same product
        var $quickview = $product.find('.quick-view[data-prod]');
        if ($quickview.length) {
            productId = $quickview.attr('data-prod');
            if (productId) return productId;
        }
        
        // Try 5: From href attribute parsing
        var href = $btn.attr('href');
        if (href && href.includes('add-to-cart=')) {
            var match = href.match(/add-to-cart=(\d+)/);
            if (match && match[1]) {
                productId = match[1];
                return productId;
            }
        }
        
        return null;
    };
    
    /**
     * Open NASA quickview sidebar for product
     */
    VDHome.openNasaQuickviewSidebar = function(productId, $product) {
        // Check if sidebar exists
        if (!$('#nasa-quickview-sidebar').length) {
            return;
        }
        
        // Direct AJAX call using NASA theme's mechanism
        if (typeof nasa_params_quickview !== 'undefined' && nasa_params_quickview.wc_ajax_url) {
            var _urlAjax = nasa_params_quickview.wc_ajax_url.toString().replace('%%endpoint%%', 'nasa_quick_view');
            
            var _data = {
                product: productId,
                quickview: 'sidebar'
            };
            
            // Ensure black-window exists
            if (!$('.black-window').length) {
                $('body').append('<div class="black-window"></div>');
            }
            
            // Show loader
            $('.black-window').fadeIn(200).addClass('desk-window');
            $('#nasa-quickview-sidebar').addClass('nasa-active');
            $('#nasa-quickview-sidebar #nasa-quickview-sidebar-content').html('<div class="nasa-loader"></div>');
            
            // Add close button handler
            $('.black-window').off('click.vdQuickview').on('click.vdQuickview', function(e) {
                if ($(e.target).hasClass('black-window')) {
                    $('#nasa-quickview-sidebar').removeClass('nasa-active');
                    $('.black-window').fadeOut(200).removeClass('desk-window');
                }
            });
            
            $.ajax({
                url: _urlAjax,
                type: 'post',
                dataType: 'json',
                data: _data,
                cache: false,
                success: function(response) {
                    if (response && response.content) {
                        $('#nasa-quickview-sidebar #nasa-quickview-sidebar-content').html('<div class="product-lightbox">' + response.content + '</div>');
                        
                        setTimeout(function() {
                            $('#nasa-quickview-sidebar #nasa-quickview-sidebar-content .product-lightbox').addClass('nasa-loaded');
                            $('body').trigger('nasa_after_quickview_timeout');
                            $('body').trigger('nasa_after_quickview');
                            
                            // Initialize variations form if exists
                            var $form = $('.product-lightbox').find('.variations_form');
                            if ($form.length) {
                                $('body').trigger('init_quickview_variations_form', [$form]);
                            }
                        }, 200);
                    }
                },
                error: function() {
                    $('#nasa-quickview-sidebar').removeClass('nasa-active');
                    $('.black-window').fadeOut(200).removeClass('desk-window');
                }
            });
        }
    };
    
    /**
     * Re-initialize after AJAX updates
     */
    VDHome.reinitSelectOptionsQuickview = function() {
        VDHome.initSelectOptionsOpenQuickview();
        VDHome.overrideNasaQuickview();
    };
    
    /**
     * Override NASA quickview to prevent opening in plugin sections
     */
    VDHome.overrideNasaQuickview = function() {
        // Wait a bit to ensure theme handler is loaded
        setTimeout(function() {
            // Store original NASA quickview handler
            var originalNasaHandler = null;
            
            // Check if there's a click handler on body for .quick-view
            var bodyHandlers = $._data(document.body, 'events');
            if (bodyHandlers && bodyHandlers.click) {
                bodyHandlers.click.forEach(function(handler) {
                    if (handler.selector === '.quick-view') {
                        originalNasaHandler = handler.handler;
                    }
                });
            }
            
            // Remove the original handler
            $('body').off('click', '.quick-view');
            
            // Add our wrapped handler
            $('body').on('click', '.quick-view', function(e) {
                var $this = $(this);
                
                // Check if this is in a plugin section
                if ($this.closest('.vd-home-section.vd-home-products').length) {
                    // Don't call NASA handler, let custom quickview handle it
                    return;
                }
                
                // For quickview outside plugin sections, call original handler
                if (originalNasaHandler) {
                    originalNasaHandler.call(this, e);
                }
            });
        }, 100);
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Add CSS fixes for NASA quickview sidebar
        var fixCSS = `
            <style id="vd-quickview-fix">
                /* Ensure NASA quickview sidebar is visible */
                #nasa-quickview-sidebar {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }
                
                #nasa-quickview-sidebar.nasa-active {
                    transform: translateX(0) !important;
                    right: 0 !important;
                }
                
                /* Ensure black window is visible when needed */
                .black-window.desk-window {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 0.8 !important;
                    z-index: 9998 !important;
                }
                
                /* Fix z-index for sidebar */
                #nasa-quickview-sidebar {
                    z-index: 9999 !important;
                }
                
                /* Remove any hiding from custom quickview */
                body.vd-custom-quickview-active #nasa-quickview-sidebar {
                    display: block !important;
                }
            </style>
        `;
        
        $('head').append(fixCSS);
        
        VDHome.initSelectOptionsOpenQuickview();
        VDHome.overrideNasaQuickview();
    });
    
    // Re-initialize after AJAX events
    $(document).on('vidieu_products_filtered vidieu_products_page_loaded vidieu_items_loaded', function() {
        VDHome.reinitSelectOptionsQuickview();
    });
    
    // Legacy event names support
    $(document).on('vd_products_filtered vd_products_page_loaded vd_items_loaded', function() {
        VDHome.reinitSelectOptionsQuickview();
    });
    
    // NASA theme events
    $(document).on('nasa_after_load nasa_refresh_shop', function() {
        setTimeout(function() {
            VDHome.reinitSelectOptionsQuickview();
        }, 200);
    });
    
    // Monitor for dynamic content changes with MutationObserver
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldReinit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length) {
                    // Check if products were added
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === 1) { // Element node
                            if ($(node).hasClass('product') || $(node).find('.product').length) {
                                shouldReinit = true;
                                break;
                            }
                        }
                    }
                }
            });
            
            if (shouldReinit) {
                // Debounce re-initialization
                clearTimeout(VDHome._reinitTimeout);
                VDHome._reinitTimeout = setTimeout(function() {
                    VDHome.reinitSelectOptionsQuickview();
                }, 150);
            }
        });
        
        // Observe product containers
        $('div[id^="vd-products-"]').each(function() {
            observer.observe(this, {
                childList: true,
                subtree: true
            });
        });
    }
    
    // Expose to global scope
    window.VDHome = VDHome;
    
})(jQuery);