<?php
/**
 * Performance Optimization for Vidieu.vn
 * Version: 1.0
 * Author: Performance Team
 * 
 * IMPORTANT: Test thoroughly after enabling each section
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. REMOVE UNNECESSARY SCRIPTS & STYLES
 */
add_action('wp_enqueue_scripts', function() {
    // Remove Kaspersky Labs resources (816KB bloat)
    wp_dequeue_script('kaspersky-labs');
    wp_dequeue_style('kaspersky-labs-css');
    
    // Remove emoji support (not needed)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    
    // Remove WordPress embed
    wp_deregister_script('wp-embed');
    
    // Remove block library CSS if not using Gutenberg
    if (!is_admin()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-blocks-style'); // WooCommerce blocks
    }
}, 100);

/**
 * 2. DEFER NON-CRITICAL SCRIPTS
 */
add_filter('script_loader_tag', function($tag, $handle, $src) {
    // Scripts to defer
    $defer_scripts = [
        'nasa-core-min-js',
        'elessi-theme-js',
        'vidieu-home-js',
        'wc-add-to-cart',
        'wc-cart-fragments',
        'instagram-feed',
        'js_composer_front'
    ];
    
    // Scripts to async
    $async_scripts = [
        'google-analytics',
        'facebook-pixel',
        'gtag'
    ];
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    if (in_array($handle, $async_scripts)) {
        return str_replace(' src', ' async src', $tag);
    }
    
    return $tag;
}, 10, 3);

/**
 * 3. CRITICAL CSS INLINE
 */
