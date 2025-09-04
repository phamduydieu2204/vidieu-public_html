<?php
/**
 * AJAX handlers class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Ajax class
 */
class VD_Ajax {
    
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
        add_action('wp_ajax_vidieu_filter_products', array($this, 'filter_products'));
        add_action('wp_ajax_nopriv_vidieu_filter_products', array($this, 'filter_products'));
        add_action('wp_ajax_vidieu_filter_posts', array($this, 'filter_posts'));
        add_action('wp_ajax_nopriv_vidieu_filter_posts', array($this, 'filter_posts'));
    }
    
    /**
     * Send standardized JSON error response
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $details Additional debug details
     */
    private function send_json_error($code, $message, $details = array()) {
        $response = array(
            'code' => $code,
            'message' => $message,
            'timestamp' => current_time('timestamp')
        );
        
        // Add debug details if available
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        wp_send_json_error($response);
    }
    
    /**
     * Filter products AJAX handler
     */
    public function filter_products() {
        
        // Security check
        if (!check_ajax_referer('vd_home_nonce', 'nonce', false)) {
            $this->send_json_error(
                'SECURITY_FAILED', 
                __('Security check failed.', VD_HOME_TEXT_DOMAIN)
            );
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->send_json_error(
                'WOOCOMMERCE_REQUIRED',
                __('WooCommerce is required for product display.', VD_HOME_TEXT_DOMAIN)
            );
        }
        
        // Get and sanitize parameters
        $taxonomy = sanitize_text_field($_POST['taxonomy'] ?? 'product_cat');
        $term_id = absint($_POST['term_id'] ?? 0);
        $paged = max(1, absint($_POST['paged'] ?? 1));
        $per_page = max(1, min(50, absint($_POST['per_page'] ?? 12)));
        $columns = max(1, min(6, absint($_POST['columns'] ?? 4)));
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $load_more = isset($_POST['load_more']) && $_POST['load_more'] === 'true';
        
        
        // Validate parameters
        $allowed_taxonomies = array('product_cat', 'product_category');
        if (!in_array($taxonomy, $allowed_taxonomies)) {
            // Auto-correct common taxonomy names
            if (in_array($taxonomy, array('category', 'post_category'))) {
                $taxonomy = 'product_cat'; // Force to product_cat for product sections
            } else {
                $this->send_json_error(
                    'INVALID_TAXONOMY',
                    __('Invalid taxonomy.', VD_HOME_TEXT_DOMAIN),
                    array('received_taxonomy' => $taxonomy, 'allowed' => $allowed_taxonomies)
                );
            }
        }
        
        // Validate term if provided
        if ($term_id > 0) {
            $term = get_term($term_id, $taxonomy);
            if (!$term || is_wp_error($term)) {
                $this->send_json_error(
                    'INVALID_TERM',
                    __('Invalid category.', VD_HOME_TEXT_DOMAIN),
                    array('term_id' => $term_id, 'taxonomy' => $taxonomy)
                );
            }
        }
        
        try {
            // Get products HTML
            $args = array(
                'per_page' => $per_page,
                'columns' => $columns,
                'category' => $term_id,
                'paged' => $paged,
                'section_id' => $section_id
            );
            
            
            $html = VD_Templates::get_products_grid($args);
            
            
            if (empty($html)) {
                $html = '<div class="vd-no-results"><p>' . __('No products found.', VD_HOME_TEXT_DOMAIN) . '</p></div>';
            }
            
            wp_send_json_success(array(
                'html' => $html,
                'paged' => $paged
            ));
            
        } catch (Exception $e) {
            $this->send_json_error(
                'AJAX_EXCEPTION',
                __('Failed to load products. Please try again.', VD_HOME_TEXT_DOMAIN),
                array('exception' => $e->getMessage())
            );
        }
    }
    
    /**
     * Filter posts AJAX handler
     */
    public function filter_posts() {
        // Security check
        if (!check_ajax_referer('vd_home_nonce', 'nonce', false)) {
            wp_die(__('Security check failed.', VD_HOME_TEXT_DOMAIN), '', array('response' => 403));
        }
        
        // Get and sanitize parameters
        $taxonomy = sanitize_text_field($_POST['taxonomy'] ?? 'category');
        $term_id = absint($_POST['term_id'] ?? 0);
        $paged = max(1, absint($_POST['paged'] ?? 1));
        $per_page = max(1, min(50, absint($_POST['per_page'] ?? 9)));
        $columns = absint($_POST['columns'] ?? 3);
        $columns_desktop = absint($_POST['columns_desktop'] ?? $columns);
        $columns_tablet = absint($_POST['columns_tablet'] ?? 2);
        $columns_mobile = absint($_POST['columns_mobile'] ?? 1);
        $show_author = ($_POST['show_author'] ?? 'yes') === 'yes';
        $show_category = ($_POST['show_category'] ?? 'yes') === 'yes';
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = strtoupper(sanitize_text_field($_POST['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $load_more = isset($_POST['load_more']) && $_POST['load_more'] === 'true';
        
        // Validate parameters
        $allowed_taxonomies = array('category', 'post_category', 'post_tag');
        if (!in_array($taxonomy, $allowed_taxonomies)) {
            // Auto-correct common taxonomy names
            if (in_array($taxonomy, array('product_cat', 'product_category'))) {
                $taxonomy = 'category'; // Force to category for post sections
            } else {
                $this->send_json_error(
                    'INVALID_TAXONOMY',
                    __('Invalid taxonomy.', VD_HOME_TEXT_DOMAIN),
                    array('received_taxonomy' => $taxonomy, 'allowed' => $allowed_taxonomies)
                );
            }
        }
        
        // Validate term if provided
        if ($term_id > 0) {
            $term = get_term($term_id, $taxonomy);
            if (!$term || is_wp_error($term)) {
                $this->send_json_error(
                    'INVALID_TERM',
                    __('Invalid category.', VD_HOME_TEXT_DOMAIN),
                    array('term_id' => $term_id, 'taxonomy' => $taxonomy)
                );
            }
        }
        
        try {
            // Get posts HTML
            $html = VD_Templates::get_posts_grid(array(
                'per_page' => $per_page,
                'columns' => $columns,
                'columns_desktop' => $columns_desktop,
                'columns_tablet' => $columns_tablet,
                'columns_mobile' => $columns_mobile,
                'category' => $term_id,
                'paged' => $paged,
                'show_author' => $show_author,
                'show_category' => $show_category,
                'orderby' => $orderby,
                'order' => $order,
                'section_id' => $section_id
            ));
            
            wp_send_json_success(array(
                'html' => $html,
                'paged' => $paged
            ));
            
        } catch (Exception $e) {
            $this->send_json_error(
                'AJAX_EXCEPTION',
                __('Failed to load posts. Please try again.', VD_HOME_TEXT_DOMAIN),
                array('exception' => $e->getMessage())
            );
        }
    }
}