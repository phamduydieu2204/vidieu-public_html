<?php
/**
 * Fix Cart Count Display Issue
 * Ensures cart count is always visible when there are items in cart
 * 
 * @package Elessi-theme-child
 * @since 2025-08-31
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add inline CSS to ensure cart count is visible with proper styling
 */
add_action('wp_head', 'vidieu_fix_cart_count_css', 999);
function vidieu_fix_cart_count_css() {
    ?>
    <style id="vidieu-cart-count-fix">
        /* Apply wishlist-style classes to cart count - match wishlist exactly */
        .nasa-cart-count,
        .cart-number {
            /* Copy wishlist styles exactly */
            position: absolute;
            top: -8px;
            right: -12px;
            background-color: #F76B6A !important; /* Match wishlist color */
            color: #fff;
            width: 17px !important; /* Match wishlist size */
            height: 17px !important; /* Match wishlist size */
            line-height: 17px !important; /* Match wishlist line-height */
            text-align: center;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
            display: inline-block;
            font-family: inherit;
        }
        
        /* Ensure cart count is visible when it has items */
        .nasa-cart-count.nasa-mini-number:not(:empty),
        .cart-number:not(:empty) {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Hide when empty or zero */
        .nasa-cart-count.nasa-mini-number:empty,
        .cart-number:empty,
        .nasa-cart-count.nasa-mini-number.nasa-product-empty,
        .cart-number.nasa-product-empty {
            display: none !important;
        }
        
        /* Make cart count match wishlist count exactly */
        .mini-icon-cart .cart-number,
        .cart-link .cart-number {
            position: absolute;
            top: -8px;
            right: -12px;
        }
    </style>
    <?php
}

/**
 * Add inline script in header for instant cart count
 */
add_action('wp_head', 'vidieu_instant_cart_count_js', 999);
function vidieu_instant_cart_count_js() {
    if (!is_admin()) {
        ?>
        <script id="vidieu-instant-cart-count">
        // Instant cart count display (like wishlist)
        (function() {
            var count = localStorage.getItem('vidieu_cart_count_instant');
            var countTime = localStorage.getItem('vidieu_cart_count_instant_time');
            
            if (count !== null && countTime) {
                var age = Date.now() - parseInt(countTime);
                if (age < 3600000) { // Less than 1 hour old
                    // Create style to show count instantly
                    var style = document.createElement('style');
                    style.textContent = '.nasa-cart-count:not(:empty), .cart-number:not(:empty) { display: inline-block !important; }';
                    document.head.appendChild(style);
                    
                    // Wait for elements to be available
                    var attempts = 0;
                    var showCount = function() {
                        var elements = document.querySelectorAll('.nasa-cart-count, .cart-number');
                        if (elements.length > 0) {
                            elements.forEach(function(el) {
                                // Add wishlist-style classes
                                if (!el.classList.contains('nasa-mini-number')) {
                                    el.classList.add('nasa-cart-count', 'nasa-mini-number');
                                }
                                
                                if (parseInt(count) > 0) {
                                    el.textContent = count;
                                    el.classList.remove('nasa-product-empty');
                                } else {
                                    el.textContent = '';
                                    el.classList.add('nasa-product-empty');
                                }
                            });
                        } else if (attempts < 10) {
                            attempts++;
                            setTimeout(showCount, 50);
                        }
                    };
                    
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', showCount);
                    } else {
                        showCount();
                    }
                }
            }
        })();
        </script>
        <?php
    }
}

/**
 * Add JavaScript to update cart count on page load
 */
