<?php
/**
 * VD Migration Manager
 *
 * Handles database versioning, migrations, and default data setup
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Migration_Manager class
 *
 * Manages database migrations and versioning
 */
class VD_Migration_Manager {

    /**
     * Current database version
     *
     * @var string
     */
    private $current_version = '1.0.0';

    /**
     * Migration option key
     *
     * @var string
     */
    private $version_option = 'vd_license_manager_db_version';

    /**
     * Singleton instance
     *
     * @var VD_Migration_Manager
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return VD_Migration_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Initialize migration system
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('admin_init', [$this, 'check_for_migrations']);
        add_action('wp_ajax_vd_run_migration', [$this, 'handle_ajax_migration']);
    }

    /**
     * Check if migrations need to be run
     *
     * @since 1.0.0
     * @return bool True if migrations are needed
     */
    public function check_for_migrations() {
        $installed_version = get_option($this->version_option, '0.0.0');

        if (version_compare($installed_version, $this->current_version, '<')) {
            // Migrations needed
            add_action('admin_notices', [$this, 'migration_notice']);
            return true;
        }

        return false;
    }

    /**
     * Display migration notice
     *
     * @since 1.0.0
     */
    public function migration_notice() {
        $installed_version = get_option($this->version_option, '0.0.0');

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . __('VD License Manager:', VD_LM_TEXT_DOMAIN) . '</strong> ';
        printf(
            __('Database migration required. Current version: %s, Required version: %s', VD_LM_TEXT_DOMAIN),
            $installed_version,
            $this->current_version
        );
        echo '</p>';
        echo '<p>';
        echo '<button type="button" class="button button-primary" onclick="vdRunMigration()">';
        echo __('Run Migration Now', VD_LM_TEXT_DOMAIN);
        echo '</button>';
        echo '</p>';
        echo '</div>';

        // Add inline JavaScript for migration
        ?>
        <script>
        function vdRunMigration() {
            if (!confirm('<?php echo esc_js(__('Run database migration now? This may take a few minutes.', VD_LM_TEXT_DOMAIN)); ?>')) {
                return;
            }

            var button = event.target;
            button.disabled = true;
            button.textContent = '<?php echo esc_js(__('Running...', VD_LM_TEXT_DOMAIN)); ?>';

            jQuery.post(ajaxurl, {
                action: 'vd_run_migration',
                nonce: '<?php echo wp_create_nonce('vd_migration_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Migration completed successfully!', VD_LM_TEXT_DOMAIN)); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Migration failed:', VD_LM_TEXT_DOMAIN)); ?> ' + (response.data.message || 'Unknown error'));
                    button.disabled = false;
                    button.textContent = '<?php echo esc_js(__('Run Migration Now', VD_LM_TEXT_DOMAIN)); ?>';
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('Migration request failed. Please try again.', VD_LM_TEXT_DOMAIN)); ?>');
                button.disabled = false;
                button.textContent = '<?php echo esc_js(__('Run Migration Now', VD_LM_TEXT_DOMAIN)); ?>';
            });
        }
        </script>
        <?php
    }

    /**
     * Handle AJAX migration request
     *
     * @since 1.0.0
     */
    public function handle_ajax_migration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vd_migration_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', VD_LM_TEXT_DOMAIN)]);
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', VD_LM_TEXT_DOMAIN)]);
        }

        try {
            $result = $this->run_migrations();

            if ($result) {
                wp_send_json_success(['message' => __('Migration completed successfully', VD_LM_TEXT_DOMAIN)]);
            } else {
                wp_send_json_error(['message' => __('Migration failed', VD_LM_TEXT_DOMAIN)]);
            }
        } catch (Exception $e) {
            vd_debug_log('Migration error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Run all pending migrations
     *
     * @since 1.0.0
     * @return bool True on success
     */
    public function run_migrations() {
        $installed_version = get_option($this->version_option, '0.0.0');

        vd_debug_log("Starting migration from version {$installed_version} to {$this->current_version}");

        try {
            // Migration from 0.0.0 to 1.0.0 (initial setup)
            if (version_compare($installed_version, '1.0.0', '<')) {
                $this->migrate_to_1_0_0();
            }

            // Update version
            update_option($this->version_option, $this->current_version);

            vd_debug_log("Migration completed successfully to version {$this->current_version}");
            return true;

        } catch (Exception $e) {
            vd_debug_log("Migration failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Migration to version 1.0.0
     *
     * @since 1.0.0
     * @throws Exception If migration fails
     */
    private function migrate_to_1_0_0() {
        global $wpdb;

        vd_debug_log("Running migration to 1.0.0");

        // Ensure tables exist
        $this->ensure_tables_exist();

        // Insert default data
        $this->insert_default_data();

        // Setup default encryption keys if needed
        $this->setup_default_encryption();

        // Create default system configuration
        $this->setup_default_config();

        vd_debug_log("Migration to 1.0.0 completed");
    }

    /**
     * Ensure all required tables exist
     *
     * @since 1.0.0
     * @throws Exception If table creation fails
     */
    private function ensure_tables_exist() {
        // Use database manager to create tables
        if (class_exists('VD_Database_Manager')) {
            $db_manager = new VD_Database_Manager();
            $result = $db_manager->create_tables();

            if (!$result) {
                throw new Exception(__('Failed to create database tables', VD_LM_TEXT_DOMAIN));
            }
        } else {
            throw new Exception(__('Database Manager class not found', VD_LM_TEXT_DOMAIN));
        }
    }

    /**
     * Insert default data
     *
     * @since 1.0.0
     */
    private function insert_default_data() {
        global $wpdb;

        // Default providers
        $default_providers = [
            [
                'name' => 'LMfWC Provider',
                'type' => 'lmfwc',
                'description' => 'License Manager for WooCommerce integration',
                'status' => 'active',
                'priority' => 1,
                'settings' => json_encode([
                    'timeout' => 30,
                    'retries' => 3,
                    'fallback_enabled' => true
                ])
            ],
            [
                'name' => 'API Provider',
                'type' => 'api',
                'description' => 'External API provider for license validation',
                'status' => 'inactive',
                'priority' => 2,
                'settings' => json_encode([
                    'timeout' => 15,
                    'retries' => 2,
                    'fallback_enabled' => false
                ])
            ]
        ];

        $table_name = $wpdb->prefix . 'vd_providers';

        foreach ($default_providers as $provider) {
            // Check if provider already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE name = %s",
                $provider['name']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $table_name,
                    array_merge($provider, [
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ])
                );

                vd_debug_log("Created default provider: " . $provider['name']);
            }
        }

        // Default system configuration values
        $default_configs = [
            'license_key_length' => 32,
            'license_key_format' => 'XXXX-XXXX-XXXX-XXXX',
            'license_key_charset' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'max_devices_per_license' => 5,
            'device_approval_mode' => 'auto',
            'audit_log_retention_days' => 90,
            'encryption_algorithm' => 'AES-256-GCM',
            'api_rate_limit_requests' => 1000,
            'api_rate_limit_window' => 3600,
            'cache_ttl_seconds' => 300
        ];

        $config_table = $wpdb->prefix . 'vd_system_config';

        foreach ($default_configs as $key => $value) {
            // Check if config already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$config_table} WHERE config_key = %s",
                $key
            ));

            if (!$exists) {
                $wpdb->insert(
                    $config_table,
                    [
                        'config_key' => $key,
                        'config_value' => $value,
                        'description' => $this->get_config_description($key),
                        'is_encrypted' => 0,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ]
                );

                vd_debug_log("Created default config: {$key} = {$value}");
            }
        }
    }

    /**
     * Setup default encryption
     *
     * @since 1.0.0
     */
    private function setup_default_encryption() {
        // Verify encryption key is configured
        if (!vd_is_encryption_key_valid()) {
            vd_debug_log("Warning: VD_ENCRYPTION_KEY is not properly configured");

            // Log audit entry
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::log_action(
                    'system',
                    'encryption_warning',
                    0,
                    null,
                    'Encryption key not properly configured during migration'
                );
            }
        } else {
            vd_debug_log("Encryption key validation successful");
        }
    }

    /**
     * Setup default system configuration
     *
     * @since 1.0.0
     */
    private function setup_default_config() {
        // Create initial cache entries if needed
        global $wpdb;

        $cache_table = $wpdb->prefix . 'vd_cache_data';

        // Cache system status
        $system_status = [
            'database_version' => $this->current_version,
            'migration_completed_at' => current_time('mysql'),
            'total_tables' => 11,
            'encryption_enabled' => vd_is_encryption_key_valid()
        ];

        $wpdb->replace(
            $cache_table,
            [
                'cache_key' => 'system_status',
                'cache_value' => json_encode($system_status),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );

        vd_debug_log("Default system configuration completed");
    }

    /**
     * Get configuration description
     *
     * @since 1.0.0
     * @param string $key Configuration key
     * @return string Description
     */
    private function get_config_description($key) {
        $descriptions = [
            'license_key_length' => 'Length of generated license keys',
            'license_key_format' => 'Format pattern for license keys',
            'license_key_charset' => 'Character set for license key generation',
            'max_devices_per_license' => 'Maximum devices allowed per license',
            'device_approval_mode' => 'Device approval mode (auto/manual)',
            'audit_log_retention_days' => 'Number of days to retain audit logs',
            'encryption_algorithm' => 'Encryption algorithm for sensitive data',
            'api_rate_limit_requests' => 'API requests allowed per window',
            'api_rate_limit_window' => 'Rate limiting window in seconds',
            'cache_ttl_seconds' => 'Default cache time-to-live in seconds'
        ];

        return $descriptions[$key] ?? 'System configuration value';
    }

    /**
     * Get current database version
     *
     * @since 1.0.0
     * @return string Current version
     */
    public function get_current_version() {
        return $this->current_version;
    }

    /**
     * Get installed database version
     *
     * @since 1.0.0
     * @return string Installed version
     */
    public function get_installed_version() {
        return get_option($this->version_option, '0.0.0');
    }

    /**
     * Check if migrations are needed
     *
     * @since 1.0.0
     * @return bool True if migrations needed
     */
    public function needs_migration() {
        return version_compare($this->get_installed_version(), $this->current_version, '<');
    }

    /**
     * Force reset database version (for testing)
     *
     * @since 1.0.0
     * @param string $version Version to set
     */
    public function force_version($version = '0.0.0') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            update_option($this->version_option, $version);
            vd_debug_log("Forced database version to: {$version}");
        }
    }

    /**
     * Get migration history
     *
     * @since 1.0.0
     * @return array Migration history
     */
    public function get_migration_history() {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'vd_audit_logs';

        $migrations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$audit_table}
             WHERE entity_type = 'migration'
             ORDER BY created_at DESC
             LIMIT 20"
        ), ARRAY_A);

        return $migrations ?: [];
    }
}