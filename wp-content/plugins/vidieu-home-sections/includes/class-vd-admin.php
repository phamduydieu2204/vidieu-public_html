<?php
/**
 * Admin settings class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Admin class
 */
class VD_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Vidieu Home Sections', VD_HOME_TEXT_DOMAIN),
            __('Vidieu Home Sections', VD_HOME_TEXT_DOMAIN),
            'manage_options',
            'vidieu-home-sections',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function settings_init() {
        register_setting('vidieu_home_settings', 'vidieu_home_options');
        
        // General Section
        add_settings_section(
            'vidieu_home_general_section',
            __('General Settings', VD_HOME_TEXT_DOMAIN),
            array($this, 'general_section_callback'),
            'vidieu_home_settings'
        );
        
        // Pagination Section
        add_settings_section(
            'vidieu_home_pagination_section',
            __('Pagination Settings', VD_HOME_TEXT_DOMAIN),
            array($this, 'pagination_section_callback'),
            'vidieu_home_settings'
        );
        
        // Products pagination settings
        add_settings_field(
            'enable_products_pagination',
            __('Enable Products Pagination', VD_HOME_TEXT_DOMAIN),
            array($this, 'checkbox_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'enable_products_pagination',
                'description' => __('Enable pagination for product sections', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'products_pagination_type',
            __('Products Pagination Type', VD_HOME_TEXT_DOMAIN),
            array($this, 'select_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'products_pagination_type',
                'options' => array(
                    'numbers' => __('Numbers Only', VD_HOME_TEXT_DOMAIN),
                    'prev_next' => __('Previous/Next', VD_HOME_TEXT_DOMAIN),
                    'both' => __('Both Numbers and Prev/Next', VD_HOME_TEXT_DOMAIN)
                ),
                'description' => __('Choose pagination style for products', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'products_items_per_page',
            __('Products per Page', VD_HOME_TEXT_DOMAIN),
            array($this, 'number_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'products_items_per_page',
                'min' => 1,
                'max' => 50,
                'default' => 12,
                'description' => __('Number of products to show per page (1-50)', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Posts pagination settings
        add_settings_field(
            'enable_posts_pagination',
            __('Enable Posts Pagination', VD_HOME_TEXT_DOMAIN),
            array($this, 'checkbox_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'enable_posts_pagination',
                'description' => __('Enable pagination for post sections', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'posts_pagination_type',
            __('Posts Pagination Type', VD_HOME_TEXT_DOMAIN),
            array($this, 'select_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'posts_pagination_type',
                'options' => array(
                    'numbers' => __('Numbers Only', VD_HOME_TEXT_DOMAIN),
                    'prev_next' => __('Previous/Next', VD_HOME_TEXT_DOMAIN),
                    'both' => __('Both Numbers and Prev/Next', VD_HOME_TEXT_DOMAIN)
                ),
                'description' => __('Choose pagination style for posts', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'posts_items_per_page',
            __('Posts per Page', VD_HOME_TEXT_DOMAIN),
            array($this, 'number_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'posts_items_per_page',
                'min' => 1,
                'max' => 50,
                'default' => 9,
                'description' => __('Number of posts to show per page (1-50)', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Performance settings
        add_settings_field(
            'pagination_range',
            __('Pagination Range', VD_HOME_TEXT_DOMAIN),
            array($this, 'number_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_pagination_section',
            array(
                'name' => 'pagination_range',
                'min' => 1,
                'max' => 10,
                'default' => 3,
                'description' => __('Number of page links to show on each side of current page (1-10)', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Debug section
        add_settings_section(
            'vidieu_home_debug_section',
            __('Debug Settings', VD_HOME_TEXT_DOMAIN),
            array($this, 'debug_section_callback'),
            'vidieu_home_settings'
        );
        
        // Debug mode option
        add_settings_field(
            'enable_debug_mode',
            __('Debug Mode', VD_HOME_TEXT_DOMAIN),
            array($this, 'checkbox_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_debug_section',
            array(
                'name' => 'enable_debug_mode',
                'description' => __('Enable console logging for developers (not recommended for production)', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Buy Now section
        add_settings_section(
            'vidieu_home_buy_now_section',
            __('Buy Now Settings', VD_HOME_TEXT_DOMAIN),
            array($this, 'buy_now_section_callback'),
            'vidieu_home_settings'
        );
        
        // Enable Buy Now feature
        add_settings_field(
            'enable_buy_now',
            __('Enable Buy Now Button', VD_HOME_TEXT_DOMAIN),
            array($this, 'checkbox_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_buy_now_section',
            array(
                'name' => 'enable_buy_now',
                'description' => __('Show "Buy Now" button on product cards', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Buy Now destination
        add_settings_field(
            'buy_now_destination',
            __('After Adding to Cart', VD_HOME_TEXT_DOMAIN),
            array($this, 'select_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_buy_now_section',
            array(
                'name' => 'buy_now_destination',
                'options' => array(
                    'checkout' => __('Go to Checkout', VD_HOME_TEXT_DOMAIN),
                    'cart' => __('Go to Cart', VD_HOME_TEXT_DOMAIN)
                ),
                'default' => 'checkout',
                'description' => __('Where to redirect after clicking Buy Now', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Buy Now button label
        add_settings_field(
            'buy_now_label',
            __('Button Label (Simple Products)', VD_HOME_TEXT_DOMAIN),
            array($this, 'text_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_buy_now_section',
            array(
                'name' => 'buy_now_label',
                'default' => __('Buy Now', VD_HOME_TEXT_DOMAIN),
                'description' => __('Label for simple product buy now button', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Variable product button label
        add_settings_field(
            'buy_now_variable_label',
            __('Button Label (Variable Products)', VD_HOME_TEXT_DOMAIN),
            array($this, 'text_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_buy_now_section',
            array(
                'name' => 'buy_now_variable_label',
                'default' => __('Select Options', VD_HOME_TEXT_DOMAIN),
                'description' => __('Label for variable product button', VD_HOME_TEXT_DOMAIN)
            )
        );
        
        // Quickview Behavior section
        add_settings_section(
            'vidieu_home_quickview_section',
            __('Quickview Behavior Settings', VD_HOME_TEXT_DOMAIN),
            array($this, 'quickview_section_callback'),
            'vidieu_home_settings'
        );
        
        // Enable custom quickview behavior
        add_settings_field(
            'enable_custom_quickview',
            __('Enable Custom Quickview Behavior', VD_HOME_TEXT_DOMAIN),
            array($this, 'checkbox_field_callback'),
            'vidieu_home_settings',
            'vidieu_home_quickview_section',
            array(
                'name' => 'enable_custom_quickview',
                'description' => __('Enable custom quickview behavior (click to show attributes instead of sidebar)', VD_HOME_TEXT_DOMAIN)
            )
        );
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for Vidieu Home Sections plugin.', VD_HOME_TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Pagination section callback
     */
    public function pagination_section_callback() {
        echo '<p>' . __('Configure pagination settings for product and post sections.', VD_HOME_TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Debug section callback
     */
    public function debug_section_callback() {
        echo '<p>' . __('Debug settings for developers. <strong>Warning:</strong> Do not enable on production sites.', VD_HOME_TEXT_DOMAIN) . '</p>';
        echo '<p><em>' . __('Debug mode enables JavaScript console logging to help developers track events and troubleshoot issues.', VD_HOME_TEXT_DOMAIN) . '</em></p>';
    }
    
    /**
     * Buy Now section callback
     */
    public function buy_now_section_callback() {
        echo '<p><em>' . __('Configure the Buy Now feature to enable quick checkout for customers. Works with both simple and variable products.', VD_HOME_TEXT_DOMAIN) . '</em></p>';
    }
    
    /**
     * Quickview section callback
     */
    public function quickview_section_callback() {
        echo '<p>' . __('Configure quickview behavior for product sections.', VD_HOME_TEXT_DOMAIN) . '</p>';
        echo '<p><em>' . __('When enabled: Quickview icon will show attributes panel directly in the product card instead of opening the sidebar.', VD_HOME_TEXT_DOMAIN) . '</em></p>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $options = get_option('vidieu_home_options', array());
        $value = isset($options[$args['name']]) ? $options[$args['name']] : false;
        ?>
        <input type="checkbox" 
               name="vidieu_home_options[<?php echo esc_attr($args['name']); ?>]" 
               value="1" 
               <?php checked($value, 1); ?> />
        <?php if (!empty($args['description'])) : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Select field callback
     */
    public function select_field_callback($args) {
        $options = get_option('vidieu_home_options', array());
        $value = isset($options[$args['name']]) ? $options[$args['name']] : '';
        ?>
        <select name="vidieu_home_options[<?php echo esc_attr($args['name']); ?>]">
            <?php foreach ($args['options'] as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($args['description'])) : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $options = get_option('vidieu_home_options', array());
        $value = isset($options[$args['name']]) ? $options[$args['name']] : ($args['default'] ?? '');
        ?>
        <input type="number" 
               name="vidieu_home_options[<?php echo esc_attr($args['name']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($args['min'] ?? ''); ?>"
               max="<?php echo esc_attr($args['max'] ?? ''); ?>" />
        <?php if (!empty($args['description'])) : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $options = get_option('vidieu_home_options', array());
        $value = isset($options[$args['name']]) ? $options[$args['name']] : ($args['default'] ?? '');
        ?>
        <input type="text" 
               name="vidieu_home_options[<?php echo esc_attr($args['name']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (!empty($args['description'])) : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('vidieu_home_settings');
                do_settings_sections('vidieu_home_settings');
                submit_button(__('Save Settings', VD_HOME_TEXT_DOMAIN));
                ?>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                <h3><?php _e('Usage Instructions', VD_HOME_TEXT_DOMAIN); ?></h3>
                <p><strong><?php _e('Products Section:', VD_HOME_TEXT_DOMAIN); ?></strong></p>
                <code>[vidieu_home_products per_page="12" columns="4" title="Our Products"]</code>
                
                <p style="margin-top: 15px;"><strong><?php _e('Posts Section:', VD_HOME_TEXT_DOMAIN); ?></strong></p>
                <code>[vidieu_home_posts per_page="9" columns="3" title="Latest Posts"]</code>
                
                <p style="margin-top: 15px;"><strong><?php _e('Available Parameters:', VD_HOME_TEXT_DOMAIN); ?></strong></p>
                <ul style="margin-left: 20px;">
                    <li><code>per_page</code> - <?php _e('Number of items per page', VD_HOME_TEXT_DOMAIN); ?></li>
                    <li><code>columns</code> - <?php _e('Number of columns', VD_HOME_TEXT_DOMAIN); ?></li>
                    <li><code>title</code> - <?php _e('Section title', VD_HOME_TEXT_DOMAIN); ?></li>
                    <li><code>default_cat</code> - <?php _e('Default category to display', VD_HOME_TEXT_DOMAIN); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get option value
     */
    public static function get_option($key, $default = null) {
        $options = get_option('vidieu_home_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
}