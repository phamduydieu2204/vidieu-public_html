<?php
/**
 * VD Audit Logger
 *
 * Handles audit trail logging for all system actions
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Audit_Logger class
 *
 * Static class for logging audit trail events
 */
class VD_Audit_Logger {

    /**
     * Log an action to the audit trail
     *
     * @since 1.0.0
     * @param string $entity_type Type of entity (license, provider, device, etc.)
     * @param string $action Action performed (create, update, delete, etc.)
     * @param int $entity_id ID of the affected entity
     * @param int|null $user_id User who performed the action
     * @param string $details Additional details about the action
     * @param array $metadata Optional metadata
     * @return bool True on success
     */
    public static function log_action($entity_type, $action, $entity_id, $user_id = null, $details = '', $metadata = []) {
        global $wpdb;

        // Get current user if not provided
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        // Get user IP
        $ip_address = self::get_client_ip();

        // Prepare data
        $log_data = [
            'entity_type' => sanitize_text_field($entity_type),
            'entity_id' => intval($entity_id),
            'action' => sanitize_text_field($action),
            'user_id' => intval($user_id),
            'ip_address' => sanitize_text_field($ip_address),
            'user_agent' => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'details' => sanitize_textarea_field($details),
            'metadata' => json_encode($metadata),
            'created_at' => current_time('mysql')
        ];

        // Insert into audit log table
        $table_name = $wpdb->prefix . 'vd_audit_logs';

        // Debug log the exact table name being used
        vd_debug_log("VD_Audit_Logger: Using table name: {$table_name} (prefix: {$wpdb->prefix})");

        // Clear any cached table schema to prevent old table references
        wp_cache_delete($table_name, 'table_schema');
        wp_cache_delete('vd_audit_logs', 'table_schema');
        wp_cache_delete('bz_vd_audit_logs', 'table_schema');

        // Also flush wpdb cache
        if (method_exists($wpdb, 'flush')) {
            $wpdb->flush();
        }

        // Clear any WordPress object cache for table structure (only if object cache is working)
        // Skip Redis cache operations as server doesn't have Redis service
        if (wp_using_ext_object_cache() && function_exists('wp_cache_flush_group') && !class_exists('Redis')) {
            try {
                wp_cache_flush_group('table_structures');
            } catch (Exception $e) {
                vd_debug_log("Failed to flush cache group 'table_structures': " . $e->getMessage());
            }
        }

        // Delete any possible cached references to old table names
        $old_incorrect_names = [
            'bz_bz_vd_audit_logs',
            $wpdb->prefix . 'bz_vd_audit_logs',
            'bz_' . $wpdb->prefix . 'vd_audit_logs'
        ];

        foreach ($old_incorrect_names as $old_name) {
            wp_cache_delete($old_name, 'table_schema');
            wp_cache_delete($old_name . '_structure', 'database');
        }

        // Verify table exists with correct name
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if (!$table_check) {
            vd_debug_log("Audit table does not exist: {$table_name}. Attempting to create it.");
            // Try to create table if it doesn't exist
            if (class_exists('VD_Database_Manager')) {
                VD_Database_Manager::create_tables();
            }
        }

        $result = $wpdb->insert($table_name, $log_data);

        if (false === $result) {
            vd_debug_log("Failed to log audit action: {$entity_type}.{$action} for entity {$entity_id}");
            return false;
        }

        return true;
    }

    /**
     * Get client IP address
     *
     * @since 1.0.0
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get audit logs for an entity
     *
     * @since 1.0.0
     * @param string $entity_type Entity type
     * @param int $entity_id Entity ID
     * @param int $limit Number of logs to retrieve
     * @return array Audit logs
     */
    public static function get_entity_logs($entity_type, $entity_id, $limit = 20) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_audit_logs';

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE entity_type = %s AND entity_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $entity_type,
            $entity_id,
            $limit
        ), ARRAY_A);

        return $logs ?: [];
    }

    /**
     * Get recent audit logs
     *
     * @since 1.0.0
     * @param int $limit Number of logs to retrieve
     * @param string $entity_type Optional entity type filter
     * @return array Recent audit logs
     */
    public static function get_recent_logs($limit = 50, $entity_type = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_audit_logs';

        $where_clause = '';
        $params = [$limit];

        if (!empty($entity_type)) {
            $where_clause = 'WHERE entity_type = %s';
            array_unshift($params, $entity_type);
        }

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}
             {$where_clause}
             ORDER BY created_at DESC
             LIMIT %d",
            ...$params
        ), ARRAY_A);

        return $logs ?: [];
    }

    /**
     * Clean up old audit logs
     *
     * @since 1.0.0
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public static function cleanup_old_logs($days = 90) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_audit_logs';

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name}
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        if ($result > 0) {
            vd_debug_log("Cleaned up {$result} old audit log entries (older than {$days} days)");
        }

        return intval($result);
    }

    /**
     * Get audit log statistics
     *
     * @since 1.0.0
     * @return array Statistics
     */
    public static function get_statistics() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_audit_logs';

        // Total logs
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // Logs by entity type
        $by_entity = $wpdb->get_results(
            "SELECT entity_type, COUNT(*) as count
             FROM {$table_name}
             GROUP BY entity_type
             ORDER BY count DESC",
            ARRAY_A
        );

        // Logs by action
        $by_action = $wpdb->get_results(
            "SELECT action, COUNT(*) as count
             FROM {$table_name}
             GROUP BY action
             ORDER BY count DESC",
            ARRAY_A
        );

        // Recent activity (last 24 hours)
        $recent_activity = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        return [
            'total_logs' => intval($total_logs),
            'by_entity_type' => $by_entity ?: [],
            'by_action' => $by_action ?: [],
            'recent_activity_24h' => intval($recent_activity)
        ];
    }
}