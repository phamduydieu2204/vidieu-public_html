<?php
/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
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
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Activator {

    /**
     * Plugin activation handler
     *
     * Performs the following checks and actions:
     * - Verifies WordPress version compatibility
     * - Verifies PHP version compatibility
     * - Checks for required plugins (WooCommerce, LMfWC)
     * - Creates database tables (placeholder for now)
     * - Flushes rewrite rules
     * - Sets default options
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Check WordPress version
        if ( ! self::check_wordpress_version() ) {
            deactivate_plugins( VD_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'VD License Manager requires WordPress version 6.0 or higher.', 'vd-license-manager' ),
                esc_html__( 'Plugin Activation Error', 'vd-license-manager' ),
                array( 'back_link' => true )
            );
        }

        // Check PHP version
        if ( ! self::check_php_version() ) {
            deactivate_plugins( VD_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'VD License Manager requires PHP version 7.4 or higher.', 'vd-license-manager' ),
                esc_html__( 'Plugin Activation Error', 'vd-license-manager' ),
                array( 'back_link' => true )
            );
        }

        // Check required plugins
        if ( ! self::check_required_plugins() ) {
            deactivate_plugins( VD_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'VD License Manager requires WooCommerce and License Manager for WooCommerce (LMfWC) plugins to be installed and activated.', 'vd-license-manager' ),
                esc_html__( 'Plugin Activation Error', 'vd-license-manager' ),
                array( 'back_link' => true )
            );
        }

        // Check encryption key
        if ( ! self::check_encryption_key() ) {
            deactivate_plugins( VD_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'VD License Manager requires VD_ENCRYPTION_KEY to be defined in wp-config.php.', 'vd-license-manager' ),
                esc_html__( 'Plugin Activation Error', 'vd-license-manager' ),
                array( 'back_link' => true )
            );
        }

        // Create database tables (placeholder - will be implemented in Day 2-3)
        // self::create_database_tables();

        // Set default options
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log activation
        if ( function_exists( 'error_log' ) ) {
            error_log( 'VD License Manager: Plugin activated successfully' );
        }
    }

    /**
     * Check WordPress version compatibility
     *
     * @since  1.0.0
     * @return bool True if WordPress version is compatible
     */
    private static function check_wordpress_version() {
        global $wp_version;
        return version_compare( $wp_version, '6.0', '>=' );
    }

    /**
     * Check PHP version compatibility
     *
     * @since  1.0.0
     * @return bool True if PHP version is compatible
     */
    private static function check_php_version() {
        return version_compare( PHP_VERSION, '7.4', '>=' );
    }

    /**
     * Check if required plugins are active
     *
     * @since  1.0.0
     * @return bool True if all required plugins are active
     */
    private static function check_required_plugins() {
        $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

        // Check WooCommerce
        $woocommerce_active = in_array( 'woocommerce/woocommerce.php', $active_plugins, true );

        // Check License Manager for WooCommerce
        $lmfwc_active = in_array( 'license-manager-for-woocommerce/license-manager-for-woocommerce.php', $active_plugins, true );

        return $woocommerce_active && $lmfwc_active;
    }

    /**
     * Check if encryption key is defined
     *
     * @since  1.0.0
     * @return bool True if encryption key is defined
     */
    private static function check_encryption_key() {
        return defined( 'VD_ENCRYPTION_KEY' ) && ! empty( VD_ENCRYPTION_KEY );
    }

    /**
     * Set default plugin options
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'version'                => VD_PLUGIN_VERSION,
            'enable_debug'          => false,
            'enable_vps_detection'  => true,
            'enable_rate_limiting'  => true,
            'enable_device_tracking'=> true,
            'max_devices_per_license' => 2,
            'default_validity_days' => 30,
            'max_requests_per_day'  => 10,
            'session_timeout'       => 3600,
            'rate_limit_window'     => 300,
            'rate_limit_max_hits'   => 10,
            'device_cooldown'       => 86400,
        );

        // Only add options if they don't exist
        foreach ( $default_options as $option => $value ) {
            $option_name = 'vd_license_manager_' . $option;
            if ( false === get_option( $option_name ) ) {
                add_option( $option_name, $value );
            }
        }

        // Set activation timestamp
        if ( false === get_option( 'vd_license_manager_activated_at' ) ) {
            add_option( 'vd_license_manager_activated_at', current_time( 'mysql' ) );
        }
    }

    /**
     * Create database tables (placeholder)
     *
     * This will be implemented in Day 2-3 of development.
     * For now, this is just a placeholder method.
     *
     * @since 1.0.0
     */
    private static function create_database_tables() {
        // TODO: Implement database table creation
        // This will be done in Day 2-3 according to the roadmap

        // Tables to create:
        // 1. bz_vd_provider_accounts
        // 2. bz_vd_product_pools
        // 3. bz_vd_pool_accounts
        // 4. bz_vd_cookie_assignments
        // 5. bz_vd_product_share_configs
        // 6. bz_vd_device_fingerprints
        // 7. bz_vd_license_devices
        // 8. bz_vd_license_device_limits
        // 9. bz_vd_account_fetch_log
        // 10. bz_vd_license_access_log
        // 11. bz_vd_license_rate_limits
    }
}