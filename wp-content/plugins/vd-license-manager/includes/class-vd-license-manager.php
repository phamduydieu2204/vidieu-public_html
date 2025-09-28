<?php
/**
 * VD License Manager Main Class
 *
 * The core plugin class that orchestrates all functionality
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_License_Manager class
 *
 * Main plugin class that handles initialization and coordination
 */
class VD_License_Manager {

    /**
     * Single instance of the plugin
     *
     * @var VD_License_Manager
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version = VD_LM_VERSION;

    /**
     * Admin menu instance
     *
     * @var VD_Admin_Menu
     */
    private $admin_menu;

    /**
     * Get single instance of the plugin
     *
     * @since 1.0.0
     * @return VD_License_Manager Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     */
    public function init() {
        // Load text domain for translations
        add_action('init', [$this, 'load_textdomain']);

        // Load dependencies
        $this->load_dependencies();

        // Setup hooks
        $this->setup_hooks();

        // Initialize components
        $this->init_components();

        // Setup REST API routes
        $this->setup_api_routes();

        // Setup cron hooks
        $this->setup_cron_hooks();
    }

    /**
     * Load plugin text domain for translations
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            VD_LM_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(VD_LM_FILE)) . '/languages/'
        );
    }

    /**
     * Load plugin dependencies
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Core classes (will be expanded in later sprints)
        // For Sprint 1, we only load basic admin functionality

        // Sprint 2.1 - Load encryption manager
        require_once VD_LM_PATH . 'includes/class-vd-encryption-manager.php';

        // Sprint 2.2 - Load database manager (schema definitions only)
        require_once VD_LM_PATH . 'includes/class-vd-database-manager.php';

        // Sprint 2.4 - Load license core CRUD operations
        require_once VD_LM_PATH . 'includes/class-vd-license-core.php';

        // Sprint 2.6 - Load provider account and device managers
        require_once VD_LM_PATH . 'includes/class-vd-provider-account.php';
        require_once VD_LM_PATH . 'includes/class-vd-device-manager.php';

        // Sprint 2.7 - Load migration manager and audit logger
        require_once VD_LM_PATH . 'includes/class-vd-audit-logger.php';
        require_once VD_LM_PATH . 'includes/class-vd-migration-manager.php';

        // Table prefix migration utility (temporary)
        require_once VD_LM_PATH . 'includes/class-vd-table-migrator.php';

        // Sprint 3.1 - Load security manager
        require_once VD_LM_PATH . 'includes/class-vd-security-manager.php';

        // Sprint 3.3.1 - Load capability manager (basic structure only)
        require_once VD_LM_PATH . 'includes/class-vd-capability-manager.php';

        // Step 3.4.6.2 - Safe Variable Declaration (security audit file path)
        $security_audit_file = VD_LM_PATH . 'includes/class-vd-security-audit.php';
        // Note: Variable declared only - no file loading or execution yet

        // Load admin interface if in admin
        if (vd_is_admin()) {
            require_once VD_LM_PATH . 'admin/class-vd-admin-menu.php';
        }

        // Load public classes if not in admin
        if (!vd_is_admin()) {
            // Will be implemented in Sprint 7 - Frontend Portal
            // require_once VD_LM_PATH . 'public/class-vd-shortcode.php';
        }

        // Load integrations (will be implemented in Sprint 5)
        // require_once VD_LM_PATH . 'integrations/class-vd-lmfwc.php';
        // require_once VD_LM_PATH . 'integrations/class-vd-woocommerce.php';
    }

    /**
     * Setup WordPress hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Asset enqueueing
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers (will be expanded in later sprints)
        add_action('wp_ajax_vd_test_connection', [$this, 'handle_test_connection']);
        add_action('wp_ajax_nopriv_vd_validate_license', [$this, 'handle_public_license_validation']);

        // Admin notices
        add_action('admin_notices', [$this, 'display_admin_notices']);

        // Plugin action links
        add_filter('plugin_action_links_' . VD_LM_BASENAME, [$this, 'add_plugin_action_links']);
    }

    /**
     * Initialize plugin components
     *
     * @since 1.0.0
     */
    private function init_components() {
        // Initialize admin menu if in admin
        if (vd_is_admin() && class_exists('VD_Admin_Menu')) {
            $this->admin_menu = new VD_Admin_Menu();
        }

        // Initialize migration manager (Sprint 2.7)
        if (class_exists('VD_Migration_Manager')) {
            $migration_manager = VD_Migration_Manager::get_instance();
            $migration_manager->init();
        }

        // Initialize security manager (Sprint 3.1)
        if (class_exists('VD_Security_Manager')) {
            VD_Security_Manager::get_instance();
        }

        // Initialize capability manager (Sprint 3.3.1 - basic structure only)
        if (class_exists('VD_Capability_Manager')) {
            VD_Capability_Manager::get_instance();
        }

        // Initialize other components in later sprints
        // API Controllers (Sprint 4)
        // LMfWC Integration (Sprint 5)
    }

