<?php
/**
 * Admin interface base class for VD License Manager
 *
 * Handles admin menu, assets, and notices
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Admin class
 *
 * Manages WordPress admin interface for the plugin
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_License_Admin {

    /**
     * Constructor
     *
     * Hooks into WordPress admin initialization
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_notices', array($this, 'show_notices'));
    }

    /**
     * Add admin menu pages
     *
     * Creates main menu and submenus for plugin administration
     *
     * @since 1.0.0
     */
    public function add_menu_pages() {
        // Main menu page
        add_menu_page(
            __('VD License Manager', 'vd-license-manager'),
            __('VD License Manager', 'vd-license-manager'),
            'manage_options',
            'vd-license-manager',
            array('VD_Admin_Dashboard', 'render'),
            'dashicons-shield',
            58
        );

        // Dashboard submenu (same as main)
        add_submenu_page(
            'vd-license-manager',
            __('Dashboard', 'vd-license-manager'),
            __('Dashboard', 'vd-license-manager'),
            'manage_options',
            'vd-license-manager',
            array('VD_Admin_Dashboard', 'render')
        );

        // Provider Accounts submenu
        add_submenu_page(
            'vd-license-manager',
            __('Provider Accounts', 'vd-license-manager'),
            __('Provider Accounts', 'vd-license-manager'),
            'manage_options',
            'vd-provider-accounts',
            array('VD_Admin_Provider_Accounts', 'render')
        );

        // Product Pools submenu
        add_submenu_page(
            'vd-license-manager',
            __('Product Pools', 'vd-license-manager'),
            __('Product Pools', 'vd-license-manager'),
            'manage_options',
            'vd-product-pools',
            array('VD_Admin_Product_Pools', 'render')
        );

        // Share Configs submenu
        add_submenu_page(
            'vd-license-manager',
            __('Cấu Hình Chia Sẻ', 'vd-license-manager'),
            __('Cấu Hình Chia Sẻ', 'vd-license-manager'),
            'manage_options',
            'vd-share-configs',
            array('VD_Admin_Share_Configs', 'render')
        );

        // Devices submenu
        add_submenu_page(
            'vd-license-manager',
            __('Devices', 'vd-license-manager'),
            __('Devices', 'vd-license-manager'),
            'manage_options',
            'vd-devices',
            array('VD_Admin_Devices', 'render')
        );

        // Logs submenu
        add_submenu_page(
            'vd-license-manager',
            __('Logs', 'vd-license-manager'),
            __('Logs', 'vd-license-manager'),
            'manage_options',
            'vd-logs',
            array('VD_Admin_Logs', 'render')
        );

        // Settings submenu
        add_submenu_page(
            'vd-license-manager',
            __('Settings', 'vd-license-manager'),
            __('Settings', 'vd-license-manager'),
            'manage_options',
            'vd-settings',
            array('VD_Admin_Settings', 'render')
        );
    }

    /**
     * Enqueue admin assets
     *
     * Loads CSS and JavaScript files for plugin admin pages
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'vd-') === false && $hook !== 'toplevel_page_vd-license-manager') {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'vd-admin-css',
            VD_PLUGIN_URL . 'admin/css/vd-admin.css',
            array(),
            VD_VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'vd-admin-js',
            VD_PLUGIN_URL . 'admin/js/vd-admin.js',
            array('jquery'),
            VD_VERSION,
            true
        );

        // Localize script with admin data
        wp_localize_script('vd-admin-js', 'vdAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vd_admin_nonce'),
            'currentPage' => $this->get_current_page(),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'vd-license-manager'),
                'loading' => __('Loading...', 'vd-license-manager'),
                'error' => __('An error occurred. Please try again.', 'vd-license-manager')
            )
        ));
    }

    /**
     * Check system requirements
     *
     * Validates if all plugin requirements are met
     *
     * @since 1.0.0
     * @return bool True if requirements met
     */
    public function check_requirements() {
        global $wp_version;

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            return false;
        }

        // Check WordPress version
        if (version_compare($wp_version, '5.8', '<')) {
            return false;
        }

        // Check WooCommerce
        if (!class_exists('WooCommerce')) {
            return false;
        }

        // Check LMFWC
        if (!function_exists('lmfwc_get_license')) {
            return false;
        }

        return true;
    }

    /**
     * Show admin notices
     *
     * Displays success, error, and warning messages in admin
     *
     * @since 1.0.0
     */
    public function show_notices() {
        // Check requirements and show warning if not met
        if (!$this->check_requirements()) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('VD License Manager:', 'vd-license-manager') . '</strong> ';
            echo esc_html__('Plugin requirements not met. Please check PHP version, WordPress version, WooCommerce, and LMFWC.', 'vd-license-manager');
            echo '</p></div>';
        }

        // Show message from URL parameter
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

            $class = $type === 'error' ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Get current admin page slug
     *
     * Returns the current plugin admin page being viewed
     *
     * @since 1.0.0
     * @return string Current page slug
     */
    public function get_current_page() {
        if (!isset($_GET['page'])) {
            return '';
        }

        $page = sanitize_text_field($_GET['page']);

        // List of valid plugin pages
        $plugin_pages = array(
            'vd-license-manager',
            'vd-provider-accounts',
            'vd-product-pools',
            'vd-product-configs',
            'vd-devices',
            'vd-logs',
            'vd-settings'
        );

        return in_array($page, $plugin_pages) ? $page : '';
    }
}