<?php
/**
 * AJAX Performance Optimizations for Vidieu Home Sections
 * Version: 1.0
 * 
 * This file implements caching, query optimization, and response size reduction
 * for the vidieu-home-sections plugin AJAX requests.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. IMPLEMENT TRANSIENT CACHING FOR AJAX RESPONSES
 */
add_filter('vidieu_ajax_response_data', function($response_data, $request_data) {
    // Generate cache key based on request parameters
    $cache_key = 'vd_ajax_' . md5(serialize([
        'action' => $request_data['action'] ?? '',
        'category' => $request_data['category'] ?? '',
        'page' => $request_data['page'] ?? 1,
        'per_page' => $request_data['per_page'] ?? 12,
        'section_id' => $request_data['section_id'] ?? '',
        'orderby' => $request_data['orderby'] ?? '',
        'order' => $request_data['order'] ?? ''
    ]));
    
    // Try to get cached response
    $cached_response = get_transient($cache_key);
    
    if ($cached_response !== false) {
        // Add cache hit header for debugging
        $cached_response['cache_hit'] = true;
        return $cached_response;
    }
    
    // Store response in cache for 1 hour
    set_transient($cache_key, $response_data, HOUR_IN_SECONDS);
    
    return $response_data;
}, 10, 2);

/**
 * 2. OPTIMIZE DATABASE QUERIES FOR PRODUCTS
 */
add_filter('vidieu_products_query_args', function($args) {
    // Add optimization flags
    $args['no_found_rows'] = true; // Skip counting total rows if not needed
    $args['update_post_meta_cache'] = false; // Don't update meta cache
    $args['update_post_term_cache'] = false; // Don't update term cache
    
    // Only get required fields
    $args['fields'] = 'ids'; // Get only IDs initially
    
    return $args;
});

/**
 * 3. IMPLEMENT LAZY LOADING FOR PRODUCT DATA
 */
add_filter('vidieu_product_data_fields', function($fields) {
    // Only load essential fields for initial display
    return [
        'ID',
        'post_title',
        'post_name',
        '_price',
        '_regular_price',
        '_sale_price',
        '_thumbnail_id'
    ];
});

/**
 * 4. ADD JSON RESPONSE OPTION
 */
add_action('wp_ajax_vidieu_filter_products_json', 'vidieu_ajax_filter_products_json');
add_action('wp_ajax_nopriv_vidieu_filter_products_json', 'vidieu_ajax_filter_products_json');

function vidieu_ajax_filter_products_json() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vidieu_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    // Get parameters
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 12;
    
    // Build query args with optimizations
    $args = [
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
        'no_found_rows' => true,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => '_visibility',
                'value' => ['catalog', 'visible'],
                'compare' => 'IN'
            ]
        ]
    ];
    
    // Add category filter
    if (!empty($category) && $category !== 'all') {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category
            ]
        ];
    }
    
    // Execute optimized query
    $products = new WP_Query($args);
    $product_data = [];
    
    if ($products->have_posts()) {
        foreach ($products->posts as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;
            
            // Build minimal product data
            $product_data[] = [
                'id' => $product_id,
                'title' => $product->get_name(),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail'),
                'type' => $product->get_type(),
                'in_stock' => $product->is_in_stock(),
                'on_sale' => $product->is_on_sale()
            ];
        }
    }
    
    // Calculate pagination
    $total_pages = ceil(wp_count_posts('product')->publish / $per_page);
    
    wp_send_json_success([
        'products' => $product_data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ],
        'found_posts' => count($product_data)
    ]);
}

/**
 * 5. CLEAR CACHE ON PRODUCT UPDATES
 */
add_action('save_post_product', function($post_id) {
    // Clear all product AJAX caches
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_vd_ajax_%' 
         OR option_name LIKE '_transient_timeout_vd_ajax_%'"
    );
});

/**
 * 6. ADD PERFORMANCE HEADERS
 */