    /**
     * Setup REST API routes
     *
     * @since 1.0.0
     */
    private function setup_api_routes() {
        add_action('rest_api_init', function() {
            // API routes will be implemented in Sprint 4
            // For now, just register the namespace
            register_rest_route('vd/v1', '/status', [
                'methods' => 'GET',
                'callback' => [$this, 'api_status_check'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    /**
     * Setup cron hooks
     *
     * @since 1.0.0
     */
    private function setup_cron_hooks() {
        // Cleanup logs
        add_action('vd_cleanup_logs', [$this, 'cleanup_old_logs']);

        // Provider health check (will be implemented in Sprint 5)
        add_action('vd_check_provider_health', [$this, 'check_provider_health']);

        // License expiration check
        add_action('vd_check_license_expiration', [$this, 'check_license_expiration']);
    }

    /**
     * Enqueue public assets
     *
     * @since 1.0.0
     */
    public function enqueue_public_assets() {
        // Only enqueue on pages that need the portal
        if (!$this->should_load_public_assets()) {
            return;
        }

        wp_enqueue_script(
            'vd-license-manager-public',
            VD_LM_URL . 'public/assets/js/public.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'vd-license-manager-public',
            VD_LM_URL . 'public/assets/css/public.css',
            [],
            $this->version
        );

        // Localize script for AJAX
        wp_localize_script('vd-license-manager-public', 'vd_public_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vd/v1/'),
            'nonce' => wp_create_nonce('vd_public_nonce'),
            'strings' => [
                'loading' => __('Loading...', VD_LM_TEXT_DOMAIN),
                'error' => __('An error occurred', VD_LM_TEXT_DOMAIN),
                'invalid_license' => __('Invalid license key', VD_LM_TEXT_DOMAIN)
            ]
        ]);
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on VD License Manager admin pages
        if (strpos($hook, 'vd-license') === false) {
            return;
        }

        wp_enqueue_script(
            'vd-license-manager-admin',
            VD_LM_URL . 'admin/assets/js/admin.js',
            ['jquery', 'wp-api'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'vd-license-manager-admin',
            VD_LM_URL . 'admin/assets/css/admin.css',
            [],
            $this->version
        );

        // Localize script for admin AJAX
        wp_localize_script('vd-license-manager-admin', 'vd_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vd/v1/'),
            'nonce' => wp_create_nonce('vd_admin_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this item?', VD_LM_TEXT_DOMAIN),
                'saving' => __('Saving...', VD_LM_TEXT_DOMAIN),
                'saved' => __('Saved!', VD_LM_TEXT_DOMAIN),
                'error' => __('An error occurred', VD_LM_TEXT_DOMAIN)
            ]
        ]);
    }

    /**
     * Check if public assets should be loaded
     *
     * @since 1.0.0
     * @return bool True if assets should be loaded
     */
    private function should_load_public_assets() {
        global $post;

        // Load if shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vd_license_portal')) {
            return true;
        }

        // Load on specific pages if needed
        $load_pages = apply_filters('vd_load_public_assets_pages', []);
        if (is_page($load_pages)) {
            return true;
        }

        return false;
    }

    /**
     * Handle test connection AJAX
     *
     * @since 1.0.0
     */
    public function handle_test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vd_admin_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', VD_LM_TEXT_DOMAIN)]);
        }

