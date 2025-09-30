<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License LMfWC Database Adapter
 *
 * Specialized adapter for LMfWC database operations and schema compatibility
 * Provides LMfWC-specific methods for license management and data transformation
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Database
 */
class VD_License_LMfWC_Adapter {

    /**
     * Singleton instance
     *
     * @var VD_License_LMfWC_Adapter|null
     */
    private static $instance = null;

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Query manager dependency
     *
     * @var VD_License_Query_Manager|null
     */
    private $query_manager = null;

    /**
     * LMfWC table configuration
     *
     * @var array
     */
    private $lmfwc_config = array();

    /**
     * LMfWC operation statistics
     *
     * @var array
     */
    private $lmfwc_stats = array(
        'total_operations' => 0,
        'successful_operations' => 0,
        'failed_operations' => 0,
        'status_mappings' => 0,
        'schema_validations' => 0,
        'performance_time' => 0,
        'error_count' => 0
    );

    /**
     * LMfWC schema validation cache
     *
     * @var array
     */
    private $schema_cache = array();

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->init_lmfwc_config();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_LMfWC_Adapter
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set query manager dependency
     *
     * @param VD_License_Query_Manager $query_manager Query manager instance
     * @return void
     */
    public function set_query_manager($query_manager) {
        $this->query_manager = $query_manager;
    }

    /**
     * Initialize LMfWC configuration
     *
     * @return void
     */
    private function init_lmfwc_config() {
        $this->lmfwc_config = array(
            'table_name' => 'bz_lmfwc_licenses',
            'primary_key' => 'id',
            'license_key_field' => 'license_key',
            'status_field' => 'status',
            'created_field' => 'created_at',
            'updated_field' => 'updated_at',
            'activation_limit_field' => 'times_activated_max',
            'activation_count_field' => 'times_activated',
            'expiry_field' => 'expires_at',
            'hash_field' => 'hash',
            'status_mapping' => array(
                1 => 'active',      // SOLD/DELIVERED
                2 => 'inactive',    // INACTIVE
                3 => 'expired',     // EXPIRED
                4 => 'suspended',   // DISABLED
                'active' => 1,
                'inactive' => 2,
                'expired' => 3,
                'suspended' => 4
            ),
            'required_fields' => array(
                'id', 'license_key', 'status', 'created_at'
            ),
            'optional_fields' => array(
                'order_id', 'product_id', 'user_id', 'hash', 'expires_at',
                'valid_for', 'source', 'times_activated', 'times_activated_max',
                'created_by', 'updated_at', 'updated_by'
            )
        );
    }

    /**
     * Get LMfWC license by key with full metadata
     *
     * @param string $license_key License key to lookup
     * @param bool $include_metadata Whether to include additional metadata
     * @return array|null License data with LMfWC-specific formatting
     */
    public function get_lmfwc_license($license_key, $include_metadata = true) {
        $this->lmfwc_stats['total_operations']++;
        $start_time = microtime(true);

        try {
            if (!$this->query_manager) {
                throw new Exception('Query Manager dependency not set');
            }

            // Get license using query manager
            $license = $this->query_manager->lookup_license($license_key, true);

            if (!$license) {
                $this->lmfwc_stats['failed_operations']++;
                return null;
            }

            // Apply LMfWC-specific transformations
            $transformed_license = $this->transform_lmfwc_license($license);

            if ($include_metadata) {
                $transformed_license = $this->add_lmfwc_metadata($transformed_license);
            }

            $this->lmfwc_stats['successful_operations']++;
            return $transformed_license;

        } catch (Exception $e) {
            $this->lmfwc_stats['error_count']++;
            error_log("VD LMfWC Adapter: Failed to get license '{$license_key}': " . $e->getMessage());
            return null;
        } finally {
            $this->lmfwc_stats['performance_time'] += (microtime(true) - $start_time) * 1000;
        }
    }

    /**
     * Transform LMfWC license data to standardized format
     *
     * @param array $license Raw LMfWC license data
     * @return array Transformed license data
     */
    private function transform_lmfwc_license($license) {
        $transformed = $license;

        // Map LMfWC status to standardized status
        if (isset($license['status'])) {
            $transformed['original_status'] = $license['status'];
            $transformed['mapped_status'] = $this->map_lmfwc_status($license['status']);
            $this->lmfwc_stats['status_mappings']++;
        }

        // Standardize activation data
        if (isset($license['times_activated']) && isset($license['times_activated_max'])) {
            $transformed['activation_info'] = array(
                'current_activations' => (int) $license['times_activated'],
                'max_activations' => (int) $license['times_activated_max'],
                'activations_remaining' => max(0, (int) $license['times_activated_max'] - (int) $license['times_activated']),
                'activation_limit_reached' => ((int) $license['times_activated'] >= (int) $license['times_activated_max'])
            );
        }

        // Standardize expiry data
        if (isset($license['expires_at']) && $license['expires_at']) {
            $expiry_timestamp = strtotime($license['expires_at']);
            $current_timestamp = time();

            $transformed['expiry_info'] = array(
                'expires_at' => $license['expires_at'],
                'expires_timestamp' => $expiry_timestamp,
                'is_expired' => $expiry_timestamp < $current_timestamp,
                'days_until_expiry' => max(0, ceil(($expiry_timestamp - $current_timestamp) / DAY_IN_SECONDS)),
                'expiry_status' => $expiry_timestamp < $current_timestamp ? 'expired' : 'valid'
            );
        }

        return $transformed;
    }

