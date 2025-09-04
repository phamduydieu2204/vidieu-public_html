/**
 * Optimized AJAX Handler for Vidieu Home Sections
 * 
 * Implements request debouncing, caching, and performance optimizations
 * to reduce AJAX response times and improve user experience
 */

(function($) {
    'use strict';
    
    // Performance optimization module
    window.VidieuAjaxOptimized = {
        
        // Configuration
        config: {
            debounceDelay: 300, // ms
            cacheExpiration: 300000, // 5 minutes
            maxCacheSize: 50, // Maximum cached responses
            enableClientCache: true,
            enableRequestQueue: true,
            enableImagePreload: true
        },
        
        // State management
        state: {
            activeRequests: new Map(),
            cache: new Map(),
            requestQueue: [],
            isProcessing: false
        },
        
        /**
         * Initialize optimizations
         */
        init: function() {
            this.bindEvents();
            this.setupImageLazyLoading();
            this.preloadCriticalData();
            
            // Monitor performance
            if (window.performance && window.performance.mark) {
                window.performance.mark('vd-ajax-init');
            }
        },
        
        /**
         * Bind optimized event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Debounced filter handler
            var debouncedFilter = this.debounce(function(params) {
                self.filterProducts(params);
            }, this.config.debounceDelay);
            
            // Category filter with debouncing
            $(document).on('click', '.vd-sidebar-menu a[data-term-id]', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var params = self.extractParams($link);
                
                // Cancel any pending requests
                self.cancelPendingRequests(params.section_id);
                
                // Update UI immediately
                $('.vd-sidebar-menu a').removeClass('active');
                $link.addClass('active');
                
                // Show loading state
                self.showLoadingState(params.section_id);
                
                // Execute with debouncing
                debouncedFilter(params);
            });
            
            // Pagination with request cancellation
            $(document).on('click', '.vd-pagination-wrapper a.page-numbers', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var params = self.extractPaginationParams($link);
                
                // Immediate feedback
                $link.addClass('loading');
                
                // Execute without debouncing for pagination
                self.filterProducts(params);
            });
        },
        
        /**
         * Optimized product filtering
         */
        filterProducts: function(params) {
            var self = this;
            
            // Check client-side cache first
            var cacheKey = this.generateCacheKey(params);
            var cachedData = this.getFromCache(cacheKey);
            
            if (cachedData) {
                this.renderProducts(cachedData, params);
                return;
            }
            
            // Mark performance
            if (window.performance) {
                window.performance.mark('vd-ajax-start');
            }
            
            // Prepare request
            var request = $.ajax({
                url: vidieu_ajax.ajax_url,
                type: 'POST',
                data: $.extend({
                    action: 'vidieu_filter_products',
                    nonce: vidieu_ajax.nonce
                }, params),
                beforeSend: function(xhr) {
                    // Add performance headers
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    
                    // Store active request
                    self.state.activeRequests.set(params.section_id, xhr);
                    
                    // Cancel previous request for same section
                    var prevRequest = self.state.activeRequests.get(params.section_id);
                    if (prevRequest && prevRequest !== xhr) {
                        prevRequest.abort();
                    }
                }
            });
            
            // Handle response
            request.done(function(response) {
                if (response.success) {
                    // Cache successful response
                    self.saveToCache(cacheKey, response.data);
                    
                    // Render products
                    self.renderProducts(response.data, params);
                    
                    // Preload next page
                    if (params.page < response.data.max_pages) {
                        self.preloadNextPage(params);
                    }
                    
                    // Performance measurement
                    if (window.performance) {
                        window.performance.mark('vd-ajax-end');
                        window.performance.measure('vd-ajax-duration', 'vd-ajax-start', 'vd-ajax-end');
                        
                        var measure = window.performance.getEntriesByName('vd-ajax-duration')[0];
                    }
                }
            }).fail(function(xhr) {
                if (xhr.statusText !== 'abort') {
                    self.showError(params.section_id);
                }
            }).always(function() {
                self.state.activeRequests.delete(params.section_id);
                self.hideLoadingState(params.section_id);
            });
        },
        
        /**
         * Render products with optimizations
         */
        renderProducts: function(data, params) {
            var $section = $('#' + params.section_id);
            var $container = $section.find('.vd-products-grid');
            var $pagination = $section.find('.vd-pagination-wrapper');
            
            // Use requestAnimationFrame for smooth rendering
            requestAnimationFrame(function() {
                // Update content
                $container.html(data.html);
                
                // Update pagination if exists
                if (data.pagination) {
                    $pagination.html(data.pagination);
                }
                
                // Lazy load images
                if ('IntersectionObserver' in window) {
                    var images = $container.find('img[loading="lazy"]');
                    images.each(function() {
                        var img = this;
                        var observer = new IntersectionObserver(function(entries) {
                            if (entries[0].isIntersecting) {
                                img.src = img.dataset.src || img.src;
                                observer.unobserve(img);
                            }
                        });
                        observer.observe(img);
                    });
                }
                
                // Trigger custom event
                $(document).trigger('vd:products:rendered', [data, params]);
            });
        },
        
        /**
         * Cache management
         */
        generateCacheKey: function(params) {
            return 'vd_' + JSON.stringify(params);
        },
        
        getFromCache: function(key) {
            if (!this.config.enableClientCache) return null;
            
            var cached = this.state.cache.get(key);
            if (cached && Date.now() - cached.timestamp < this.config.cacheExpiration) {
                return cached.data;
            }
            
            // Remove expired entry
            if (cached) {
                this.state.cache.delete(key);
            }
            
            return null;
        },
        
        saveToCache: function(key, data) {
            if (!this.config.enableClientCache) return;
            
            // Limit cache size
            if (this.state.cache.size >= this.config.maxCacheSize) {
                // Remove oldest entry
                var firstKey = this.state.cache.keys().next().value;
                this.state.cache.delete(firstKey);
            }
            
            this.state.cache.set(key, {
                data: data,
                timestamp: Date.now()
            });
        },
        
        /**
         * Preload next page for faster navigation
         */
        preloadNextPage: function(currentParams) {
            var nextParams = $.extend({}, currentParams, {
                page: currentParams.page + 1
            });
            
            var cacheKey = this.generateCacheKey(nextParams);
            
            // Skip if already cached
            if (this.getFromCache(cacheKey)) return;
            
            // Preload in background with low priority
            setTimeout(function() {
                $.ajax({
                    url: vidieu_ajax.ajax_url,
                    type: 'POST',
                    data: $.extend({
                        action: 'vidieu_filter_products',
                        nonce: vidieu_ajax.nonce
                    }, nextParams),
                    success: function(response) {
                        if (response.success) {
                            VidieuAjaxOptimized.saveToCache(cacheKey, response.data);
                        }
                    }
                });
            }, 1000);
        },
        
        /**
         * UI State Management
         */
        showLoadingState: function(sectionId) {
            var $section = $('#' + sectionId);
            $section.find('.vd-products-grid').addClass('vd-loading');
        },
        
        hideLoadingState: function(sectionId) {
            var $section = $('#' + sectionId);
            $section.find('.vd-products-grid').removeClass('vd-loading');
        },
        
        showError: function(sectionId) {
            var $section = $('#' + sectionId);
            var $container = $section.find('.vd-products-grid');
            
            $container.html(
                '<div class="vd-error-message">' +
                    '<p>Unable to load products. Please try again.</p>' +
                    '<button class="vd-retry-button">Retry</button>' +
                '</div>'
            );
        },
        
        /**
         * Cancel pending requests
         */
        cancelPendingRequests: function(sectionId) {
            var request = this.state.activeRequests.get(sectionId);
            if (request && request.readyState !== 4) {
                request.abort();
                this.state.activeRequests.delete(sectionId);
            }
        },
        
        /**
         * Extract parameters from elements
         */
        extractParams: function($element) {
            var $section = $element.closest('.vd-home-section');
            var sectionType = $section.hasClass('vd-home-products') ? 'products' : 'posts';
            
            return {
                category: $element.data('term-id') || '',
                taxonomy: $element.data('taxonomy') || (sectionType === 'products' ? 'product_cat' : 'category'),
                section_id: $section.attr('id'),
                section_type: sectionType,
                page: 1,
                per_page: $section.data('per-page') || (sectionType === 'products' ? 12 : 9),
                columns: $section.data('columns') || (sectionType === 'products' ? 4 : 3)
            };
        },
        
        extractPaginationParams: function($element) {
            var $wrapper = $element.closest('.vd-pagination-wrapper');
            return {
                category: $wrapper.data('category') || '',
                taxonomy: $wrapper.data('taxonomy') || 'product_cat',
                section_id: $wrapper.data('section-id'),
                page: $element.data('page') || 1,
                per_page: $wrapper.data('per-page') || 12,
                columns: $wrapper.data('columns') || 4
            };
        },
        
        /**
         * Setup lazy loading for images
         */
        setupImageLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            img.src = img.dataset.src || img.src;
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });
                
                // Observe all lazy images
                document.querySelectorAll('img[loading="lazy"]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },
        
        /**
         * Preload critical data on page load
         */
        preloadCriticalData: function() {
            // Preload first category for each section
            $('.vd-home-section').each(function() {
                var $section = $(this);
                var $firstCategory = $section.find('.vd-sidebar-menu a:first');
                
                if ($firstCategory.length) {
                    var params = VidieuAjaxOptimized.extractParams($firstCategory);
                    var cacheKey = VidieuAjaxOptimized.generateCacheKey(params);
                    
                    // Preload if not cached
                    if (!VidieuAjaxOptimized.getFromCache(cacheKey)) {
                        setTimeout(function() {
                            $.ajax({
                                url: vidieu_ajax.ajax_url,
                                type: 'POST',
                                data: $.extend({
                                    action: 'vidieu_filter_products',
                                    nonce: vidieu_ajax.nonce
                                }, params),
                                success: function(response) {
                                    if (response.success) {
                                        VidieuAjaxOptimized.saveToCache(cacheKey, response.data);
                                    }
                                }
                            });
                        }, 2000); // Delay to not interfere with initial page load
                    }
                }
            });
        },
        
        /**
         * Utility: Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize when DOM ready
    $(document).ready(function() {
        VidieuAjaxOptimized.init();
    });
    
})(jQuery);