add_action('wp_head', function() {
    ?>
    <style id="critical-css">
        /* Critical CSS for above-the-fold content */
        *,:after,:before{box-sizing:border-box}
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;font-size:14px;line-height:1.6;color:#333;background:#fff}
        .header-wrapper{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.1);position:relative;z-index:100}
        .vd-home-products{padding:40px 0}
        .vd-home-products .section-title{text-align:center;font-size:24px;margin-bottom:20px;font-weight:600}
        .products{display:flex;flex-wrap:wrap;list-style:none;margin:0 -15px;padding:0}
        .product-warp-item{width:25%;padding:0 15px;margin-bottom:30px}
        @media(max-width:991px){.product-warp-item{width:33.333%}}
        @media(max-width:767px){.product-warp-item{width:50%}}
        .product{background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;height:auto}
        .product-img-wrap{position:relative;width:100%;aspect-ratio:5/6;overflow:hidden;background:#f8f8f8}
        .product-img-wrap .main-img{width:100%;height:100%;position:relative}
        .product-img-wrap img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
        .product-info-wrap{padding:15px}
        .product-title{font-size:14px;margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .price{color:#ff0000;font-size:16px;font-weight:600}
        
        /* Fix for first product */
        .products li:first-child img{width:247px;height:296px}
    </style>
    <?php
}, 5);

/**
 * 4. DEFER NON-CRITICAL CSS
 */
add_filter('style_loader_tag', function($html, $handle, $href, $media) {
    // Critical styles to keep
    $critical_styles = [
        'elessi-style',
        'nasa-core-style',
        'vidieu-home-style'
    ];
    
    if (!in_array($handle, $critical_styles)) {
        $html = '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
        $html .= '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
    }
    
    return $html;
}, 10, 4);

/**
 * 5. PRELOAD CRITICAL RESOURCES
 */
add_action('wp_head', function() {
    // Commented out - these files don't exist and cause 404 errors
    // echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/fonts/main-font.woff2" as="font" type="font/woff2" crossorigin>';
    // echo '<link rel="preload" href="' . get_template_directory_uri() . '/style.min.css" as="style">';
    
    // Preconnect to external domains
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="dns-prefetch" href="https://www.google-analytics.com">';
    echo '<link rel="dns-prefetch" href="https://connect.facebook.net">';
}, 1);

/**
 * 6. OPTIMIZE IMAGES
 */
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    // Add loading lazy except for first image
    if (!isset($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    
    // Ensure dimensions are set
    if (empty($attr['width']) || empty($attr['height'])) {
        $image_src = wp_get_attachment_image_src($attachment->ID, $size);
        if ($image_src) {
            $attr['width'] = $image_src[1];
            $attr['height'] = $image_src[2];
        }
    }
    
    return $attr;
}, 10, 3);

/**
 * 7. FIX FIRST PRODUCT LAYOUT
 */
add_action('wp_footer', function() {
    ?>
    <script>
    // Fix first product dimensions immediately
    (function() {
        var firstImg = document.querySelector('.vd-home-products .products li:first-child .product-img-wrap img');
        if (firstImg) {
            firstImg.width = 247;
            firstImg.height = 296;
            firstImg.loading = 'eager'; // Don't lazy load LCP image
        }
    })();
    </script>
    <?php
}, 999);

/**
 * 8. DISABLE UNNECESSARY FEATURES
 */
// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// Remove WP version
remove_action('wp_head', 'wp_generator');

// Disable RSS feeds if not needed
add_action('do_feed', function() {
    wp_die(__('No feed available'));
}, 1);

/**
 * 9. OPTIMIZE DATABASE QUERIES
 */
// Limit post revisions
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

// Increase memory limit if needed
if (!defined('WP_MEMORY_LIMIT')) {
    define('WP_MEMORY_LIMIT', '256M');
}

/**
 * 10. BROWSER CACHING HEADERS
 */
add_action('send_headers', function() {
    if (!is_admin()) {
        header('Cache-Control: max-age=31536000, public');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
});

/**
 * 11. ENABLE AJAX ADD TO CART
 */
// Ensure WooCommerce AJAX add to cart is enabled
add_filter('woocommerce_product_add_to_cart_url', function($url, $product) {
    if ($product && $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
        $url = add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url());
    }
    return $url;
}, 999, 2);

// Force AJAX add to cart support
add_filter('woocommerce_product_supports', function($supports, $feature, $product) {
    if ($feature === 'ajax_add_to_cart' && $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
        return true;
    }
    return $supports;
}, 999, 3);

// Disable redirect after add to cart - MULTIPLE APPROACHES
add_filter('woocommerce_add_to_cart_redirect', '__return_false', 999);
add_filter('woocommerce_continue_shopping_redirect_to_cart', '__return_false', 999);
add_filter('wc_add_to_cart_message_html', '__return_false', 999); // Hide message that causes redirect

// Override AJAX response to remove redirect
add_action('wp_ajax_woocommerce_add_to_cart', function() {
    ob_start();
}, 1);

add_action('wp_ajax_nopriv_woocommerce_add_to_cart', function() {
    ob_start();
}, 1);

// Modify AJAX responses to remove redirect
add_filter('woocommerce_add_to_cart_fragments', function($fragments) {
    // Ensure no redirect in fragments
    if (isset($fragments['redirect'])) {
        unset($fragments['redirect']);
    }
    return $fragments;
}, 999);

// Force AJAX behavior on all add to cart forms
add_action('wp_footer', function() {
    if (is_shop() || is_product_category() || is_product_tag() || is_front_page()) {
        ?>
        <script>
        jQuery(function($) {
            // Override add to cart behavior
            $(document).on('submit', 'form.cart', function(e) {
                var $form = $(this);
                var $button = $form.find('.single_add_to_cart_button');
                
                // If it's not variable product and not already processing
                if (!$button.hasClass('disabled') && !$button.hasClass('loading')) {
                    e.preventDefault();
                    
                    // Trigger click on button to use AJAX
                    $button.trigger('click');
                }
            });
            
            // Prevent any redirect after add to cart
            $(document.body).on('wc_cart_button_updated', function(e, button) {
                e.stopPropagation();
                return false;
            });
        });
        </script>
        <?php
    }
}, 999);

// Enable AJAX add to cart on archives
add_action('init', function() {
    // Enable AJAX add to cart
    if (get_option('woocommerce_enable_ajax_add_to_cart') !== 'yes') {
        update_option('woocommerce_enable_ajax_add_to_cart', 'yes');
    }
    
    // IMPORTANT: Disable redirect after add to cart
    if (get_option('woocommerce_cart_redirect_after_add') !== 'no') {
        update_option('woocommerce_cart_redirect_after_add', 'no');
    }
});

/**
 * DEBUG MODE - Uncomment to see what's loading
 */
/*
add_action('wp_print_scripts', function() {
    global $wp_scripts;
    foreach($wp_scripts->queue as $handle) {
        echo "Script: " . $handle . "\n";
    }
});

add_action('wp_print_styles', function() {
    global $wp_styles;
    foreach($wp_styles->queue as $handle) {
        echo "Style: " . $handle . "\n";
    }
});
*/

/**
 * IMPORTANT NOTES:
 * 
 * 1. Test each section individually
 * 2. Clear all caches after changes
 * 3. Monitor console for JavaScript errors
 * 4. Check mobile experience
 * 5. Verify checkout process still works
 * 
 * TO ROLLBACK:
 * - Rename this file to functions-performance.php.backup
 * - Clear all caches
 */