add_action('wp_footer', 'vidieu_fix_cart_count_js', 999);
function vidieu_fix_cart_count_js() {
    if (!is_admin()) {
        ?>
        <script id="vidieu-cart-count-fix-js">
        (function() {
            'use strict';
            
            // Store cart count in localStorage for instant access
            var CART_COUNT_KEY = 'vidieu_cart_count_instant';
            
            // Function to get instant cart count from storage
            function getInstantCartCount() {
                // First check localStorage for instant display
                var instantCount = localStorage.getItem(CART_COUNT_KEY);
                var countTime = localStorage.getItem(CART_COUNT_KEY + '_time');
                
                if (instantCount !== null && countTime) {
                    var age = Date.now() - parseInt(countTime);
                    // Use cached count if less than 1 hour old
                    if (age < 3600000) {
                        return parseInt(instantCount) || 0;
                    }
                }
                return null;
            }
            
            // Function to update cart count
            function updateCartCount(forceRefresh) {
                
                // Show instant count first (like wishlist)
                if (!forceRefresh) {
                    var instantCount = getInstantCartCount();
                    if (instantCount !== null) {
                        updateCartCountDisplay(instantCount, true);
                        // Continue to verify with server in background
                    }
                }
                
                // Then try to get from fragments in session (WooCommerce standard)
                var fragments = sessionStorage.getItem('wc_fragments');
                
                if (fragments) {
                    try {
                        var fragmentsData = JSON.parse(fragments);
                        
                        // Look for cart count in various possible locations
                        var count = 0;
                        var found = false;
                        
                        // Check .nasa-cart-count
                        if (fragmentsData['.nasa-cart-count']) {
                            var countMatch = fragmentsData['.nasa-cart-count'].match(/>([0-9]+)</);;
                            if (countMatch) {
                                count = parseInt(countMatch[1]) || 0;
                                found = true;
                            }
                        }
                        
                        // Check .cart-inner for count
                        if (!found && fragmentsData['.cart-inner']) {
                            var cartInnerMatch = fragmentsData['.cart-inner'].match(/nasa-cart-count[^>]*>([0-9]+)</);;
                            if (cartInnerMatch) {
                                count = parseInt(cartInnerMatch[1]) || 0;
                                found = true;
                            }
                        }
                        
                        if (found) {
                            updateCartCountDisplay(count);
                            return;
                        }
                    } catch (e) {
                    }
                }
                
                // Additional check: if we're on cart page and cart is empty, clear count
                if (document.body.classList.contains('woocommerce-cart')) {
                    var emptyMessage = document.querySelector('.cart-empty');
                    if (emptyMessage) {
                        // Cart is empty, clear all storage
                        localStorage.removeItem(CART_COUNT_KEY);
                        localStorage.removeItem(CART_COUNT_KEY + '_time');
                        sessionStorage.removeItem('vidieu_cart_count');
                        sessionStorage.removeItem('wc_fragments');
                        updateCartCountDisplay(0);
                        return;
                    }
                }
                
                // Additional check: if we're on thank you page, clear count
                if (document.body.classList.contains('woocommerce-order-received')) {
                    // Order completed, clear all storage
                    localStorage.removeItem(CART_COUNT_KEY);
                    localStorage.removeItem(CART_COUNT_KEY + '_time');
                    sessionStorage.removeItem('vidieu_cart_count');
                    sessionStorage.removeItem('wc_fragments');
                    updateCartCountDisplay(0);
                    return;
                }
                
                // Try custom storage
                var cart_count_data = sessionStorage.getItem('vidieu_cart_count');
                
                if (cart_count_data && cart_count_data !== 'undefined') {
                    var count = parseInt(cart_count_data) || 0;
                    updateCartCountDisplay(count);
                } else {
                    // Fallback: get cart count via AJAX
                    getCartCountViaAjax();
                }
            }
            
            // Function to update cart count display
            function updateCartCountDisplay(count, skipStorage) {
                
                var cartCountElements = document.querySelectorAll('.nasa-cart-count, .cart-number');
                
                cartCountElements.forEach(function(element) {
                    // Add wishlist-style classes to match
                    if (!element.classList.contains('nasa-mini-number')) {
                        element.classList.add('nasa-cart-count', 'nasa-mini-number');
                    }
                    
                    if (count > 0) {
                        element.textContent = count;
                        element.classList.remove('nasa-product-empty');
                        // Let CSS handle the display
                    } else {
                        element.textContent = '';
                        element.classList.add('nasa-product-empty');
                    }
                });
                
                // Store in both session and local storage for instant access
                if (!skipStorage) {
                    sessionStorage.setItem('vidieu_cart_count', count);
                    localStorage.setItem(CART_COUNT_KEY, count);
                    localStorage.setItem(CART_COUNT_KEY + '_time', Date.now());
                }
            }
            
            // Function to get cart count via AJAX
            function getCartCountViaAjax() {
                if (typeof jQuery === 'undefined') {
                    return;
                }
                
                // Try to find ajax URL from various sources
                var ajaxUrl = '';
                if (typeof wc_cart_fragments_params !== 'undefined') {
                    ajaxUrl = wc_cart_fragments_params.ajax_url;
                } else if (typeof woocommerce_params !== 'undefined') {
                    ajaxUrl = woocommerce_params.ajax_url;
                } else if (typeof nasa_ajax_params !== 'undefined') {
                    ajaxUrl = nasa_ajax_params.ajax_url;
                } else {
                    // Fallback to wp-admin/admin-ajax.php
                    ajaxUrl = '/wp-admin/admin-ajax.php';
                }
                
                
                jQuery.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vidieu_get_cart_count'
                    },
                    success: function(response) {
                        if (response.success && typeof response.data.count !== 'undefined') {
                            var count = parseInt(response.data.count) || 0;
                            updateCartCountDisplay(count);
                        }
                    },
                    error: function(xhr, status, error) {
                    }
                });
            }
            
            // Listen for WooCommerce cart updates
            if (typeof jQuery !== 'undefined') {
                jQuery(document).on('added_to_cart removed_from_cart', function(event, fragments) {
                    
                    // Extract count from fragments if available
                    if (fragments) {
                        var count = 0;
                        var found = false;
                        
                        // Check different possible fragment keys
                        if (fragments['.nasa-cart-count']) {
                            var match = fragments['.nasa-cart-count'].match(/>([0-9]+)</);;
                            if (match) {
                                count = parseInt(match[1]) || 0;
                                found = true;
                            }
                        }
                        
                        if (!found && fragments['.cart-inner']) {
                            var match = fragments['.cart-inner'].match(/nasa-cart-count[^>]*>([0-9]+)</);;
                            if (match) {
                                count = parseInt(match[1]) || 0;
                                found = true;
                            }
                        }
                        
                        if (found) {
                            updateCartCountDisplay(count);
                            return;
                        }
                    }
                    
                    // Fallback: Wait a bit for DOM to update
                    setTimeout(function() {
                        var cartCount = jQuery('.nasa-cart-count:visible:first').text();
                        if (cartCount && cartCount !== '') {
                            var count = parseInt(cartCount) || 0;
                            updateCartCountDisplay(count);
                        }
                    }, 200);
                });
                
                // Also listen for fragment refresh
                jQuery(document).on('wc_fragments_refreshed', function() {
                    var cartCount = jQuery('.nasa-cart-count:first').text();
                    if (cartCount && cartCount !== '') {
                        updateCartCountDisplay(parseInt(cartCount) || 0);
                    }
                });
                
                // Listen for cart totals update
                jQuery(document).on('updated_cart_totals', function() {
                    setTimeout(function() {
                        var cartCount = jQuery('.nasa-cart-count:first').text();
                        if (cartCount && cartCount !== '') {
                            updateCartCountDisplay(parseInt(cartCount) || 0);
                        }
                    }, 100);
                });
            }
            
            // Initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    // Show instant count immediately
                    var instantCount = getInstantCartCount();
                    if (instantCount !== null) {
                        updateCartCountDisplay(instantCount, true);
                    }
                    // Then verify with server
                    updateCartCount();
                });
            } else {
                // Show instant count immediately
                var instantCount = getInstantCartCount();
                if (instantCount !== null) {
                    updateCartCountDisplay(instantCount, true);
                }
                // Then verify with server
                updateCartCount();
            }
            
            // Also run after a short delay to catch any dynamic updates
            setTimeout(function() {
                updateCartCount(true);
            }, 500);
            
            // Expose updateCartCount globally for manual refresh
            window.updateCartCount = updateCartCount;
            
        })();
        </script>
        <?php
    }
}

