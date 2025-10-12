<?php
/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_License_Manager {

    /**
     * The loader that's responsible for maintaining and registering all hooks
     *
     * @since  1.0.0
     * @access protected
     * @var    VD_LM_Loader $loader Maintains and registers all hooks for the plugin
     */
    protected $loader;

    /**
     * The unique identifier of this plugin
     *
     * @since  1.0.0
     * @access protected
     * @var    string $plugin_name The string used to uniquely identify this plugin
     */
    protected $plugin_name;

    /**
     * The current version of the plugin
     *
     * @since  1.0.0
     * @access protected
     * @var    string $version The current version of the plugin
     */
    protected $version;

    /**
     * Define the core functionality of the plugin
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct() {
        if ( defined( 'VD_PLUGIN_VERSION' ) ) {
            $this->version = VD_PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'vd-license-manager';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin
     *
     * Include the following files that make up the plugin:
     *
     * - VD_Loader. Orchestrates the hooks of the plugin.
     * - VD_i18n. Defines internationalization functionality.
     * - VD_LM_Admin. Defines all hooks for the admin area.
     * - VD_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin
        require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-loader.php';

        // The class responsible for defining internationalization functionality of the plugin
        require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-i18n.php';

        // The class responsible for defining all actions that occur in the admin area
        require_once VD_PLUGIN_DIR . 'admin/class-vd-lm-admin.php';

        // The class responsible for defining all actions that occur in the public-facing side of the site
        require_once VD_PLUGIN_DIR . 'public/class-vd-lm-public.php';

        // Load Share Configs AJAX Handler
        if ( is_admin() ) {
            $ajax_file = VD_PLUGIN_DIR . 'admin/class-vd-lm-share-configs-ajax.php';
            if ( file_exists( $ajax_file ) ) {
                require_once $ajax_file;
                error_log( 'VD Loader: Loaded Share Configs AJAX handler from: ' . $ajax_file );
            } else {
                error_log( 'VD Loader: AJAX handler file NOT FOUND: ' . $ajax_file );
            }
        }

        $this->loader = new VD_LM_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization
     *
     * Uses the VD_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function set_locale() {
        $plugin_i18n = new VD_LM_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     *
     * @since  1.0.0
     * @access private
     */
    private function define_admin_hooks() {
        $plugin_admin = new VD_LM_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

        // Note: Share Configs AJAX handler auto-initializes via init hook
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     *
     * @since  1.0.0
     * @access private
     */
    private function define_public_hooks() {
        $plugin_public = new VD_LM_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress
     *
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality
     *
     * @since  1.0.0
     * @return string The name of the plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin
     *
     * @since  1.0.0
     * @return VD_LM_Loader Orchestrates the hooks of the plugin
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin
     *
     * @since  1.0.0
     * @return string The version number of the plugin
     */
    public function get_version() {
        return $this->version;
    }
}