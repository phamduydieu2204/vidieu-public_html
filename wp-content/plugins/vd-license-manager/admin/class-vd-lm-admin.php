<?php
/**
 * The admin-specific functionality of the plugin
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the admin-specific stylesheet and JavaScript, as well as
 * creating the admin menu structure.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The admin-specific functionality of the plugin
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the admin-specific stylesheet and JavaScript.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Admin {

    /**
     * The ID of this plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string $plugin_name The ID of this plugin
     */
    private $plugin_name;

    /**
     * The version of this plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string $version The current version of this plugin
     */
    private $version;

    /**
     * Initialize the class and set its properties
     *
     * @since 1.0.0
     * @param string $plugin_name The name of this plugin
     * @param string $version     The version of this plugin
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area
     *
     * @since 1.0.0
     * @param string $hook The current admin page
     */
    public function enqueue_styles( $hook ) {
        // Only enqueue on our plugin pages
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            VD_PLUGIN_URL . 'admin/css/vd-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area
     *
     * @since 1.0.0
     * @param string $hook The current admin page
     */
    public function enqueue_scripts( $hook ) {
        // Only enqueue on our plugin pages
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        // Enqueue general admin JS
        wp_enqueue_script(
            $this->plugin_name,
            VD_PLUGIN_URL . 'admin/js/vd-admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        // Localize script with data
        wp_localize_script(
            $this->plugin_name,
            'vd_admin_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vd_admin_nonce' ),
                'strings'  => array(
                    'confirm_delete' => __( 'Are you sure you want to delete this item?', 'vd-license-manager' ),
                    'loading'        => __( 'Loading...', 'vd-license-manager' ),
                    'error'          => __( 'An error occurred. Please try again.', 'vd-license-manager' ),
                ),
            )
        );

        // Accounts page specific assets
        if ( strpos( $hook, 'vd-accounts' ) !== false ) {
            $this->enqueue_accounts_assets();
        }
    }

    /**
     * Enqueue accounts page specific assets
     *
     * @since 1.0.0
     */
    private function enqueue_accounts_assets() {
        // Enqueue accounts form CSS
        wp_enqueue_style(
            'vd-accounts-form',
            VD_PLUGIN_URL . 'admin/css/accounts-form.css',
            array(),
            $this->version
        );

        // Enqueue enhanced error styling
        wp_enqueue_style(
            'vd-accounts-form-errors',
            VD_PLUGIN_URL . 'admin/css/accounts-form-errors.css',
            array( 'vd-accounts-form' ),
            $this->version
        );

        // Enqueue accounts form JavaScript
        wp_enqueue_script(
            'vd-accounts-form',
            VD_PLUGIN_URL . 'admin/js/accounts-form.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        // Localize script with translations and config
        $is_edit = isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? '1' : '0';

        wp_localize_script(
            'vd-accounts-form',
            'vdAccountFormL10n',
            array(
                'isEdit' => $is_edit,
                'show' => __( 'Show', 'vd-license-manager' ),
                'hide' => __( 'Hide', 'vd-license-manager' ),
                'remove' => __( 'Remove', 'vd-license-manager' ),
                'fieldKey' => __( 'Field Key', 'vd-license-manager' ),
                'fieldLabel' => __( 'Field Label', 'vd-license-manager' ),
                'fieldValue' => __( 'Field Value', 'vd-license-manager' ),
                'fieldTypes' => array(
                    'text' => __( 'Text', 'vd-license-manager' ),
                    'email' => __( 'Email', 'vd-license-manager' ),
                    'url' => __( 'URL', 'vd-license-manager' ),
                    'tel' => __( 'Phone', 'vd-license-manager' ),
                    'password' => __( 'Password (encrypted)', 'vd-license-manager' ),
                    'textarea' => __( 'Long Text', 'vd-license-manager' ),
                ),
                'errors' => array(
                    'formErrors' => __( 'Please fix the following errors:', 'vd-license-manager' ),
                    'providerRequired' => __( 'Provider name is required.', 'vd-license-manager' ),
                    'loginRequired' => __( 'Account login is required.', 'vd-license-manager' ),
                    'passwordRequired' => __( 'Password is required for new accounts.', 'vd-license-manager' ),
                    'capacityInvalid' => __( 'Capacity must be between 1 and 100.', 'vd-license-manager' ),
                ),
            )
        );
    }

    /**
     * Add plugin admin menu
     *
     * @since 1.0.0
     */
    public function add_plugin_admin_menu() {
        // Add main menu page
        add_menu_page(
            __( 'VD License Manager', 'vd-license-manager' ),           // Page title
            __( 'VD License Manager', 'vd-license-manager' ),           // Menu title
            'manage_options',                                            // Capability
            'vd-license-manager',                                        // Menu slug
            array( $this, 'display_dashboard_page' ),                   // Callback
            'dashicons-admin-network',                                   // Icon
            58                                                           // Position
        );

        // Add submenu pages
        $submenus = array(
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Dashboard', 'vd-license-manager' ),
                'menu_title'  => __( 'Dashboard', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-license-manager',
                'function'    => array( $this, 'display_dashboard_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Product Pools', 'vd-license-manager' ),
                'menu_title'  => __( 'Pools', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-pools',
                'function'    => array( $this, 'display_pools_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Provider Accounts', 'vd-license-manager' ),
                'menu_title'  => __( 'Accounts', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-accounts',
                'function'    => array( $this, 'display_accounts_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Share Configurations', 'vd-license-manager' ),
                'menu_title'  => __( 'Share Configs', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-share-configs',
                'function'    => array( $this, 'display_share_configs_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'License Keys', 'vd-license-manager' ),
                'menu_title'  => __( 'Licenses', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-licenses',
                'function'    => array( $this, 'display_licenses_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Device Management', 'vd-license-manager' ),
                'menu_title'  => __( 'Devices', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-devices',
                'function'    => array( $this, 'display_devices_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Access Logs', 'vd-license-manager' ),
                'menu_title'  => __( 'Logs', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-logs',
                'function'    => array( $this, 'display_logs_page' ),
            ),
            array(
                'parent_slug' => 'vd-license-manager',
                'page_title'  => __( 'Settings', 'vd-license-manager' ),
                'menu_title'  => __( 'Settings', 'vd-license-manager' ),
                'capability'  => 'manage_options',
                'menu_slug'   => 'vd-settings',
                'function'    => array( $this, 'display_settings_page' ),
            ),
        );

        // Add all submenu pages
        foreach ( $submenus as $submenu ) {
            add_submenu_page(
                $submenu['parent_slug'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                $submenu['function']
            );
        }
    }

    /**
     * Display the dashboard page
     *
     * @since 1.0.0
     */
    public function display_dashboard_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'Dashboard', 'vd-license-manager' ) );
        $this->load_partial( 'dashboard' );
    }

    /**
     * Display the pools management page
     *
     * @since 1.0.0
     */
    public function display_pools_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'Product Pools', 'vd-license-manager' ) );
        $this->load_partial( 'pools' );
    }

    /**
     * Display the accounts management page
     *
     * @since 1.0.0
     */
    public function display_accounts_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        // Load and use the new Accounts Page Controller
        require_once VD_PLUGIN_DIR . 'admin/class-vd-lm-accounts-page.php';
        $accounts_page = new VD_LM_Accounts_Page();
        $accounts_page->render();
    }

    /**
     * Display the share configs page
     *
     * @since 1.0.0
     */
    public function display_share_configs_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        // Load and use the Share Configs Page Controller
        require_once VD_PLUGIN_DIR . 'admin/class-vd-lm-share-configs-page.php';
        $share_configs_page = new VD_LM_Share_Configs_Page();
        $share_configs_page->render();
    }

    /**
     * Display the licenses management page
     *
     * @since 1.0.0
     */
    public function display_licenses_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'License Keys', 'vd-license-manager' ) );
        $this->load_partial( 'licenses' );
    }

    /**
     * Display the devices management page
     *
     * @since 1.0.0
     */
    public function display_devices_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'Device Management', 'vd-license-manager' ) );
        $this->load_partial( 'devices' );
    }

    /**
     * Display the logs page
     *
     * @since 1.0.0
     */
    public function display_logs_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'Access Logs', 'vd-license-manager' ) );
        $this->load_partial( 'logs' );
    }

    /**
     * Display the settings page
     *
     * @since 1.0.0
     */
    public function display_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
        }

        $this->render_page_header( __( 'Settings', 'vd-license-manager' ) );
        $this->load_partial( 'settings' );
    }

    /**
     * Render page header
     *
     * @since  1.0.0
     * @access private
     * @param  string $title Page title
     */
    private function render_page_header( $title ) {
        echo '<div class="wrap vd-admin-wrap">';
        echo '<h1 class="vd-page-title">' . esc_html( $title ) . '</h1>';

        // Add breadcrumb navigation
        $this->render_breadcrumb();

        // Check for admin notices
        $this->render_admin_notices();
    }

    /**
     * Render breadcrumb navigation
     *
     * @since  1.0.0
     * @access private
     */
    private function render_breadcrumb() {
        $current_screen = get_current_screen();
        $breadcrumbs = array();

        // Add main plugin link
        $breadcrumbs[] = '<a href="' . admin_url( 'admin.php?page=vd-license-manager' ) . '">' .
                         __( 'VD License Manager', 'vd-license-manager' ) . '</a>';

        // Add current page if not dashboard
        if ( $current_screen && $current_screen->id !== 'toplevel_page_vd-license-manager' ) {
            $page_titles = array(
                'vd-license-manager_page_vd-pools'         => __( 'Pools', 'vd-license-manager' ),
                'vd-license-manager_page_vd-accounts'      => __( 'Accounts', 'vd-license-manager' ),
                'vd-license-manager_page_vd-share-configs' => __( 'Share Configs', 'vd-license-manager' ),
                'vd-license-manager_page_vd-licenses'      => __( 'Licenses', 'vd-license-manager' ),
                'vd-license-manager_page_vd-devices'       => __( 'Devices', 'vd-license-manager' ),
                'vd-license-manager_page_vd-logs'          => __( 'Logs', 'vd-license-manager' ),
                'vd-license-manager_page_vd-settings'      => __( 'Settings', 'vd-license-manager' ),
            );

            if ( isset( $page_titles[ $current_screen->id ] ) ) {
                $breadcrumbs[] = '<span class="current">' . esc_html( $page_titles[ $current_screen->id ] ) . '</span>';
            }
        }

        if ( count( $breadcrumbs ) > 1 ) {
            echo '<nav class="vd-breadcrumb">';
            echo implode( ' &gt; ', $breadcrumbs );
            echo '</nav>';
        }
    }

    /**
     * Render admin notices
     *
     * @since  1.0.0
     * @access private
     */
    private function render_admin_notices() {
        // Check if encryption is configured
        if ( ! VD_LM_Encryption_Service::is_configured() ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__( 'VD License Manager:', 'vd-license-manager' ) . '</strong> ';
            echo esc_html__( 'Encryption is not properly configured. Please check your VD_ENCRYPTION_KEY constant in wp-config.php.', 'vd-license-manager' );
            echo '</p></div>';
        }

        // Check if required plugins are active
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . esc_html__( 'VD License Manager:', 'vd-license-manager' ) . '</strong> ';
            echo esc_html__( 'WooCommerce plugin is required but not active.', 'vd-license-manager' );
            echo '</p></div>';
        }

        // Test encryption functionality
        if ( VD_LM_Encryption_Service::is_configured() && ! VD_LM_Encryption_Service::test() ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__( 'VD License Manager:', 'vd-license-manager' ) . '</strong> ';
            echo esc_html__( 'Encryption test failed. Please verify your encryption configuration.', 'vd-license-manager' );
            echo '</p></div>';
        }
    }

    /**
     * Load a partial template
     *
     * @since  1.0.0
     * @access private
     * @param  string $partial Partial name (without .php extension)
     */
    private function load_partial( $partial ) {
        $partial_file = VD_PLUGIN_DIR . 'admin/partials/' . $partial . '.php';

        if ( file_exists( $partial_file ) ) {
            include $partial_file;
        } else {
            // Show placeholder content
            echo '<div class="vd-placeholder">';
            echo '<h3>' . sprintf( __( '%s Page', 'vd-license-manager' ), ucfirst( $partial ) ) . '</h3>';
            echo '<p>' . __( 'This page is under development. Full functionality will be available soon.', 'vd-license-manager' ) . '</p>';
            echo '<p><em>' . sprintf( __( 'Template file: %s', 'vd-license-manager' ), esc_html( $partial_file ) ) . '</em></p>';
            echo '</div>';
        }

        echo '</div>'; // Close .wrap
    }

    /**
     * Check if current page is a plugin page
     *
     * @since  1.0.0
     * @access private
     * @param  string $hook The current admin page hook
     * @return bool         True if this is a plugin page
     */
    private function is_plugin_page( $hook ) {
        $plugin_pages = array(
            'toplevel_page_vd-license-manager',
            'vd-license-manager_page_vd-pools',
            'vd-license-manager_page_vd-accounts',
            'vd-license-manager_page_vd-share-configs',
            'vd-license-manager_page_vd-licenses',
            'vd-license-manager_page_vd-devices',
            'vd-license-manager_page_vd-logs',
            'vd-license-manager_page_vd-settings',
        );

        return in_array( $hook, $plugin_pages, true );
    }
}