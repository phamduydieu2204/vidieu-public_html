<?php
/**
 * Template management class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Templates class
 */
class VD_Templates {
    
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
        // Constructor logic if needed
    }
    
    /**
     * Get products grid HTML using WooCommerce templates (Elessi compatible)
     */
    public static function get_products_grid($args = array()) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<div class="vd-notice">' . __('WooCommerce is required for product display.', VD_HOME_TEXT_DOMAIN) . '</div>';
        }
        
        $defaults = array(
            'per_page' => 12,
            'columns' => 4,
            'category' => '',
            'paged' => 1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'show_pagination' => true,
            'section_id' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Apply admin settings if not explicitly set
        if ($args['per_page'] == 12) {
            $args['per_page'] = VD_Admin::get_option('products_items_per_page', 12);
        }
        
        // Query products using WP_Query for proper loop compatibility
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged' => $args['paged'],
            'orderby' => $args['orderby'],
            'order' => $args['order']
        );
        
        // Add category filter (supports both ID and slug)
        if (!empty($args['category'])) {
            // Validate category exists
            if (is_numeric($args['category'])) {
                // Numeric ID - validate term exists
                $term = get_term($args['category'], 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $query_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => absint($args['category'])
                        )
                    );
                }
            } else {
                // String slug - validate term exists
                $term = get_term_by('slug', $args['category'], 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $query_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'slug',
                            'terms' => sanitize_title($args['category'])
                        )
                    );
                }
            }
        }
        
        // Use WP_Query for proper WordPress loop compatibility
        $products = new WP_Query($query_args);
        
        if (!$products->have_posts()) {
            wp_reset_postdata();
            return '<div class="vd-no-results">' . __('No products found.', VD_HOME_TEXT_DOMAIN) . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Set up WooCommerce loop globals
        global $woocommerce_loop;
        $woocommerce_loop['is_shortcode'] = true;
        $woocommerce_loop['columns'] = $args['columns'];
        
        // Render using WooCommerce templates (Elessi compatible)
        self::render_products_with_wc_templates($products, $args);
        
        // Add pagination if enabled and has multiple pages
        if ($args['show_pagination'] && $products->max_num_pages > 1) {
            $pagination_args = array(
                'total_pages' => $products->max_num_pages,
                'current_page' => $args['paged'],
                'section_id' => $args['section_id'],
                'section_type' => 'products',
                'ajax_action' => 'vidieu_filter_products',
                'category' => $args['category'],
                'taxonomy' => 'product_cat',
                'per_page' => $args['per_page'],
                'columns' => $args['columns'],
                'range' => VD_Admin::get_option('pagination_range', 3)
            );
            echo VD_Pagination::render_pagination($pagination_args);
        }
        
        $html = ob_get_clean();
        
        return $html;
    }
    
    /**
     * Get posts grid HTML
     */
    public static function get_posts_grid($args = array()) {
        $defaults = array(
            'per_page' => 9,
            'columns' => 3,
            'columns_desktop' => 3,
            'columns_tablet' => 2,
            'columns_mobile' => 1,
            'category' => '',
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_author' => true,
            'show_category' => true,
            'show_pagination' => true,
            'section_id' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Apply admin settings if not explicitly set
        if ($args['per_page'] == 9) {
            $args['per_page'] = VD_Admin::get_option('posts_items_per_page', 9);
        }
        
        // Query posts
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged' => $args['paged'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'no_found_rows' => false // Enable found_rows for pagination
        );
        
        // Add category filter (support both ID and slug)
        if (!empty($args['category'])) {
            if (is_numeric($args['category'])) {
                // Validate category ID exists
                $term = get_term($args['category'], 'category');
                if ($term && !is_wp_error($term)) {
                    $query_args['cat'] = absint($args['category']);
                }
            } else {
                // Validate category slug exists
                $term = get_term_by('slug', $args['category'], 'category');
                if ($term && !is_wp_error($term)) {
                    $query_args['category_name'] = sanitize_title($args['category']);
                }
            }
        }
        
        $posts = new WP_Query($query_args);
        
        ob_start();
        include VD_HOME_PLUGIN_DIR . 'templates/posts-grid.php';
        
        // Add pagination if enabled and has multiple pages
        if ($args['show_pagination'] && $posts->max_num_pages > 1) {
            // Ensure category is numeric ID for consistency
            $category_for_pagination = $args['category'];
            if (!empty($category_for_pagination) && !is_numeric($category_for_pagination)) {
                // If it's a slug, convert to ID
                $term = get_term_by('slug', $category_for_pagination, 'category');
                if ($term && !is_wp_error($term)) {
                    $category_for_pagination = $term->term_id;
                }
            }
            
            $pagination_args = array(
                'total_pages' => $posts->max_num_pages,
                'current_page' => $args['paged'],
                'section_id' => $args['section_id'],
                'section_type' => 'posts',
                'ajax_action' => 'vidieu_filter_posts',
                'category' => $category_for_pagination,
                'taxonomy' => 'category',
                'per_page' => $args['per_page'],
                'columns' => $args['columns_desktop'],
                'show_author' => $args['show_author'],
                'show_category' => $args['show_category'],
                'orderby' => $args['orderby'],
                'order' => $args['order'],
                'range' => VD_Admin::get_option('pagination_range', 3)
            );
            
            echo VD_Pagination::render_pagination($pagination_args);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get grid column class based on columns number
     */
    public static function get_column_class($columns, $total_columns = 12) {
        $column_width = floor($total_columns / $columns);
        return 'vd-col-' . $column_width;
    }
    
    /**
     * Render products using WooCommerce templates (Elessi compatible)
     */
    private static function render_products_with_wc_templates($products, $args) {
        global $post, $product, $nasa_opt, $woocommerce_loop;
        
        
        // Set up loop globals for Elessi compatibility
        $woocommerce_loop['columns'] = $args['columns'];
        $woocommerce_loop['is_shortcode'] = true;
        
        // Set filter to indicate we're rendering vidieu products
        add_filter('vidieu_is_rendering_products', '__return_true');
        
        // Start the product loop using WooCommerce template
        woocommerce_product_loop_start();
        
        $delay = 0;
        $count = 0;
        while ($products->have_posts()) {
            $products->the_post();
            
            // Get the global product object
            $product = wc_get_product(get_the_ID());
            
            if (!$product || !$product->is_visible()) {
                continue;
            }
            
            
            // Set delay for animations (Elessi style)
            $_delay = $delay;
            
            // Set variables in global scope for template
            $GLOBALS['show_in_list'] = true;
            $GLOBALS['wrapper'] = 'li';
            $GLOBALS['wrapper_class'] = 'product-item grid wow fadeInUp hover-fade has-add';
            $GLOBALS['description_info'] = true;
            $GLOBALS['_delay'] = $_delay;
            
            // Use WooCommerce template part to render product
            // This will use theme's template if it exists
            wc_get_template_part('content', 'product');
            
            $delay += 100; // Increment delay for staggered animations
            $count++;
        }
        
        
        // End the product loop
        woocommerce_product_loop_end();
        
        // Remove filter after rendering
        remove_filter('vidieu_is_rendering_products', '__return_true');
        
        // Reset global post data
        wp_reset_postdata();
    }
    
    /**
     * Get responsive column classes
     */
    public static function get_responsive_classes($columns) {
        $classes = array();
        
        switch ($columns) {
            case 6:
                $classes[] = 'vd-col-2';  // 12/6 = 2
                $classes[] = 'vd-col-md-3'; // 12/4 = 3 on tablet
                $classes[] = 'vd-col-sm-6'; // 12/2 = 6 on mobile
                break;
            case 4:
                $classes[] = 'vd-col-3';  // 12/4 = 3
                $classes[] = 'vd-col-md-4'; // 12/3 = 4 on tablet
                $classes[] = 'vd-col-sm-6'; // 12/2 = 6 on mobile
                break;
            case 3:
                $classes[] = 'vd-col-4';  // 12/3 = 4
                $classes[] = 'vd-col-md-6'; // 12/2 = 6 on tablet
                $classes[] = 'vd-col-sm-12'; // full width on mobile
                break;
            case 2:
                $classes[] = 'vd-col-6';  // 12/2 = 6
                $classes[] = 'vd-col-sm-12'; // full width on mobile
                break;
            default:
                $classes[] = 'vd-col-12'; // full width
        }
        
        return implode(' ', $classes);
    }
}