    /**
     * Add LMfWC-specific metadata to license
     *
     * @param array $license License data
     * @return array License with additional metadata
     */
    private function add_lmfwc_metadata($license) {
        $license['lmfwc_metadata'] = array(
            'adapter_version' => '1.5.0-rc.1',
            'table_source' => 'lmfwc',
            'transformation_applied' => true,
            'schema_validated' => $this->validate_lmfwc_schema($license),
            'lookup_timestamp' => current_time('mysql'),
            'data_integrity_check' => $this->check_lmfwc_data_integrity($license)
        );

        return $license;
    }

    /**
     * Map LMfWC status code to standardized status
     *
     * @param mixed $lmfwc_status LMfWC status code or string
     * @return string Mapped status
     */
    public function map_lmfwc_status($lmfwc_status) {
        $mapping = $this->lmfwc_config['status_mapping'];
        return isset($mapping[$lmfwc_status]) ? $mapping[$lmfwc_status] : 'unknown';
    }

    /**
     * Map standardized status to LMfWC status code
     *
     * @param string $standard_status Standardized status
     * @return mixed LMfWC status code
     */
    public function map_to_lmfwc_status($standard_status) {
        $mapping = $this->lmfwc_config['status_mapping'];
        return isset($mapping[$standard_status]) ? $mapping[$standard_status] : 2; // Default to inactive
    }

    /**
     * Get multiple LMfWC licenses by criteria
     *
     * @param array $criteria Search criteria
     * @param array $options Query options
     * @return array Array of LMfWC licenses
     */
    public function get_lmfwc_licenses_by_criteria($criteria, $options = array()) {
        $this->lmfwc_stats['total_operations']++;

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
            'include_metadata' => true
        );

        $options = wp_parse_args($options, $defaults);

        try {
            $where_conditions = array();
            $where_values = array();

            // Build WHERE conditions
            foreach ($criteria as $field => $value) {
                if (in_array($field, array_merge($this->lmfwc_config['required_fields'], $this->lmfwc_config['optional_fields']))) {
                    if (is_array($value)) {
                        $placeholders = implode(',', array_fill(0, count($value), '%s'));
                        $where_conditions[] = "{$field} IN ({$placeholders})";
                        $where_values = array_merge($where_values, $value);
                    } else {
                        $where_conditions[] = "{$field} = %s";
                        $where_values[] = $value;
                    }
                }
            }

            if (empty($where_conditions)) {
                return array();
            }

            $table_name = $this->lmfwc_config['table_name'];
            $where_clause = implode(' AND ', $where_conditions);

            $sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$options['order_by']} {$options['order']} LIMIT %d OFFSET %d";
            $where_values[] = $options['limit'];
            $where_values[] = $options['offset'];

            $prepared_sql = $this->wpdb->prepare($sql, $where_values);
            $licenses = $this->wpdb->get_results($prepared_sql, ARRAY_A);

            // Transform each license
            $transformed_licenses = array();
            foreach ($licenses as $license) {
                $transformed = $this->transform_lmfwc_license($license);
                if ($options['include_metadata']) {
                    $transformed = $this->add_lmfwc_metadata($transformed);
                }
                $transformed_licenses[] = $transformed;
            }

