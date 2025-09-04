<?php
/**
 * Optimized AJAX Handler for Vidieu Home Sections
 * 
 * Implements caching, query optimization, and reduced payload sizes
 * to improve AJAX response times from 2.6s to <500ms
 * 
 * @package VidieuHomeSections
 * @since 1.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VD_Ajax_Optimized
 */
class VD_Ajax_Optimized {
    
    /**
     * Cache group name
     */
    const CACHE_GROUP = 'vidieu_ajax';
    
    /**
     * Cache expiration time (1 hour)
     */
    const CACHE_EXPIRATION = 3600;
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize optimized AJAX handlers
        add_action('init', array($this, 'init_ajax_handlers'));
        
        // Add cache clearing hooks
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('deleted_post', array($this, 'clear_cache_on_save'));
        add_action('woocommerce_update_product', array($this, 'clear_cache_on_save'));
        
        // Optimize queries
        add_filter('posts_pre_query', array($this, 'maybe_use_cached_query'), 10, 2);
    }
    
    /**
     * Initialize AJAX handlers
     */
    public function init_ajax_handlers() {
        // Replace original handlers with optimized versions
        remove_action('wp_ajax_vidieu_filter_products', array('VD_Ajax', 'filter_products'));
        remove_action('wp_ajax_nopriv_vidieu_filter_products', array('VD_Ajax', 'filter_products'));
        
        add_action('wp_ajax_vidieu_filter_products', array($this, 'filter_products_optimized'));
        add_action('wp_ajax_nopriv_vidieu_filter_products', array($this, 'filter_products_optimized'));
        
        // Add JSON endpoint
        add_action('wp_ajax_vidieu_get_products_json', array($this, 'get_products_json'));
        add_action('wp_ajax_nopriv_vidieu_get_products_json', array($this, 'get_products_json'));
    }
    
    /**
     * Optimized product filtering with caching
     */
    public function filter_products_optimized() {
        // Start output buffering for compression
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vidieu_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', VD_HOME_TEXT_DOMAIN)));
        }
        
        // Get and validate parameters
        $params = $this->get_sanitized_params();
        
        // Generate cache key
        $cache_key = $this->generate_cache_key('products', $params);
        
        // Try to get from cache
        $cached_response = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if ($cached_response !== false) {
            // Add cache hit header
            header('X-VD-Cache: HIT');
            wp_send_json_success($cached_response);
        }
        
        // Cache miss - generate response
        header('X-VD-Cache: MISS');
        
        // Start timing
        $start_time = microtime(true);
        
        // Get products with optimized query
        $products_data = $this->get_products_optimized($params);
        
        // Generate minimal HTML
        $html = $this->render_products_html($products_data['products'], $params);
        
        // Prepare response
        $response = array(
            'html' => $html,
            'pagination' => $this->render_pagination_html($products_data['pagination'], $params),
            'found_posts' => $products_data['found_posts'],
            'max_pages' => $products_data['max_pages'],
            'load_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        );
        
        // Store in cache
        wp_cache_set($cache_key, $response, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        
        // Send response
        wp_send_json_success($response);
    }
    
    /**
     * Get products with optimized query
     */
    private function get_products_optimized($params) {
        global $wpdb;
        
        // Build optimized query
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page'],
            'post_status' => 'publish',
            'orderby' => $params['orderby'],
            'order' => $params['order'],
            // Optimization flags
            'no_found_rows' => false, // We need pagination
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields' => 'ids', // Get only IDs first
            'cache_results' => true
        );
        
        // Add category filter
        if (!empty($params['category']) && $params['category'] !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $params['taxonomy'],
                    'field' => 'term_id',
                    'terms' => $params['category'],
                    'operator' => 'IN'
                )
            );
        }
        
        // Add visibility meta query for WooCommerce
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key' => '_visibility',
                'value' => array('hidden'),
                'compare' => 'NOT IN'
            )
        );
        
        // Execute query
        $query = new WP_Query($args);
        
        // Get product objects efficiently
        $products = array();
        if ($query->have_posts()) {
            // Batch load product data
            $product_ids = $query->posts;
            
            // Preload meta data in one query
            update_meta_cache('post', $product_ids);
            
            // Get products
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product && $product->is_visible()) {
                    $products[] = $product;
                }
            }
        }
        
        return array(
            'products' => $products,
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'pagination' => array(
                'current_page' => $params['page'],
                'total_pages' => $query->max_num_pages,
                'per_page' => $params['per_page']
            )
        );
    }
    
    /**
     * Render minimal product HTML
     */
    private function render_products_html($products, $params) {
        ob_start();
        
        // Use simplified template
        global $woocommerce_loop;
        $woocommerce_loop['columns'] = $params['columns'];
        
        if (!empty($products)) {
            echo '<ul class="products columns-' . esc_attr($params['columns']) . '">';
            
            foreach ($products as $product) {
                // Set global product
                $GLOBALS['product'] = $product;
                
                // Render minimal product template
                ?>
                <li class="product-warp-item">
                    <div class="product-item">
                        <div class="product-img-wrap">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="main-img">
                                <?php echo $product->get_image('woocommerce_thumbnail', array(
                                    'loading' => 'lazy',
                                    'decoding' => 'async'
                                )); ?>
                            </a>
                        </div>
                        <div class="product-info-wrap info">
                            <h3 class="name"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                            <div class="price-wrap"><?php echo $product->get_price_html(); ?></div>
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        </div>
                    </div>
                </li>
                <?php
            }
            
            echo '</ul>';
        } else {
            echo '<div class="vd-no-results">' . __('No products found.', VD_HOME_TEXT_DOMAIN) . '</div>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render pagination HTML
     */
    private function render_pagination_html($pagination, $params) {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        ob_start();
        
        $args = array(
            'total_pages' => $pagination['total_pages'],
            'current_page' => $pagination['current_page'],
            'section_id' => $params['section_id'],
            'ajax_action' => 'vidieu_filter_products',
            'category' => $params['category'],
            'taxonomy' => $params['taxonomy'],
            'per_page' => $params['per_page'],
            'columns' => $params['columns']
        );
        
        echo VD_Pagination::render_pagination($args);
        
        return ob_get_clean();
    }
    
    /**
     * JSON endpoint for ultra-fast responses
     */
    public function get_products_json() {
        // Set JSON headers
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=300'); // 5 min browser cache
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'vidieu_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }
        
        $params = $this->get_sanitized_params('GET');
        $cache_key = $this->generate_cache_key('products_json', $params);
        
        // Try cache
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        if ($cached !== false) {
            header('X-VD-Cache: HIT');
            echo json_encode($cached);
            exit;
        }
        
        // Get products
        $products_data = $this->get_products_optimized($params);
        
        // Build JSON response
        $response = array(
            'products' => array(),
            'pagination' => $products_data['pagination'],
            'found_posts' => $products_data['found_posts']
        );
        
        foreach ($products_data['products'] as $product) {
            $response['products'][] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'price_html' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail'),
                'in_stock' => $product->is_in_stock(),
                'type' => $product->get_type()
            );
        }
        
        // Cache and send
        wp_cache_set($cache_key, $response, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        header('X-VD-Cache: MISS');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Get sanitized parameters
     */
    private function get_sanitized_params($method = 'POST') {
        $input = $method === 'POST' ? $_POST : $_GET;
        
        return array(
            'category' => isset($input['category']) ? sanitize_text_field($input['category']) : '',
            'taxonomy' => isset($input['taxonomy']) ? sanitize_text_field($input['taxonomy']) : 'product_cat',
            'page' => isset($input['page']) ? absint($input['page']) : 1,
            'per_page' => isset($input['per_page']) ? absint($input['per_page']) : 12,
            'columns' => isset($input['columns']) ? absint($input['columns']) : 4,
            'orderby' => isset($input['orderby']) ? sanitize_text_field($input['orderby']) : 'menu_order',
            'order' => isset($input['order']) ? strtoupper(sanitize_text_field($input['order'])) : 'ASC',
            'section_id' => isset($input['section_id']) ? sanitize_text_field($input['section_id']) : ''
        );
    }
    
    /**
     * Generate cache key
     */
    private function generate_cache_key($type, $params) {
        return 'vd_' . $type . '_' . md5(serialize($params));
    }
    
    /**
     * Clear cache on post save
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'product') {
            // Clear all AJAX cache - check if function exists first
            if (function_exists('wp_cache_delete_group')) {
                wp_cache_delete_group(self::CACHE_GROUP);
            }
            
            // Also clear transients if used
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_vd_%' 
                OR option_name LIKE '_transient_timeout_vd_%'"
            );
        }
    }
    
    /**
     * Maybe use cached query results
     */
    public function maybe_use_cached_query($posts, $query) {
        if (!is_admin() && $query->get('post_type') === 'product') {
            // Add query optimization hints
            $query->set('cache_results', true);
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
        }
        
        return $posts;
    }
}

// Initialize
VD_Ajax_Optimized::get_instance();