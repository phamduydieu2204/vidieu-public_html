<?php
defined('ABSPATH') || exit;

class VD_Migration_Manager {

    private static $instance = null;
    private $current_version = '1.0.0';
    private $db_version_option = 'vd_license_manager_db_version';

    private function __construct() {
        // Private constructor
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_migrations() {
        $installed_version = get_option($this->db_version_option, '0.0.0');

        if (version_compare($installed_version, $this->current_version, '<')) {
            $this->execute_migrations($installed_version);
            update_option($this->db_version_option, $this->current_version);
        }
    }

    private function execute_migrations($from_version) {
        $migrations = $this->get_migration_list();

        foreach ($migrations as $version => $migration) {
            if (version_compare($from_version, $version, '<')) {
                $this->log_migration('Starting migration to version: ' . $version);

                try {
                    call_user_func($migration);
                    $this->log_migration('Completed migration to version: ' . $version);
                } catch (Exception $e) {
                    $this->log_migration('Failed migration to version ' . $version . ': ' . $e->getMessage(), 'error');
                    throw $e;
                }
            }
        }
    }

    private function get_migration_list() {
        return array(
            '1.0.0' => array($this, 'migrate_to_1_0_0')
        );
    }

    public function migrate_to_1_0_0() {
        if (!class_exists('VD_Database_Manager')) {
            require_once VD_LM_PATH . 'includes/class-vd-database-manager.php';
        }

        $result = VD_Database_Manager::create_tables();

        if (!$result['success']) {
            throw new Exception('Failed to create database tables: ' . implode(', ', $result['errors']));
        }

        $this->create_initial_settings();
        $this->create_default_data();

        $this->log_migration('Initial database structure created successfully');
    }

    private function create_initial_settings() {
        $default_settings = array(
            'default_device_limit' => 3,
            'auto_approval_threshold' => 25.0,
            'rate_limit_requests_per_hour' => 60,
            'rate_limit_requests_per_day' => 1000,
            'log_retention_days' => 90,
            'enable_debug_logging' => false,
            'assignment_algorithm' => 'least_loaded',
            'session_timeout_minutes' => 30,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15
        );

        foreach ($default_settings as $setting => $value) {
            $option_name = 'vd_license_manager_' . $setting;
            if (!get_option($option_name)) {
                add_option($option_name, $value);
            }
        }
    }

    private function create_default_data() {
        global $wpdb;

        $this->create_default_products();
        $this->create_sample_field_sharing_config();
    }

    private function create_default_products() {
        global $wpdb;

        $products_table = $wpdb->prefix . 'bz_product_settings';

        $default_products = array(
            array(
                'product_id' => 1,
                'product_name' => 'Helium10',
                'product_slug' => 'helium10',
                'device_limit' => 3,
                'rate_limit_per_hour' => 60,
                'rate_limit_per_day' => 1000,
                'assignment_algorithm' => 'least_loaded',
                'auto_approval_threshold' => 25.0,
                'is_active' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true)
            ),
            array(
                'product_id' => 2,
                'product_name' => 'Midjourney',
                'product_slug' => 'midjourney',
                'device_limit' => 2,
                'rate_limit_per_hour' => 30,
                'rate_limit_per_day' => 500,
                'assignment_algorithm' => 'round_robin',
                'auto_approval_threshold' => 20.0,
                'is_active' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true)
            ),
            array(
                'product_id' => 3,
                'product_name' => 'Freepik',
                'product_slug' => 'freepik',
                'device_limit' => 5,
                'rate_limit_per_hour' => 100,
                'rate_limit_per_day' => 2000,
                'assignment_algorithm' => 'sequential',
                'auto_approval_threshold' => 30.0,
                'is_active' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true)
            )
        );

        foreach ($default_products as $product) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $products_table WHERE product_id = %d",
                    $product['product_id']
                )
            );

            if ($existing == 0) {
                $wpdb->insert(
                    $products_table,
                    $product,
                    array('%d', '%s', '%s', '%d', '%d', '%d', '%s', '%f', '%d', '%s', '%s')
                );
            }
        }
    }

    private function create_sample_field_sharing_config() {
        global $wpdb;

        $field_sharing_table = $wpdb->prefix . 'bz_product_field_sharing_config';

        $default_configs = array(
            array(
                'product_id' => 1,
                'field_name' => 'email',
                'is_shared' => 1,
                'display_order' => 1,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 1,
                'field_name' => 'password',
                'is_shared' => 1,
                'display_order' => 2,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 1,
                'field_name' => 'two_factor_secret',
                'is_shared' => 0,
                'display_order' => 3,
                'is_required' => 0,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 2,
                'field_name' => 'email',
                'is_shared' => 1,
                'display_order' => 1,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 2,
                'field_name' => 'password',
                'is_shared' => 1,
                'display_order' => 2,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 3,
                'field_name' => 'email',
                'is_shared' => 1,
                'display_order' => 1,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 3,
                'field_name' => 'password',
                'is_shared' => 1,
                'display_order' => 2,
                'is_required' => 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            ),
            array(
                'product_id' => 3,
                'field_name' => 'cookies',
                'is_shared' => 1,
                'display_order' => 3,
                'is_required' => 0,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true')
            )
        );

        foreach ($default_configs as $config) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $field_sharing_table WHERE product_id = %d AND field_name = %s",
                    $config['product_id'],
                    $config['field_name']
                )
            );

            if ($existing == 0) {
                $wpdb->insert(
                    $field_sharing_table,
                    $config,
                    array('%d', '%s', '%d', '%d', '%d', '%s', '%s')
                );
            }
        }
    }

    public function check_database_integrity() {
        global $wpdb;

        $errors = array();
        $warnings = array();

        $required_tables = array(
            'bz_licenses',
            'bz_provider_accounts',
            'bz_content_versions',
            'bz_license_assignments',
            'bz_product_settings',
            'bz_product_provider_mapping',
            'bz_product_field_sharing_config',
            'bz_device_requests',
            'bz_access_logs',
            'bz_credential_audit',
            'bz_rate_limits'
        );

        foreach ($required_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");

            if ($table_exists != $full_table_name) {
                $errors[] = "Missing table: $full_table_name";
            }
        }

        $foreign_key_checks = array(
            array(
                'table' => 'bz_license_assignments',
                'column' => 'license_id',
                'references' => 'bz_licenses',
                'ref_column' => 'id'
            ),
            array(
                'table' => 'bz_license_assignments',
                'column' => 'provider_account_id',
                'references' => 'bz_provider_accounts',
                'ref_column' => 'id'
            ),
            array(
                'table' => 'bz_device_requests',
                'column' => 'license_id',
                'references' => 'bz_licenses',
                'ref_column' => 'id'
            )
        );

        foreach ($foreign_key_checks as $check) {
            $orphaned_records = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}{$check['table']} t1
                     LEFT JOIN {$wpdb->prefix}{$check['references']} t2 ON t1.{$check['column']} = t2.{$check['ref_column']}
                     WHERE t1.{$check['column']} IS NOT NULL AND t2.{$check['ref_column']} IS NULL"
                )
            );

            if ($orphaned_records > 0) {
                $warnings[] = "Found $orphaned_records orphaned records in {$check['table']}.{$check['column']}";
            }
        }

        return array(
            'errors' => $errors,
            'warnings' => $warnings,
            'healthy' => empty($errors)
        );
    }

    public function repair_database() {
        global $wpdb;

        $repair_results = array();

        try {
            $wpdb->query('START TRANSACTION');

            $orphaned_assignments = $wpdb->query(
                "DELETE la FROM {$wpdb->prefix}bz_license_assignments la
                 LEFT JOIN {$wpdb->prefix}bz_licenses l ON la.license_id = l.id
                 WHERE la.license_id IS NOT NULL AND l.id IS NULL"
            );

            if ($orphaned_assignments !== false) {
                $repair_results[] = "Removed $orphaned_assignments orphaned license assignments";
            }

            $orphaned_devices = $wpdb->query(
                "DELETE dr FROM {$wpdb->prefix}bz_device_requests dr
                 LEFT JOIN {$wpdb->prefix}bz_licenses l ON dr.license_id = l.id
                 WHERE dr.license_id IS NOT NULL AND l.id IS NULL"
            );

            if ($orphaned_devices !== false) {
                $repair_results[] = "Removed $orphaned_devices orphaned device requests";
            }

            $wpdb->query('COMMIT');

            $repair_results[] = 'Database repair completed successfully';

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $repair_results[] = 'Database repair failed: ' . $e->getMessage();
        }

        return $repair_results;
    }

    public function get_database_statistics() {
        global $wpdb;

        $stats = array();

        $table_stats = array(
            'licenses' => 'bz_licenses',
            'provider_accounts' => 'bz_provider_accounts',
            'license_assignments' => 'bz_license_assignments',
            'device_requests' => 'bz_device_requests',
            'access_logs' => 'bz_access_logs'
        );

        foreach ($table_stats as $name => $table) {
            $full_table_name = $wpdb->prefix . $table;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            $stats[$name] = intval($count);
        }

        $stats['active_licenses'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bz_licenses WHERE status = 'active'"
        );

        $stats['approved_devices'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bz_device_requests WHERE approval_status = 'approved'"
        );

        $stats['healthy_accounts'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bz_provider_accounts WHERE account_status = 'active' AND health_score >= 80"
        );

        return $stats;
    }

    private function log_migration($message, $level = 'info') {
        $log_message = '[VD License Manager Migration] ' . $message;

        if (function_exists('error_log')) {
            error_log($log_message);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($level === 'error') {
                trigger_error($log_message, E_USER_WARNING);
            }
        }
    }

    public function get_current_version() {
        return $this->current_version;
    }

    public function get_installed_version() {
        return get_option($this->db_version_option, '0.0.0');
    }

    public function needs_migration() {
        return version_compare($this->get_installed_version(), $this->current_version, '<');
    }
}