add_action('send_headers', function() {
    if (wp_doing_ajax() && isset($_REQUEST['action']) && 
        strpos($_REQUEST['action'], 'vidieu_') === 0) {
        // Add cache headers for AJAX responses
        header('Cache-Control: public, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        
        // Enable compression
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
});

/**
 * 7. PRELOAD CRITICAL RESOURCES
 */
add_action('wp_head', function() {
    if (is_front_page()) {
        // Preload AJAX endpoint
        echo '<link rel="preconnect" href="' . admin_url('admin-ajax.php') . '">';
        
        // Prefetch first page of products
        echo '<link rel="prefetch" href="' . admin_url('admin-ajax.php?action=vidieu_filter_products_json&page=1') . '">';
    }
}, 1);

/**
 * 8. OPTIMIZE IMAGE LOADING IN AJAX RESPONSES
 */
add_filter('vidieu_product_image_attributes', function($attr) {
    // Add lazy loading and async decoding
    $attr['loading'] = 'lazy';
    $attr['decoding'] = 'async';
    
    // Add srcset for responsive images
    if (isset($attr['src']) && !isset($attr['srcset'])) {
        $attachment_id = attachment_url_to_postid($attr['src']);
        if ($attachment_id) {
            $attr['srcset'] = wp_get_attachment_image_srcset($attachment_id, 'woocommerce_thumbnail');
            $attr['sizes'] = wp_get_attachment_image_sizes($attachment_id, 'woocommerce_thumbnail');
        }
    }
    
    return $attr;
});

/**
 * 9. DATABASE QUERY MONITOR
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('shutdown', function() {
        if (wp_doing_ajax() && isset($_REQUEST['action']) && 
            strpos($_REQUEST['action'], 'vidieu_') === 0) {
            
            global $wpdb;
            $total_query_time = 0;
            
            foreach ($wpdb->queries as $query) {
                $total_query_time += $query[1];
            }
            
            // Log slow queries
            if ($total_query_time > 0.5) {
                error_log(sprintf(
                    'Slow AJAX request: %s - Total query time: %.3fs - Query count: %d',
                    $_REQUEST['action'],
                    $total_query_time,
                    count($wpdb->queries)
                ));
            }
        }
    });
}

/**
 * 10. IMPLEMENT REQUEST QUEUE MANAGEMENT
 */
add_action('wp_footer', function() {
    ?>
    <script>
    (function() {
        // Override AJAX handling with request queue
        if (typeof window.VidieuHomeSections !== 'undefined') {
            var ajaxQueue = [];
            var activeRequest = null;
            
            // Wrap the original AJAX method
            var originalAjax = jQuery.ajax;
            jQuery.ajax = function(options) {
                if (options.url && options.url.includes('vidieu_filter_')) {
                    // Cancel previous request if still pending
                    if (activeRequest && activeRequest.readyState !== 4) {
                        activeRequest.abort();
                    }
                    
                    // Add loading optimization
                    options.beforeSend = function(xhr) {
                        activeRequest = xhr;
                        // Add performance headers
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.setRequestHeader('Accept-Encoding', 'gzip, deflate');
                    };
                    
                    // Add response caching
                    var cacheKey = JSON.stringify(options.data);
                    var cached = sessionStorage.getItem('vd_ajax_' + cacheKey);
                    
                    if (cached && Date.now() - JSON.parse(cached).timestamp < 300000) { // 5 min cache
                        var cachedData = JSON.parse(cached);
                        if (options.success) {
                            options.success(cachedData.response);
                        }
                        return;
                    }
                    
                    // Store response in session storage
                    var originalSuccess = options.success;
                    options.success = function(response) {
                        sessionStorage.setItem('vd_ajax_' + cacheKey, JSON.stringify({
                            response: response,
                            timestamp: Date.now()
                        }));
                        
                        if (originalSuccess) {
                            originalSuccess(response);
                        }
                    };
                }
                
                return originalAjax.call(this, options);
            };
        }
    })();
    </script>
    <?php
});