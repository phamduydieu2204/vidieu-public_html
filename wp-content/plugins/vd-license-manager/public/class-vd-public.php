<?php
/**
 * The public-facing functionality of the plugin
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the public-facing stylesheet and JavaScript.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/public
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The public-facing functionality of the plugin
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the public-facing stylesheet and JavaScript.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/public
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Public {

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
     * @param string $plugin_name The name of the plugin
     * @param string $version     The version of this plugin
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        // Only enqueue if needed (future implementation will check for portal pages)
        wp_enqueue_style(
            $this->plugin_name,
            VD_PLUGIN_URL . 'public/css/vd-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue if needed (future implementation will check for portal pages)
        wp_enqueue_script(
            $this->plugin_name,
            VD_PLUGIN_URL . 'public/js/vd-public.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        // Localize script for AJAX (will be used in future portal implementation)
        wp_localize_script(
            $this->plugin_name,
            'vd_public_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vd_public_nonce' ),
                'api_base' => home_url( '/wp-json/vd/v1' ),
                'strings'  => array(
                    'loading'       => __( 'Loading...', 'vd-license-manager' ),
                    'error'         => __( 'An error occurred. Please try again.', 'vd-license-manager' ),
                    'invalid_key'   => __( 'Invalid license key format.', 'vd-license-manager' ),
                    'access_denied' => __( 'Access denied.', 'vd-license-manager' ),
                ),
            )
        );
    }
}