/**
 * Add AJAX handler to get cart count
 */
add_action('wp_ajax_vidieu_get_cart_count', 'vidieu_ajax_get_cart_count');
add_action('wp_ajax_nopriv_vidieu_get_cart_count', 'vidieu_ajax_get_cart_count');

function vidieu_ajax_get_cart_count() {
    $count = 0;
    
    if (WC()->cart) {
        $count = WC()->cart->get_cart_contents_count();
    }
    
    wp_send_json_success(array('count' => $count));
}

/**
 * Clear cart count storage on thank you page
 */
add_action('woocommerce_thankyou', 'vidieu_clear_cart_count_on_thankyou', 1);
function vidieu_clear_cart_count_on_thankyou($order_id) {
    ?>
    <script>
    // Clear cart count after successful order
    (function() {
        if (typeof(Storage) !== "undefined") {
            localStorage.removeItem('vidieu_cart_count_instant');
            localStorage.removeItem('vidieu_cart_count_instant_time');
            sessionStorage.removeItem('vidieu_cart_count');
            sessionStorage.removeItem('wc_fragments');
            
            // Update display
            var cartCountElements = document.querySelectorAll('.nasa-cart-count, .cart-number');
            cartCountElements.forEach(function(element) {
                element.textContent = '';
                element.classList.add('nasa-product-empty');
            });
        }
    })();
    </script>
    <?php
}

