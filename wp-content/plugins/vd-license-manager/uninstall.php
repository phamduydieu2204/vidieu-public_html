<?php
/**
 * VD License Manager - Uninstall script
 *
 * Clean up all plugin data when uninstalling
 * Only runs if user chooses to delete data
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete data
$delete_data = get_option('vd_uninstall_delete_data', false);

if ($delete_data) {
    global $wpdb;

    // Drop all plugin tables
    $tables = array(
        'vd_provider_accounts',
        'vd_product_pools',
        'vd_pool_accounts',
        'vd_cookie_assignments',
        'vd_product_share_configs',
        'vd_device_fingerprints',
        'vd_license_devices',
        'vd_license_device_limits',
        'vd_account_fetch_log',
        'vd_license_access_log',
        'vd_license_rate_limits'
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }

    // Delete all plugin options
    $options = array(
        'vd_version',
        'vd_plugin_mode',
        'vd_debug_enabled',
        'vd_admin_email',
        'vd_api_rate_limit',
        'vd_device_limit_default',
        'vd_assignment_strategy',
        'vd_cookie_timeout',
        'vd_ip_anonymize_days',
        'vd_log_retention_days',
        'vd_expiry_warning_days',
        'vd_capacity_warning_percent',
        'vd_notify_assignments',
        'vd_portal_enabled',
        'vd_portal_title',
        'vd_portal_description',
        'vd_portal_logo',
        'vd_portal_theme',
        'vd_portal_custom_css',
        'vd_uninstall_delete_data',
        'vd_database_version',
        'vd_last_cleanup',
        'vd_last_capacity_check',
        'vd_wc_assignment_notice'
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear scheduled cron jobs
    wp_clear_scheduled_hook('vd_daily_cleanup');
    wp_clear_scheduled_hook('vd_hourly_check');
    wp_clear_scheduled_hook('vd_capacity_check');

    // Delete all plugin transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_vd_%'
         OR option_name LIKE '_transient_timeout_vd_%'"
    );

    // Delete user meta data related to plugin
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta}
         WHERE meta_key LIKE 'vd_%'"
    );

    // Delete post meta data for products
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta}
         WHERE meta_key LIKE '_vd_%'"
    );

    // Clear any plugin-specific cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    // Log uninstall completion
    if (defined('VD_DEBUG') && VD_DEBUG) {
        error_log('[VD License Manager] Plugin uninstalled and all data removed');
    }

    // Delete plugin directory if empty (WordPress core will handle this)
    // No need to manually delete files as WordPress handles this
}

// Clean exit
exit;