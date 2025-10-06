<?php
/**
 * Settings page for VD License Manager
 *
 * Uses WordPress Settings API for configuration management
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
 * VD Admin Settings class
 *
 * Manages plugin configuration with WordPress Settings API
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Settings {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Render settings page with tabs
     *
     * @since 1.0.0
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $active_tab = $_GET['tab'] ?? 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('VD License Manager Settings', 'vd-license-manager'); ?></h1>

            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=vd-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('General', 'vd-license-manager'); ?>
                </a>
                <a href="?page=vd-settings&tab=rate" class="nav-tab <?php echo $active_tab === 'rate' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Rate Limiting', 'vd-license-manager'); ?>
                </a>
                <a href="?page=vd-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Notifications', 'vd-license-manager'); ?>
                </a>
                <a href="?page=vd-settings&tab=privacy" class="nav-tab <?php echo $active_tab === 'privacy' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Privacy', 'vd-license-manager'); ?>
                </a>
                <a href="?page=vd-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Advanced', 'vd-license-manager'); ?>
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('vd_settings_' . $active_tab);
                do_settings_sections('vd_settings_' . $active_tab);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register all settings sections and fields
     *
     * @since 1.0.0
     */
    public function register_settings() {
        // General Settings
        register_setting('vd_settings_general', 'vd_plugin_mode', array($this, 'sanitize_plugin_mode'));
        register_setting('vd_settings_general', 'vd_default_device_limit', array($this, 'sanitize_number'));
        register_setting('vd_settings_general', 'vd_default_cooldown', array($this, 'sanitize_number'));
        register_setting('vd_settings_general', 'vd_auto_assign', array($this, 'sanitize_checkbox'));

        add_settings_section('vd_general_section', __('General Settings', 'vd-license-manager'), null, 'vd_settings_general');

        add_settings_field('vd_plugin_mode', __('Plugin Mode', 'vd-license-manager'), array($this, 'field_plugin_mode'), 'vd_settings_general', 'vd_general_section');
        add_settings_field('vd_default_device_limit', __('Default Device Limit', 'vd-license-manager'), array($this, 'field_device_limit'), 'vd_settings_general', 'vd_general_section');
        add_settings_field('vd_default_cooldown', __('Default Cooldown (seconds)', 'vd-license-manager'), array($this, 'field_cooldown'), 'vd_settings_general', 'vd_general_section');
        add_settings_field('vd_auto_assign', __('Auto Assign Accounts', 'vd-license-manager'), array($this, 'field_auto_assign'), 'vd_settings_general', 'vd_general_section');

        // Rate Limiting Settings
        register_setting('vd_settings_rate', 'vd_rate_window', array($this, 'sanitize_number'));
        register_setting('vd_settings_rate', 'vd_rate_max_hits', array($this, 'sanitize_number'));
        register_setting('vd_settings_rate', 'vd_rate_whitelist_ips', array($this, 'sanitize_textarea'));

        add_settings_section('vd_rate_section', __('Rate Limiting Settings', 'vd-license-manager'), null, 'vd_settings_rate');

        add_settings_field('vd_rate_window', __('Rate Window (seconds)', 'vd-license-manager'), array($this, 'field_rate_window'), 'vd_settings_rate', 'vd_rate_section');
        add_settings_field('vd_rate_max_hits', __('Max Hits per Window', 'vd-license-manager'), array($this, 'field_rate_max_hits'), 'vd_settings_rate', 'vd_rate_section');
        add_settings_field('vd_rate_whitelist_ips', __('Whitelist IPs', 'vd-license-manager'), array($this, 'field_whitelist_ips'), 'vd_settings_rate', 'vd_rate_section');

        // Notifications Settings
        register_setting('vd_settings_notifications', 'vd_notify_rotation', array($this, 'sanitize_checkbox'));
        register_setting('vd_settings_notifications', 'vd_notify_expiring', array($this, 'sanitize_checkbox'));
        register_setting('vd_settings_notifications', 'vd_notify_capacity', array($this, 'sanitize_checkbox'));
        register_setting('vd_settings_notifications', 'vd_admin_email', array($this, 'sanitize_email'));
        register_setting('vd_settings_notifications', 'vd_expiry_warning_days', array($this, 'sanitize_number'));
        register_setting('vd_settings_notifications', 'vd_capacity_warning_percent', array($this, 'sanitize_number'));

        add_settings_section('vd_notifications_section', __('Notification Settings', 'vd-license-manager'), null, 'vd_settings_notifications');

        add_settings_field('vd_notify_rotation', __('Account Rotation Notifications', 'vd-license-manager'), array($this, 'field_notify_rotation'), 'vd_settings_notifications', 'vd_notifications_section');
        add_settings_field('vd_notify_expiring', __('Expiring License Notifications', 'vd-license-manager'), array($this, 'field_notify_expiring'), 'vd_settings_notifications', 'vd_notifications_section');
        add_settings_field('vd_notify_capacity', __('Capacity Warning Notifications', 'vd-license-manager'), array($this, 'field_notify_capacity'), 'vd_settings_notifications', 'vd_notifications_section');
        add_settings_field('vd_admin_email', __('Admin Email', 'vd-license-manager'), array($this, 'field_admin_email'), 'vd_settings_notifications', 'vd_notifications_section');
        add_settings_field('vd_expiry_warning_days', __('Expiry Warning Days', 'vd-license-manager'), array($this, 'field_expiry_days'), 'vd_settings_notifications', 'vd_notifications_section');
        add_settings_field('vd_capacity_warning_percent', __('Capacity Warning %', 'vd-license-manager'), array($this, 'field_capacity_percent'), 'vd_settings_notifications', 'vd_notifications_section');

        // Privacy Settings
        register_setting('vd_settings_privacy', 'vd_ip_anonymize_days', array($this, 'sanitize_number'));
        register_setting('vd_settings_privacy', 'vd_log_retention_days', array($this, 'sanitize_number'));

        add_settings_section('vd_privacy_section', __('Privacy Settings', 'vd-license-manager'), null, 'vd_settings_privacy');

        add_settings_field('vd_ip_anonymize_days', __('IP Anonymize After (days)', 'vd-license-manager'), array($this, 'field_ip_anonymize'), 'vd_settings_privacy', 'vd_privacy_section');
        add_settings_field('vd_log_retention_days', __('Log Retention (days)', 'vd-license-manager'), array($this, 'field_log_retention'), 'vd_settings_privacy', 'vd_privacy_section');

        // Advanced Settings
        register_setting('vd_settings_advanced', 'vd_debug_mode', array($this, 'sanitize_checkbox'));
        register_setting('vd_settings_advanced', 'vd_log_level', array($this, 'sanitize_log_level'));
        register_setting('vd_settings_advanced', 'vd_custom_css', array($this, 'sanitize_textarea'));

        add_settings_section('vd_advanced_section', __('Advanced Settings', 'vd-license-manager'), null, 'vd_settings_advanced');

        add_settings_field('vd_debug_mode', __('Debug Mode', 'vd-license-manager'), array($this, 'field_debug_mode'), 'vd_settings_advanced', 'vd_advanced_section');
        add_settings_field('vd_log_level', __('Log Level', 'vd-license-manager'), array($this, 'field_log_level'), 'vd_settings_advanced', 'vd_advanced_section');
        add_settings_field('vd_custom_css', __('Custom CSS', 'vd-license-manager'), array($this, 'field_custom_css'), 'vd_settings_advanced', 'vd_advanced_section');
    }

    // Field Rendering Methods
    public function field_plugin_mode() {
        $value = get_option('vd_plugin_mode', 'live');
        echo '<select name="vd_plugin_mode">';
        echo '<option value="live"' . selected($value, 'live', false) . '>' . __('Live', 'vd-license-manager') . '</option>';
        echo '<option value="test"' . selected($value, 'test', false) . '>' . __('Test', 'vd-license-manager') . '</option>';
        echo '</select>';
    }

    public function field_device_limit() {
        $value = get_option('vd_default_device_limit', 1);
        echo '<input type="number" name="vd_default_device_limit" value="' . esc_attr($value) . '" min="1" max="10" />';
    }

    public function field_cooldown() {
        $value = get_option('vd_default_cooldown', 86400);
        echo '<input type="number" name="vd_default_cooldown" value="' . esc_attr($value) . '" min="0" /> <small>' . __('seconds', 'vd-license-manager') . '</small>';
    }

    public function field_auto_assign() {
        $value = get_option('vd_auto_assign', 1);
        echo '<input type="checkbox" name="vd_auto_assign" value="1"' . checked($value, 1, false) . ' /> ' . __('Automatically assign accounts to new licenses', 'vd-license-manager');
    }

    public function field_rate_window() {
        $value = get_option('vd_rate_window', 300);
        echo '<input type="number" name="vd_rate_window" value="' . esc_attr($value) . '" min="60" max="3600" /> <small>' . __('seconds', 'vd-license-manager') . '</small>';
    }

    public function field_rate_max_hits() {
        $value = get_option('vd_rate_max_hits', 10);
        echo '<input type="number" name="vd_rate_max_hits" value="' . esc_attr($value) . '" min="1" max="1000" />';
    }

    public function field_whitelist_ips() {
        $value = get_option('vd_rate_whitelist_ips', '');
        echo '<textarea name="vd_rate_whitelist_ips" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('One IP per line. CIDR notation supported.', 'vd-license-manager') . '</p>';
    }

    public function field_notify_rotation() {
        $value = get_option('vd_notify_rotation', 0);
        echo '<input type="checkbox" name="vd_notify_rotation" value="1"' . checked($value, 1, false) . ' /> ' . __('Notify when accounts are rotated', 'vd-license-manager');
    }

    public function field_notify_expiring() {
        $value = get_option('vd_notify_expiring', 0);
        echo '<input type="checkbox" name="vd_notify_expiring" value="1"' . checked($value, 1, false) . ' /> ' . __('Notify about expiring licenses', 'vd-license-manager');
    }

    public function field_notify_capacity() {
        $value = get_option('vd_notify_capacity', 0);
        echo '<input type="checkbox" name="vd_notify_capacity" value="1"' . checked($value, 1, false) . ' /> ' . __('Notify when capacity threshold reached', 'vd-license-manager');
    }

    public function field_admin_email() {
        $value = get_option('vd_admin_email', get_option('admin_email'));
        echo '<input type="email" name="vd_admin_email" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    public function field_expiry_days() {
        $value = get_option('vd_expiry_warning_days', 7);
        echo '<input type="number" name="vd_expiry_warning_days" value="' . esc_attr($value) . '" min="1" max="365" /> <small>' . __('days before expiry', 'vd-license-manager') . '</small>';
    }

    public function field_capacity_percent() {
        $value = get_option('vd_capacity_warning_percent', 90);
        echo '<input type="number" name="vd_capacity_warning_percent" value="' . esc_attr($value) . '" min="50" max="100" />%';
    }

    public function field_ip_anonymize() {
        $value = get_option('vd_ip_anonymize_days', 90);
        echo '<input type="number" name="vd_ip_anonymize_days" value="' . esc_attr($value) . '" min="1" max="3650" /> <small>' . __('days', 'vd-license-manager') . '</small>';
    }

    public function field_log_retention() {
        $value = get_option('vd_log_retention_days', 365);
        echo '<input type="number" name="vd_log_retention_days" value="' . esc_attr($value) . '" min="30" max="3650" /> <small>' . __('days', 'vd-license-manager') . '</small>';
    }

    public function field_debug_mode() {
        $value = get_option('vd_debug_mode', 0);
        echo '<input type="checkbox" name="vd_debug_mode" value="1"' . checked($value, 1, false) . ' /> ' . __('Enable debug logging', 'vd-license-manager');
    }

    public function field_log_level() {
        $value = get_option('vd_log_level', 'error');
        echo '<select name="vd_log_level">';
        echo '<option value="error"' . selected($value, 'error', false) . '>' . __('Error', 'vd-license-manager') . '</option>';
        echo '<option value="warning"' . selected($value, 'warning', false) . '>' . __('Warning', 'vd-license-manager') . '</option>';
        echo '<option value="info"' . selected($value, 'info', false) . '>' . __('Info', 'vd-license-manager') . '</option>';
        echo '<option value="debug"' . selected($value, 'debug', false) . '>' . __('Debug', 'vd-license-manager') . '</option>';
        echo '</select>';
    }

    public function field_custom_css() {
        $value = get_option('vd_custom_css', '');
        echo '<textarea name="vd_custom_css" rows="10" cols="80" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('Custom CSS for admin pages', 'vd-license-manager') . '</p>';
    }

    // Sanitization Methods
    public function sanitize_plugin_mode($input) {
        return in_array($input, array('live', 'test')) ? $input : 'live';
    }

    public function sanitize_number($input) {
        return is_numeric($input) ? absint($input) : 0;
    }

    public function sanitize_checkbox($input) {
        return $input ? 1 : 0;
    }

    public function sanitize_email($input) {
        return sanitize_email($input);
    }

    public function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }

    public function sanitize_log_level($input) {
        return in_array($input, array('error', 'warning', 'info', 'debug')) ? $input : 'error';
    }
}