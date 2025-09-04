<?php
/**
 * Shortcodes class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Shortcodes class
 */
class VD_Shortcodes {
    
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
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('vidieu_home_products', array($this, 'products_shortcode'));
        add_shortcode('vidieu_home_posts', array($this, 'posts_shortcode'));
    }
    
    /**
     * Products shortcode
     */
    public function products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'per_page' => '12',
            'columns' => '4',
            'default_cat' => '',
            'title' => __('Our Products', VD_HOME_TEXT_DOMAIN),
            'show_title' => 'yes',
            'max_width' => '1400',
            'breakpoint' => '991',
            'sticky_sidebar' => 'true',
            'heading' => '',
            'custom_sidebar' => '',
            'custom_sidebar_config' => ''
        ), $atts, 'vidieu_home_products');
        
        // Sanitize attributes
        $per_page = absint($atts['per_page']);
        $columns = absint($atts['columns']);
        $default_cat = sanitize_text_field($atts['default_cat']);
        $title = sanitize_text_field($atts['title']);
        $show_title = $atts['show_title'] === 'yes';
        $max_width = absint($atts['max_width']);
        $breakpoint = absint($atts['breakpoint']);
        $sticky_sidebar = $atts['sticky_sidebar'] === 'true';
        $heading = !empty($atts['heading']) ? sanitize_text_field($atts['heading']) : $title;
        
        // Validate columns (max 6, min 1)
        $columns = max(1, min(6, $columns));
        
        // Generate unique ID for this section
        $section_id = 'vd-products-' . uniqid();
        
        // Get current page for this section
        $current_page = VD_Pagination::get_pagination_params($section_id);
        
        // Set custom CSS variables if provided
        $inline_styles = '';
        if ($max_width && $max_width !== 1400) {
            $inline_styles .= '--vd-max-width: ' . $max_width . 'px; ';
        }
        if ($breakpoint && $breakpoint !== 991) {
            $inline_styles .= '--vd-mobile-breakpoint: ' . $breakpoint . 'px; ';
        }
        
        ob_start();
        ?>
        <div class="vd-home-section vd-home-products" 
             id="<?php echo esc_attr($section_id); ?>"
             data-per-page="<?php echo esc_attr($per_page); ?>" 
             data-columns="<?php echo esc_attr($columns); ?>"
             data-default-cat="<?php echo esc_attr($default_cat); ?>"
             data-breakpoint="<?php echo esc_attr($breakpoint); ?>"
             <?php if (!empty($inline_styles)) : ?>
             style="<?php echo esc_attr($inline_styles); ?>"
             <?php endif; ?>>
            
            <div class="vd-container">
                <?php if ($show_title && !empty($heading)) : ?>
                    <h2 class="vd-home-section-title"><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>
                
                <div class="vd-row">
                    <div class="vd-sidebar<?php echo $sticky_sidebar ? ' vd-sticky' : ''; ?>">
                        <?php
                        // Check if custom sidebar is provided (from page mapping)
                        if (!empty($atts['custom_sidebar'])) {
                            // Include renderer
                            require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-page-sidebar-renderer.php';
                            
                            // Create mapping object for renderer
                            $mapping = new stdClass();
                            $mapping->sidebar_type = $atts['custom_sidebar'];
                            $mapping->sidebar_config = $atts['custom_sidebar_config'];
                            
                            // Get sidebar HTML without wrapper
                            $sidebar_html = VD_Page_Sidebar_Renderer::render_sidebar($mapping);
                            
                            // Extract inner content (remove wrapper div)
                            if (preg_match('/<div[^>]*class="vd-sidebar[^"]*"[^>]*>(.*)<\/div>$/s', $sidebar_html, $matches)) {
                                echo $matches[1];
                            } else {
                                echo $sidebar_html;
                            }
                        } else {
                            // Default: render menu from settings
                            VD_Menus::render_menu('home_products_sidebar_menu', array(
                                'echo' => true,
                                'menu_class' => 'vd-sidebar-menu',
                                'container_class' => 'vd-menu-container'
                            ));
                        }
                        ?>
                    </div>
                    
                    <div class="vd-main">
                        <!-- Mobile Filter Toggle -->
                        <button class="vd-filter-toggle" 
                                aria-expanded="false" 
                                aria-controls="vd-filter-panel-<?php echo esc_attr($section_id); ?>">
                            <svg class="vd-filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 7H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M6 12H18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="vd-filter-toggle-text">
                                <span class="vd-filter-label"><?php esc_html_e('Category', VD_HOME_TEXT_DOMAIN); ?>:</span>
                                <span class="vd-filter-current"><?php esc_html_e('All', VD_HOME_TEXT_DOMAIN); ?></span>
                            </span>
                            <span class="vd-filter-arrow">▾</span>
                        </button>
                        
                        <div class="vd-products-grid">
                        <?php
                        // If default_cat is provided, show products immediately
                        if (!empty($default_cat)) {
                            echo VD_Templates::get_products_grid(array(
                                'per_page' => $per_page,
                                'columns' => $columns,
                                'category' => $default_cat,
                                'paged' => $current_page,
                                'section_id' => $section_id
                            ));
                        } else {
                            // Auto-load products from first category in sidebar
                            $first_category = null;
                            
                            // Check if custom sidebar is provided
                            if (!empty($atts['custom_sidebar'])) {
                                $first_category = self::get_first_category_from_custom_sidebar($atts['custom_sidebar'], $atts['custom_sidebar_config']);
                            } else {
                                // Default: get from menu
                                $first_category = self::get_first_menu_category('home_products_sidebar_menu');
                            }
                            
                            if ($first_category) {
                                echo VD_Templates::get_products_grid(array(
                                    'per_page' => $per_page,
                                    'columns' => $columns,
                                    'category' => $first_category,
                                    'paged' => $current_page,
                                    'section_id' => $section_id
                                ));
                            } else {
                                // Fallback: Show placeholder message when no categories found
                                echo '<div class="vd-placeholder-message">';
                                echo '<div class="vd-placeholder-content">';
                                echo '<h3>' . __('Choose a Category', VD_HOME_TEXT_DOMAIN) . '</h3>';
                                echo '<p>' . __('Please select a category from the sidebar to view products.', VD_HOME_TEXT_DOMAIN) . '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Filter Panel -->
            <div class="vd-filter-overlay"></div>
            <aside id="vd-filter-panel-<?php echo esc_attr($section_id); ?>" 
                   class="vd-filter-panel" 
                   aria-hidden="true"
                   data-section="products">
                <div class="vd-filter-panel-header">
                    <h3 class="vd-filter-panel-title"><?php esc_html_e('Filter Products', VD_HOME_TEXT_DOMAIN); ?></h3>
                    <button class="vd-filter-panel-close" aria-label="<?php esc_attr_e('Close filter panel', VD_HOME_TEXT_DOMAIN); ?>">
                        &times;
                    </button>
                </div>
                <div class="vd-filter-panel-body">
                    <?php
                    // Check if custom sidebar is provided (from page mapping)
                    if (!empty($atts['custom_sidebar'])) {
                        // Include renderer if not already included
                        if (!class_exists('VD_Page_Sidebar_Renderer')) {
                            require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-page-sidebar-renderer.php';
                        }
                        
                        // Create mapping object for renderer
                        $mapping = new stdClass();
                        $mapping->sidebar_type = $atts['custom_sidebar'];
                        $mapping->sidebar_config = $atts['custom_sidebar_config'];
                        
                        // Get sidebar HTML without wrapper
                        $sidebar_html = VD_Page_Sidebar_Renderer::render_sidebar($mapping);
                        
                        // Extract inner content (remove wrapper div)
                        if (preg_match('/<div[^>]*class="vd-sidebar[^"]*"[^>]*>(.*)<\/div>$/s', $sidebar_html, $matches)) {
                            echo $matches[1];
                        } else {
                            echo $sidebar_html;
                        }
                    } else {
                        // Default: render menu from settings
                        VD_Menus::render_menu('home_products_sidebar_menu', array(
                            'echo' => true,
                            'menu_class' => 'vd-sidebar-menu',
                            'container_class' => 'vd-menu-container'
                        ));
                    }
                    ?>
                </div>
            </aside>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Posts shortcode
     */
    public function posts_shortcode($atts) {
        $atts = shortcode_atts(array(
            'per_page' => '9',
            'columns' => '3', // Backward compatibility - back to 3
            'columns_desktop' => '3', // Updated to 3 columns default
            'columns_tablet' => '2', 
            'columns_mobile' => '1',
            'default_cat' => '',
            'title' => __('Latest Posts', VD_HOME_TEXT_DOMAIN),
            'show_title' => 'yes',
            'show_author' => 'yes',
            'show_category' => 'yes',
            'max_width' => '1400',
            'breakpoint' => '991',
            'sticky_sidebar' => 'true',
            'heading' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts, 'vidieu_home_posts');
        
        // Sanitize attributes
        $per_page = absint($atts['per_page']);
        $columns = absint($atts['columns']); // Backward compatibility
        $columns_desktop = !empty($atts['columns_desktop']) ? absint($atts['columns_desktop']) : $columns;
        $columns_tablet = !empty($atts['columns_tablet']) ? absint($atts['columns_tablet']) : min(2, $columns_desktop);
        $columns_mobile = !empty($atts['columns_mobile']) ? absint($atts['columns_mobile']) : 1;
        $default_cat = sanitize_text_field($atts['default_cat']);
        $title = sanitize_text_field($atts['title']);
        $show_title = $atts['show_title'] === 'yes';
        $show_author = $atts['show_author'] === 'yes';
        $show_category = $atts['show_category'] === 'yes';
        $max_width = absint($atts['max_width']);
        $breakpoint = absint($atts['breakpoint']);
        $sticky_sidebar = $atts['sticky_sidebar'] === 'true';
        $heading = !empty($atts['heading']) ? sanitize_text_field($atts['heading']) : $title;
        $orderby = sanitize_text_field($atts['orderby']);
        $order = strtoupper(sanitize_text_field($atts['order'])) === 'ASC' ? 'ASC' : 'DESC';
        
        // Validate columns (max 6, min 1)
        $columns_desktop = max(1, min(6, $columns_desktop));
        $columns_tablet = max(1, min(4, $columns_tablet));
        $columns_mobile = max(1, min(2, $columns_mobile));
        
        // Generate unique ID for this section
        $section_id = 'vd-posts-' . uniqid();
        
        // Get current page for this section
        $current_page = VD_Pagination::get_pagination_params($section_id);
        
        // Set custom CSS variables if provided
        $inline_styles = '';
        if ($max_width && $max_width !== 1400) {
            $inline_styles .= '--vd-max-width: ' . $max_width . 'px; ';
        }
        if ($breakpoint && $breakpoint !== 991) {
            $inline_styles .= '--vd-mobile-breakpoint: ' . $breakpoint . 'px; ';
        }
        // Add columns variables
        $inline_styles .= '--vd-posts-cols-desktop: ' . $columns_desktop . '; ';
        $inline_styles .= '--vd-posts-cols-tablet: ' . $columns_tablet . '; ';
        $inline_styles .= '--vd-posts-cols-mobile: ' . $columns_mobile . '; ';
        
        ob_start();
        ?>
        <div class="vd-home-section vd-home-posts" 
             id="<?php echo esc_attr($section_id); ?>"
             data-per-page="<?php echo esc_attr($per_page); ?>" 
             data-columns="<?php echo esc_attr($columns_desktop); ?>"
             data-columns-desktop="<?php echo esc_attr($columns_desktop); ?>"
             data-columns-tablet="<?php echo esc_attr($columns_tablet); ?>"
             data-columns-mobile="<?php echo esc_attr($columns_mobile); ?>"
             data-default-cat="<?php echo esc_attr($default_cat); ?>"
             data-breakpoint="<?php echo esc_attr($breakpoint); ?>"
             data-show-author="<?php echo $show_author ? 'yes' : 'no'; ?>"
             data-show-category="<?php echo $show_category ? 'yes' : 'no'; ?>"
             data-orderby="<?php echo esc_attr($orderby); ?>"
             data-order="<?php echo esc_attr($order); ?>"
             <?php if (!empty($inline_styles)) : ?>
             style="<?php echo esc_attr($inline_styles); ?>"
             <?php endif; ?>>
            
            <div class="vd-container">
                <?php if ($show_title && !empty($heading)) : ?>
                    <h2 class="vd-home-section-title"><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>
                
                <div class="vd-row">
                    <div class="vd-sidebar<?php echo $sticky_sidebar ? ' vd-sticky' : ''; ?>">
                        <?php
                        VD_Menus::render_menu('home_posts_sidebar_menu', array(
                            'echo' => true,
                            'menu_class' => 'vd-sidebar-menu',
                            'container_class' => 'vd-menu-container'
                        ));
                        ?>
                    </div>
                    
                    <div class="vd-main">
                        <!-- Mobile Filter Toggle -->
                        <button class="vd-filter-toggle" 
                                aria-expanded="false" 
                                aria-controls="vd-filter-panel-<?php echo esc_attr($section_id); ?>">
                            <svg class="vd-filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 7H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M6 12H18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="vd-filter-toggle-text">
                                <span class="vd-filter-label"><?php esc_html_e('Category', VD_HOME_TEXT_DOMAIN); ?>:</span>
                                <span class="vd-filter-current"><?php esc_html_e('All', VD_HOME_TEXT_DOMAIN); ?></span>
                            </span>
                            <span class="vd-filter-arrow">▾</span>
                        </button>
                        
                        <div class="vd-posts-grid">
                        <?php
                        // If default_cat is provided, show posts immediately
                        if (!empty($default_cat)) {
                            echo VD_Templates::get_posts_grid(array(
                                'per_page' => $per_page,
                                'columns' => $columns_desktop,
                                'columns_desktop' => $columns_desktop,
                                'columns_tablet' => $columns_tablet,
                                'columns_mobile' => $columns_mobile,
                                'category' => $default_cat,
                                'show_author' => $show_author,
                                'show_category' => $show_category,
                                'orderby' => $orderby,
                                'order' => $order,
                                'paged' => $current_page,
                                'section_id' => $section_id
                            ));
                        } else {
                            // Auto-load posts from first category in sidebar menu
                            $first_category = self::get_first_menu_category('home_posts_sidebar_menu');
                            if ($first_category) {
                                echo VD_Templates::get_posts_grid(array(
                                    'per_page' => $per_page,
                                    'columns' => $columns_desktop,
                                    'columns_desktop' => $columns_desktop,
                                    'columns_tablet' => $columns_tablet,
                                    'columns_mobile' => $columns_mobile,
                                    'category' => $first_category,
                                    'show_author' => $show_author,
                                    'show_category' => $show_category,
                                    'orderby' => $orderby,
                                    'order' => $order,
                                    'paged' => $current_page,
                                    'section_id' => $section_id
                                ));
                            } else {
                                // Fallback: Show placeholder message when no categories found
                                echo '<div class="vd-placeholder-message">';
                                echo '<div class="vd-placeholder-content">';
                                echo '<h3>' . __('Choose a Category', VD_HOME_TEXT_DOMAIN) . '</h3>';
                                echo '<p>' . __('Please select a category from the sidebar to view posts.', VD_HOME_TEXT_DOMAIN) . '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Filter Panel -->
            <div class="vd-filter-overlay"></div>
            <aside id="vd-filter-panel-<?php echo esc_attr($section_id); ?>" 
                   class="vd-filter-panel" 
                   aria-hidden="true"
                   data-section="posts">
                <div class="vd-filter-panel-header">
                    <h3 class="vd-filter-panel-title"><?php esc_html_e('Filter Posts', VD_HOME_TEXT_DOMAIN); ?></h3>
                    <button class="vd-filter-panel-close" aria-label="<?php esc_attr_e('Close filter panel', VD_HOME_TEXT_DOMAIN); ?>">
                        &times;
                    </button>
                </div>
                <div class="vd-filter-panel-body">
                    <?php
                    VD_Menus::render_menu('home_posts_sidebar_menu', array(
                        'echo' => true,
                        'menu_class' => 'vd-sidebar-menu',
                        'container_class' => 'vd-menu-container'
                    ));
                    ?>
                </div>
            </aside>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get first category from menu
     */
    private static function get_first_menu_category($menu_location) {
        // Get menu items
        $menu_items = wp_get_nav_menu_items(get_nav_menu_locations()[$menu_location] ?? 0);
        
        if (!$menu_items) {
            return null;
        }
        
        // Determine taxonomy based on menu location
        $taxonomy = strpos($menu_location, 'products') !== false ? 'product_cat' : 'category';
        $object_type = strpos($menu_location, 'products') !== false ? 'product_cat' : 'category';
        
        // Find first category
        foreach ($menu_items as $item) {
            if ($item->object === $object_type && !empty($item->object_id)) {
                $term = get_term($item->object_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    return $item->object_id; // Return term ID instead of slug for consistent handling
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get first category from custom sidebar
     * 
     * @param string $sidebar_type The type of sidebar
     * @param string $sidebar_config The sidebar configuration
     * @return int|null The first category ID or null
     */
    private static function get_first_category_from_custom_sidebar($sidebar_type, $sidebar_config = '') {
        switch ($sidebar_type) {
            case 'product_categories':
                // Get first product category
                $categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent' => 0,
                    'number' => 1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC'
                ));
                
                if (!empty($categories) && !is_wp_error($categories)) {
                    return $categories[0]->term_id;
                }
                break;
                
            case 'post_categories':
                // Get first post category
                $categories = get_terms(array(
                    'taxonomy' => 'category',
                    'hide_empty' => false,
                    'parent' => 0,
                    'number' => 1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC'
                ));
                
                if (!empty($categories) && !is_wp_error($categories)) {
                    return $categories[0]->term_id;
                }
                break;
                
            case 'custom_taxonomy':
                // Handle custom taxonomy if config is provided
                if (!empty($sidebar_config)) {
                    $config = json_decode($sidebar_config, true);
                    if ($config && isset($config['taxonomy'])) {
                        $categories = get_terms(array(
                            'taxonomy' => $config['taxonomy'],
                            'hide_empty' => false,
                            'parent' => 0,
                            'number' => 1,
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($categories) && !is_wp_error($categories)) {
                            return $categories[0]->term_id;
                        }
                    }
                }
                break;
        }
        
        return null;
    }
}