<?php
/**
 * Page Sidebar Mappings class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Page_Sidebar_Mappings class
 */
class VD_Page_Sidebar_Mappings {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Table name
     */
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vd_page_sidebar_mappings';
        
        // Check if table exists on admin init
        add_action('admin_init', array($this, 'maybe_create_table'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vd_save_mapping', array($this, 'ajax_save_mapping'));
        add_action('wp_ajax_vd_delete_mapping', array($this, 'ajax_delete_mapping'));
        add_action('wp_ajax_vd_get_mappings', array($this, 'ajax_get_mappings'));
        
        // Frontend hooks
        add_action('template_redirect', array($this, 'check_page_mapping'));
        add_filter('body_class', array($this, 'add_body_classes'));
        add_filter('the_content', array($this, 'inject_sidebar_layout'), 999);
    }
    
    /**
     * Maybe create table
     */
    public function maybe_create_table() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_exists) {
            self::create_table();
        } else {
            // Check if shortcode_config column exists
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'shortcode_config'",
                DB_NAME,
                $this->table_name
            ));
            
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN shortcode_config longtext DEFAULT NULL AFTER sidebar_config");
            }

            // Check if per_page column exists
            $per_page_column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'per_page'",
                DB_NAME,
                $this->table_name
            ));

            if (!$per_page_column_exists) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN per_page int(11) DEFAULT 16 AFTER shortcode_config");
            }
        }
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_page_sidebar_mappings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            sidebar_type varchar(50) NOT NULL,
            sidebar_config longtext DEFAULT NULL,
            shortcode_config longtext DEFAULT NULL,
            per_page int(11) DEFAULT 16,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY page_id (page_id),
            KEY sidebar_type (sidebar_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('vd_page_sidebar_mappings_db_version', '1.0');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('Page Sidebar Mappings', VD_HOME_TEXT_DOMAIN),
            __('VD Page Mappings', VD_HOME_TEXT_DOMAIN),
            'manage_options',
            'vd-page-sidebar-mappings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_vd-page-sidebar-mappings' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'vd-page-mappings-admin',
            VD_HOME_PLUGIN_URL . 'assets/css/vd-page-mappings-admin.css',
            array(),
            VD_HOME_VERSION
        );
        
        wp_enqueue_script(
            'vd-page-mappings-admin',
            VD_HOME_PLUGIN_URL . 'assets/js/vd-page-mappings-admin.js',
            array('jquery', 'wp-util'),
            VD_HOME_VERSION,
            true
        );
        
        wp_localize_script('vd-page-mappings-admin', 'vdPageMappings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vd_page_mappings_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this mapping?', VD_HOME_TEXT_DOMAIN),
                'saved' => __('Mapping saved successfully.', VD_HOME_TEXT_DOMAIN),
                'deleted' => __('Mapping deleted successfully.', VD_HOME_TEXT_DOMAIN),
                'error' => __('An error occurred. Please try again.', VD_HOME_TEXT_DOMAIN)
            )
        ));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Page Sidebar Mappings', VD_HOME_TEXT_DOMAIN); ?></h1>
            
            <div class="vd-mappings-container">
                <div class="vd-add-mapping-section">
                    <h2><?php _e('Add New Mapping', VD_HOME_TEXT_DOMAIN); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="vd-page-select"><?php _e('Select Page', VD_HOME_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <select id="vd-page-select" class="regular-text">
                                    <option value=""><?php _e('Select a page...', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <?php
                                    $pages = get_pages(array('post_status' => 'publish'));
                                    foreach ($pages as $page) {
                                        echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vd-sidebar-type"><?php _e('Sidebar Type', VD_HOME_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <select id="vd-sidebar-type" class="regular-text">
                                    <option value=""><?php _e('Select sidebar type...', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <option value="product_categories"><?php _e('Product Categories Tree', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <option value="post_categories"><?php _e('Post Categories Tree', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <option value="homepage_preset"><?php _e('Homepage Preset', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <option value="custom_taxonomy"><?php _e('Custom Taxonomy', VD_HOME_TEXT_DOMAIN); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="vd-taxonomy-row" style="display: none;">
                            <th scope="row">
                                <label for="vd-taxonomy-select"><?php _e('Custom Taxonomy', VD_HOME_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <select id="vd-taxonomy-select" class="regular-text">
                                    <option value=""><?php _e('Select taxonomy...', VD_HOME_TEXT_DOMAIN); ?></option>
                                    <?php
                                    $taxonomies = get_taxonomies(array('public' => true), 'objects');
                                    foreach ($taxonomies as $taxonomy) {
                                        if (!in_array($taxonomy->name, array('product_cat', 'category'))) {
                                            echo '<option value="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->label) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vd-per-page"><?php _e('Products Per Page', VD_HOME_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <input type="number" id="vd-per-page" class="small-text"
                                       min="1" max="50" value="16" />
                                <p class="description"><?php _e('Number of products to display per page (1-50)', VD_HOME_TEXT_DOMAIN); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="vd-shortcode-config"><?php _e('Additional Configuration', VD_HOME_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <input type="text" id="vd-shortcode-config" class="large-text"
                                       placeholder='columns="4" title="SẢN PHẨM THEO NHU CẦU"'
                                       value='columns="4" title="SẢN PHẨM THEO NHU CẦU"' />
                                <p class="description"><?php _e('Additional shortcode attributes (per_page will be set from field above)', VD_HOME_TEXT_DOMAIN); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="vd-save-mapping" class="button button-primary">
                            <?php _e('Save Mapping', VD_HOME_TEXT_DOMAIN); ?>
                        </button>
                    </p>
                </div>
                
                <div class="vd-mappings-list-section">
                    <h2><?php _e('Current Mappings', VD_HOME_TEXT_DOMAIN); ?></h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Page', VD_HOME_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Sidebar Type', VD_HOME_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Configuration', VD_HOME_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Actions', VD_HOME_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody id="vd-mappings-tbody">
                            <tr>
                                <td colspan="4"><?php _e('Loading...', VD_HOME_TEXT_DOMAIN); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ajax save mapping
     */
    public function ajax_save_mapping() {
        check_ajax_referer('vd_page_mappings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $page_id = intval($_POST['page_id']);
        $sidebar_type = sanitize_text_field($_POST['sidebar_type']);
        $sidebar_config = isset($_POST['sidebar_config']) ? sanitize_text_field($_POST['sidebar_config']) : '';
        $shortcode_config = isset($_POST['shortcode_config']) ? sanitize_text_field($_POST['shortcode_config']) : '';
        $per_page = isset($_POST['per_page']) ? max(1, min(50, intval($_POST['per_page']))) : 16;
        
        if (!$page_id || !$sidebar_type) {
            wp_send_json_error(array('message' => __('Invalid data provided.', VD_HOME_TEXT_DOMAIN)));
        }
        
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE page_id = %d",
            $page_id
        ));
        
        if ($existing) {
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'sidebar_type' => $sidebar_type,
                    'sidebar_config' => $sidebar_config,
                    'shortcode_config' => $shortcode_config,
                    'per_page' => $per_page
                ),
                array('page_id' => $page_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'page_id' => $page_id,
                    'sidebar_type' => $sidebar_type,
                    'sidebar_config' => $sidebar_config,
                    'shortcode_config' => $shortcode_config,
                    'per_page' => $per_page
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            $error = $wpdb->last_error ?: __('Failed to save mapping.', VD_HOME_TEXT_DOMAIN);
            wp_send_json_error(array('message' => $error));
        }
    }
    
    /**
     * Ajax delete mapping
     */
    public function ajax_delete_mapping() {
        check_ajax_referer('vd_page_mappings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $id = intval($_POST['id']);
        
        if (!$id) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $result = $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * Ajax get mappings
     */
    public function ajax_get_mappings() {
        check_ajax_referer('vd_page_mappings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        global $wpdb;
        $mappings = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY id DESC");
        
        $data = array();
        foreach ($mappings as $mapping) {
            $page = get_post($mapping->page_id);
            if ($page) {
                $sidebar_type_label = $this->get_sidebar_type_label($mapping->sidebar_type);
                $config_label = $mapping->sidebar_config ?: '-';
                
                $data[] = array(
                    'id' => $mapping->id,
                    'page_title' => $page->post_title,
                    'sidebar_type_label' => $sidebar_type_label,
                    'config_label' => $config_label
                );
            }
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get sidebar type label
     */
    private function get_sidebar_type_label($type) {
        $labels = array(
            'product_categories' => __('Product Categories Tree', VD_HOME_TEXT_DOMAIN),
            'post_categories' => __('Post Categories Tree', VD_HOME_TEXT_DOMAIN),
            'homepage_preset' => __('Homepage Preset', VD_HOME_TEXT_DOMAIN),
            'custom_taxonomy' => __('Custom Taxonomy', VD_HOME_TEXT_DOMAIN)
        );
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    /**
     * Check page mapping on frontend
     */
    public function check_page_mapping() {
        if (!is_page()) {
            return;
        }
        
        $page_id = get_the_ID();
        $mapping = $this->get_page_mapping($page_id);
        
        if ($mapping) {
            // Store mapping data for later use
            $GLOBALS['vd_current_page_mapping'] = $mapping;
            
            // Enqueue necessary assets
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        }
    }
    
    /**
     * Get page mapping
     */
    public function get_page_mapping($page_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE page_id = %d",
            $page_id
        ));
    }
    
    /**
     * Add body classes
     */
    public function add_body_classes($classes) {
        if (isset($GLOBALS['vd_current_page_mapping'])) {
            $classes[] = 'vd-has-sidebar-mapping';
            $classes[] = 'vd-sidebar-' . $GLOBALS['vd_current_page_mapping']->sidebar_type;
        }
        return $classes;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Reuse existing home page assets
        wp_enqueue_style('vidieu-home', VD_HOME_PLUGIN_URL . 'assets/css/vidieu-home.css', array(), VD_HOME_VERSION);
        wp_enqueue_script('vidieu-home', VD_HOME_PLUGIN_URL . 'assets/js/vidieu-home.js', array('jquery'), VD_HOME_VERSION, true);
        
        // Enqueue page mapping specific styles
        wp_add_inline_style('vidieu-home', $this->get_page_mapping_styles());
    }
    
    /**
     * Get page mapping specific styles
     */
    private function get_page_mapping_styles() {
        return '
        /* Page mapping specific styles */
        .vd-has-sidebar-mapping .site-content {
            padding: 20px 0;
        }
        
        .vd-has-sidebar-mapping .vd-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        @media (min-width: 1200px) {
            .vd-has-sidebar-mapping .vd-container {
                max-width: 1200px;
            }
        }
        
        /* Hide default page title and breadcrumb when using sidebar layout */
        .vd-has-sidebar-mapping .entry-header,
        .vd-has-sidebar-mapping .page-header,
        .vd-has-sidebar-mapping .nasa-breadcrumb,
        .vd-has-sidebar-mapping .breadcrumb,
        .vd-has-sidebar-mapping .page-title {
            display: none !important;
        }
        
        /* Hide theme sidebar */
        .vd-has-sidebar-mapping #secondary,
        .vd-has-sidebar-mapping .widget-area,
        .vd-has-sidebar-mapping .nasa-sidebar {
            display: none !important;
        }
        
        /* Make content full width */
        .vd-has-sidebar-mapping #main-content,
        .vd-has-sidebar-mapping #primary,
        .vd-has-sidebar-mapping .content-area {
            width: 100% !important;
            max-width: none !important;
        }
        
        /* Remove theme margins/paddings */
        .vd-has-sidebar-mapping .site-content {
            padding: 0 !important;
        }
        
        .vd-has-sidebar-mapping #main-content > .container,
        .vd-has-sidebar-mapping #main-content > .row {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Our plugin container takes full control */
        .vd-has-sidebar-mapping .vd-home-section {
            margin: 0;
            padding: 20px 0;
        }
        ';
    }
    
    /**
     * Inject sidebar layout into page content
     */
    public function inject_sidebar_layout($content) {
        if (!is_page() || !isset($GLOBALS['vd_current_page_mapping'])) {
            return $content;
        }
        
        // Only inject on main query
        if (!in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        // Get mapping
        $mapping = $GLOBALS['vd_current_page_mapping'];
        
        // Get per_page from mapping or use default
        $per_page = !empty($mapping->per_page) ? $mapping->per_page : 16;

        // Get shortcode attributes from config and add per_page
        $shortcode_attrs = !empty($mapping->shortcode_config) ? $mapping->shortcode_config : 'columns="4" title="SẢN PHẨM THEO NHU CẦU"';
        $shortcode_attrs = 'per_page="' . $per_page . '" ' . $shortcode_attrs;
        
        // Add sidebar type to attributes so shortcode knows to skip its own sidebar
        $shortcode_attrs .= ' custom_sidebar="' . $mapping->sidebar_type . '"';
        if ($mapping->sidebar_config) {
            $shortcode_attrs .= ' custom_sidebar_config="' . esc_attr($mapping->sidebar_config) . '"';
        }
        
        // Start output buffering
        ob_start();
        
        // Check if we should display products or posts
        if ($mapping->sidebar_type === 'product_categories' || 
            ($mapping->sidebar_type === 'custom_taxonomy' && taxonomy_exists($mapping->sidebar_config))) {
            
            // Display products grid
            echo do_shortcode('[vidieu_home_products ' . $shortcode_attrs . ']');
            
        } elseif ($mapping->sidebar_type === 'post_categories') {
            
            // Display posts grid
            echo do_shortcode('[vidieu_home_posts per_page="9" columns="3"]');
            
        } elseif ($mapping->sidebar_type === 'homepage_preset') {
            
            // Display both sections
            ?>
            <div class="vd-home-sections">
                <div class="vd-section" data-section="products">
                    <?php echo do_shortcode('[vidieu_home_products ' . $shortcode_attrs . ']'); ?>
                </div>
                
                <div class="vd-section" data-section="posts" style="display: none;">
                    <?php echo do_shortcode('[vidieu_home_posts per_page="9" columns="3" title="Posts"]'); ?>
                </div>
            </div>
            <?php
        }
        
        $new_content = ob_get_clean();
        
        return $new_content;
    }
}