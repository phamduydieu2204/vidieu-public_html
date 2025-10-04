<?php

namespace VD\LicenseManager\SecurityAudit;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Security Audit Logger Module
 *
 * Phase 3.2 - Extracted from monolithic validator class to provide
 * comprehensive security auditing, logging, and context enhancement capabilities.
 *
 * @package VD_License_Manager
 * @subpackage SecurityAudit
 * @since 3.2.0
 * @author VD Team
 */
class VD_License_Security_Audit_Logger {

    /**
     * Singleton instance
     *
     * @var VD_License_Security_Audit_Logger|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '3.2.0';

    /**
     * Security audit instance reference
     *
     * @var object|null
     */
    private $security_audit = null;

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'audit_events_logged' => 0,
        'security_checks_performed' => 0,
        'context_generations' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Security_Audit_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module
     *
     * @return void
     */
    private function init_module() {
        $start_memory = memory_get_usage();

        // Initialize security audit if available
        if (class_exists('VD_Security_Audit')) {
            $this->security_audit = VD_Security_Audit::get_instance();
        }

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Security Audit Logger: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Log status validation debug information
     *
     * @since 3.2.0
     * @param string $event_type Event type
     * @param array $status_info Status information
     * @param array $debug_info Debug information
     * @return void
     */
    public function log_status_validation_debug($event_type, $status_info, $debug_info) {
        if (function_exists('vd_debug_log')) {
            $log_data = array(
                'event' => $event_type,
                'license_key' => substr($status_info['license_key'] ?? '', 0, 8) . '***',
                'validation_time' => $debug_info['validation_time_ms'] ?? 0,
                'memory_usage' => memory_get_usage()
            );

            vd_debug_log(sprintf(
                "VD License Status Validation %s: License %s (Time: %sms)",
                ucfirst($event_type),
                $log_data['license_key'],
                $debug_info['validation_time_ms'] ?? 0
            ), $log_data);
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log successful license validation for audit
     *
     * @since 3.2.0
     * @param string $license_key License key
     * @param array $license License data
     * @return void
     */
    public function log_license_validation_success($license_key, $license) {
        if (function_exists('vd_debug_log')) {
            vd_debug_log(sprintf(
                "VD License Validation Success: License %s validated successfully",
                substr($license_key, 0, 8) . '***'
            ));
        }

        // Integration with audit logger if available
        if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
            $this->security_audit->log_security_event(
                'license_validation_success',
                array(
                    'license_key' => substr($license_key, 0, 8) . '***', // Masked for security
                    'license_status' => $license['status'] ?? 'unknown',
                    'validation_timestamp' => current_time('mysql'),
                    'validation_method' => 'manual'
                )
            );
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log automatic status update for audit
     *
     * @since 3.2.0
     * @param array $license License data
     * @param string $new_status New status
     * @return void
     */
    public function log_automatic_status_update($license, $new_status) {
        if (function_exists('vd_debug_log')) {
            vd_debug_log(sprintf(
                "VD License Automatic Status Update: License %s updated to %s",
                substr($license['license_key'] ?? '', 0, 8) . '***',
                $new_status
            ));
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log automatic status change audit
     *
     * @since 3.2.0
     * @param array $license License data
     * @param string $new_status New status
     * @param array $options Change options
     * @return void
     */
    public function log_automatic_status_change($license, $new_status, $options) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'automatic_status_change',
                    'license_key' => substr($license['license_key'] ?? '', 0, 8) . '***',
                    'old_status' => $license['status'] ?? 'unknown',
                    'new_status' => $new_status,
                    'change_reason' => $options['reason'] ?? 'automatic',
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to log status change - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log batch update completion
     *
     * @since 3.2.0
     * @param int $batch_number Batch number
     * @param array $batch_result Batch result
     * @param array $options Update options
     * @return void
     */
    public function log_batch_update_completion($batch_number, $batch_result, $options) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'batch_update_completion',
                    'batch_number' => $batch_number,
                    'licenses_updated' => count($batch_result['updated'] ?? array()),
                    'errors_count' => count($batch_result['errors'] ?? array()),
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to log batch completion - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log update error
     *
     * @since 3.2.0
     * @param string $function Function name
     * @param Exception $exception Exception
     * @param array $options Error context
     * @return void
     */
    public function log_update_error($function, $exception, $options) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'update_error',
                    'function' => $function,
                    'error_message' => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to log update error - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Audit automatic update completion
     *
     * @since 3.2.0
     * @param array $results Update results
     * @param array $options Update options
     * @return void
     */
    public function audit_automatic_update_completion($results, $options) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'automatic_update_completion',
                    'total_processed' => $results['total_processed'] ?? 0,
                    'successful_updates' => $results['successful_updates'] ?? 0,
                    'failed_updates' => $results['failed_updates'] ?? 0,
                    'execution_time_ms' => $results['execution_time_ms'] ?? 0,
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to audit update completion - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log notification completion
     *
     * @since 3.2.0
     * @param array $license License data
     * @param string $old_status Previous status
     * @param string $new_status New status
     * @param array $results Notification results
     * @param array $context Notification context
     * @return void
     */
    public function log_notification_completion($license, $old_status, $new_status, $results, $context) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'notification_completion',
                    'license_key' => substr($license['license_key'] ?? '', 0, 8) . '***',
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                    'notifications_sent' => count($results['sent'] ?? array()),
                    'notifications_failed' => count($results['failed'] ?? array()),
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to log notification completion - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Log notification error
     *
     * @since 3.2.0
     * @param string $function Function name
     * @param Exception $exception Exception
     * @param array $license License data
     * @param array $context Error context
     * @return void
     */
    public function log_notification_error($function, $exception, $license, $context) {
        try {
            if ($this->security_audit && method_exists($this->security_audit, 'log_security_event')) {
                $this->security_audit->log_security_event(array(
                    'event_type' => 'notification_error',
                    'function' => $function,
                    'license_key' => substr($license['license_key'] ?? '', 0, 8) . '***',
                    'error_message' => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                    'timestamp' => current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Security Audit: Failed to log notification error - " . $e->getMessage());
            }
        }

        $this->status['audit_events_logged']++;
    }

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'security_audit_available' => $this->security_audit !== null
        ));
    }

    /**
     * Module health check
     *
     * @return array Health check results
     */
    public function health_check() {
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
            'warnings' => array(),
            'errors' => array()
        );

        // Check if initialized
        if (!$this->status['initialized']) {
            $health['errors'][] = 'Security Audit Logger not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check security audit integration
        if ($this->security_audit === null) {
            $health['warnings'][] = 'Security audit integration not available';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Security audit integration active';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 524288) { // 512KB
            $health['warnings'][] = 'High memory usage: ' . number_format($this->status['memory_usage'] / 1024, 2) . ' KB';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Memory usage within limits';
        }

        return $health;
    }

    /**
     * Get debug information
     *
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'module' => 'Security Audit Logger',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'audit_events_logged' => $this->status['audit_events_logged'],
            'security_checks_performed' => $this->status['security_checks_performed'],
            'context_generations' => $this->status['context_generations'],
            'memory_usage' => $this->status['memory_usage'],
            'security_audit_available' => $this->security_audit !== null,
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}