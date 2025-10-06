<?php
/**
 * History API endpoint for VD License Manager
 *
 * Handles access history retrieval requests
 *
 * @package VD_License_Manager
 * @subpackage API
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD API History class
 *
 * Processes history requests via REST API
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_API_History {

    /**
     * Handle history request
     *
     * Main endpoint handler for access history retrieval
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public static function handle($request) {
        try {
            // Validate license key
            $license_key = $request->get_param('license_key');
            $sanitized_key = VD_Security::sanitize_license_key($license_key);
            if (!$sanitized_key) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => 'Invalid license key format'
                ), 400);
            }

            // Verify license and get license_id
            $license_result = VD_License_Verify::verify_license($sanitized_key);
            if (!$license_result['ok']) {
                return new WP_REST_Response(array(
                    'ok' => false,
                    'error' => $license_result['error']
                ), 401);
            }

            $license_id = $license_result['license_id'];

            // Get parameters
            $limit = min(100, max(1, (int) $request->get_param('limit') ?: 20));
            $offset = max(0, (int) $request->get_param('offset') ?: 0);
            $type = $request->get_param('type') ?: 'all';

            // Get logs based on type
            $access_logs = ($type === 'fetch') ? array() : self::get_access_logs($license_id, $limit, $offset);
            $fetch_logs = ($type === 'access') ? array() : self::get_fetch_logs($license_id, $limit, $offset);

            // Merge and sort timeline
            $timeline = self::merge_timeline($access_logs, $fetch_logs, $limit);

            // Get total count for pagination
            $total_count = self::get_total_count($license_id, $type);

            return new WP_REST_Response(array(
                'ok' => true,
                'timeline' => $timeline,
                'pagination' => array(
                    'total' => $total_count,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total_count
                )
            ), 200);

        } catch (Exception $e) {
            error_log('VD API History: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'ok' => false,
                'error' => 'Internal server error'
            ), 500);
        }
    }

    /**
     * Get access logs for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array Access log entries
     */
    private static function get_access_logs($license_id, $limit, $offset) {
        global $wpdb;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT
                al.log_id,
                al.action,
                al.status,
                al.reason,
                al.ip_address,
                al.ip_country,
                al.ip_city,
                al.accessed_at as timestamp,
                df.fp_data
             FROM {$wpdb->prefix}vd_license_access_log al
             LEFT JOIN {$wpdb->prefix}vd_device_fingerprints df ON al.device_fp = df.fp_hash
             WHERE al.license_id = %d
             ORDER BY al.accessed_at DESC
             LIMIT %d OFFSET %d",
            $license_id,
            $limit,
            $offset
        ));

        $formatted_logs = array();
        foreach ($logs as $log) {
            $days_old = (time() - strtotime($log->timestamp)) / DAY_IN_SECONDS;
            $formatted_logs[] = self::format_timeline_entry($log, 'access', $days_old);
        }

        return $formatted_logs;
    }

    /**
     * Get fetch logs for license
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array Fetch log entries
     */
    private static function get_fetch_logs($license_id, $limit, $offset) {
        global $wpdb;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT
                fl.log_id,
                fl.provider_account_id,
                fl.fields_accessed,
                fl.ip_address,
                fl.status,
                fl.accessed_at as timestamp,
                df.fp_data
             FROM {$wpdb->prefix}vd_account_fetch_log fl
             LEFT JOIN {$wpdb->prefix}vd_device_fingerprints df ON fl.device_fp = df.fp_hash
             WHERE fl.license_id = %d
             ORDER BY fl.accessed_at DESC
             LIMIT %d OFFSET %d",
            $license_id,
            $limit,
            $offset
        ));

        $formatted_logs = array();
        foreach ($logs as $log) {
            $days_old = (time() - strtotime($log->timestamp)) / DAY_IN_SECONDS;
            $formatted_logs[] = self::format_timeline_entry($log, 'fetch', $days_old);
        }

        return $formatted_logs;
    }

    /**
     * Merge and sort timeline entries
     *
     * @since 1.0.0
     * @param array $access_logs Access log entries
     * @param array $fetch_logs Fetch log entries
     * @param int $limit Final limit
     * @return array Merged timeline
     */
    private static function merge_timeline($access_logs, $fetch_logs, $limit) {
        $merged = array_merge($access_logs, $fetch_logs);

        // Sort by timestamp descending
        usort($merged, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        // Apply limit
        return array_slice($merged, 0, $limit);
    }

    /**
     * Format timeline entry
     *
     * @since 1.0.0
     * @param object $entry Raw log entry
     * @param string $type Entry type (access|fetch)
     * @param int $days_old Age in days
     * @return array Formatted entry
     */
    private static function format_timeline_entry($entry, $type, $days_old) {
        // Apply IP privacy
        $ip_address = VD_Security::hash_ip_for_privacy($entry->ip_address, $days_old);

        // Parse device data
        $device_info = array('fp' => '', 'os' => '', 'browser' => '');
        if (!empty($entry->fp_data)) {
            $fp_data = json_decode($entry->fp_data, true);
            if (is_array($fp_data)) {
                $parsed_ua = VD_Data::parse_user_agent($fp_data['ua'] ?? '');
                $device_info = array(
                    'fp' => substr(md5($entry->fp_data), 0, 8) . '...',
                    'os' => $fp_data['os'] ?? $parsed_ua['os'],
                    'browser' => $parsed_ua['browser']
                );
            }
        }

        // Format location
        $location = '';
        if (!empty($entry->ip_city) && !empty($entry->ip_country)) {
            $location = $entry->ip_city . ', ' . $entry->ip_country;
        } elseif (!empty($entry->ip_country)) {
            $location = $entry->ip_country;
        }

        $formatted = array(
            'type' => $type,
            'timestamp' => gmdate('c', strtotime($entry->timestamp)),
            'device' => $device_info,
            'ip' => $ip_address,
            'location' => $location,
            'status' => $entry->status
        );

        // Add type-specific fields
        if ($type === 'access') {
            $formatted['action'] = $entry->action;
            $formatted['reason'] = $entry->reason;
        } else {
            $formatted['fields'] = !empty($entry->fields_accessed) ?
                json_decode($entry->fields_accessed, true) : array();
            $formatted['account_id'] = (int) $entry->provider_account_id;
        }

        return $formatted;
    }

    /**
     * Get total count for pagination
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $type Log type filter
     * @return int Total count
     */
    private static function get_total_count($license_id, $type) {
        global $wpdb;

        $count = 0;

        if ($type === 'all' || $type === 'access') {
            $access_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vd_license_access_log WHERE license_id = %d",
                $license_id
            ));
            $count += $access_count;
        }

        if ($type === 'all' || $type === 'fetch') {
            $fetch_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vd_account_fetch_log WHERE license_id = %d",
                $license_id
            ));
            $count += $fetch_count;
        }

        return $count;
    }
}