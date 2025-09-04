<?php
/**
 * Pagination management class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Pagination class
 */
class VD_Pagination {
    
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
     * Generate pagination HTML
     */
    public static function render_pagination($args = array()) {
        $defaults = array(
            'total_pages' => 1,
            'current_page' => 1,
            'section_id' => '',
            'section_type' => 'products', // 'products' or 'posts'
            'pagination_type' => 'both', // 'numbers', 'prev_next', 'both'
            'range' => 3,
            'ajax_action' => 'vidieu_filter_products',
            'category' => '',
            'taxonomy' => '',
            'per_page' => 12,
            'columns' => 4,
            'show_author' => true,
            'show_category' => true,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Don't show pagination if only one page
        if ($args['total_pages'] <= 1) {
            return '';
        }
        
        // Get pagination settings
        $enable_key = 'enable_' . $args['section_type'] . '_pagination';
        $type_key = $args['section_type'] . '_pagination_type';
        
        $pagination_enabled = VD_Admin::get_option($enable_key, true);
        $pagination_type = VD_Admin::get_option($type_key, 'both');
        
        if (!$pagination_enabled) {
            return '';
        }
        
        // Use settings value if not overridden
        if ($args['pagination_type'] === 'both') {
            $args['pagination_type'] = $pagination_type;
        }
        
        ob_start();
        ?>
        <nav class="page-numbers-wrap vd-pagination-wrapper" 
             data-section-id="<?php echo esc_attr($args['section_id']); ?>"
             data-section-type="<?php echo esc_attr($args['section_type']); ?>"
             data-ajax-action="<?php echo esc_attr($args['ajax_action']); ?>"
             data-category="<?php echo esc_attr($args['category']); ?>"
             data-taxonomy="<?php echo esc_attr($args['taxonomy']); ?>"
             data-per-page="<?php echo esc_attr($args['per_page']); ?>"
             data-columns="<?php echo esc_attr($args['columns']); ?>"
             <?php if ($args['section_type'] === 'posts') : ?>
             data-show-author="<?php echo $args['show_author'] ? 'yes' : 'no'; ?>"
             data-show-category="<?php echo $args['show_category'] ? 'yes' : 'no'; ?>"
             data-orderby="<?php echo esc_attr($args['orderby']); ?>"
             data-order="<?php echo esc_attr($args['order']); ?>"
             <?php endif; ?>>
            
            <ul class="page-numbers vd-pagination">
                <?php
                // Previous button
                if (in_array($args['pagination_type'], array('prev_next', 'both'))) {
                    self::render_prev_button($args);
                }
                
                // Page numbers
                if (in_array($args['pagination_type'], array('numbers', 'both'))) {
                    self::render_page_numbers($args);
                }
                
                // Next button
                if (in_array($args['pagination_type'], array('prev_next', 'both'))) {
                    self::render_next_button($args);
                }
                ?>
            </ul>
            
            <!-- Loading indicator -->
            <div class="vd-pagination-loading" style="display: none;">
                <span class="vd-spinner"></span>
                <?php esc_html_e('Loading...', VD_HOME_TEXT_DOMAIN); ?>
            </div>
        </nav>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render previous button
     */
    private static function render_prev_button($args) {
        $disabled = $args['current_page'] <= 1;
        $prev_page = max(1, $args['current_page'] - 1);
        
        // Only show previous button if not on first page
        if (!$disabled) : ?>
            <li>
                <a class="prev page-numbers vd-pagination-link" 
                   href="#" 
                   data-page="<?php echo esc_attr($prev_page); ?>">
                    <span class="pe7-icon pe-7s-angle-left"></span>
                </a>
            </li>
        <?php endif;
    }
    
    /**
     * Render next button
     */
    private static function render_next_button($args) {
        $disabled = $args['current_page'] >= $args['total_pages'];
        $next_page = min($args['total_pages'], $args['current_page'] + 1);
        
        // Only show next button if not on last page
        if (!$disabled) : ?>
            <li>
                <a class="next page-numbers vd-pagination-link" 
                   href="#" 
                   data-page="<?php echo esc_attr($next_page); ?>">
                    <span class="pe7-icon pe-7s-angle-right"></span>
                </a>
            </li>
        <?php endif;
    }
    
    /**
     * Render page numbers
     */
    private static function render_page_numbers($args) {
        $current = $args['current_page'];
        $total = $args['total_pages'];
        $range = $args['range'];
        
        // Calculate start and end
        $start = max(1, $current - $range);
        $end = min($total, $current + $range);
        
        // Show first page if not in range
        if ($start > 1) {
            self::render_page_link(1, $args, false);
            if ($start > 2) {
                self::render_ellipsis();
            }
        }
        
        // Show page numbers in range
        for ($i = $start; $i <= $end; $i++) {
            self::render_page_link($i, $args, $i === $current);
        }
        
        // Show last page if not in range
        if ($end < $total) {
            if ($end < $total - 1) {
                self::render_ellipsis();
            }
            self::render_page_link($total, $args, false);
        }
    }
    
    /**
     * Render single page link
     */
    private static function render_page_link($page, $args, $is_current = false) {
        ?>
        <li>
            <?php if ($is_current) : ?>
                <span aria-label="<?php printf(esc_attr__('Page %d', VD_HOME_TEXT_DOMAIN), $page); ?>" 
                      aria-current="page" 
                      class="page-numbers current">
                    <?php echo esc_html($page); ?>
                </span>
            <?php else : ?>
                <a aria-label="<?php printf(esc_attr__('Page %d', VD_HOME_TEXT_DOMAIN), $page); ?>" 
                   class="page-numbers vd-pagination-link" 
                   href="#"
                   data-page="<?php echo esc_attr($page); ?>">
                    <?php echo esc_html($page); ?>
                </a>
            <?php endif; ?>
        </li>
        <?php
    }
    
    /**
     * Render ellipsis
     */
    private static function render_ellipsis() {
        ?>
        <li>
            <span class="page-numbers dots">...</span>
        </li>
        <?php
    }
    
    /**
     * Calculate pagination info
     */
    public static function calculate_pagination($total_items, $per_page, $current_page = 1) {
        $total_pages = ceil($total_items / $per_page);
        $current_page = max(1, min($total_pages, $current_page));
        
        return array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'has_pagination' => $total_pages > 1,
            'offset' => ($current_page - 1) * $per_page
        );
    }
    
    /**
     * Get pagination parameters from request
     */
    public static function get_pagination_params($section_id) {
        // Check for section-specific page parameter first
        $page_param = 'vd_page_' . $section_id;
        $page = isset($_GET[$page_param]) ? absint($_GET[$page_param]) : 1;
        
        // Fallback to general page parameter if section-specific not found
        if ($page <= 1 && isset($_GET['paged'])) {
            $page = absint($_GET['paged']);
        }
        
        return max(1, $page);
    }
    
    /**
     * Get pagination URL
     */
    public static function get_pagination_url($page, $section_id) {
        $current_url = home_url(add_query_arg(null, null));
        
        // Remove existing pagination parameters
        $current_url = remove_query_arg(array('paged', 'vd_page_' . $section_id), $current_url);
        
        // Add section-specific page parameter
        if ($page > 1) {
            $current_url = add_query_arg('vd_page_' . $section_id, $page, $current_url);
        }
        
        return $current_url;
    }
}