            $this->lmfwc_stats['successful_operations']++;
            return $transformed_licenses;

        } catch (Exception $e) {
            $this->lmfwc_stats['error_count']++;
            error_log("VD LMfWC Adapter: Failed to get licenses by criteria: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Count LMfWC licenses by status
     *
     * @param mixed $status LMfWC status to count
     * @return int Number of licenses
     */
    public function count_lmfwc_licenses_by_status($status) {
        $table_name = $this->lmfwc_config['table_name'];
        $status_field = $this->lmfwc_config['status_field'];

        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE {$status_field} = %s",
            $status
        ));

        return (int) $count;
    }

    /**
     * Get LMfWC activation statistics
     *
     * @param string $license_key License key
     * @return array Activation statistics
     */
    public function get_lmfwc_activation_stats($license_key) {
        $license = $this->get_lmfwc_license($license_key, false);

        if (!$license || !isset($license['activation_info'])) {
            return array(
                'found' => false,
                'error' => 'License not found or activation data unavailable'
            );
        }

        return array(
            'found' => true,
            'license_key' => $license_key,
            'activation_data' => $license['activation_info'],
            'status' => $license['mapped_status'],
            'last_updated' => $license['updated_at'] ?? $license['created_at']
        );
    }

    /**
     * Validate LMfWC schema compatibility
     *
     * @param array $license License data to validate
     * @return array Validation results
     */
    public function validate_lmfwc_schema($license) {
        $this->lmfwc_stats['schema_validations']++;

        $validation = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'missing_required' => array(),
            'unexpected_fields' => array()
        );

        // Check required fields
        foreach ($this->lmfwc_config['required_fields'] as $required_field) {
            if (!isset($license[$required_field]) || $license[$required_field] === '') {
                $validation['missing_required'][] = $required_field;
                $validation['valid'] = false;
            }
        }

        // Check for unexpected fields (informational only)
        $expected_fields = array_merge(
            $this->lmfwc_config['required_fields'],
            $this->lmfwc_config['optional_fields']
        );

        foreach (array_keys($license) as $field) {
            if (!in_array($field, $expected_fields) && !in_array($field, array('mapped_status', 'original_status', 'activation_info', 'expiry_info', 'lmfwc_metadata'))) {
                $validation['unexpected_fields'][] = $field;
            }
        }

        // Validate status field
        if (isset($license['status'])) {
            $mapped_status = $this->map_lmfwc_status($license['status']);
            if ($mapped_status === 'unknown') {
                $validation['warnings'][] = "Unknown status code: {$license['status']}";
            }
        }

        return $validation;
    }

    /**
     * Check LMfWC data integrity
     *
     * @param array $license License data
     * @return array Integrity check results
     */
    private function check_lmfwc_data_integrity($license) {
        $integrity = array(
            'status' => 'valid',
            'issues' => array(),
            'recommendations' => array()
        );

        // Check activation limits
        if (isset($license['times_activated']) && isset($license['times_activated_max'])) {
            $current = (int) $license['times_activated'];
            $max = (int) $license['times_activated_max'];

            if ($current > $max) {
                $integrity['issues'][] = "Activation count ({$current}) exceeds maximum ({$max})";
                $integrity['status'] = 'warning';
            }

            if ($max === 0) {
                $integrity['recommendations'][] = "Consider setting a positive activation limit";
            }
        }

        // Check expiry consistency
        if (isset($license['expires_at']) && $license['expires_at']) {
            $expiry_time = strtotime($license['expires_at']);
            $mapped_status = $license['mapped_status'] ?? 'unknown';

            if ($expiry_time < time() && $mapped_status === 'active') {
                $integrity['issues'][] = "License is expired but marked as active";
                $integrity['status'] = 'error';
            }
        }

        return $integrity;
    }

    /**
     * Get LMfWC table information
     *
     * @return array Table information and statistics
     */
    public function get_lmfwc_table_info() {
        $table_name = $this->lmfwc_config['table_name'];

        // Get table existence
        $table_exists = $this->query_manager ?
            $this->query_manager->table_exists($table_name) :
            $this->check_table_exists($table_name);

        $info = array(
            'table_name' => $table_name,
            'exists' => $table_exists,
            'configuration' => $this->lmfwc_config
        );

        if ($table_exists) {
            // Get row count
            $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $info['row_count'] = (int) $row_count;

            // Get status distribution
            $status_distribution = array();
            foreach (array(1, 2, 3, 4) as $status_code) {
                $count = $this->count_lmfwc_licenses_by_status($status_code);
                $status_name = $this->map_lmfwc_status($status_code);
                $status_distribution[$status_name] = $count;
            }
            $info['status_distribution'] = $status_distribution;
        }

        return $info;
    }

    /**
     * Check if table exists (fallback method)
     *
     * @param string $table_name Table name
     * @return bool True if exists
     */
    private function check_table_exists($table_name) {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));

        return $result > 0;
    }

    /**
     * Get adapter statistics
     *
     * @return array Adapter statistics
     */
    public function get_stats() {
        return $this->lmfwc_stats;
    }

    /**
     * Reset adapter statistics
     *
     * @return void
     */
    public function reset_stats() {
        $this->lmfwc_stats = array(
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'status_mappings' => 0,
            'schema_validations' => 0,
            'performance_time' => 0,
            'error_count' => 0
        );
    }

    /**
     * Get module information
     *
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'VD License LMfWC Database Adapter',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Database',
            'description' => 'Specialized adapter for LMfWC database operations and schema compatibility',
            'dependencies' => array('database.query_manager'),
            'table_supported' => 'bz_lmfwc_licenses',
            'status_mapping' => $this->lmfwc_config['status_mapping'],
            'statistics' => $this->lmfwc_stats
        );
    }
}