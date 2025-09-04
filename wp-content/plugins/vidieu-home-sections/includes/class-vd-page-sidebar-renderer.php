<?php
/**
 * Page Sidebar Renderer class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Page_Sidebar_Renderer class
 */
class VD_Page_Sidebar_Renderer {
    
    /**
     * Render sidebar based on mapping
     */
    public static function render_sidebar($mapping) {
        if (!$mapping) {
            return;
        }
        
        $output = '<div class="vd-sidebar vd-sticky">';
        
        switch ($mapping->sidebar_type) {
            case 'product_categories':
                $output .= self::render_product_categories();
                break;
                
            case 'post_categories':
                $output .= self::render_post_categories();
                break;
                
            case 'homepage_preset':
                $output .= self::render_homepage_preset();
                break;
                
            case 'custom_taxonomy':
                if ($mapping->sidebar_config) {
                    $output .= self::render_taxonomy_tree($mapping->sidebar_config);
                }
                break;
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render product categories tree
     */
    private static function render_product_categories() {
        $output = '<div class="vd-menu-section" data-section="all">';
        $output .= '<h3 class="vd-menu-title">' . __('Product Categories', VD_HOME_TEXT_DOMAIN) . '</h3>';
        $output .= '<ul class="vd-menu vd-sidebar-menu">';
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0
        ));
        
        foreach ($categories as $category) {
            $output .= self::render_category_item($category, 'product_cat');
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render post categories tree
     */
    private static function render_post_categories() {
        $output = '<div class="vd-menu-section" data-section="all">';
        $output .= '<h3 class="vd-menu-title">' . __('Post Categories', VD_HOME_TEXT_DOMAIN) . '</h3>';
        $output .= '<ul class="vd-menu vd-sidebar-menu">';
        
        $categories = get_terms(array(
            'taxonomy' => 'category',
            'hide_empty' => false,
            'parent' => 0
        ));
        
        foreach ($categories as $category) {
            $output .= self::render_category_item($category, 'category');
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render homepage preset
     */
    private static function render_homepage_preset() {
        $output = '';
        
        // Products section
        $output .= '<div class="vd-menu-section" data-section="products">';
        $output .= '<h3 class="vd-menu-title">' . __('Products', VD_HOME_TEXT_DOMAIN) . '</h3>';
        $output .= '<ul class="vd-menu vd-sidebar-menu">';
        
        $product_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
            'number' => 10
        ));
        
        foreach ($product_categories as $category) {
            $output .= '<li class="vd-menu-item">';
            $output .= '<a href="#" data-term-id="' . esc_attr($category->term_id) . '" data-taxonomy="product_cat">';
            $output .= esc_html($category->name);
            $output .= '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        // Posts section
        $output .= '<div class="vd-menu-section" data-section="posts">';
        $output .= '<h3 class="vd-menu-title">' . __('Posts', VD_HOME_TEXT_DOMAIN) . '</h3>';
        $output .= '<ul class="vd-menu vd-sidebar-menu">';
        
        $post_categories = get_terms(array(
            'taxonomy' => 'category',
            'hide_empty' => false,
            'parent' => 0,
            'number' => 10
        ));
        
        foreach ($post_categories as $category) {
            $output .= '<li class="vd-menu-item">';
            $output .= '<a href="#" data-term-id="' . esc_attr($category->term_id) . '" data-taxonomy="category">';
            $output .= esc_html($category->name);
            $output .= '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render custom taxonomy tree
     */
    private static function render_taxonomy_tree($taxonomy) {
        $taxonomy_obj = get_taxonomy($taxonomy);
        if (!$taxonomy_obj) {
            return '';
        }
        
        $output = '<div class="vd-menu-section" data-section="all">';
        $output .= '<h3 class="vd-menu-title">' . esc_html($taxonomy_obj->label) . '</h3>';
        $output .= '<ul class="vd-menu vd-sidebar-menu">';
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => 0
        ));
        
        foreach ($terms as $term) {
            $output .= self::render_category_item($term, $taxonomy);
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render category item with children (max 3 levels)
     */
    private static function render_category_item($category, $taxonomy, $level = 1) {
        $output = '<li class="vd-menu-item vd-level-' . $level;
        
        $children = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => $category->term_id
        ));
        
        if (!empty($children)) {
            $output .= ' vd-has-children';
            // Add expanded class for level 1 only by default
            if ($level < 2) {
                $output .= ' vd-expanded';
            }
        }
        
        $output .= '">';
        
        $output .= '<a href="#" data-term-id="' . esc_attr($category->term_id) . '" data-taxonomy="' . esc_attr($taxonomy) . '">';
        $output .= esc_html($category->name);
        $output .= '</a>';
        
        // Show all children without level limit
        if (!empty($children)) {
            // Display expanded up to level 2 by default
            $display = $level < 2 ? 'block' : 'none';
            $output .= '<ul class="vd-submenu" style="display: ' . $display . ';">';
            foreach ($children as $child) {
                $output .= self::render_category_item($child, $taxonomy, $level + 1);
            }
            $output .= '</ul>';
        }
        
        $output .= '</li>';
        
        return $output;
    }
}