/**
 * Force refresh cart fragments on specific pages
 */
add_action('wp_footer', 'vidieu_force_cart_fragments_refresh');
function vidieu_force_cart_fragments_refresh() {
    // Only on specific pages where issue occurs
    if (is_shop() || is_cart() || is_checkout() || is_front_page()) {
        ?>
        <script>
        // Force cart fragments refresh on these pages
        if (typeof jQuery !== 'undefined') {
            jQuery(function($) {
                // Trigger fragment refresh if available
                if (typeof wc_cart_fragments_params !== 'undefined') {
                    $(document.body).trigger('wc_fragment_refresh');
                } else {
                    // Manual refresh via AJAX
                    setTimeout(function() {
                        if (window.updateCartCount) {
                            window.updateCartCount();
                        }
                    }, 500);
                }
            });
        }
        </script>
        <?php
    }
    
    // Special handling for order received page
    if (is_order_received_page()) {
        ?>
        <script>
        // Clear cart count on order received page
        if (typeof(Storage) !== "undefined") {
            localStorage.removeItem('vidieu_cart_count_instant');
            localStorage.removeItem('vidieu_cart_count_instant_time');
            sessionStorage.removeItem('vidieu_cart_count');
            sessionStorage.removeItem('wc_fragments');
        }
        </script>
        <?php
    }
}

/**
 * Ensure WooCommerce cart fragments script is loaded
 */
add_action('wp_enqueue_scripts', 'vidieu_ensure_cart_fragments_script', 99);
function vidieu_ensure_cart_fragments_script() {
    // Load cart fragments script on all pages (not just cart/checkout)
    if (!is_admin()) {
        wp_enqueue_script('wc-cart-fragments');
    }
}

/**
 * Ensure cart count is included in fragments
 */
add_filter('woocommerce_add_to_cart_fragments', 'vidieu_cart_count_fragments', 999);
function vidieu_cart_count_fragments($fragments) {
    // Get cart count
    $count = WC()->cart->get_cart_contents_count();
    
    // Add NASA cart count to fragments with wishlist-style classes
    ob_start();
    $class = $count > 0 ? 'nasa-cart-count nasa-mini-number cart-number' : 'nasa-cart-count nasa-mini-number cart-number nasa-product-empty';
    ?>
    <span class="<?php echo esc_attr($class); ?>"><?php echo $count > 0 ? esc_html($count) : ''; ?></span>
    <?php
    $fragments['.nasa-cart-count'] = ob_get_clean();
    
    // Update standalone cart-number with wishlist-style classes
    ob_start();
    $class = $count > 0 ? 'nasa-cart-count nasa-mini-number cart-number' : 'nasa-cart-count nasa-mini-number cart-number nasa-product-empty';
    ?>
    <span class="<?php echo esc_attr($class); ?>"><?php echo $count > 0 ? esc_html($count) : ''; ?></span>
    <?php
    $fragments['.cart-number'] = ob_get_clean();
    
    return $fragments;
}

/**
 * Add filter to modify cart count HTML structure
 */
add_action('init', 'vidieu_modify_cart_count_classes');
function vidieu_modify_cart_count_classes() {
    // Hook into NASA theme cart display
    add_filter('nasa_cart_content_html', 'vidieu_add_wishlist_classes_to_cart', 10, 2);
}

function vidieu_add_wishlist_classes_to_cart($html, $count) {
    // Replace cart-number class with wishlist-style classes
    $html = str_replace(
        'class="cart-number"',
        'class="nasa-cart-count nasa-mini-number cart-number"',
        $html
    );
    
    return $html;
}