        // Check capabilities
        if (!current_user_can('manage_vd_settings')) {
            wp_send_json_error(['message' => __('Insufficient permissions', VD_LM_TEXT_DOMAIN)]);
        }

        // For Sprint 1, just return a success response
        wp_send_json_success([
            'message' => __('Connection test successful', VD_LM_TEXT_DOMAIN),
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Handle public license validation AJAX
     *
     * @since 1.0.0
     */
    public function handle_public_license_validation() {
        // This will be implemented in Sprint 4 - API Layer
        wp_send_json_error(['message' => __('Not implemented yet', VD_LM_TEXT_DOMAIN)]);
    }

    /**
     * Display admin notices
     *
     * @since 1.0.0
     */
    public function display_admin_notices() {
        // Only display notices if text domain is loaded to avoid translation timing issues
        if (!did_action('init')) {
            return;
        }

        // Check if database tables need to be created
        if (get_option('vd_license_manager_needs_db_creation')) {
            echo '<div class="notice notice-warning"><p>';
            printf(
                /* translators: %s: link to settings page */
                __('VD License Manager: Database tables will be created in Sprint 2. Current status: %s', VD_LM_TEXT_DOMAIN),
                '<strong>' . __('Foundation Ready', VD_LM_TEXT_DOMAIN) . '</strong>'
            );
            echo '</p></div>';
        }

        // Check encryption key
        if (!vd_is_encryption_key_valid()) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . __('VD License Manager:', VD_LM_TEXT_DOMAIN) . '</strong> ';
            echo __('VD_ENCRYPTION_KEY is not properly configured in wp-config.php', VD_LM_TEXT_DOMAIN);
            echo '</p></div>';
        }
    }

    /**
     * Add plugin action links
     *
     * @since 1.0.0
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_plugin_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=vd-license-dashboard') . '">' . __('Dashboard', VD_LM_TEXT_DOMAIN) . '</a>',
            '<a href="' . admin_url('admin.php?page=vd-license-settings') . '">' . __('Settings', VD_LM_TEXT_DOMAIN) . '</a>'
        ];

        return array_merge($plugin_links, $links);
    }

    /**
     * API status check endpoint
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response
     */
    public function api_status_check($request) {
        return new WP_REST_Response([
            'status' => 'ok',
            'version' => $this->version,
            'timestamp' => current_time('mysql'),
            'requirements' => VD_Activator::get_requirements_status()
        ], 200);
    }

    /**
     * Cleanup old logs (cron job)
     *
     * @since 1.0.0
     */
    public function cleanup_old_logs() {
        // This will be implemented in Sprint 2 - Database Layer
        vd_debug_log('Cleanup logs cron job executed (placeholder)');
    }

    /**
     * Check provider health (cron job)
     *
     * @since 1.0.0
     */
    public function check_provider_health() {
        // This will be implemented in Sprint 5 - LMfWC Integration
        vd_debug_log('Provider health check cron job executed (placeholder)');
    }

    /**
     * Check license expiration (cron job)
     *
     * @since 1.0.0
     */
    public function check_license_expiration() {
        // This will be implemented in Sprint 2 - Database Layer
        vd_debug_log('License expiration check cron job executed (placeholder)');
    }

    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string Plugin version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get admin menu instance
     *
     * @since 1.0.0
     * @return VD_Admin_Menu|null Admin menu instance
     */
    public function get_admin_menu() {
        return $this->admin_menu;
    }
}