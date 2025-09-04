<?php
/**
 * WPBakery Page Builder integration
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_WPBakery class
 */
class VD_WPBakery {
    
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
        add_action('vc_before_init', array($this, 'register_elements'));
    }
    
    /**
     * Register WPBakery elements
     */
    public function register_elements() {
        // Check if WPBakery is active
        if (!function_exists('vc_map')) {
            return;
        }
        
        // Register Products element
        vc_map(array(
            'name' => __('Vidieu Home - Products', VD_HOME_TEXT_DOMAIN),
            'description' => __('Display products with category sidebar', VD_HOME_TEXT_DOMAIN),
            'base' => 'vidieu_home_products',
            'category' => __('Vidieu Sections', VD_HOME_TEXT_DOMAIN),
            'icon' => 'icon-wpb-woocommerce',
            'params' => array(
                array(
                    'type' => 'textfield',
                    'heading' => __('Title', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'title',
                    'value' => __('Our Products', VD_HOME_TEXT_DOMAIN),
                    'description' => __('Section title (leave empty to hide)', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Show Title', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'show_title',
                    'value' => array(
                        __('Yes', VD_HOME_TEXT_DOMAIN) => 'yes',
                        __('No', VD_HOME_TEXT_DOMAIN) => 'no'
                    ),
                    'std' => 'yes'
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Products per Page', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'per_page',
                    'value' => array(
                        '6' => '6',
                        '8' => '8',
                        '12' => '12',
                        '16' => '16',
                        '20' => '20',
                        '24' => '24'
                    ),
                    'std' => '12',
                    'description' => __('Number of products to display per page', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Columns', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'columns',
                    'value' => array(
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6'
                    ),
                    'std' => '4',
                    'description' => __('Number of product columns', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'autocomplete',
                    'heading' => __('Default Category', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'default_cat',
                    'settings' => array(
                        'multiple' => false,
                        'sortable' => false,
                        'groups' => false,
                        'unique_values' => true,
                        'display_inline' => true,
                        'delay' => 300,
                        'auto_focus' => true,
                        'values' => $this->get_product_categories_for_autocomplete()
                    ),
                    'description' => __('Select default product category to display (optional)', VD_HOME_TEXT_DOMAIN)
                )
            )
        ));
        
        // Register Posts element
        vc_map(array(
            'name' => __('Vidieu Home - Posts', VD_HOME_TEXT_DOMAIN),
            'description' => __('Display posts with category sidebar', VD_HOME_TEXT_DOMAIN),
            'base' => 'vidieu_home_posts',
            'category' => __('Vidieu Sections', VD_HOME_TEXT_DOMAIN),
            'icon' => 'icon-wpb-posts-grid',
            'params' => array(
                array(
                    'type' => 'textfield',
                    'heading' => __('Title', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'title',
                    'value' => __('Latest Posts', VD_HOME_TEXT_DOMAIN),
                    'description' => __('Section title (leave empty to hide)', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Show Title', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'show_title',
                    'value' => array(
                        __('Yes', VD_HOME_TEXT_DOMAIN) => 'yes',
                        __('No', VD_HOME_TEXT_DOMAIN) => 'no'
                    ),
                    'std' => 'yes'
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Posts per Page', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'per_page',
                    'value' => array(
                        '6' => '6',
                        '9' => '9',
                        '12' => '12',
                        '15' => '15',
                        '18' => '18',
                        '21' => '21'
                    ),
                    'std' => '9',
                    'description' => __('Number of posts to display per page', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Columns', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'columns',
                    'value' => array(
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4'
                    ),
                    'std' => '3',
                    'description' => __('Number of post columns', VD_HOME_TEXT_DOMAIN)
                ),
                array(
                    'type' => 'autocomplete',
                    'heading' => __('Default Category', VD_HOME_TEXT_DOMAIN),
                    'param_name' => 'default_cat',
                    'settings' => array(
                        'multiple' => false,
                        'sortable' => false,
                        'groups' => false,
                        'unique_values' => true,
                        'display_inline' => true,
                        'delay' => 300,
                        'auto_focus' => true,
                        'values' => $this->get_post_categories_for_autocomplete()
                    ),
                    'description' => __('Select default post category to display (optional)', VD_HOME_TEXT_DOMAIN)
                )
            )
        ));
        
        // Add autocomplete hooks
        add_filter('vc_autocomplete_vidieu_home_products_default_cat_callback', array($this, 'autocomplete_product_categories'), 10, 1);
        add_filter('vc_autocomplete_vidieu_home_products_default_cat_render', array($this, 'render_product_category'), 10, 1);
        add_filter('vc_autocomplete_vidieu_home_posts_default_cat_callback', array($this, 'autocomplete_post_categories'), 10, 1);
        add_filter('vc_autocomplete_vidieu_home_posts_default_cat_render', array($this, 'render_post_category'), 10, 1);
    }
    
    /**
     * Get product categories for autocomplete
     */
    private function get_product_categories_for_autocomplete() {
        $categories = array();
        
        if (class_exists('WooCommerce')) {
            $terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'number' => 100
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = array(
                        'label' => $term->name,
                        'value' => $term->term_id
                    );
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Get post categories for autocomplete
     */
    private function get_post_categories_for_autocomplete() {
        $categories = array();
        
        $terms = get_terms(array(
            'taxonomy' => 'category',
            'hide_empty' => false,
            'number' => 100
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = array(
                    'label' => $term->name,
                    'value' => $term->term_id
                );
            }
        }
        
        return $categories;
    }
    
    /**
     * Autocomplete product categories
     */
    public function autocomplete_product_categories($search_string) {
        $categories = array();
        
        if (class_exists('WooCommerce')) {
            $terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'name__like' => $search_string,
                'hide_empty' => false,
                'number' => 20
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = array(
                        'label' => $term->name,
                        'value' => $term->term_id
                    );
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Render product category
     */
    public function render_product_category($term_data) {
        $term_id = isset($term_data['value']) ? $term_data['value'] : $term_data;
        $term = get_term($term_id, 'product_cat');
        
        if (!is_wp_error($term) && $term) {
            return array(
                'label' => $term->name,
                'value' => $term->term_id
            );
        }
        
        return false;
    }
    
    /**
     * Autocomplete post categories
     */
    public function autocomplete_post_categories($search_string) {
        $categories = array();
        
        $terms = get_terms(array(
            'taxonomy' => 'category',
            'name__like' => $search_string,
            'hide_empty' => false,
            'number' => 20
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = array(
                    'label' => $term->name,
                    'value' => $term->term_id
                );
            }
        }
        
        return $categories;
    }
    
    /**
     * Render post category
     */
    public function render_post_category($term_data) {
        $term_id = isset($term_data['value']) ? $term_data['value'] : $term_data;
        $term = get_term($term_id, 'category');
        
        if (!is_wp_error($term) && $term) {
            return array(
                'label' => $term->name,
                'value' => $term->term_id
            );
        }
        
        return false;
    }
}