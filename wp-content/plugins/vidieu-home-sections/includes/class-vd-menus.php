<?php
/**
 * Menu registration and management
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Menus class
 */
class VD_Menus {
    
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
        add_action('init', array($this, 'register_menus'));
        add_filter('nav_menu_link_attributes', array($this, 'add_taxonomy_data_attributes'), 10, 4);
    }
    
    /**
     * Register menu locations
     */
    public function register_menus() {
        register_nav_menus(array(
            'home_products_sidebar_menu' => __('Home - Product Categories', VD_HOME_TEXT_DOMAIN),
            'home_posts_sidebar_menu' => __('Home - Blog Categories', VD_HOME_TEXT_DOMAIN)
        ));
    }
    
    /**
     * Add data attributes to menu items
     */
    public function add_taxonomy_data_attributes($atts, $item, $args, $depth) {
        // Only add attributes for our specific menu locations
        if (!isset($args->theme_location) || 
            (!in_array($args->theme_location, array('home_products_sidebar_menu', 'home_posts_sidebar_menu')))) {
            return $atts;
        }
        
        // Get menu item object type and object ID
        $object_type = get_post_meta($item->ID, '_menu_item_type', true);
        $object_id = get_post_meta($item->ID, '_menu_item_object_id', true);
        $object = get_post_meta($item->ID, '_menu_item_object', true);
        
        if ($object_type === 'taxonomy') {
            $term = get_term($object_id);
            
            if ($term && !is_wp_error($term)) {
                // Add data attributes based on taxonomy
                if ($term->taxonomy === 'product_cat' && $args->theme_location === 'home_products_sidebar_menu') {
                    $atts['data-taxonomy'] = 'product_cat';
                    $atts['data-term-id'] = $term->term_id;
                    $atts['data-term-slug'] = $term->slug;
                } elseif ($term->taxonomy === 'category' && $args->theme_location === 'home_posts_sidebar_menu') {
                    $atts['data-taxonomy'] = 'category';
                    $atts['data-term-id'] = $term->term_id;
                    $atts['data-term-slug'] = $term->slug;
                }
            }
        }
        
        return $atts;
    }
    
    /**
     * Render menu with custom walker if needed
     */
    public static function render_menu($location, $args = array()) {
        $defaults = array(
            'theme_location' => $location,
            'menu_class' => 'vd-sidebar-menu',
            'container_class' => 'vd-menu-container',
            'fallback_cb' => array(__CLASS__, 'fallback_menu')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return wp_nav_menu($args);
    }
    
    /**
     * Fallback menu when no menu is assigned
     */
    public static function fallback_menu($args) {
        if (!current_user_can('manage_options')) {
            return '';
        }
        
        $location_name = '';
        if ($args['theme_location'] === 'home_products_sidebar_menu') {
            $location_name = __('Home - Product Categories', VD_HOME_TEXT_DOMAIN);
        } elseif ($args['theme_location'] === 'home_posts_sidebar_menu') {
            $location_name = __('Home - Blog Categories', VD_HOME_TEXT_DOMAIN);
        }
        
        return sprintf(
            '<div class="vd-menu-fallback"><p>%s <a href="%s">%s</a></p></div>',
            sprintf(__('Please assign a menu to "%s" location.', VD_HOME_TEXT_DOMAIN), $location_name),
            admin_url('nav-menus.php'),
            __('Manage Menus', VD_HOME_TEXT_DOMAIN)
        );
    }
}