<?php
/**
 * VD License Manager - Security Audit Storage Manager
 *
 * Handles audit log storage, archiving, cleanup, and performance optimization.
 * Extracted from VD_License_Validator as part of Step 3.2.4 refactor.
 *
 * @package VD_License_Manager
 * @subpackage Security\Storage
 * @since 1.0.0
 * @version 1.4.0-rc.1
 * @author VD Team
 * @namespace VD\LicenseManager\Security\Storage
 * @dependencies VD_License_Security_Event_Logger, VD_License_Security_Privacy_Manager
 */

namespace VD\LicenseManager\Security\Storage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Audit Storage Manager
 *
 * Manages audit log storage operations including database logging,
 * file-based storage, log rotation, cleanup, and archiving.
 */
class VD_License_Security_Storage_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Storage_Manager|null
     */
    private static $instance = null;

    /**
     * Event logger instance for dependency injection
     *
     * @var mixed
     */
    private $event_logger = null;

    /**
     * Privacy manager instance for dependency injection
     *
     * @var mixed
     */
    private $privacy_manager = null;

    /**
     * Storage configuration
     *
     * @var array
     */
    private $config = array();

    /**
     * In-memory audit log storage
     *
     * @var array
     */
    private $audit_logs = array();

    /**
     * History storage for license events
     *
     * @var array
     */
    private $history_storage = array();

    /**
     * Storage statistics
     *
     * @var array
     */
    private $stats = array(
        'logs_stored' => 0,
        'logs_archived' => 0,
        'logs_cleaned' => 0,
        'database_operations' => 0,
        'file_operations' => 0,
        'start_time' => 0,
        'memory_usage' => 0,
        'execution_time' => 0
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_configuration();
        $this->init_storage();
        $this->stats['start_time'] = microtime(true);
        $this->stats['memory_usage'] = memory_get_usage(true);
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Storage_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize storage configuration
     *
     * @return void
     */
    private function init_configuration() {
        $this->config = array(
            'database_storage' => array(
                'enabled' => true,
                'table_prefix' => 'vd_license_audit_',
                'batch_size' => 100,
                'max_records' => 10000
            ),
            'file_storage' => array(
                'enabled' => true,
                'base_path' => WP_CONTENT_DIR . '/uploads/vd-license-logs/',
                'rotation' => array(
                    'enabled' => true,
                    'max_size' => 10485760, // 10MB
                    'max_files' => 30
                ),
                'compression' => array(
                    'enabled' => true,
                    'type' => 'gzip'
                )
            ),
            'cleanup' => array(
                'enabled' => true,
                'retention_days' => 90,
                'archive_before_delete' => true,
                'auto_cleanup_interval' => 24 // hours
            ),
            'archiving' => array(
                'enabled' => true,
                'archive_after_days' => 30,
                'archive_location' => WP_CONTENT_DIR . '/uploads/vd-license-archives/',
                'compress_archives' => true
            ),
            'performance' => array(
                'memory_limit' => 128 * 1024 * 1024, // 128MB
                'batch_processing' => true,
                'async_operations' => false,
                'cache_enabled' => true
            )
        );
    }

    /**
     * Initialize storage systems
     *
     * @return void
     */
    private function init_storage() {
        // Initialize audit logs storage
        $this->audit_logs = array();
        $this->history_storage = array();

        // Create directories if needed
        $this->create_storage_directories();

        // Initialize database tables if needed
        $this->maybe_create_database_tables();
    }

    /**
     * Set event logger dependency
     *
     * @param mixed $event_logger Event logger instance
     * @return void
     */
    public function set_event_logger($event_logger) {
        $this->event_logger = $event_logger;
    }

    /**
     * Set privacy manager dependency
     *
     * @param mixed $privacy_manager Privacy manager instance
     * @return void
     */
    public function set_privacy_manager($privacy_manager) {
        $this->privacy_manager = $privacy_manager;
    }

    /**
     * Store audit log entry
     *
     * @param array $log_entry Log entry data
     * @param array $options Storage options
     * @return array Storage result
     */
    public function store_audit_log($log_entry, $options = array()) {
        $this->stats['logs_stored']++;

        $default_options = array(
            'storage_type' => 'both', // database, file, memory, both
            'immediate_flush' => false,
            'privacy_filter' => true,
            'compression' => true
        );

        $options = array_merge($default_options, $options);

        $result = array(
            'success' => false,
            'log_id' => null,
            'storage_locations' => array(),
            'errors' => array()
        );

        try {
            // Generate unique log ID
            $log_id = $this->generate_log_id();
            $result['log_id'] = $log_id;

            // Prepare log entry
            $prepared_entry = $this->prepare_log_entry($log_entry, $log_id, $options);

            // Store in memory
            if (in_array($options['storage_type'], array('memory', 'both'))) {
                $this->store_in_memory($prepared_entry, $log_id);
                $result['storage_locations'][] = 'memory';
            }

            // Store in database
            if (in_array($options['storage_type'], array('database', 'both'))) {
                if ($this->store_in_database($prepared_entry, $options)) {
                    $result['storage_locations'][] = 'database';
                    $this->stats['database_operations']++;
                }
            }

            // Store in file
            if (in_array($options['storage_type'], array('file', 'both'))) {
                if ($this->store_in_file($prepared_entry, $options)) {
                    $result['storage_locations'][] = 'file';
                    $this->stats['file_operations']++;
                }
            }

            $result['success'] = !empty($result['storage_locations']);

            // Log storage operation
            $this->log_storage_operation('audit_log_stored', $result);

        } catch (Exception $e) {
            $result['errors'][] = 'Storage failed: ' . $e->getMessage();
            error_log('[VD Storage Manager] Store audit log error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Store license history entry
     *
     * @param array $history_entry History entry data
     * @param array $options Storage options
     * @return array Storage result
     */
    public function store_license_history($history_entry, $options = array()) {
        $default_options = array(
            'storage_type' => 'memory',
            'track_changes' => true,
            'privacy_filter' => true
        );

        $options = array_merge($default_options, $options);

        $result = array(
            'success' => false,
            'history_id' => null,
            'storage_type' => $options['storage_type'],
            'errors' => array()
        );

        try {
            // Generate history ID
            $history_id = $this->generate_history_id($history_entry);
            $result['history_id'] = $history_id;

            // Apply privacy filtering if enabled
            if ($options['privacy_filter'] && $this->privacy_manager) {
                $history_entry = $this->apply_privacy_filter($history_entry);
            }

            // Prepare complete history record
            $history_record = array(
                'history_id' => $history_id,
                'license_id' => $history_entry['license_id'] ?? null,
                'user_id' => $history_entry['user_id'] ?? get_current_user_id(),
                'action' => $history_entry['action'] ?? 'unknown',
                'old_status' => $history_entry['old_status'] ?? null,
                'new_status' => $history_entry['new_status'] ?? null,
                'context' => $history_entry['context'] ?? array(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $this->get_user_agent_hash(),
                'timestamp' => current_time('mysql'),
                'storage_type' => $options['storage_type']
            );

            // Store in appropriate location
            if ($options['storage_type'] === 'memory') {
                $this->history_storage[$history_id] = $history_record;
                $result['success'] = true;
            }

            // Log history storage
            $this->log_storage_operation('license_history_stored', array(
                'history_id' => $history_id,
                'license_id' => $history_record['license_id'],
                'action' => $history_record['action'],
                'storage_type' => $options['storage_type']
            ));

        } catch (Exception $e) {
            $result['errors'][] = 'History storage failed: ' . $e->getMessage();
            error_log('[VD Storage Manager] Store license history error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Retrieve license history
     *
     * @param int $license_id License ID
     * @param array $options Retrieval options
     * @return array History records
     */
    public function get_license_history($license_id, $options = array()) {
        $default_options = array(
            'storage_type' => 'memory',
            'limit' => 100,
            'order_by' => 'timestamp',
            'order' => 'DESC',
            'include_context' => true
        );

        $options = array_merge($default_options, $options);

        $result = array(
            'success' => false,
            'records' => array(),
            'total_records' => 0,
            'storage_type' => $options['storage_type'],
            'errors' => array()
        );

        try {
            if ($options['storage_type'] === 'memory') {
                // Filter records by license_id from memory storage
                $filtered_records = array();
                foreach ($this->history_storage as $record_id => $record) {
                    if (isset($record['license_id']) && $record['license_id'] == $license_id) {
                        $filtered_records[] = $record;
                    }
                }

                // Apply ordering and limiting
                $result['records'] = $this->apply_query_options($filtered_records, $options);
                $result['total_records'] = count($filtered_records);
                $result['success'] = true;
            }

        } catch (Exception $e) {
            $result['errors'][] = 'History retrieval failed: ' . $e->getMessage();
            error_log('[VD Storage Manager] Get license history error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Archive old logs
     *
     * @param array $options Archive options
     * @return array Archive result
     */
    public function archive_logs($options = array()) {
        $this->stats['logs_archived']++;

        $default_options = array(
            'archive_older_than_days' => $this->config['archiving']['archive_after_days'],
            'storage_types' => array('database', 'file'),
            'compress' => $this->config['archiving']['compress_archives'],
            'dry_run' => false
        );

        $options = array_merge($default_options, $options);

        $result = array(
            'success' => false,
            'archived_count' => 0,
            'archive_location' => null,
            'compression_used' => $options['compress'],
            'errors' => array()
        );

        try {
            $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $options['archive_older_than_days'] . ' days'));

            // Create archive file
            $archive_file = $this->create_archive_file($cutoff_date, $options);
            $result['archive_location'] = $archive_file;

            // Archive database logs
            if (in_array('database', $options['storage_types'])) {
                $archived_count = $this->archive_database_logs($cutoff_date, $archive_file, $options);
                $result['archived_count'] += $archived_count;
            }

            // Archive file logs
            if (in_array('file', $options['storage_types'])) {
                $archived_count = $this->archive_file_logs($cutoff_date, $archive_file, $options);
                $result['archived_count'] += $archived_count;
            }

            $result['success'] = $result['archived_count'] > 0;

            // Log archiving operation
            $this->log_storage_operation('logs_archived', $result);

        } catch (Exception $e) {
            $result['errors'][] = 'Archive failed: ' . $e->getMessage();
            error_log('[VD Storage Manager] Archive logs error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Clean up old logs
     *
     * @param array $options Cleanup options
     * @return array Cleanup result
     */
    public function cleanup_logs($options = array()) {
        $this->stats['logs_cleaned']++;

        $default_options = array(
            'cleanup_older_than_days' => $this->config['cleanup']['retention_days'],
            'archive_before_delete' => $this->config['cleanup']['archive_before_delete'],
            'storage_types' => array('database', 'file', 'memory'),
            'dry_run' => false
        );

        $options = array_merge($default_options, $options);

        $result = array(
            'success' => false,
            'cleaned_count' => 0,
            'archived_before_cleanup' => false,
            'storage_types_cleaned' => array(),
            'errors' => array()
        );

        try {
            // Archive before cleanup if enabled
            if ($options['archive_before_delete']) {
                $archive_result = $this->archive_logs(array(
                    'archive_older_than_days' => $options['cleanup_older_than_days'],
                    'dry_run' => $options['dry_run']
                ));
                $result['archived_before_cleanup'] = $archive_result['success'];
            }

            $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $options['cleanup_older_than_days'] . ' days'));

            // Clean memory storage
            if (in_array('memory', $options['storage_types'])) {
                $cleaned_count = $this->cleanup_memory_storage($cutoff_date, $options);
                if ($cleaned_count > 0) {
                    $result['cleaned_count'] += $cleaned_count;
                    $result['storage_types_cleaned'][] = 'memory';
                }
            }

            // Clean database storage
            if (in_array('database', $options['storage_types'])) {
                $cleaned_count = $this->cleanup_database_storage($cutoff_date, $options);
                if ($cleaned_count > 0) {
                    $result['cleaned_count'] += $cleaned_count;
                    $result['storage_types_cleaned'][] = 'database';
                }
            }

            // Clean file storage
            if (in_array('file', $options['storage_types'])) {
                $cleaned_count = $this->cleanup_file_storage($cutoff_date, $options);
                if ($cleaned_count > 0) {
                    $result['cleaned_count'] += $cleaned_count;
                    $result['storage_types_cleaned'][] = 'file';
                }
            }

            $result['success'] = $result['cleaned_count'] > 0;

            // Log cleanup operation
            $this->log_storage_operation('logs_cleaned', $result);

        } catch (Exception $e) {
            $result['errors'][] = 'Cleanup failed: ' . $e->getMessage();
            error_log('[VD Storage Manager] Cleanup logs error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get storage statistics
     *
     * @return array Storage statistics
     */
    public function get_storage_statistics() {
        $this->stats['execution_time'] = microtime(true) - $this->stats['start_time'];
        $this->stats['memory_usage'] = memory_get_usage(true) - $this->stats['memory_usage'];

        return array(
            'storage_stats' => $this->stats,
            'memory_storage' => array(
                'audit_logs_count' => count($this->audit_logs),
                'history_records_count' => count($this->history_storage),
                'memory_usage_bytes' => strlen(serialize($this->audit_logs)) + strlen(serialize($this->history_storage))
            ),
            'configuration' => array(
                'database_enabled' => $this->config['database_storage']['enabled'],
                'file_storage_enabled' => $this->config['file_storage']['enabled'],
                'cleanup_enabled' => $this->config['cleanup']['enabled'],
                'archiving_enabled' => $this->config['archiving']['enabled']
            ),
            'performance' => array(
                'execution_time' => $this->stats['execution_time'],
                'memory_peak_usage' => memory_get_peak_usage(true),
                'operations_per_second' => $this->calculate_operations_per_second()
            )
        );
    }

    /**
     * Get storage configuration
     *
     * @return array Storage configuration
     */
    public function get_configuration() {
        return $this->config;
    }

    /**
     * Update storage configuration
     *
     * @param array $new_config New configuration
     * @return bool Success status
     */
    public function update_configuration($new_config) {
        try {
            $this->config = array_merge($this->config, $new_config);
            return true;
        } catch (Exception $e) {
            error_log('[VD Storage Manager] Update configuration error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get module information
     *
     * @return array Module information
     */
    public function get_module_info() {
        return array(
            'name' => 'Security Audit Storage Manager',
            'version' => '1.4.0-rc.1',
            'namespace' => 'VD\\LicenseManager\\Security\\Storage',
            'file' => __FILE__,
            'size' => filesize(__FILE__) . ' bytes',
            'methods' => get_class_methods($this),
            'stats' => $this->get_storage_statistics(),
            'dependencies' => array('security.event_logger', 'security.privacy_manager'),
            'storage_features' => array(
                'database_storage',
                'file_storage',
                'memory_storage',
                'log_archiving',
                'automatic_cleanup',
                'compression_support',
                'performance_optimization',
                'privacy_filtering'
            ),
            'supported_operations' => array(
                'store_audit_log',
                'store_license_history',
                'get_license_history',
                'archive_logs',
                'cleanup_logs',
                'get_storage_statistics'
            )
        );
    }

    // Private helper methods

    private function generate_log_id() {
        return 'audit_' . uniqid() . '_' . time();
    }

    private function generate_history_id($history_entry) {
        $components = array(
            $history_entry['license_id'] ?? 'unknown',
            $history_entry['action'] ?? 'unknown',
            time(),
            uniqid()
        );
        return 'hist_' . md5(implode('_', $components));
    }

    private function prepare_log_entry($log_entry, $log_id, $options) {
        $prepared = array(
            'log_id' => $log_id,
            'timestamp' => current_time('mysql'),
            'level' => $log_entry['level'] ?? 'info',
            'event_type' => $log_entry['event_type'] ?? 'unknown',
            'component' => $log_entry['component'] ?? 'storage_manager',
            'message' => $log_entry['message'] ?? '',
            'context' => $log_entry['context'] ?? array(),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent_hash' => $this->get_user_agent_hash()
        );

        // Apply privacy filtering if enabled
        if ($options['privacy_filter'] && $this->privacy_manager) {
            $prepared = $this->apply_privacy_filter($prepared);
        }

        return $prepared;
    }

    private function store_in_memory($prepared_entry, $log_id) {
        $this->audit_logs[$log_id] = $prepared_entry;
        return true;
    }

    private function store_in_database($prepared_entry, $options) {
        // Placeholder for database storage implementation
        // In production, this would use WordPress $wpdb or custom database manager
        return true;
    }

    private function store_in_file($prepared_entry, $options) {
        // Placeholder for file storage implementation
        // In production, this would write to log files with rotation
        return true;
    }

    private function apply_privacy_filter($data) {
        if ($this->privacy_manager && method_exists($this->privacy_manager, 'sanitize_context_data')) {
            return $this->privacy_manager->sanitize_context_data($data);
        }
        return $data;
    }

    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function get_user_agent_hash() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $user_agent);
    }

    private function apply_query_options($records, $options) {
        // Simple implementation for memory storage
        if ($options['order'] === 'DESC') {
            $records = array_reverse($records);
        }

        if ($options['limit'] > 0) {
            $records = array_slice($records, 0, $options['limit']);
        }

        return $records;
    }

    private function create_storage_directories() {
        $directories = array(
            $this->config['file_storage']['base_path'],
            $this->config['archiving']['archive_location']
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    private function maybe_create_database_tables() {
        // Placeholder for database table creation
        // In production, this would create audit log tables
    }

    private function create_archive_file($cutoff_date, $options) {
        return $this->config['archiving']['archive_location'] . 'archive_' . date('Y-m-d_H-i-s') . '.zip';
    }

    private function archive_database_logs($cutoff_date, $archive_file, $options) {
        // Placeholder for database archiving
        return 0;
    }

    private function archive_file_logs($cutoff_date, $archive_file, $options) {
        // Placeholder for file archiving
        return 0;
    }

    private function cleanup_memory_storage($cutoff_date, $options) {
        $cleaned_count = 0;
        $cutoff_timestamp = strtotime($cutoff_date);

        // Clean audit logs
        foreach ($this->audit_logs as $log_id => $log) {
            if (isset($log['timestamp']) && strtotime($log['timestamp']) < $cutoff_timestamp) {
                if (!$options['dry_run']) {
                    unset($this->audit_logs[$log_id]);
                }
                $cleaned_count++;
            }
        }

        // Clean history storage
        foreach ($this->history_storage as $history_id => $history) {
            if (isset($history['timestamp']) && strtotime($history['timestamp']) < $cutoff_timestamp) {
                if (!$options['dry_run']) {
                    unset($this->history_storage[$history_id]);
                }
                $cleaned_count++;
            }
        }

        return $cleaned_count;
    }

    private function cleanup_database_storage($cutoff_date, $options) {
        // Placeholder for database cleanup
        return 0;
    }

    private function cleanup_file_storage($cutoff_date, $options) {
        // Placeholder for file cleanup
        return 0;
    }

    private function calculate_operations_per_second() {
        $total_operations = $this->stats['logs_stored'] + $this->stats['database_operations'] + $this->stats['file_operations'];
        $execution_time = $this->stats['execution_time'];
        return $execution_time > 0 ? round($total_operations / $execution_time, 2) : 0;
    }

    private function log_storage_operation($operation_type, $context) {
        if ($this->event_logger && method_exists($this->event_logger, 'log_security_event')) {
            try {
                $this->event_logger->log_security_event(array(
                    'event_type' => 'storage_' . $operation_type,
                    'component' => 'security_storage_manager',
                    'severity' => 'INFO',
                    'context' => $context,
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $this->get_client_ip()
                ));
            } catch (Exception $e) {
                error_log('[VD Storage Manager] Logging failed: ' . $e->getMessage());
            }
        }
    }
}