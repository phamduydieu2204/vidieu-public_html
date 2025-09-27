<?php
/**
 * VD License Manager Admin Menu
 *
 * Handles admin menu creation and basic admin interface
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Admin_Menu class
 *
 * Manages admin menu and basic admin interface
 */
class VD_Admin_Menu {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Delay admin menu until after init to ensure text domain is loaded
        add_action('init', [$this, 'init_admin_menu'], 11);
        add_action('admin_init', [$this, 'admin_init']);
    }

    /**
     * Initialize admin menu after text domain is loaded
     *
     * @since 1.0.0
     */
    public function init_admin_menu() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Add admin menu pages
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('VD License Manager', VD_LM_TEXT_DOMAIN),           // Page title
            __('VD License', VD_LM_TEXT_DOMAIN),                  // Menu title
            'manage_options',                                      // Capability
            'vd-license-dashboard',                               // Menu slug
            [$this, 'dashboard_page'],                            // Callback
            'dashicons-shield',                                   // Icon
            30                                                    // Position
        );

        // Dashboard submenu (same as main page)
        add_submenu_page(
            'vd-license-dashboard',
            __('Dashboard', VD_LM_TEXT_DOMAIN),
            __('Dashboard', VD_LM_TEXT_DOMAIN),
            'manage_options',
            'vd-license-dashboard',
            [$this, 'dashboard_page']
        );

        // Status submenu for Sprint 1
        add_submenu_page(
            'vd-license-dashboard',
            __('System Status', VD_LM_TEXT_DOMAIN),
            __('System Status', VD_LM_TEXT_DOMAIN),
            'manage_options',
            'vd-license-status',
            [$this, 'status_page']
        );

        // Settings submenu
        add_submenu_page(
            'vd-license-dashboard',
            __('Settings', VD_LM_TEXT_DOMAIN),
            __('Settings', VD_LM_TEXT_DOMAIN),
            'manage_options',
            'vd-license-settings',
            [$this, 'settings_page']
        );

        // Placeholder submenus for future sprints (disabled for now)
        /*
        add_submenu_page(
            'vd-license-dashboard',
            __('Licenses', VD_LM_TEXT_DOMAIN),
            __('Licenses', VD_LM_TEXT_DOMAIN),
            'manage_vd_licenses',
            'vd-licenses',
            [$this, 'licenses_page']
        );

        add_submenu_page(
            'vd-license-dashboard',
            __('Provider Accounts', VD_LM_TEXT_DOMAIN),
            __('Provider Accounts', VD_LM_TEXT_DOMAIN),
            'manage_vd_provider_accounts',
            'vd-provider-accounts',
            [$this, 'provider_accounts_page']
        );
        */
    }

    /**
     * Admin initialization
     *
     * @since 1.0.0
     */
    public function admin_init() {
        // Register settings for Sprint 1
        register_setting('vd_license_manager_settings', 'vd_license_manager_debug_mode');
        register_setting('vd_license_manager_settings', 'vd_license_manager_default_device_limit');
    }

    /**
     * Dashboard page callback
     *
     * @since 1.0.0
     */
    public function dashboard_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', VD_LM_TEXT_DOMAIN));
        }

        // Get plugin status
        $status = $this->get_plugin_status();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="vd-dashboard-content">
                <!-- Welcome Message -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Welcome to VD License Manager', VD_LM_TEXT_DOMAIN); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('VD License Manager is now installed and ready for configuration.', VD_LM_TEXT_DOMAIN); ?></p>
                        <p><?php printf(
                            /* translators: %s: plugin version */
                            __('Version: %s', VD_LM_TEXT_DOMAIN),
                            '<strong>' . VD_LM_VERSION . '</strong>'
                        ); ?></p>
                    </div>
                </div>

                <!-- System Status -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('System Status', VD_LM_TEXT_DOMAIN); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><strong><?php _e('Plugin Status', VD_LM_TEXT_DOMAIN); ?></strong></td>
                                    <td>
                                        <span class="status-indicator status-<?php echo $status['plugin_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $status['plugin_active'] ? __('Active', VD_LM_TEXT_DOMAIN) : __('Inactive', VD_LM_TEXT_DOMAIN); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('WordPress Version', VD_LM_TEXT_DOMAIN); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($status['wp_version']); ?>
                                        <?php if ($status['wp_compatible']): ?>
                                            <span class="status-indicator status-active">✓</span>
                                        <?php else: ?>
                                            <span class="status-indicator status-error">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('PHP Version', VD_LM_TEXT_DOMAIN); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($status['php_version']); ?>
                                        <?php if ($status['php_compatible']): ?>
                                            <span class="status-indicator status-active">✓</span>
                                        <?php else: ?>
                                            <span class="status-indicator status-error">✗</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Encryption Key', VD_LM_TEXT_DOMAIN); ?></strong></td>
                                    <td>
                                        <?php if ($status['encryption_configured']): ?>
                                            <span class="status-indicator status-active"><?php _e('Configured', VD_LM_TEXT_DOMAIN); ?></span>
                                        <?php else: ?>
                                            <span class="status-indicator status-error"><?php _e('Not Configured', VD_LM_TEXT_DOMAIN); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Quick Actions', VD_LM_TEXT_DOMAIN); ?></h2>
                    </div>
                    <div class="inside">
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=vd-license-status'); ?>" class="button button-primary">
                                <?php _e('View System Status', VD_LM_TEXT_DOMAIN); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=vd-license-settings'); ?>" class="button">
                                <?php _e('Configure Settings', VD_LM_TEXT_DOMAIN); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Implementation Progress -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Implementation Progress', VD_LM_TEXT_DOMAIN); ?></h2>
                    </div>
                    <div class="inside">
                        <ul>
                            <li>✅ <strong><?php _e('Sprint 1: Plugin Foundation', VD_LM_TEXT_DOMAIN); ?></strong> - <?php _e('Completed', VD_LM_TEXT_DOMAIN); ?></li>
                            <li>⏳ <?php _e('Sprint 2: Database Layer', VD_LM_TEXT_DOMAIN); ?> - <?php _e('Pending', VD_LM_TEXT_DOMAIN); ?></li>
                            <li>⏳ <?php _e('Sprint 3: Security & Encryption', VD_LM_TEXT_DOMAIN); ?> - <?php _e('Pending', VD_LM_TEXT_DOMAIN); ?></li>
                            <li>⏳ <?php _e('Sprint 4: API Layer', VD_LM_TEXT_DOMAIN); ?> - <?php _e('Pending', VD_LM_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .vd-dashboard-content .postbox {
            margin-bottom: 20px;
        }
        .status-indicator {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-indicator.status-active {
            background: #46b450;
            color: white;
        }
        .status-indicator.status-inactive {
            background: #ffb900;
            color: white;
        }
        .status-indicator.status-error {
            background: #dc3232;
            color: white;
        }
        </style>
        <?php
    }

    /**
     * Status page callback
     *
     * @since 1.0.0
     */
    public function status_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', VD_LM_TEXT_DOMAIN));
        }

        // Ensure VD_Activator class is loaded
        if (!class_exists('VD_Activator')) {
            require_once VD_LM_PATH . 'includes/class-vd-activator.php';
        }

        // Get requirements status with error handling
        try {
            $requirements = VD_Activator::get_requirements_status();
        } catch (Exception $e) {
            // Fallback if VD_Activator method fails
            $requirements = $this->get_fallback_requirements_status();
            error_log('[VD License Manager] Admin Menu - Status page error: ' . $e->getMessage());
        }

        // Get advanced encryption status (Sprint 3.2)
        $encryption_status = $this->get_encryption_status();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('System Requirements', VD_LM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="inside">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Requirement', VD_LM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Required', VD_LM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Current', VD_LM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Status', VD_LM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php _e('WordPress Version', VD_LM_TEXT_DOMAIN); ?></td>
                                <td><?php echo esc_html($requirements['wordpress_version']['required']); ?>+</td>
                                <td><?php echo esc_html($requirements['wordpress_version']['current']); ?></td>
                                <td>
                                    <?php if ($requirements['wordpress_version']['met']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Not Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('PHP Version', VD_LM_TEXT_DOMAIN); ?></td>
                                <td><?php echo esc_html($requirements['php_version']['required']); ?>+</td>
                                <td><?php echo esc_html($requirements['php_version']['current']); ?></td>
                                <td>
                                    <?php if ($requirements['php_version']['met']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Not Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('PHP Extensions', VD_LM_TEXT_DOMAIN); ?></td>
                                <td><?php echo esc_html(implode(', ', $requirements['extensions']['required'])); ?></td>
                                <td>
                                    <?php
                                    $loaded_extensions = [];
                                    foreach ($requirements['extensions']['required'] as $ext) {
                                        $loaded_extensions[] = $ext . (extension_loaded($ext) ? ' ✓' : ' ✗');
                                    }
                                    echo esc_html(implode(', ', $loaded_extensions));
                                    ?>
                                </td>
                                <td>
                                    <?php if ($requirements['extensions']['met']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Not Met', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Encryption Key', VD_LM_TEXT_DOMAIN); ?></td>
                                <td><?php _e('VD_ENCRYPTION_KEY defined', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <?php if ($requirements['encryption_key']['configured']): ?>
                                        <?php _e('Configured', VD_LM_TEXT_DOMAIN); ?>
                                        <?php if ($requirements['encryption_key']['valid']): ?>
                                            (<?php _e('Valid', VD_LM_TEXT_DOMAIN); ?>)
                                        <?php else: ?>
                                            (<?php _e('Invalid Format', VD_LM_TEXT_DOMAIN); ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php _e('Not Configured', VD_LM_TEXT_DOMAIN); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($requirements['encryption_key']['valid']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Valid', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Invalid', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Advanced Encryption Status (Sprint 3.2) -->
            <?php if ($encryption_status && $encryption_status['key_configured']): ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Advanced Encryption Status', VD_LM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="inside">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Feature', VD_LM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Status', VD_LM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Details', VD_LM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php _e('Encryption Version', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <span class="status-indicator status-active">
                                        <?php echo esc_html($encryption_status['version']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($encryption_status['algorithm']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Field-Level Encryption', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <?php if ($encryption_status['field_encryption']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Working', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Failed', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php _e('HKDF key derivation with per-field keys', VD_LM_TEXT_DOMAIN); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Metadata Support', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <?php if ($encryption_status['metadata_support']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Enabled', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-error">✗ <?php _e('Disabled', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php _e('Encryption versioning and context tracking', VD_LM_TEXT_DOMAIN); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Legacy Compatibility', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <?php if ($encryption_status['legacy_compatibility']): ?>
                                        <span class="status-indicator status-active">✓ <?php _e('Compatible', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php else: ?>
                                        <span class="status-indicator status-warning">⚠ <?php _e('Issues', VD_LM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php _e('Backward compatibility with Sprint 2 data', VD_LM_TEXT_DOMAIN); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Cached Field Keys', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <span class="status-indicator status-info">
                                        <?php echo esc_html($encryption_status['cached_keys']); ?>
                                    </span>
                                </td>
                                <td><?php _e('Number of derived keys in memory cache', VD_LM_TEXT_DOMAIN); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Recent Events (24h)', VD_LM_TEXT_DOMAIN); ?></td>
                                <td>
                                    <span class="status-indicator status-info">
                                        <?php echo esc_html($encryption_status['recent_events']); ?>
                                    </span>
                                </td>
                                <td><?php _e('Encryption/decryption operations logged', VD_LM_TEXT_DOMAIN); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$requirements['encryption_key']['configured']): ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Encryption Key Setup', VD_LM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e('To use VD License Manager, you need to add the encryption key to your wp-config.php file:', VD_LM_TEXT_DOMAIN); ?></p>
                    <code>define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');</code>
                    <p><em><?php _e('Add this line above the "/* That\'s all, stop editing!" comment in wp-config.php', VD_LM_TEXT_DOMAIN); ?></em></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Settings page callback
     *
     * @since 1.0.0
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', VD_LM_TEXT_DOMAIN));
        }

        // Handle form submission
        if (isset($_POST['submit'])) {
            check_admin_referer('vd_license_manager_settings');

            update_option('vd_license_manager_debug_mode', isset($_POST['debug_mode']) ? 1 : 0);
            update_option('vd_license_manager_default_device_limit', intval($_POST['default_device_limit']));

            echo '<div class="notice notice-success"><p>' . __('Settings saved.', VD_LM_TEXT_DOMAIN) . '</p></div>';
        }

        $debug_mode = get_option('vd_license_manager_debug_mode', false);
        $default_device_limit = get_option('vd_license_manager_default_device_limit', 3);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('vd_license_manager_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', VD_LM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" value="1" <?php checked($debug_mode, 1); ?>>
                                <?php _e('Enable debug logging', VD_LM_TEXT_DOMAIN); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, the plugin will log debug information to the error log.', VD_LM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Device Limit', VD_LM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="number" name="default_device_limit" value="<?php echo esc_attr($default_device_limit); ?>" min="1" max="100" class="regular-text">
                            <p class="description"><?php _e('Default number of devices allowed per license.', VD_LM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get plugin status information
     *
     * @since 1.0.0
     * @return array Status information
     */
    private function get_plugin_status() {
        global $wp_version;

        return [
            'plugin_active' => true, // Plugin is active if we're here
            'wp_version' => $wp_version,
            'wp_compatible' => version_compare($wp_version, '5.0', '>='),
            'php_version' => PHP_VERSION,
            'php_compatible' => version_compare(PHP_VERSION, '7.4', '>='),
            'encryption_configured' => defined('VD_ENCRYPTION_KEY') && !empty(VD_ENCRYPTION_KEY),
            'activation_time' => get_option('vd_license_manager_activation_time', time())
        ];
    }

    /**
     * Get fallback requirements status if VD_Activator fails
     *
     * @since 1.0.0
     * @return array Fallback requirements status
     */
    private function get_fallback_requirements_status() {
        global $wp_version;

        return [
            'wordpress_version' => [
                'required' => '5.0',
                'current' => $wp_version,
                'met' => version_compare($wp_version, '5.0', '>=')
            ],
            'php_version' => [
                'required' => '7.4',
                'current' => PHP_VERSION,
                'met' => version_compare(PHP_VERSION, '7.4', '>=')
            ],
            'extensions' => [
                'required' => ['openssl', 'json', 'mysqli', 'curl', 'mbstring'],
                'met' => $this->check_required_extensions()
            ],
            'encryption_key' => [
                'configured' => defined('VD_ENCRYPTION_KEY'),
                'valid' => $this->check_encryption_key_fallback()
            ]
        ];
    }

    /**
     * Check required PHP extensions (fallback method)
     *
     * @since 1.0.0
     * @return bool True if all required extensions are loaded
     */
    private function check_required_extensions() {
        $required_extensions = ['openssl', 'json', 'mysqli', 'curl', 'mbstring'];

        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check encryption key configuration (fallback method)
     *
     * @since 1.0.0
     * @return bool True if encryption key is properly configured
     */
    private function check_encryption_key_fallback() {
        if (!defined('VD_ENCRYPTION_KEY') || empty(VD_ENCRYPTION_KEY)) {
            return false;
        }

        $key = VD_ENCRYPTION_KEY;

        // Handle base64 encoded keys
        if (strpos($key, 'base64:') === 0) {
            $decoded = base64_decode(substr($key, 7));
            return $decoded !== false && strlen($decoded) === 32;
        }

        // Direct key should be 32 bytes
        return strlen($key) === 32;
    }

    /**
     * Get encryption status for System Status page
     *
     * @since 1.0.0 (Sprint 3.2)
     * @return array|null Encryption status or null if not available
     */
    private function get_encryption_status() {
        try {
            if (class_exists('VD_Encryption_Manager')) {
                $encryption_manager = VD_Encryption_Manager::get_instance();
                return $encryption_manager->get_encryption_status();
            }
        } catch (Exception $e) {
            error_log('[VD License Manager] Admin Menu - Encryption status error: ' . $e->getMessage());
        }

        return null;
    }
}