/**
 * Vidieu Home Sections - JavaScript
 * 
 * @package VidieuHomeSections
 * @version 1.1.1
 * jQuery noConflict compatible
 */

(function($) {
    'use strict';
    
    // Prevent multiple script execution
    if (window.VidieuHomeSectionsLoaded) {
        return;
    }
    window.VidieuHomeSectionsLoaded = true;
    
    /**
     * Synchronize product card heights
     */
    function syncProductInfoHeights() {
        $('.vd-home-products ul.products').each(function() {
            var $productList = $(this);
            var $products = $productList.find('li.product-warp-item');
            
            if ($products.length <= 1) return;
            
            // Find the tallest info wrap
            var maxHeight = 0;
            var infoWraps = [];
            
            $products.each(function(index) {
                var $product = $(this);
                var $infoWrap = $product.find('.product-info-wrap.info');
                
                if ($infoWrap.length) {
                    // Reset height to auto to get natural height
                    $infoWrap.css('height', 'auto');
                    var height = $infoWrap.outerHeight();
                    
                    infoWraps.push({
                        element: $infoWrap,
                        height: height,
                        index: index
                    });
                    
                    if (height > maxHeight) {
                        maxHeight = height;
                    }
                    
                }
            });
            
            
            // Apply max height to all info wraps
            infoWraps.forEach(function(item) {
                item.element.css({
                    'height': maxHeight + 'px',
                    'min-height': maxHeight + 'px'
                });
            });
            
            // Also sync the product inner containers
            var maxProductHeight = 0;
            $products.each(function() {
                var $productInner = $(this).find('.product-inner, .product');
                if ($productInner.length) {
                    $productInner.css('height', 'auto');
                    var height = $productInner.outerHeight();
                    if (height > maxProductHeight) {
                        maxProductHeight = height;
                    }
                }
            });
            
            // Apply max height to all product containers
            if (maxProductHeight > 0) {
                $products.each(function() {
                    $(this).find('.product-inner, .product').css({
                        'min-height': maxProductHeight + 'px'
                    });
                });
            }
        });
    }
    
    // Early exit if required objects are not available
    if (typeof vd_home_ajax === 'undefined') {
        return;
    }
    
    // Debounce function to prevent rapid fire clicks
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    /**
     * Create enhanced event payload with metadata
     */
    function createEventPayload($content, data, extraData) {
        var $section = $content.closest('.vd-home-section');
        var sectionId = $section.attr('id') || $section.data('section-id') || 'unknown';
        var sectionType = $section.data('section-type') || 'unknown';
        
        return $.extend({
            timestamp: Date.now(),
            section_id: sectionId,
            section_type: sectionType,
            content: $content,
            data: data
        }, extraData || {});
    }
    
    /**
     * Trigger events with backward compatibility
     * Emits both new vidieu_* events and deprecated vd_* aliases
     * @param {string} eventName - The new event name (without prefix)
     * @param {Object} payload - The new payload structure
     * @param {Object} legacyData - The legacy data format (optional)
     */
    function triggerCompatibleEvent(eventName, payload, legacyData) {
        // Trigger new event with enhanced payload
        var newEventName = 'vidieu_' + eventName;
        $(document).trigger(newEventName, [payload]);
        
        // Trigger deprecated event with backward compatibility
        var deprecatedEventName = 'vd_' + eventName;
        var compatData = legacyData || payload.data || payload;
        
        $(document).trigger(deprecatedEventName, [compatData]);
    }
    
    var VidieuHomeSections = {
        
        initialized: false,
        filterPanelToggling: false,
        
        /**
         * Initialize the plugin
         */
        init: function() {
            var self = this;
            
            // Prevent double initialization
            if (self.initialized) {
                return;
            }
            
            // Check if we have the required sections on page
            if (!$('.vd-home-products, .vd-home-posts').length) {
                return;
            }
            
            self.bindEvents();
            self.setInitialActiveStates();
            self.initializeFilterPanel();
            self.updateFilterToggleText();
            
            // Make sure all filter panels are hidden initially
            $('.vd-filter-panel').hide().attr('aria-hidden', 'true');
            
            // Mark as initialized FIRST before any async operations
            self.initialized = true;
            
            // Delay initialization to ensure DOM is ready
            setTimeout(function() {
                self.repositionVariantSelectors();
                self.initializeBuyNowButtons();
                self.handleBrokenImages($('.vd-home-products'));
                
                // Force layout recalculation for first product
                self.fixFirstProductLayout();
            }, 100);
            
        },
        
        /**
         * Initialize Buy Now button states
         */
        initializeBuyNowButtons: function() {
            var self = this;
            
            // Initial update for all variable product Buy Now buttons
            $('.vd-home-section .vd-buy-now-button.vd-buy-now-variable').each(function() {
                var $button = $(this);
                var $product = $button.closest('.product');
                if (typeof self.updateBuyNowButtonState === 'function') {
                    self.updateBuyNowButtonState($product);
                }
            });
            
            // Also monitor NASA theme variation changes
            $(document).on('nasa_changed_variations', function(e, $form) {
                var $product = $form.closest('.product');
                if ($product.length && typeof self.updateBuyNowButtonState === 'function') {
                    self.updateBuyNowButtonState($product);
                }
            });
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Removed expand/collapse handler as it conflicts with smart tree display
            
            // Handle menu clicks in desktop sidebar
            $(document).on('click', '.vd-sidebar .vd-sidebar-menu a[data-term-id]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $link = $(this);
                
                // Debounce to prevent rapid clicks
                if ($link.hasClass('vd-processing')) {
                    return false;
                }
                
                $link.addClass('vd-processing');
                setTimeout(function() {
                    $link.removeClass('vd-processing');
                }, 1000);
                
                // Apply smart tree display immediately for better UX
                self.applySmartTreeDisplay($link);
                
                var section = $link.closest('.vd-home-products').length ? 'products' : 'posts';
                self.handleFilterClick($link, section);
                return false;
            });
            
            // Handle menu clicks in mobile filter panel
            $(document).on('click', '.vd-filter-panel .vd-sidebar-menu a[data-term-id]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $link = $(this);
                
                // Apply smart tree display immediately for mobile too
                self.applySmartTreeDisplay($link);
                
                // Update active state
                $('.vd-filter-panel .vd-sidebar-menu a').removeClass('active');
                $link.addClass('active');
                
                // Update filter toggle text to show selected category
                var $section = $link.closest('.vd-home-section');
                self.updateSingleFilterToggle($section);
                
                // Load products/posts based on the selected category
                var section = $section.hasClass('vd-home-products') ? 'products' : 'posts';
                self.handleFilterClick($link, section);
                
                return false;
            });
            
            // Handle load more buttons
            $(document).on('click', '.vd-load-more-btn', debounce(function(e) {
                e.preventDefault();
                self.handleLoadMore($(this));
            }, 500));
            
            // Handle pagination clicks
            $(document).on('click', '.vd-pagination-wrapper a.page-numbers:not(.current)', debounce(function(e) {
                e.preventDefault();
                self.handlePaginationClick($(this));
            }, 300));
            
            // Handle Buy Now button clicks
            $(document).on('click', '.vd-buy-now-button', debounce(function(e) {
                e.preventDefault();
                self.handleBuyNowClick($(this));
            }, 300));
            
            // Handle "All" or items without data-term-id (show all items)
            $(document).on('click', '.vd-sidebar-menu a:not([data-term-id])', function(e) {
                e.preventDefault();
                var $link = $(this);
                $link.addClass('vd-processing');
                setTimeout(function() {
                    $link.removeClass('vd-processing');
                }, 1000);
                
                var section = $link.closest('.vd-home-products').length ? 'products' : 
                             $link.closest('[data-section]').data('section') || 'posts';
                self.handleFilterClick($link, section);
                
                // Close panel on mobile (<768px)
                if (window.innerWidth < 768 && $link.closest('.vd-filter-panel').length) {
                    var $section = $link.closest('.vd-home-section');
                    self.closeFilterPanel($section);
                }
                
                return false;
            });
        },
        
        /**
         * Initialize filter panel for mobile
         */
        initializeFilterPanel: function() {
            var self = this;
            
            // Prevent multiple initialization
            if (self.filterPanelInitialized) {
                return;
            }
            self.filterPanelInitialized = true;
            
            // Unbind any existing handlers first
            $(document).off('click.vdfilter');
            
            // Toggle filter panel
            $(document).on('click.vdfilter', '.vd-filter-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var $button = $(this);
                var $section = $button.closest('.vd-home-section');
                
                self.toggleFilterPanel($section);
                
                return false;
            });
            
            // Close panel button
            $(document).on('click.vdfilter', '.vd-filter-panel-close', function(e) {
                e.preventDefault();
                var $section = $(this).closest('.vd-home-section');
                self.applyFilterAndClose($section);
            });
            
            // Click overlay to close
            $(document).on('click.vdfilter', '.vd-filter-overlay', function(e) {
                var $section = $(this).closest('.vd-home-section');
                self.applyFilterAndClose($section);
            });
            
            // Monitor variation selections to update Buy Now button
            $(document).on('click', '.vd-home-section .nasa-attr-ux-item', function(e) {
                var $item = $(this);
                var $product = $item.closest('.product');
                
                // Wait for NASA theme to update selections
                setTimeout(function() {
                    self.updateBuyNowButtonState($product);
                }, 200);
            });
            
            // ESC key to close panel
            $(document).keydown(function(e) {
                if (e.keyCode === 27) { // ESC key
                    self.closeAllFilterPanels();
                }
            });
            
            // Window resize handler with debounce
            var resizeTimer;
            $(window).on('resize.vd-filter', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    // Close all panels when resizing to tablet or desktop (≥768px)
                    if (window.innerWidth >= 768) {
                        self.closeAllFilterPanels();
                    }
                }, 250);
            });
            
            // Trap focus in panel
            $(document).on('keydown', '.vd-filter-open .vd-filter-panel', function(e) {
                if (e.keyCode === 9) { // Tab key
                    self.trapFocus(e, $(this));
                }
            });
        },
        
        /**
         * Toggle filter panel
         */
        toggleFilterPanel: function($section) {
            var self = this;
            
            // Prevent rapid toggling
            if (self.filterPanelToggling) {
                return;
            }
            
            self.filterPanelToggling = true;
            
            if (this.isFilterPanelOpen($section)) {
                this.closeFilterPanel($section);
            } else {
                this.openFilterPanel($section);
            }
            
            // Reset toggling flag after animation completes
            setTimeout(function() {
                self.filterPanelToggling = false;
            }, 300);
        },
        
        /**
         * Open filter panel
         */
        openFilterPanel: function($section) {
            var self = this;
            var $button = $section.find('.vd-filter-toggle');
            var $panel = $section.find('.vd-filter-panel');
            
            
            // Only close other panels, not the current one
            $('.vd-filter-open').not($section).each(function() {
                self.closeFilterPanel($(this));
            });
            
            // Set state
            $section.addClass('vd-filter-open');
            $('body').addClass('vd-scroll-lock');
            
            // Update ARIA attributes
            $button.attr('aria-expanded', 'true');
            $panel.attr('aria-hidden', 'false');
            
            // Make sure panel is visible and properly positioned
            $panel.show().css({
                'transform': 'translateX(0)',
                'visibility': 'visible'
            });
            
            // Focus management - delay to ensure panel is visible
            setTimeout(function() {
                // Don't focus on close button, find first menu link instead
                var $firstLink = $panel.find('.vd-sidebar-menu a').first();
                if ($firstLink.length) {
                    $firstLink.focus();
                } else {
                    // Fallback to any focusable element except close button
                    var $firstFocusable = $panel.find('a:not(.vd-filter-panel-close), button:not(.vd-filter-panel-close)').first();
                    if ($firstFocusable.length) {
                        $firstFocusable.focus();
                    }
                }
            }, 300);
        },
        
        /**
         * Update filter toggle text with current category
         */
        updateFilterToggleText: function(sectionId) {
            var self = this;
            
            // If sectionId provided, update specific section
            if (sectionId) {
                var $section = $('#' + sectionId);
                self.updateSingleFilterToggle($section);
            } else {
                // Update all sections on initial load
                $('.vd-home-section').each(function() {
                    self.updateSingleFilterToggle($(this));
                });
            }
        },
        
        /**
         * Update single filter toggle text
         */
        updateSingleFilterToggle: function($section) {
            var $toggle = $section.find('.vd-filter-toggle');
            var $currentSpan = $toggle.find('.vd-filter-current');
            
            if (!$currentSpan.length) return;
            
            // Find active menu item
            var $activeItem = $section.find('.vd-sidebar-menu a.active, .vd-filter-panel .vd-sidebar-menu a.active').first();
            
            if ($activeItem.length) {
                var categoryName = $activeItem.text().trim();
                $currentSpan.text(categoryName);
            } else {
                // Default to "All" if no active category
                $currentSpan.text(vd_home_ajax.i18n.all || 'All');
            }
        },
        
        /**
         * Close filter panel
         */
        closeFilterPanel: function($section) {
            var $button = $section.find('.vd-filter-toggle');
            var $panel = $section.find('.vd-filter-panel');
            
            // Remove state
            $section.removeClass('vd-filter-open');
            
            // Update ARIA attributes
            $button.attr('aria-expanded', 'false');
            $panel.attr('aria-hidden', 'true');
            
            // Hide panel after transition
            setTimeout(function() {
                $panel.hide();
            }, 300);
            
            // Restore body scroll if no other panels are open
            if (!$('.vd-filter-open').length) {
                $('body').removeClass('vd-scroll-lock');
            }
            
            // Return focus to toggle button
            $button.focus();
        },
        
        /**
         * Close all filter panels
         */
        closeAllFilterPanels: function() {
            var self = this;
            $('.vd-filter-open').each(function() {
                self.closeFilterPanel($(this));
            });
        },
        
        /**
         * Apply filter and close panel
         */
        applyFilterAndClose: function($section) {
            var self = this;
            
            // Just close the panel, don't load products
            // Products are already loaded when user clicks on category
            self.closeFilterPanel($section);
        },
        
        /**
         * Check if filter panel is open
         */
        isFilterPanelOpen: function($section) {
            return $section.hasClass('vd-filter-open');
        },
        
        /**
         * Trap focus within panel
         */
        trapFocus: function(e, $panel) {
            var $focusableElements = $panel.find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
            var $firstFocusable = $focusableElements.first();
            var $lastFocusable = $focusableElements.last();
            
            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === $firstFocusable[0]) {
                    e.preventDefault();
                    $lastFocusable.focus();
                }
            } else { // Tab
                if (document.activeElement === $lastFocusable[0]) {
                    e.preventDefault();
                    $firstFocusable.focus();
                }
            }
        },
        
        /**
         * Set initial active states for menu items
         */
        setInitialActiveStates: function() {
            var self = this;
            
            // Handle products sections
            $('.vd-home-products').each(function() {
                var $section = $(this);
                var $menus = $section.find('.vd-sidebar-menu');
                var defaultCat = $section.data('default-cat');
                
                if (defaultCat) {
                    // If there's a default category, mark that menu item as active
                    var $activeItems = $menus.find('a[data-term-id="' + defaultCat + '"]');
                    if ($activeItems.length) {
                        $activeItems.addClass('active');
                        // Mark parent items
                        $activeItems.each(function() {
                            $(this).parents('.vd-menu-item').addClass('vd-has-active-child');
                        });
                        // Apply smart tree display
                        self.applySmartTreeDisplay($activeItems.first());
                    } else {
                        // Fallback to first item
                        $menus.find('a').first().addClass('active');
                    }
                } else {
                    // No default category, mark first item as active since products are auto-loaded
                    var $firstItem = $menus.find('a').first();
                    $firstItem.addClass('active');
                    $firstItem.parents('.vd-menu-item').addClass('vd-has-active-child');
                }
            });
            
            // Handle posts sections
            $('.vd-home-posts').each(function() {
                var $section = $(this);
                var $menus = $section.find('.vd-sidebar-menu');
                var defaultCat = $section.data('default-cat');
                
                if (defaultCat) {
                    // If there's a default category, mark that menu item as active
                    var $activeItems = $menus.find('a[data-term-id="' + defaultCat + '"]');
                    if ($activeItems.length) {
                        $activeItems.addClass('active');
                        // Apply smart tree display
                        self.applySmartTreeDisplay($activeItems.first());
                    } else {
                        // Fallback to first item
                        $menus.find('a').first().addClass('active');
                    }
                } else {
                    // No default category, mark first item as active
                    var $firstMenuItem = $menus.find('a[data-term-id]').first();
                    $firstMenuItem.addClass('active');
                    
                    // Check if pagination wrapper already exists and has category data
                    var $paginationWrapper = $section.find('.vd-pagination-wrapper');
                    if ($paginationWrapper.length) {
                        var existingCategory = $paginationWrapper.data('category');
                        
                        if (existingCategory) {
                            // Pagination wrapper already has category data from server-side rendering
                            // Make sure the correct menu item is marked as active
                            $menus.find('a').removeClass('active');
                            var $correctMenuItem = $menus.find('a[data-term-id="' + existingCategory + '"]');
                            if ($correctMenuItem.length) {
                                $correctMenuItem.addClass('active');
                                // Apply smart tree display
                                self.applySmartTreeDisplay($correctMenuItem);
                            } else {
                                // Fallback to first menu item if exact match not found
                                $firstMenuItem.addClass('active');
                            }
                        } else {
                            // No category in pagination wrapper, update with first menu item
                            var firstTermId = $firstMenuItem.data('term-id');
                            var firstTaxonomy = $firstMenuItem.data('taxonomy') || 'category';
                            
                            if (firstTermId) {
                                $paginationWrapper.attr('data-category', firstTermId);
                                $paginationWrapper.attr('data-taxonomy', firstTaxonomy);
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Handle filter clicks (unified for products and posts)
         */
        handleFilterClick: function($menuItem, sectionType) {
            var self = this;
            
            // Find the correct section
            var $section;
            if ($menuItem.closest('.vd-filter-panel').length) {
                // Mobile panel - find section by type
                $section = $('.vd-home-' + sectionType);
            } else {
                // Desktop - find closest section
                $section = $menuItem.closest('.vd-home-section');
            }
            
            var $content = $section.find('.vd-main .vd-products-grid, .vd-main .vd-posts-grid').first();
            var $allMenus = $('.vd-sidebar-menu, .vd-filter-panel .vd-sidebar-menu');
            
            // Get data from clicked item or section
            var taxonomy = $menuItem.data('taxonomy') || (sectionType === 'products' ? 'product_cat' : 'category');
            var termId = $menuItem.data('term-id') || '';
            var perPage = $section.data('per-page') || (sectionType === 'products' ? 12 : 9);
            var columns = $section.data('columns') || (sectionType === 'products' ? 4 : 3);
            
            // Get responsive columns data for posts
            var columnsDesktop = $section.data('columns-desktop') || columns;
            var columnsTablet = $section.data('columns-tablet') || (sectionType === 'products' ? 2 : 2);
            var columnsMobile = $section.data('columns-mobile') || 1;
            var showAuthor = $section.data('show-author') || 'yes';
            var showCategory = $section.data('show-category') || 'yes';
            var orderby = $section.data('orderby') || (sectionType === 'products' ? 'menu_order' : 'date');
            var order = $section.data('order') || (sectionType === 'products' ? 'ASC' : 'DESC');
            
            // Update active state in all menus (desktop and mobile)
            $allMenus.find('a').removeClass('active');
            $allMenus.find('.vd-menu-item').removeClass('vd-has-active-child');
            $allMenus.removeClass('has-active-item');
            
            // Only mark the clicked link as active, not all links with same term-id
            $menuItem.addClass('active');
            
            // Mark all parent items as having active child
            $menuItem.parents('.vd-menu-item').addClass('vd-has-active-child');
            $menuItem.closest('.vd-sidebar-menu').addClass('has-active-item');
            
            // Show loading state with skeleton
            self.showLoading($content, columns);
            
            
            // Make AJAX request
            var action = sectionType === 'products' ? 'vidieu_filter_products' : 'vidieu_filter_posts';
            
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                timeout: 30000, // 30 second timeout
                data: {
                    action: action,
                    nonce: vd_home_ajax.nonce,
                    taxonomy: taxonomy,
                    term_id: termId,
                    per_page: perPage,
                    columns: columns,
                    columns_desktop: columnsDesktop,
                    columns_tablet: columnsTablet,
                    columns_mobile: columnsMobile,
                    show_author: showAuthor,
                    show_category: showCategory,
                    orderby: orderby,
                    order: order,
                    paged: 1,
                    section_id: $section.attr('id') || ''
                },
                success: function(response) {
                    $content.removeClass('vd-loading');
                    
                    if (response.success && response.data && response.data.html) {
                        $content.html(response.data.html);
                        
                        // Update filter toggle text
                        self.updateSingleFilterToggle($section);
                        
                        // Update pagination wrapper data attributes with current filter
                        var $newPagination = $content.find('.vd-pagination-wrapper');
                        if ($newPagination.length) {
                            $newPagination.attr('data-category', termId || '');
                            $newPagination.attr('data-taxonomy', taxonomy);
                        }
                        
                        // Trigger custom event for extensions
                        var eventName = 'vd_' + sectionType + '_filtered';
                        var payload = createEventPayload($content, response.data, {
                            filter_type: 'category',
                            term_id: termId
                        });
                        // Use backward compatible event triggering
                        var eventBaseName = eventName.replace('vidieu_', '');
                        triggerCompatibleEvent(eventBaseName, payload);
                        
                        // Re-initialize Elessi theme scripts for new content
                        self.reinitializeElessiScripts($content);
                        
                        // Handle broken images in newly loaded content
                        self.handleBrokenImages($content);
                        
                        // Fix mobile layout after AJAX load
                        self.fixMobileLayout($content);
                    } else {
                        self.showError($content, (response.data && response.data.message) ? response.data.message : vd_home_ajax.error_text);
                    }
                },
                error: function(xhr, status, error) {
                    $content.removeClass('vd-loading');
                    
                    var errorMessage = vd_home_ajax.error_text;
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Server response error. Please try again.';
                    }
                    
                    self.showError($content, errorMessage);
                }
            });
        },
        
        /**
         * Handle load more button clicks
         */
        handleLoadMore: function($button) {
            var $section = $button.closest('.vd-home-section');
            var $content = $button.closest('.vd-main');
            var $grid = $content.find('.vd-products-grid, .vd-posts-grid');
            var $activeMenu = $('.vd-sidebar-menu a.active, .vd-filter-panel .vd-sidebar-menu a.active').first();
            
            var isProducts = $section.hasClass('vd-home-products');
            var action = isProducts ? 'vidieu_filter_products' : 'vidieu_filter_posts';
            var taxonomy = $activeMenu.data('taxonomy') || (isProducts ? 'product_cat' : 'category');
            var termId = $activeMenu.data('term-id') || '';
            var perPage = $section.data('per-page') || (isProducts ? 12 : 9);
            var columns = $section.data('columns') || (isProducts ? 4 : 3);
            var page = parseInt($button.data('page')) || 2;
            var maxPage = parseInt($button.data('max-page')) || 1;
            
            // Get responsive columns data for posts
            var columnsDesktop = $section.data('columns-desktop') || columns;
            var columnsTablet = $section.data('columns-tablet') || (isProducts ? 2 : 2);
            var columnsMobile = $section.data('columns-mobile') || 1;
            var showAuthor = $section.data('show-author') || 'yes';
            var showCategory = $section.data('show-category') || 'yes';
            var orderby = $section.data('orderby') || (isProducts ? 'menu_order' : 'date');
            var order = $section.data('order') || (isProducts ? 'ASC' : 'DESC');
            
            // Disable button and show loading
            $button.prop('disabled', true).text(vd_home_ajax.loading_text);
            
            // Make AJAX request
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: vd_home_ajax.nonce,
                    taxonomy: taxonomy,
                    term_id: termId,
                    per_page: perPage,
                    columns: columns,
                    columns_desktop: columnsDesktop,
                    columns_tablet: columnsTablet,
                    columns_mobile: columnsMobile,
                    show_author: showAuthor,
                    show_category: showCategory,
                    orderby: orderby,
                    order: order,
                    paged: page,
                    load_more: true
                },
                success: function(response) {
                    if (response.success) {
                        // Append new items to grid
                        var $newItems = $(response.data.html).find('.vd-product-item, .vd-post-card');
                        if (isProducts) {
                            $grid.find('.vd-row').append($newItems);
                        } else {
                            // For posts, append directly to grid (CSS Grid layout)
                            $grid.append($newItems);
                        }
                        
                        // Update button
                        if (page >= maxPage) {
                            $button.remove();
                        } else {
                            $button.prop('disabled', false)
                                   .data('page', page + 1)
                                   .text(isProducts ? 'Load More Products' : 'Load More Posts');
                        }
                        
                        // Trigger custom event
                        var $section = $button.closest('.vd-home-section');
                        var payload = createEventPayload($section.find('.vd-content-area'), response.data, {
                            new_items: $newItems,
                            page: page,
                            action: 'load_more'
                        });
                        // Use backward compatible event triggering
                        triggerCompatibleEvent('items_loaded', payload);
                        
                        // Re-initialize scripts for new items (including variant selectors)
                        self.reinitializeElessiScripts($newItems);
                        
                    } else {
                        $button.prop('disabled', false).text('Try Again');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Try Again');
                }
            });
        },
        
        /**
         * Handle pagination clicks
         */
        handlePaginationClick: function($paginationLink) {
            var self = this;
            var $wrapper = $paginationLink.closest('.vd-pagination-wrapper');
            var $section = $wrapper.closest('.vd-home-section');
            var $content = $section.find('.vd-main .vd-products-grid, .vd-main .vd-posts-grid').first();
            var $activeMenu = $('.vd-sidebar-menu a.active, .vd-filter-panel .vd-sidebar-menu a.active').first();
            
            // Get pagination data
            var page = parseInt($paginationLink.data('page')) || 1;
            var sectionType = $wrapper.data('section-type') || 'products';
            var sectionId = $wrapper.data('section-id') || '';
            var ajaxAction = $wrapper.data('ajax-action') || 'vidieu_filter_products';
            var perPage = $wrapper.data('per-page') || (sectionType === 'products' ? 12 : 9);
            var columns = $wrapper.data('columns') || (sectionType === 'products' ? 4 : 3);
            
            // Get additional data for posts
            var showAuthor = $wrapper.data('show-author') === 'yes';
            var showCategory = $wrapper.data('show-category') === 'yes';
            var orderby = $wrapper.data('orderby') || (sectionType === 'products' ? 'menu_order' : 'date');
            var order = $wrapper.data('order') || (sectionType === 'products' ? 'ASC' : 'DESC');
            
            // Get category - prioritize wrapper data first (most accurate for pagination), then active menu
            var category = '';
            
            // First check wrapper data (this should have the correct category for current pagination context)
            category = $wrapper.data('category') || '';
            if (!category && $activeMenu.length) {
                // Fallback to active menu
                category = $activeMenu.data('term-id') || '';
            }
            
            // Final fallback - try to get from section default
            if (!category) {
                var defaultCat = $section.data('default-cat') || '';
                if (defaultCat) {
                    category = defaultCat;
                }
            }
            
            // Get taxonomy from wrapper data attribute first, then fallback to defaults
            var taxonomy = $wrapper.data('taxonomy') || '';
            
            if (!taxonomy) {
                // Fallback to default taxonomy based on section type
                taxonomy = sectionType === 'products' ? 'product_cat' : 'category';
            }
            
            // Override with active menu taxonomy if available and wrapper doesn't have it
            if (!$wrapper.data('taxonomy') && $activeMenu.length && $activeMenu.data('taxonomy')) {
                taxonomy = $activeMenu.data('taxonomy');
            }
            
            
            // Special handling for posts - if no category found, try to get from first menu item
            if (!category && sectionType === 'posts') {
                // Try to get the first category from the sidebar menu
                var $firstMenuItem = $section.find('.vd-sidebar-menu a[data-term-id]').first();
                if ($firstMenuItem.length) {
                    category = $firstMenuItem.data('term-id') || '';
                    if (category) {
                        // Update active state to reflect this choice
                        $section.find('.vd-sidebar-menu a').removeClass('active');
                        $firstMenuItem.addClass('active');
                        
                        // Also update the wrapper data for future requests
                        $wrapper.attr('data-category', category);
                        $wrapper.attr('data-taxonomy', 'category');
                    }
                }
            }
            
            // Validate we have necessary data for the request
            if (!category) {
                self.showError($content, 'Category information is missing. Please try again.');
                return;
            }
            
            // Show loading state
            $wrapper.find('.vd-pagination-loading').show();
            $content.addClass('vd-loading');
            
            // Scroll to top of section
            var headerOffset = 80; // Account for fixed headers
            var sectionTop = $section.offset().top - headerOffset;
            $('html, body').animate({
                scrollTop: sectionTop
            }, 300);
            
            // Make AJAX request
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                timeout: 30000,
                data: {
                    action: ajaxAction,
                    nonce: vd_home_ajax.nonce,
                    taxonomy: taxonomy,
                    term_id: category,
                    per_page: perPage,
                    columns: columns,
                    show_author: showAuthor,
                    show_category: showCategory,
                    orderby: orderby,
                    order: order,
                    paged: page,
                    section_id: sectionId
                },
                success: function(response) {
                    $wrapper.find('.vd-pagination-loading').hide();
                    $content.removeClass('vd-loading');
                    
                    if (response.success && response.data && response.data.html) {
                        $content.html(response.data.html);
                        
                        // Update pagination wrapper data attributes to maintain context
                        var $newPagination = $content.find('.vd-pagination-wrapper');
                        if ($newPagination.length) {
                            $newPagination.attr('data-category', category || '');
                            $newPagination.attr('data-taxonomy', taxonomy);
                        }
                        
                        // Update browser URL without page reload (if supported)
                        if (window.history && window.history.pushState) {
                            var newUrl = self.updateUrlWithPage(page, sectionId);
                            window.history.pushState({page: page, section: sectionId}, '', newUrl);
                        }
                        
                        // Trigger custom event for extensions
                        var eventName = 'vd_' + sectionType + '_page_loaded';
                        var payload = createEventPayload($content, response.data, {
                            page: page,
                            action: 'pagination'
                        });
                        // Use backward compatible event triggering  
                        var eventBaseName = eventName.replace('vidieu_', '');
                        triggerCompatibleEvent(eventBaseName, payload);
                        
                        // Re-initialize theme scripts
                        self.reinitializeElessiScripts($content);
                        
                        // Fix mobile layout after AJAX load
                        self.fixMobileLayout($content);
                        
                    } else {
                        self.showError($content, (response.data && response.data.message) ? response.data.message : vd_home_ajax.error_text);
                    }
                },
                error: function(xhr, status, error) {
                    $wrapper.find('.vd-pagination-loading').hide();
                    $content.removeClass('vd-loading');
                    
                    var errorMessage = vd_home_ajax.error_text;
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Server response error. Please try again.';
                    }
                    
                    self.showError($content, errorMessage);
                }
            });
        },
        
        /**
         * Update URL with page parameter
         */
        updateUrlWithPage: function(page, sectionId) {
            var url = new URL(window.location);
            var pageParam = sectionId ? 'vd_page_' + sectionId : 'paged';
            
            if (page > 1) {
                url.searchParams.set(pageParam, page);
            } else {
                url.searchParams.delete(pageParam);
                url.searchParams.delete('paged'); // Remove generic paged parameter too
            }
            
            return url.toString();
        },
        
        /**
         * Update Buy Now button state based on variation selection
         */
        updateBuyNowButtonState: function($product) {
            var $buyNowBtn = $product.find('.vd-buy-now-button');
            if (!$buyNowBtn.length) return;
            
            // Check if this is a variable product
            if (!$buyNowBtn.hasClass('vd-buy-now-variable')) return;
            
            // Check if all variations are selected
            var allSelected = true;
            var requiredAttributes = 0;
            var selectedAttributes = 0;
            
            // Count required attributes and selected ones
            // First try to find in product card, then check in general content wrap
            var $contentWrap = $product.find('.nasa-product-content-select-wrap');
            if (!$contentWrap.length) {
                $contentWrap = $product.find('.nasa-product-content-variable-warp');
            }
            
            if ($contentWrap.length) {
                $contentWrap.find('.nasa-product-content-child').each(function() {
                    var $wrap = $(this);
                    if ($wrap.find('.nasa-attr-ux-item').length > 0) {
                        requiredAttributes++;
                        if ($wrap.find('.nasa-attr-ux-item.nasa-active').length > 0) {
                            selectedAttributes++;
                        }
                    }
                });
            }
            
            allSelected = (requiredAttributes > 0 && requiredAttributes === selectedAttributes);
            
            // Update button based on selection state
            if (allSelected) {
                // Change to Buy Now
                var buyNowLabel = $buyNowBtn.attr('data-buy-now-label') || 'Mua Ngay';
                $buyNowBtn.text(buyNowLabel);
                $buyNowBtn.attr('data-action', 'buy-now');
                $buyNowBtn.attr('data-variation-selected', 'true');
            } else {
                // Change to Select Options
                var selectLabel = $buyNowBtn.attr('data-select-label') || 'Tùy chọn';
                $buyNowBtn.text(selectLabel);
                $buyNowBtn.attr('data-action', 'select-options');
                $buyNowBtn.attr('data-variation-selected', 'false');
            }
        },
        
        /**
         * Handle Buy Now button clicks
         */
        handleBuyNowClick: function($button) {
            var self = this;
            
            // Prevent double clicks
            if ($button.hasClass('vd-processing')) {
                return false;
            }
            
            $button.addClass('vd-processing');
            
            var productId = $button.data('product-id');
            var productType = $button.data('product-type');
            var action = $button.data('action');
            var variationSelected = $button.attr('data-variation-selected') === 'true';
            
            // Find product section and content
            var $product = $button.closest('.product');
            var $section = $button.closest('.vd-home-section');
            
            // Create event payload
            var payload = createEventPayload($section.find('.vd-main'), {
                product_id: productId,
                product_type: productType,
                button_action: action,
                variation_selected: variationSelected
            }, {
                source: 'buy_now_button'
            });
            
            // Trigger compatible event before action
            triggerCompatibleEvent('buy_now_clicked', payload, productId);
            
            // Handle variable products
            if (productType === 'variable') {
                // Check current action and variation selection state
                if (action === 'select-options' || !variationSelected) {
                    // Open quickview for selection
                    var $quickviewBtn = $product.find('.quick-view');
                    if ($quickviewBtn.length) {
                        // Remove any loader elements before triggering quickview
                        $product.find('.nasa-loader, .nasa-light-fog, .nasa-dark-fog').remove();
                        
                        // Trigger click on quickview button
                        $quickviewBtn.trigger('click');
                        
                        // Wait for quickview to open and setup Buy Now handler
                        setTimeout(function() {
                            self.setupQuickviewBuyNow(productId);
                        }, 500);
                    } else {
                        // Fallback - redirect to product page
                        var productUrl = $product.find('a.product-img').attr('href');
                        if (productUrl) {
                            window.location.href = productUrl;
                        }
                    }
                    
                    $button.removeClass('vd-processing');
                    return;
                } else if (action === 'buy-now' && variationSelected) {
                    // All variations selected - get variation data from product card
                    var variationData = self.getSelectedVariationData($product);
                    
                    if (variationData) {
                        // Proceed with buy now for the selected variation
                        self.processBuyNowWithVariation(productId, variationData, $button);
                        return;
                    } else {
                        // If we can't get variation data, fall back to quickview
                        var $quickviewBtn = $product.find('.quick-view');
                        if ($quickviewBtn.length) {
                            // Remove any loader elements before triggering quickview
                            $product.find('.nasa-loader, .nasa-light-fog, .nasa-dark-fog').remove();
                            
                            $quickviewBtn.trigger('click');
                            setTimeout(function() {
                                self.setupQuickviewBuyNow(productId);
                            }, 500);
                        }
                        $button.removeClass('vd-processing');
                        return;
                    }
                }
            }
            
            // Handle simple products - AJAX add to cart
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vidieu_buy_now',
                    nonce: vd_home_ajax.nonce,
                    product_id: productId,
                    quantity: 1,
                    action_type: action
                },
                success: function(response) {
                    $button.removeClass('vd-processing');
                    
                    if (response.success && response.data) {
                        if (response.data.action === 'redirect' && response.data.redirect_url) {
                            // Keep original button text
                            // $button.text(response.data.message || 'Redirecting...');
                            
                            // Just redirect without changing button text
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 100);
                        } else if (response.data.action === 'open_quickview') {
                            // This shouldn't happen for simple products
                            var $quickviewBtn = $product.find('.quick-view');
                            if ($quickviewBtn.length) {
                                // Remove any loader elements before triggering quickview
                                $product.find('.nasa-loader, .nasa-light-fog, .nasa-dark-fog').remove();
                                
                                $quickviewBtn.trigger('click');
                            }
                        }
                    } else {
                        // Show error message
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    $button.removeClass('vd-processing');
                    
                    var errorMessage = vd_home_ajax.error_text;
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    alert(errorMessage);
                }
            });
        },
        
        /**
         * Setup Buy Now handler in quickview
         */
        setupQuickviewBuyNow: function(productId) {
            var self = this;
            var $quickview = $('#nasa-quickview-sidebar-content, .product-lightbox');
            
            if ($quickview.length) {
                // Override NASA Buy Now button behavior
                $quickview.find('.nasa-buy-now').off('click').on('click', function(e) {
                    e.preventDefault();
                    
                    var $form = $(this).closest('form');
                    var variationId = $form.find('.variation_id').val();
                    
                    if (variationId && variationId !== '0' && variationId !== '') {
                        // Valid variation selected
                        self.processBuyNowFromQuickview($form, productId, variationId);
                    } else {
                        alert('Please select product options.');
                    }
                    
                    return false;
                });
            }
        },
        
        /**
         * Get selected variation data from product card
         */
        getSelectedVariationData: function($product) {
            var variationData = {};
            var hasSelection = false;
            
            // Try multiple selectors to find variation elements
            var $contentWrap = $product.find('.nasa-product-content-select-wrap');
            if (!$contentWrap.length) {
                $contentWrap = $product.find('.nasa-product-content-variable-warp');
            }
            
            var $activeItems = $contentWrap.length ? 
                $contentWrap.find('.nasa-attr-ux-item.nasa-active') : 
                $product.find('.nasa-attr-ux-item.nasa-active');
            
            $activeItems.each(function() {
                var $item = $(this);
                var attrName = $item.data('pa');
                var attrValue = $item.data('value');
                
                if (attrName && attrValue) {
                    // Handle different attribute name formats
                    if (attrName.indexOf('pa_') === 0) {
                        // Already has pa_ prefix
                        variationData['attribute_' + attrName] = attrValue;
                    } else {
                        // Add pa_ prefix for custom attributes
                        variationData['attribute_pa_' + attrName] = attrValue;
                    }
                    hasSelection = true;
                }
            });
            
            // If no data found from active items, try finding from hidden selects (NASA theme sometimes uses them)
            if (!hasSelection) {
                $product.find('select[name^="attribute_"]').each(function() {
                    var $select = $(this);
                    var name = $select.attr('name');
                    var value = $select.val();
                    
                    if (name && value && value !== '') {
                        variationData[name] = value;
                        hasSelection = true;
                    }
                });
            }
            
            return hasSelection ? variationData : null;
        },
        
        /**
         * Process Buy Now with variation data
         */
        processBuyNowWithVariation: function(productId, variationData, $button) {
            var self = this;
            
            // Prepare data with proper format
            var ajaxData = {
                action: 'vidieu_buy_now',
                nonce: vd_home_ajax.nonce,
                product_id: productId,
                quantity: 1,
                action_type: 'buy-now'
            };
            
            // Add variation attributes to main data object
            for (var key in variationData) {
                if (variationData.hasOwnProperty(key)) {
                    ajaxData[key] = variationData[key];
                }
            }
            
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    $button.removeClass('vd-processing');
                    
                    if (response.success && response.data) {
                        if (response.data.action === 'redirect' && response.data.redirect_url) {
                            // Keep original button text
                            // $button.text(response.data.message || 'Redirecting...');
                            
                            // Just redirect without changing button text
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 100);
                        }
                    } else {
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                        alert(errorMsg);
                    }
                },
                error: function() {
                    $button.removeClass('vd-processing');
                    alert('An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Process Buy Now from quickview form
         */
        processBuyNowFromQuickview: function($form, productId, variationId) {
            var self = this;
            
            // Set buy now flag
            $form.find('input[name="nasa_buy_now"]').val('1');
            
            // Get form data
            var formData = $form.serialize();
            
            $.ajax({
                url: vd_home_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=vidieu_buy_now&nonce=' + vd_home_ajax.nonce + '&action_type=buy-now',
                success: function(response) {
                    if (response.success && response.data) {
                        if (response.data.action === 'redirect' && response.data.redirect_url) {
                            // Close quickview
                            if (typeof nasa_close_quickview === 'function') {
                                nasa_close_quickview();
                            }
                            
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 300);
                        }
                    } else {
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                        alert(errorMsg);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Reposition variant selectors to anchor at thumbnail top edge
         */
        repositionVariantSelectors: function() {
            var self = this;
            
            // Find all variant selectors in home products sections
            $('.vd-home-section.vd-home-products .nasa-product-content-variable-warp').each(function() {
                var $variantWrap = $(this);
                var $productCard = $variantWrap.closest('.product');
                var $thumbnailWrap = $productCard.find('.product-img-wrap');
                
                // Only reposition if thumbnail wrapper exists
                if ($thumbnailWrap.length) {
                    // Move variant selector container inside thumbnail wrapper
                    $variantWrap.appendTo($thumbnailWrap);
                }
            });
            
            // Also call after AJAX content loads - handle pagination, category changes, and load more
            $(document).on('vd_products_page_loaded vd_products_filtered vd_items_loaded', function(event, $content) {
                if ($content && $content.length) {
                    self.repositionVariantSelectorsInContainer($content);
                }
            });
            
            // Also handle general content updates
            $(document).on('nasa_after_load nasa_refresh_shop', function() {
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    self.repositionVariantSelectors();
                }, 100);
            });
        },

        /**
         * Reposition variant selectors in a specific container (for AJAX content)
         */
        repositionVariantSelectorsInContainer: function($container) {
            var self = this;
            
            // Find variant selectors in the specific container
            $container.find('.nasa-product-content-variable-warp').each(function() {
                var $variantWrap = $(this);
                var $productCard = $variantWrap.closest('.product');
                var $thumbnailWrap = $productCard.find('.product-img-wrap');
                
                // Only reposition if thumbnail wrapper exists and this variant wrap is not already inside it
                if ($thumbnailWrap.length && !$variantWrap.closest('.product-img-wrap').length) {
                    $variantWrap.appendTo($thumbnailWrap);
                }
            });
        },

        /**
         * Fix layout for first product on initial load
         */
        fixFirstProductLayout: function() {
            var $firstProduct = $('.vd-home-products .products li.product-warp-item:first');
            if ($firstProduct.length) {
                // Force reflow for first product
                $firstProduct.hide().show(0);
                
                // Recalculate image dimensions
                var $img = $firstProduct.find('.product-img-wrap .main-img img');
                if ($img.length && $img[0].complete) {
                    $img.trigger('load');
                }
            }
        },

        /**
         * Handle broken images in product grid
         */
        handleBrokenImages: function($container) {
            $container.find('.product-img-wrap .main-img img').each(function() {
                var $img = $(this);
                var $mainImg = $img.parent('.main-img');
                
                // Create a placeholder image element
                var placeholderSrc = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MDAiIGhlaWdodD0iNjAwIiB2aWV3Qm94PSIwIDAgNTAwIDYwMCI+CiAgPHJlY3Qgd2lkdGg9IjUwMCIgaGVpZ2h0PSI2MDAiIGZpbGw9IiNmOGY4ZjgiLz4KICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBJbWFnZTwvdGV4dD4KPC9zdmc+';
                
                // Add error handler - only run once
                $img.one('error', function() {
                    // Check if already processed to prevent loops
                    if ($(this).hasClass('placeholder-image')) {
                        return;
                    }
                    
                    // Mark as processed before changing src
                    $(this).addClass('placeholder-image image-processed');
                    $mainImg.addClass('has-placeholder');
                    
                    // Replace with placeholder after marking
                    $(this).attr('src', placeholderSrc);
                    
                    // Remove error handler to prevent loops
                    $(this).off('error');
                });
                
                // Add load handler to ensure proper display
                $img.on('load', function() {
                    $mainImg.removeClass('has-placeholder');
                });
                
                // Check if image is already broken
                if (this.complete && this.naturalHeight === 0 && !$(this).hasClass('image-processed')) {
                    $(this).trigger('error');
                }
            });
        },

        /**
         * Re-initialize Elessi theme scripts for AJAX loaded content
         */
        reinitializeElessiScripts: function($container) {
            // Re-initialize WOW animations if available
            if (typeof WOW !== 'undefined') {
                new WOW().init();
            }
            
            // Trigger NASA refresh event for Elessi theme
            if (typeof nasa_refresh_shop === 'function') {
                nasa_refresh_shop();
            }
            
            // Trigger general NASA refresh events
            $(document).trigger('nasa_refresh_shop');
            $(document).trigger('nasa_after_load');
            
            // Reposition variant selectors for AJAX loaded content
            this.repositionVariantSelectorsInContainer($container);
            
            // Re-initialize product hover effects
            if (typeof nasa_init_product_item === 'function') {
                $container.find('.product').each(function() {
                    nasa_init_product_item($(this));
                });
            }
            
            // Re-initialize lazy loading if available
            if (typeof nasa_lazy_load === 'function') {
                nasa_lazy_load($container);
            }
            
            // Re-initialize any tooltips
            if ($.fn.tooltip) {
                $container.find('[data-toggle="tooltip"]').tooltip();
            }
            
            // Re-initialize quick view functionality
            if (typeof nasa_quickview_init === 'function') {
                nasa_quickview_init();
            }
            
            // Re-initialize countdown timers if any
            if (typeof nasa_countdown === 'function') {
                nasa_countdown($container.find('.nasa-countdown'));
            }
            
            // Re-initialize product variations
            if (typeof wc_add_to_cart_variation_params !== 'undefined') {
                $container.find('.variations_form').each(function() {
                    $(this).wc_variation_form();
                });
            }
            
            // Re-initialize Buy Now buttons for variable products
            if (typeof self.updateBuyNowButtonState === 'function') {
                $container.find('.vd-buy-now-button.vd-buy-now-variable').each(function() {
                    var $button = $(this);
                    var $product = $button.closest('.product');
                    self.updateBuyNowButtonState($product);
                });
            }
            
        },
        
        /**
         * Show loading state
         */
        showLoading: function($container, columns) {
            $container.addClass('vd-loading');
            
            // Get columns from container if not provided
            if (!columns) {
                var $section = $container.closest('.vd-home-section');
                columns = $section.data('columns') || 4;
            }
            
            // Determine if this is posts or products
            var isPostsGrid = $container.hasClass('vd-posts-grid');
            
            // Create skeleton placeholder
            var skeletonHtml = isPostsGrid ? this.createPostsSkeleton(columns) : this.createSkeleton(columns);
            $container.html(skeletonHtml);
        },
        
        /**
         * Create skeleton loading HTML
         */
        createSkeleton: function(columns) {
            columns = columns || 4;
            var itemClass = this.getColumnClass(columns);
            
            var html = '<ul class="products columns-' + columns + '">';
            for (var i = 0; i < columns * 2; i++) {
                html += '<li class="' + itemClass + ' product-warp-item">';
                html += '<div class="vd-skeleton-card product">';
                html += '<div class="vd-skeleton vd-skeleton-image"></div>';
                html += '<div class="vd-skeleton-content">';
                html += '<div class="vd-skeleton vd-skeleton-title"></div>';
                html += '<div class="vd-skeleton vd-skeleton-text"></div>';
                html += '<div class="vd-skeleton vd-skeleton-text"></div>';
                html += '<div class="vd-skeleton vd-skeleton-button"></div>';
                html += '</div>';
                html += '</div>';
                html += '</li>';
            }
            html += '</ul>';
            return html;
        },
        
        /**
         * Create posts skeleton loading HTML
         */
        createPostsSkeleton: function(columns) {
            columns = columns || 3;
            
            var html = '<div class="vd-posts-grid columns-' + columns + '" style="--vd-posts-cols: ' + columns + ';">';
            for (var i = 0; i < columns * 2; i++) {
                html += '<article class="vd-skeleton-post-card">';
                html += '<div class="vd-skeleton-post-thumb vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-body">';
                html += '<div class="vd-skeleton-post-meta vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-title vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-title vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-excerpt vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-excerpt vd-skeleton"></div>';
                html += '<div class="vd-skeleton-post-footer vd-skeleton"></div>';
                html += '</div>';
                html += '</article>';
            }
            html += '</div>';
            return html;
        },
        
        /**
         * Get column class based on column count
         */
        getColumnClass: function(columns) {
            var classes = 'product-warp-item';
            
            // Add responsive classes
            switch (parseInt(columns)) {
                case 6:
                    classes += ' large-2 medium-3 small-6';
                    break;
                case 5:
                    classes += ' large-2-4 medium-4 small-6';
                    break;
                case 4:
                    classes += ' large-3 medium-4 small-6';
                    break;
                case 3:
                    classes += ' large-4 medium-6 small-12';
                    break;
                case 2:
                    classes += ' large-6 medium-6 small-12';
                    break;
                case 1:
                    classes += ' large-12 medium-12 small-12';
                    break;
                default:
                    classes += ' large-3 medium-4 small-6';
            }
            
            return classes;
        },
        
        /**
         * Show error message
         */
        showError: function($container, message) {
            $container.removeClass('vd-loading');
            var errorHtml = '<div class="vd-notice vd-error">' + 
                           '<p>' + (message || vd_home_ajax.error_text) + '</p>' +
                           '</div>';
            $container.html(errorHtml);
        },
        
        /**
         * Fix mobile layout after AJAX load
         */
        fixMobileLayout: function($content) {
            // Only apply on mobile (max-width: 767px)
            if (window.innerWidth <= 767) {
                var $main = $content.closest('.vd-main');
                var $section = $content.closest('.vd-home-section');
                
                // Force correct mobile layout
                $main.css({
                    'flex': '1 1 100%',
                    'max-width': '100%',
                    'width': '100%'
                });
                
                // For products section, ensure proper styling without breaking theme features
                if ($section.hasClass('vd-home-products')) {
                    // Just ensure full width without removing all styles
                    var $productsList = $main.find('ul.products');
                    if ($productsList.length) {
                        // Only set width properties, preserve other styles
                        $productsList.css({
                            'width': '100%',
                            'max-width': '100%'
                        });
                    }
                }
                
                // Trigger a resize event to ensure other scripts adapt
                setTimeout(function() {
                    $(window).trigger('resize');
                    // Also trigger Elessi specific resize event
                    $(window).trigger('nasa_resize');
                }, 100);
            }
        },
        
        /**
         * Apply smart tree display - show only relevant categories
         */
        applySmartTreeDisplay: function($activeLink) {
            if (!$activeLink || !$activeLink.length) return;
            
            var $menuItem = $activeLink.closest('.vd-menu-item');
            var $rootMenu = $activeLink.closest('.vd-sidebar-menu');
            
            
            // Use CSS transitions for smooth animation
            $rootMenu.addClass('vd-transitioning');
            
            // First, collect all items to show
            var itemsToShow = [];
            
            // Add active item
            itemsToShow.push($menuItem[0]);
            
            // Add direct children of active item BEFORE resetting
            var $directChildren = $menuItem.find('> .vd-submenu > .vd-menu-item');
            $directChildren.each(function() {
                itemsToShow.push(this);
            });
            
            // Add all parents up to root
            var $currentParent = $menuItem.parent().closest('.vd-menu-item');
            while ($currentParent.length) {
                itemsToShow.push($currentParent[0]);
                $currentParent = $currentParent.parent().closest('.vd-menu-item');
            }
            
            // Now reset and apply changes
            $rootMenu.find('.vd-menu-item').removeClass('vd-hidden vd-expanded');
            $rootMenu.find('.vd-submenu').hide();
            
            // Hide items not in show list
            $rootMenu.find('.vd-menu-item').each(function() {
                if (itemsToShow.indexOf(this) === -1) {
                    $(this).addClass('vd-hidden');
                }
            });
            
            // Expand items in active path
            $menuItem.parents('.vd-menu-item').each(function() {
                var $parent = $(this);
                $parent.addClass('vd-expanded');
                $parent.find('> .vd-submenu').show();
            });
            
            // Expand active item to show its children
            if ($menuItem.hasClass('vd-has-children')) {
                $menuItem.addClass('vd-expanded');
                var $submenu = $menuItem.find('> .vd-submenu');
                $submenu.show();
            }
            
            // Remove transitioning class after animation
            setTimeout(function() {
                $rootMenu.removeClass('vd-transitioning');
            }, 300);
        }
    };
    
    // Expose to global scope for extensibility FIRST
    window.VidieuHomeSections = VidieuHomeSections;
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Use a small delay to ensure all scripts are loaded
        setTimeout(function() {
            VidieuHomeSections.init();
            
            // Sync product info heights after initialization
            syncProductInfoHeights();
            
            // Re-sync on window resize
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    syncProductInfoHeights();
                }, 250);
            });
        }, 50);
    });
    
})(jQuery);