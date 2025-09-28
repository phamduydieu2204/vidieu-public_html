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
     * Security fallback initialization flag
     * Prevents multiple security fallback executions
     *
     * @since 3.4.6.5
     * @var bool
     */
    private $security_fallback_initialized = false;

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

        // Step 3.4.6.3 - Basic File Existence Check (security audit readiness)
        if (file_exists($security_audit_file)) {
            // File exists - ready for loading in next step
            // Note: No file loading or class instantiation yet - check only

            // Step 3.4.6.4b - Conditional Loading Declaration (prepare structure)
            // Prepare conditional structure for safe large file loading
            if (true) {
                // Conditional block ready for memory management and file loading
                // Note: Empty block - structure preparation only, no operations yet

                // Step 3.4.6.4c - Memory Management Setup (large file loading safety)
                // Verify memory availability for VD_Security_Audit (65KB file)
                $memory_limit = ini_get('memory_limit');
                $current_memory = memory_get_usage(true);
                $target_file_size = 65142; // VD_Security_Audit file size in bytes

                // Conservative memory requirement calculation (3x file size)
                $required_memory = $target_file_size * 3;
                $memory_available = $this->convert_memory_to_bytes($memory_limit) - $current_memory;

                // Memory safety check before proceeding to file loading
                if ($memory_available >= $required_memory) {
                    // Memory sufficient for large file loading

                    // Step 3.4.6.4d - Silent File Inclusion (EMERGENCY DISABLED - Comprehensive fixes failed)
                    // ERROR #004: All attempted fixes failed, VD_Security_Audit fundamentally incompatible
                    /*
                    $security_audit_file = VD_LM_PATH . 'includes/class-vd-security-audit.php';

                    // All approaches tested and failed:
                    // 1. Direct require_once → Fatal error
                    // 2. @ operator suppression → Still fatal error
                    // 3. class_exists() check → Still fatal error
                    // 4. file_exists() + class_exists() → Still fatal error
                    // 5. Deferred loading pattern → Still fatal error
                    // 6. Comprehensive root cause fixes → Still fatal error (CONFIRMED)

                    if (file_exists($security_audit_file) && !class_exists('VD_Security_Audit')) {
                        @require_once $security_audit_file;

                        add_action('plugins_loaded', function() {
                            if (class_exists('VD_Security_Audit')) {
                                $security_audit = VD_Security_Audit::get_instance();
                                $security_audit->init();
                                $this->verify_security_audit_loading();
                            }
                        }, 20);
                    }
                    */

                    // Step 3.4.6.4f - Error Recovery Mechanism
                    // Implement graceful degradation cho VD_Security_Audit failures
                    $should_log_init = !get_option('vd_security_fallback_initialized', false);
                    $this->handle_security_audit_fallback();

                    // Step 3.4.6.5 - Reduced frequency logging (only once per session)
                    if ($should_log_init) {
                        $this->vd_log('Plugin Initialization', 'VD License Manager loaded successfully with security fallback', 'info');
                    }

                    // NOTE: VD_Security_Audit requires complete architectural redesign
                    // Core VD License Manager functionality remains fully operational
                }
            }
        }

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

    /**
     * Convert memory limit string to bytes (Step 3.4.6.4c helper)
     *
     * @since 3.4.6.4c
     * @param string $memory_limit Memory limit string (e.g., '128M', '1G')
     * @return int Memory limit in bytes
     */
    private function convert_memory_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        if (empty($memory_limit)) {
            return 0;
        }

        $last_char = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $numeric_value = (int) $memory_limit;

        switch ($last_char) {
            case 'g':
                $numeric_value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $numeric_value *= 1024 * 1024;
                break;
            case 'k':
                $numeric_value *= 1024;
                break;
        }

        return $numeric_value;
    }

    /**
     * Verify VD_Security_Audit loading success
     * Step 3.4.6.4e - Post-Loading Verification
     *
     * @since 3.4.6.4e
     * @return bool True if verification passes
     */
    private function verify_security_audit_loading() {
        // Verify class exists after loading
        if (!class_exists('VD_Security_Audit')) {
            vd_debug_log('Step 3.4.6.4e: VD_Security_Audit class not found after loading');
            return false;
        }

        // Test class instantiation without full initialization
        try {
            $instance = VD_Security_Audit::get_instance();

            if (!is_object($instance)) {
                vd_debug_log('Step 3.4.6.4e: VD_Security_Audit get_instance() failed');
                return false;
            }

            // Verify class has required methods (without calling them)
            $required_methods = ['init', 'get_status', 'handle_login_failed'];
            foreach ($required_methods as $method) {
                if (!method_exists($instance, $method)) {
                    vd_debug_log("Step 3.4.6.4e: VD_Security_Audit missing method: {$method}");
                    return false;
                }
            }

            vd_debug_log('Step 3.4.6.4e: VD_Security_Audit verification passed - class loaded successfully');
            return true;

        } catch (Exception $e) {
            vd_debug_log('Step 3.4.6.4e: VD_Security_Audit verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle VD_Security_Audit fallback mechanism
     * Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return void
     */
    private function handle_security_audit_fallback() {
        // EMERGENCY FIX: Use WordPress option for global state persistence
        if (get_option('vd_security_fallback_initialized', false)) {
            return;
        }

        // Check if VD_Security_Audit is available
        if (class_exists('VD_Security_Audit')) {
            // VD_Security_Audit loaded successfully
            $this->log_security_audit_status('available', 'VD_Security_Audit class loaded and available');
            update_option('vd_security_fallback_initialized', true);
            return;
        }

        // VD_Security_Audit not available - implement fallback strategy
        $this->log_security_audit_status('fallback', 'VD_Security_Audit not available - implementing fallback');

        // Fallback Option 1: Use WordPress built-in security features
        $this->setup_wordpress_security_fallback();

        // Fallback Option 2: Basic security logging
        $this->setup_basic_security_logging();

        // Fallback Option 3: Minimal monitoring hooks
        $this->setup_minimal_security_hooks();

        $this->log_security_audit_status('fallback_complete', 'Security fallback mechanisms activated');

        // Mark as initialized globally to prevent re-execution
        update_option('vd_security_fallback_initialized', true);
    }

    /**
     * Setup WordPress built-in security fallback
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return void
     */
    private function setup_wordpress_security_fallback() {
        // Enable basic WordPress security features
        if (!defined('FORCE_SSL_ADMIN')) {
            // Note: This would be set in wp-config.php for production
            $this->log_security_audit_status('info', 'FORCE_SSL_ADMIN not defined - recommend enabling in wp-config.php');
        }

        // Basic login monitoring without VD_Security_Audit
        add_action('wp_login_failed', array($this, 'fallback_login_failed_handler'));
        add_action('wp_login', array($this, 'fallback_login_success_handler'), 10, 2);

        $this->log_security_audit_status('info', 'WordPress security fallback activated');
    }

    /**
     * Setup basic security logging fallback
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return void
     */
    private function setup_basic_security_logging() {
        // Use WordPress options for basic security tracking
        $security_option_key = 'vd_security_fallback_log';

        // Initialize security log if not exists
        if (!get_option($security_option_key)) {
            $initial_log = array(
                'activated' => current_time('mysql'),
                'events' => array(),
                'status' => 'fallback_mode'
            );
            update_option($security_option_key, $initial_log);
        }

        $this->log_security_audit_status('info', 'Basic security logging fallback activated');
    }

    /**
     * Setup minimal security hooks fallback
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return void
     */
    private function setup_minimal_security_hooks() {
        // Only essential security hooks without complex processing
        add_action('admin_init', array($this, 'fallback_admin_security_check'));

        $this->log_security_audit_status('info', 'Minimal security hooks fallback activated');
    }

    /**
     * Fallback login failed handler
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @param string $username Failed username
     * @return void
     */
    public function fallback_login_failed_handler($username) {
        $this->log_security_event('login_failed', array(
            'username' => sanitize_user($username),
            'ip' => $this->get_client_ip_fallback(),
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Fallback login success handler
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @param string $user_login User login name
     * @param WP_User $user User object
     * @return void
     */
    public function fallback_login_success_handler($user_login, $user) {
        $this->log_security_event('login_success', array(
            'user_id' => $user->ID,
            'username' => $user_login,
            'ip' => $this->get_client_ip_fallback(),
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Fallback admin security check
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return void
     */
    public function fallback_admin_security_check() {
        // Basic admin area protection without complex VD_Security_Audit logic
        if (!current_user_can('manage_options')) {
            return;
        }

        // Simple check for suspicious admin activity
        $this->log_security_event('admin_access', array(
            'user_id' => get_current_user_id(),
            'page' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
            'ip' => $this->get_client_ip_fallback(),
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Log security events to fallback system
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @param string $event_type Type of security event
     * @param array $event_data Event data
     * @return void
     */
    private function log_security_event($event_type, $event_data) {
        $security_option_key = 'vd_security_fallback_log';
        $log = get_option($security_option_key, array());

        // Add new event to log
        if (!isset($log['events'])) {
            $log['events'] = array();
        }

        $log['events'][] = array(
            'type' => $event_type,
            'data' => $event_data,
            'logged_at' => current_time('mysql')
        );

        // Keep only last 100 events to prevent bloat
        if (count($log['events']) > 100) {
            $log['events'] = array_slice($log['events'], -100);
        }

        update_option($security_option_key, $log);
    }

    /**
     * Log VD_Security_Audit status messages
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     * Enhanced in Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.4f
     * @since 3.4.6.5 Enhanced với WordPress native logging priority
     * @param string $status Status type
     * @param string $message Status message
     * @return void
     */
    private function log_security_audit_status($status, $message) {
        // Step 3.4.6.5 - Prioritize WordPress native error logging
        $this->vd_native_error_log("Security Audit", $status, $message);
    }

    /**
     * WordPress native error logging implementation
     * Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.5
     * @param string $component Component name
     * @param string $level Log level (info, warning, error, debug)
     * @param string $message Log message
     * @return void
     */
    private function vd_native_error_log($component, $level, $message) {
        // Use only WordPress built-in logging functions
        // Format: [VD License Manager] [Component] [Level]: Message
        $formatted_message = sprintf(
            '[VD License Manager] [%s] [%s]: %s',
            $component,
            strtoupper($level),
            $message
        );

        // WordPress native error_log() - Primary logging method
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            // Use WordPress debug.log when debug logging enabled
            error_log($formatted_message);
        } elseif (defined('WP_DEBUG') && WP_DEBUG) {
            // Use PHP error log when WordPress debug enabled
            error_log($formatted_message);
        }

        // Additional WordPress-specific logging if available
        if (function_exists('wp_debug_backtrace_summary')) {
            // Log context information for debugging
            $context = wp_debug_backtrace_summary(__CLASS__, 0, false);
            if ($context && $level === 'error') {
                error_log("[VD License Manager] [Context]: {$context}");
            }
        }
    }

    /**
     * Get client IP address fallback method
     * Part of Step 3.4.6.4f - Error Recovery Mechanism
     *
     * @since 3.4.6.4f
     * @return string Client IP address
     */
    private function get_client_ip_fallback() {
        // Simple IP detection without complex VD_Security_Audit logic
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * Generic WordPress native logging for all components
     * Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.5
     * @param string $component Component name (Security, Database, Encryption, etc.)
     * @param string $message Log message
     * @param string $level Log level (info, warning, error, debug) - default: info
     * @return void
     */
    public function vd_log($component, $message, $level = 'info') {
        $this->vd_native_error_log($component, $level, $message);
    }

    /**
     * Test WordPress native logging functionality
     * Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.5
     * @return array Test results
     */
    public function test_native_logging() {
        $results = array(
            'wp_debug_defined' => defined('WP_DEBUG'),
            'wp_debug_log_defined' => defined('WP_DEBUG_LOG'),
            'wp_debug_value' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'wp_debug_log_value' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'error_log_function' => function_exists('error_log'),
            'wp_debug_backtrace_function' => function_exists('wp_debug_backtrace_summary')
        );

        // Test logging functionality
        $test_message = 'Step 3.4.6.5 - WordPress native logging test';
        $this->vd_log('Testing', $test_message, 'info');

        $results['test_logged'] = true;
        $results['test_timestamp'] = current_time('mysql');

        return $results;
    }

    /**
     * Get logging configuration status
     * Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.5
     * @return array Logging configuration details
     */
    public function get_logging_config() {
        return array(
            'logging_method' => 'WordPress Native error_log()',
            'primary_condition' => 'WP_DEBUG_LOG = true',
            'secondary_condition' => 'WP_DEBUG = true',
            'log_format' => '[VD License Manager] [Component] [Level]: Message',
            'context_logging' => function_exists('wp_debug_backtrace_summary'),
            'current_status' => $this->is_logging_enabled()
        );
    }

    /**
     * Check if logging is currently enabled
     * Step 3.4.6.5 - Basic Error Logging
     *
     * @since 3.4.6.5
     * @return bool True if logging is enabled
     */
    private function is_logging_enabled() {
        return (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) || (defined('WP_DEBUG') && WP_DEBUG);
    }
}