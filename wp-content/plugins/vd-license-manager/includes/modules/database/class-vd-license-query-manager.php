<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Database Query Manager
 *
 * Handles database operations for license validation, lookups, and management
 * Extracted from main validator class for better modularity and database abstraction
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 * @namespace VD\LicenseManager\Database
 */
class VD_License_Query_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Query_Manager|null
     */
    private static $instance = null;

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Database table configurations
     *
     * @var array
     */
    private $table_config = array();

    /**
     * Query cache for performance
     *
     * @var array
     */
    private $query_cache = array();

    /**
     * Query statistics
     *
     * @var array
     */
    private $query_stats = array(
        'total_queries' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'query_time' => 0,
        'table_access' => array(),
        'error_count' => 0
    );

    /**
     * Cache TTL in seconds
     *
     * @var int
     */
    private $cache_ttl = 300; // 5 minutes

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->init_table_config();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Query_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize table configuration
     *
     * @return void
     */
    private function init_table_config() {
        $this->table_config = array(
            'lmfwc' => array(
                'table_name' => 'bz_lmfwc_licenses',
                'primary_key' => 'id',
                'license_key_field' => 'license_key',
                'status_field' => 'status',
                'fields' => array(
                    'id', 'order_id', 'product_id', 'user_id', 'license_key',
                    'hash', 'expires_at', 'valid_for', 'source', 'status',
                    'times_activated', 'times_activated_max', 'created_at',
                    'created_by', 'updated_at', 'updated_by'
                ),
                'status_mapping' => array(
                    1 => 'active',      // SOLD/DELIVERED
                    2 => 'inactive',    // INACTIVE
                    3 => 'expired',     // EXPIRED
                    4 => 'suspended',   // DISABLED
                    'active' => 'active',
                    'inactive' => 'inactive',
                    'expired' => 'expired',
                    'suspended' => 'suspended'
                )
            ),
            'vd_internal' => array(
                'table_name' => $this->wpdb->prefix . 'vd_licenses',
                'primary_key' => 'id',
                'license_key_field' => 'license_key',
                'status_field' => 'status',
                'fields' => array(
                    'id', 'license_key', 'product_id', 'order_id', 'user_id',
                    'status', 'max_devices', 'expires_at', 'created_at', 'updated_at'
                ),
                'status_mapping' => array(
                    'active' => 'active',
                    'inactive' => 'inactive',
                    'expired' => 'expired',
                    'suspended' => 'suspended'
                )
            )
        );
    }

    /**
     * Lookup license from database with fallback mechanism
     *
     * @param string $license_key License key to lookup
     * @param bool $use_cache Whether to use query cache
     * @return array|null License data or null if not found
     */
    public function lookup_license($license_key, $use_cache = true) {
        $this->query_stats['total_queries']++;
        $start_time = microtime(true);

        try {
            // Check cache first
            if ($use_cache) {
                $cached_result = $this->get_cached_query($license_key);
                if ($cached_result !== null) {
                    $this->query_stats['cache_hits']++;
                    return $cached_result;
                }
                $this->query_stats['cache_misses']++;
            }

            // Try LMfWC table first with encryption support
            $license = $this->lookup_from_table($license_key, 'lmfwc');

            if (!$license) {
                // Fallback to VD internal table
                $license = $this->lookup_from_table($license_key, 'vd_internal');
            }

            // Cache successful result
            if ($license && $use_cache) {
                $this->cache_query_result($license_key, $license);
            }

            return $license;

        } catch (Exception $e) {
            $this->query_stats['error_count']++;
            error_log("VD Query Manager: Lookup failed for key '{$license_key}': " . $e->getMessage());
            return null;
        } finally {
            $this->query_stats['query_time'] += (microtime(true) - $start_time) * 1000;
        }
    }

    /**
     * Lookup license from specific table
     *
     * @param string $license_key License key to lookup
     * @param string $table_type Table type (lmfwc or vd_internal)
     * @return array|null License data or null if not found
     */
    private function lookup_from_table($license_key, $table_type) {
        if (!isset($this->table_config[$table_type])) {
            return null;
        }

        $config = $this->table_config[$table_type];
        $table_name = $config['table_name'];

        // Check if table exists
        if (!$this->table_exists($table_name)) {
            return null;
        }

        $this->update_table_access_stats($table_name);

        // For LMfWC table, use decryption scan method (encryption has random IV)
        if ($table_type === 'lmfwc' && has_filter('lmfwc_decrypt')) {
            return $this->lookup_lmfwc_by_decryption($license_key, $config);
        }

        // Fallback to direct lookup (for VD internal table or if encryption fails)
        $fields = implode(', ', $config['fields']);
        $sql = $this->wpdb->prepare(
            "SELECT {$fields} FROM {$table_name} WHERE {$config['license_key_field']} = %s LIMIT 1",
            $license_key
        );

        $license = $this->wpdb->get_row($sql, ARRAY_A);

        if ($license) {
            // Add metadata
            $license['lookup_source'] = $table_type;
            $license['table_name'] = $table_name;
            $license['lookup_timestamp'] = current_time('mysql');
            $license['lookup_method'] = 'direct';

            // Map status
            $license['mapped_status'] = $this->map_status($license[$config['status_field']], $table_type);

            // Add expiration check
            if (isset($license['expires_at']) && $license['expires_at']) {
                $license['is_expired'] = strtotime($license['expires_at']) < time();
            } else {
                $license['is_expired'] = false;
            }
        }

        return $license;
    }

    /**
     * Check if database table exists
     *
     * @param string $table_name Table name to check
     * @return bool True if table exists, false otherwise
     */
    public function table_exists($table_name) {
        $cache_key = "table_exists_{$table_name}";
        $cached_result = $this->get_cached_query($cache_key);

        if ($cached_result !== null) {
            return $cached_result;
        }

        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));

        $result = $table_exists > 0;
        $this->cache_query_result($cache_key, $result, 3600); // Cache for 1 hour

        return $result;
    }

    /**
     * Map status code to standardized status
     *
     * @param mixed $status Status code or string
     * @param string $table_type Table type for mapping context
     * @return string Mapped status
     */
    private function map_status($status, $table_type) {
        if (!isset($this->table_config[$table_type]['status_mapping'])) {
            return 'unknown';
        }

        $mapping = $this->table_config[$table_type]['status_mapping'];

        return isset($mapping[$status]) ? $mapping[$status] : 'unknown';
    }

    /**
     * Get multiple licenses by IDs
     *
     * @param array $license_ids Array of license IDs
     * @param string $table_type Table type to query
     * @return array Array of license data
     */
    public function get_licenses_by_ids($license_ids, $table_type = 'lmfwc') {
        if (empty($license_ids) || !isset($this->table_config[$table_type])) {
            return array();
        }

        $config = $this->table_config[$table_type];
        $table_name = $config['table_name'];

        if (!$this->table_exists($table_name)) {
            return array();
        }

        $this->query_stats['total_queries']++;
        $this->update_table_access_stats($table_name);

        $placeholders = implode(',', array_fill(0, count($license_ids), '%d'));
        $fields = implode(', ', $config['fields']);

        $sql = $this->wpdb->prepare(
            "SELECT {$fields} FROM {$table_name} WHERE {$config['primary_key']} IN ({$placeholders})",
            $license_ids
        );

        $licenses = $this->wpdb->get_results($sql, ARRAY_A);

        // Add metadata to each license
        foreach ($licenses as &$license) {
            $license['lookup_source'] = $table_type;
            $license['table_name'] = $table_name;
            $license['lookup_timestamp'] = current_time('mysql');
            $license['mapped_status'] = $this->map_status($license[$config['status_field']], $table_type);
        }

        return $licenses;
    }

    /**
     * Count licenses by status
     *
     * @param string $status Status to count
     * @param string $table_type Table type to query
     * @return int Number of licenses with given status
     */
    public function count_licenses_by_status($status, $table_type = 'lmfwc') {
        if (!isset($this->table_config[$table_type])) {
            return 0;
        }

        $config = $this->table_config[$table_type];
        $table_name = $config['table_name'];

        if (!$this->table_exists($table_name)) {
            return 0;
        }

        $this->query_stats['total_queries']++;
        $this->update_table_access_stats($table_name);

        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE {$config['status_field']} = %s",
            $status
        ));

        return (int) $count;
    }

    /**
     * Get license debug information
     *
     * @param string $license_key License key to debug
     * @return array Debug information
     */
    public function get_lookup_debug_info($license_key) {
        $lmfwc_table = $this->table_config['lmfwc']['table_name'];
        $vd_table = $this->table_config['vd_internal']['table_name'];

        return array(
            'license_key' => $license_key,
            'lookup_timestamp' => current_time('mysql'),
            'lmfwc_table_exists' => $this->table_exists($lmfwc_table),
            'vd_table_exists' => $this->table_exists($vd_table),
            'lmfwc_table_name' => $lmfwc_table,
            'vd_table_name' => $vd_table,
            'database_name' => DB_NAME,
            'wpdb_prefix' => $this->wpdb->prefix,
            'cache_stats' => array(
                'cache_hits' => $this->query_stats['cache_hits'],
                'cache_misses' => $this->query_stats['cache_misses'],
                'cached_items' => count($this->query_cache)
            )
        );
    }

    /**
     * Get cached query result
     *
     * @param string $cache_key Cache key
     * @return mixed|null Cached result or null if not found/expired
     */
    private function get_cached_query($cache_key) {
        $full_key = 'vd_query_' . md5($cache_key);

        if (!isset($this->query_cache[$full_key])) {
            return null;
        }

        $cached_item = $this->query_cache[$full_key];

        if (isset($cached_item['expires']) && time() > $cached_item['expires']) {
            unset($this->query_cache[$full_key]);
            return null;
        }

        return $cached_item['data'];
    }

    /**
     * Cache query result
     *
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds
     * @return void
     */
    private function cache_query_result($cache_key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->cache_ttl;
        }

        $full_key = 'vd_query_' . md5($cache_key);

        $this->query_cache[$full_key] = array(
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        );

        // Limit cache size
        if (count($this->query_cache) > 100) {
            $this->cleanup_cache();
        }
    }

    /**
     * Clean up expired cache entries
     *
     * @return void
     */
    private function cleanup_cache() {
        $current_time = time();

        foreach ($this->query_cache as $key => $item) {
            if (isset($item['expires']) && $current_time > $item['expires']) {
                unset($this->query_cache[$key]);
            }
        }
    }

    /**
     * Update table access statistics
     *
     * @param string $table_name Table name
     * @return void
     */
    private function update_table_access_stats($table_name) {
        if (!isset($this->query_stats['table_access'][$table_name])) {
            $this->query_stats['table_access'][$table_name] = 0;
        }
        $this->query_stats['table_access'][$table_name]++;
    }

    /**
     * Clear query cache
     *
     * @return void
     */
    public function clear_cache() {
        $this->query_cache = array();
    }

    /**
     * Lookup license from LMfWC table using decryption scan method
     *
     * @param string $license_key Plaintext license key to find
     * @param array $config Table configuration
     * @return array|null License data or null if not found
     */
    private function lookup_lmfwc_by_decryption($license_key, $config) {
        $table_name = $config['table_name'];
        $start_time = microtime(true);

        try {
            // First, try hash-based lookup if available (performance optimization)
            $hash_result = $this->lookup_by_hash($license_key, $config);
            if ($hash_result) {
                return $hash_result;
            }

            // Fallback to decryption scan with optimized approach
            $scan_limit = apply_filters('vd_lmfwc_scan_limit', 200); // Allow customization
            $fields = implode(', ', $config['fields']);

            // Get licenses in batches, starting with most recent
            $sql = "SELECT {$fields} FROM {$table_name} ORDER BY id DESC LIMIT {$scan_limit}";
            $licenses = $this->wpdb->get_results($sql, ARRAY_A);

            $scan_count = 0;
            $decrypt_errors = 0;

            foreach ($licenses as $license_record) {
                $scan_count++;

                try {
                    $decrypted_key = apply_filters('lmfwc_decrypt', $license_record[$config['license_key_field']]);

                    if ($decrypted_key === $license_key) {
                        // Found matching license
                        $license_record['decrypted_license_key'] = $decrypted_key;
                        $license_record['lookup_source'] = 'lmfwc';
                        $license_record['table_name'] = $table_name;
                        $license_record['lookup_timestamp'] = current_time('mysql');
                        $license_record['lookup_method'] = 'decryption_scan';
                        $license_record['scan_count'] = $scan_count;

                        // Map status
                        $license_record['mapped_status'] = $this->map_status($license_record[$config['status_field']], 'lmfwc');

                        // Add expiration check
                        if (isset($license_record['expires_at']) && $license_record['expires_at']) {
                            $license_record['is_expired'] = strtotime($license_record['expires_at']) < time();
                        } else {
                            $license_record['is_expired'] = false;
                        }

                        // Update performance stats
                        $lookup_time = (microtime(true) - $start_time) * 1000;
                        $this->query_stats['decryption_scan_time'] += $lookup_time;
                        $this->query_stats['decryption_scan_count'] = isset($this->query_stats['decryption_scan_count']) ?
                            $this->query_stats['decryption_scan_count'] + 1 : 1;

                        // Consider adding hash for future optimization
                        $this->maybe_add_license_hash($license_record, $decrypted_key);

                        return $license_record;
                    }

                } catch (Exception $e) {
                    $decrypt_errors++;
                    // Continue scanning other licenses
                }
            }

            // Update stats for unsuccessful scan
            $lookup_time = (microtime(true) - $start_time) * 1000;
            $this->query_stats['decryption_scan_time'] += $lookup_time;
            $this->query_stats['decryption_scan_count'] = isset($this->query_stats['decryption_scan_count']) ?
                $this->query_stats['decryption_scan_count'] + 1 : 1;

            // Log scan results for debugging
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Query Manager: Decryption scan completed - {$scan_count} licenses scanned, {$decrypt_errors} errors, license '{$license_key}' not found");
            }

            return null;

        } catch (Exception $e) {
            error_log("VD Query Manager: Decryption scan failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Try hash-based lookup for performance optimization
     *
     * @param string $license_key Plaintext license key
     * @param array $config Table configuration
     * @return array|null License data or null if not found
     */
    private function lookup_by_hash($license_key, $config) {
        $table_name = $config['table_name'];

        // Check if hash column exists (performance optimization)
        $hash_column = 'license_key_hash';
        $columns = $this->wpdb->get_col("DESCRIBE {$table_name}");

        if (!in_array($hash_column, $columns)) {
            return null; // Hash column doesn't exist yet
        }

        try {
            // Generate hash of the plaintext license key
            $license_hash = hash('sha256', $license_key);

            $fields = implode(', ', $config['fields']);
            $sql = $this->wpdb->prepare(
                "SELECT {$fields} FROM {$table_name} WHERE {$hash_column} = %s LIMIT 1",
                $license_hash
            );

            $license = $this->wpdb->get_row($sql, ARRAY_A);

            if ($license) {
                // Verify by decrypting (double-check for hash collisions)
                $decrypted_key = apply_filters('lmfwc_decrypt', $license[$config['license_key_field']]);

                if ($decrypted_key === $license_key) {
                    $license['decrypted_license_key'] = $decrypted_key;
                    $license['lookup_source'] = 'lmfwc';
                    $license['table_name'] = $table_name;
                    $license['lookup_timestamp'] = current_time('mysql');
                    $license['lookup_method'] = 'hash_optimized';

                    // Map status
                    $license['mapped_status'] = $this->map_status($license[$config['status_field']], 'lmfwc');

                    // Add expiration check
                    if (isset($license['expires_at']) && $license['expires_at']) {
                        $license['is_expired'] = strtotime($license['expires_at']) < time();
                    } else {
                        $license['is_expired'] = false;
                    }

                    return $license;
                }
            }

        } catch (Exception $e) {
            error_log("VD Query Manager: Hash lookup failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Add hash for future optimization if conditions are met
     *
     * @param array $license_record License record
     * @param string $plaintext_key Plaintext license key
     * @return void
     */
    private function maybe_add_license_hash($license_record, $plaintext_key) {
        // Only add hash if we have permission and table supports it
        if (!apply_filters('vd_enable_license_hash_optimization', false)) {
            return;
        }

        $table_name = $license_record['table_name'];
        $hash_column = 'license_key_hash';

        // Check if hash column exists
        $columns = $this->wpdb->get_col("DESCRIBE {$table_name}");
        if (!in_array($hash_column, $columns)) {
            return; // Can't add hash without column
        }

        try {
            $license_hash = hash('sha256', $plaintext_key);
            $license_id = $license_record['id'];

            $this->wpdb->update(
                $table_name,
                array($hash_column => $license_hash),
                array('id' => $license_id),
                array('%s'),
                array('%d')
            );

            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Query Manager: Added hash for license ID {$license_id}");
            }

        } catch (Exception $e) {
            error_log("VD Query Manager: Failed to add license hash: " . $e->getMessage());
        }
    }

    /**
     * Get supported table types
     *
     * @return array Array of supported table types
     */
    public function get_supported_tables() {
        return array_keys($this->table_config);
    }

    /**
     * Get table configuration
     *
     * @param string $table_type Table type
     * @return array|null Table configuration or null if not found
     */
    public function get_table_config($table_type) {
        return isset($this->table_config[$table_type]) ? $this->table_config[$table_type] : null;
    }

    /**
     * Get query statistics
     *
     * @return array Query statistics
     */
    public function get_stats() {
        return $this->query_stats;
    }

    /**
     * Reset query statistics
     *
     * @return void
     */
    public function reset_stats() {
        $this->query_stats = array(
            'total_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'query_time' => 0,
            'table_access' => array(),
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
            'name' => 'VD License Database Query Manager',
            'version' => '1.5.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Database',
            'description' => 'Handles database operations for license validation and management',
            'dependencies' => array(),
            'supported_tables' => $this->get_supported_tables(),
            'statistics' => $this->query_stats
        );
    }
}