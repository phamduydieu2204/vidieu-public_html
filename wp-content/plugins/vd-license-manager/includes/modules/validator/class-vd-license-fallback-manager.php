<?php

namespace VD\LicenseManager\Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Fallback Manager
 *
 * Comprehensive fallback mechanism for when orchestrator or modules fail
 * Provides graceful degradation and robust error handling
 *
 * Step 5.4: Fallback Mechanism Implementation - Micro-Step 5.4
 *
 * @since 1.6.0
 * @package VD_License_Manager
 */
class VD_License_Fallback_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Fallback_Manager|null
     */
    private static $instance = null;

    /**
     * Fallback configuration
     *
     * @var array
     */
    private $fallback_config = array(
        'enabled' => true,
        'max_retry_attempts' => 3,
        'retry_delay_ms' => 100,
        'fallback_chain' => array(
            'orchestrator_retry',
            'constraint_validation',
            'basic_validation',
            'minimal_validation'
        ),
        'error_reporting' => true,
        'performance_tracking' => true
    );

    /**
     * Error statistics
     *
     * @var array
     */
    private $error_stats = array(
        'orchestrator_failures' => 0,
        'constraint_failures' => 0,
        'total_fallbacks' => 0,
        'successful_recoveries' => 0,
        'last_failure_time' => null,
        'fallback_success_rate' => 100.0
    );

    /**
     * Performance metrics
     *
     * @var array
     */
    private $performance_metrics = array(
        'avg_fallback_time' => 0,
        'max_fallback_time' => 0,
        'total_fallback_time' => 0,
        'fallback_count' => 0
    );

    /**
     * Legacy validator instance for fallback
     *
     * @var object|null
     */
    private $legacy_validator = null;

    /**
     * Constraint validation module for fallback
     *
     * @var object|null
     */
    private $constraint_validator = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->initialize_fallback_systems();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Fallback_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize fallback systems
     * Step 5.4 - Initialize comprehensive fallback mechanisms
     *
     * @since 1.6.0
     */
    private function initialize_fallback_systems() {
        // Load legacy validator for ultimate fallback
        $this->load_legacy_validator();

        // Load constraint validation module
        $this->load_constraint_validator();

        // Initialize error tracking
        $this->initialize_error_tracking();
    }

    /**
     * Execute fallback validation
     * Step 5.4 - Main fallback execution method
     *
     * @since 1.6.0
     * @param string $license_key License key to validate
     * @param array $context Validation context
     * @param string $original_method Original method that failed
     * @param Exception|null $original_error Original error
     * @return array Fallback validation result
     */
    public function execute_fallback_validation($license_key, $context = array(), $original_method = '', $original_error = null) {
        $start_time = microtime(true);

        // Log fallback initiation
        $this->log_fallback_initiation($license_key, $original_method, $original_error);

        $fallback_result = array(
            'valid' => false,
            'fallback_used' => true,
            'fallback_method' => '',
            'original_method' => $original_method,
            'original_error' => $original_error ? $original_error->getMessage() : 'Unknown error',
            'errors' => array(),
            'warnings' => array(),
            'fallback_chain_executed' => array(),
            'performance_metrics' => array(),
            'framework_version' => '4.2.4.5.3e-fallback'
        );

        // Execute fallback chain
        foreach ($this->fallback_config['fallback_chain'] as $fallback_method) {
            $fallback_result['fallback_chain_executed'][] = $fallback_method;

            try {
                $method_result = $this->execute_fallback_method($fallback_method, $license_key, $context);

                if ($method_result && $method_result['valid']) {
                    $fallback_result = array_merge($fallback_result, $method_result);
                    $fallback_result['fallback_method'] = $fallback_method;
                    $fallback_result['valid'] = true;

                    // Record successful recovery
                    $this->record_successful_recovery($fallback_method);
                    break;
                }

            } catch (Exception $e) {
                $fallback_result['warnings'][] = "Fallback method '$fallback_method' failed: " . $e->getMessage();
                $this->record_fallback_failure($fallback_method, $e);
                continue;
            }
        }

        // Calculate performance metrics
        $execution_time = (microtime(true) - $start_time) * 1000;
        $fallback_result['performance_metrics'] = array(
            'execution_time_ms' => round($execution_time, 3),
            'fallback_overhead' => round($execution_time, 3)
        );

        // Update statistics
        $this->update_fallback_statistics($fallback_result, $execution_time);

        // Log fallback completion
        $this->log_fallback_completion($fallback_result);

        return $fallback_result;
    }

    /**
     * Execute specific fallback method
     * Step 5.4 - Execute individual fallback methods
     *
     * @since 1.6.0
     * @param string $method Fallback method to execute
     * @param string $license_key License key
     * @param array $context Validation context
     * @return array|null Method result or null if failed
     */
    private function execute_fallback_method($method, $license_key, $context) {
        switch ($method) {
            case 'orchestrator_retry':
                return $this->retry_orchestrator_validation($license_key, $context);

            case 'constraint_validation':
                return $this->execute_constraint_validation($license_key, $context);

            case 'basic_validation':
                return $this->execute_basic_validation($license_key, $context);

            case 'minimal_validation':
                return $this->execute_minimal_validation($license_key, $context);

            default:
                return null;
        }
    }

    /**
     * Retry orchestrator validation with different options
     * Step 5.4 - Orchestrator retry with simplified options
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $context Validation context
     * @return array|null Retry result
     */
    private function retry_orchestrator_validation($license_key, $context) {
        if (!class_exists('VD\\LicenseManager\\Validator\\VD_License_Validation_Orchestrator')) {
            return null;
        }

        try {
            $orchestrator = \VD\LicenseManager\Validator\VD_License_Validation_Orchestrator::get_instance();

            // Simplified options for retry
            $retry_options = array(
                'validation_type' => 'simple',
                'include_warnings' => false,
                'generate_report' => false,
                'timeout' => 5, // 5 second timeout
                'retry_mode' => true
            );

            $result = $orchestrator->orchestrate_license_validation($license_key, $retry_options);

            if ($result && isset($result['valid'])) {
                return array(
                    'valid' => $result['valid'],
                    'method_used' => 'orchestrator_retry',
                    'simplified_result' => true,
                    'warnings' => array('Used simplified orchestrator retry')
                );
            }

        } catch (Exception $e) {
            // Orchestrator retry failed, continue to next fallback
            return null;
        }

        return null;
    }

    /**
     * Execute constraint validation fallback
     * Step 5.4 - Constraint validation module fallback
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $context Validation context
     * @return array|null Constraint validation result
     */
    private function execute_constraint_validation($license_key, $context) {
        if (!$this->constraint_validator) {
            return null;
        }

        try {
            $result = $this->constraint_validator->perform_conditional_state_validation(
                array('key' => $license_key),
                $context
            );

            if ($result && isset($result['valid'])) {
                return array(
                    'valid' => $result['valid'],
                    'method_used' => 'constraint_validation',
                    'errors' => $result['errors'] ?? array(),
                    'warnings' => array_merge(
                        $result['warnings'] ?? array(),
                        array('Used constraint validation fallback')
                    )
                );
            }

        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Execute basic validation fallback
     * Step 5.4 - Basic validation using legacy validator
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $context Validation context
     * @return array|null Basic validation result
     */
    private function execute_basic_validation($license_key, $context) {
        if (!$this->legacy_validator) {
            return null;
        }

        try {
            // Use basic format validation
            $format_valid = $this->legacy_validator->validate_license_key_format($license_key, false);

            return array(
                'valid' => (bool) $format_valid,
                'method_used' => 'basic_validation',
                'validation_level' => 'format_only',
                'warnings' => array('Used basic validation fallback - format check only')
            );

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Execute minimal validation fallback (last resort)
     * Step 5.4 - Minimal validation as last resort
     *
     * @since 1.6.0
     * @param string $license_key License key
     * @param array $context Validation context
     * @return array Minimal validation result
     */
    private function execute_minimal_validation($license_key, $context) {
        // Minimal validation - just check if license key is not empty and has reasonable length
        $is_valid = !empty($license_key) && strlen($license_key) >= 8 && strlen($license_key) <= 255;

        return array(
            'valid' => $is_valid,
            'method_used' => 'minimal_validation',
            'validation_level' => 'minimal',
            'warnings' => array(
                'Used minimal validation fallback - basic checks only',
                'This validation is very permissive and should be reviewed'
            ),
            'minimal_checks' => array(
                'not_empty' => !empty($license_key),
                'length_check' => strlen($license_key) >= 8 && strlen($license_key) <= 255,
                'overall_valid' => $is_valid
            )
        );
    }

    /**
     * Load legacy validator for fallback
     * Step 5.4 - Load legacy validator system
     *
     * @since 1.6.0
     */
    private function load_legacy_validator() {
        if (class_exists('VD_License_Validator')) {
            try {
                $this->legacy_validator = new \VD_License_Validator();
            } catch (Exception $e) {
                error_log('[VD Fallback Manager] Failed to load legacy validator: ' . $e->getMessage());
            }
        }
    }

    /**
     * Load constraint validator for fallback
     * Step 5.4 - Load constraint validation module
     *
     * @since 1.6.0
     */
    private function load_constraint_validator() {
        // Try to load constraint validation module
        $constraint_file = plugin_dir_path(__FILE__) . 'class-vd-license-constraint-validation.php';

        if (file_exists($constraint_file)) {
            try {
                require_once $constraint_file;

                if (class_exists('VD\\LicenseManager\\Validator\\VD_License_Constraint_Validation')) {
                    $this->constraint_validator = new \VD\LicenseManager\Validator\VD_License_Constraint_Validation();
                }
            } catch (Exception $e) {
                error_log('[VD Fallback Manager] Failed to load constraint validator: ' . $e->getMessage());
            }
        }
    }

    /**
     * Initialize error tracking
     * Step 5.4 - Initialize comprehensive error tracking
     *
     * @since 1.6.0
     */
    private function initialize_error_tracking() {
        // Load error statistics from WordPress options
        $stored_stats = get_option('vd_license_fallback_stats', array());

        if (!empty($stored_stats)) {
            $this->error_stats = array_merge($this->error_stats, $stored_stats);
        }

        // Load performance metrics
        $stored_metrics = get_option('vd_license_fallback_performance', array());

        if (!empty($stored_metrics)) {
            $this->performance_metrics = array_merge($this->performance_metrics, $stored_metrics);
        }
    }

    /**
     * Record successful recovery
     * Step 5.4 - Track successful fallback recoveries
     *
     * @since 1.6.0
     * @param string $method Successful fallback method
     */
    private function record_successful_recovery($method) {
        $this->error_stats['successful_recoveries']++;
        $this->error_stats['total_fallbacks']++;

        // Update success rate
        if ($this->error_stats['total_fallbacks'] > 0) {
            $this->error_stats['fallback_success_rate'] =
                ($this->error_stats['successful_recoveries'] / $this->error_stats['total_fallbacks']) * 100;
        }

        // Save statistics
        $this->save_error_statistics();
    }

    /**
     * Record fallback failure
     * Step 5.4 - Track fallback method failures
     *
     * @since 1.6.0
     * @param string $method Failed fallback method
     * @param Exception $error Error that occurred
     */
    private function record_fallback_failure($method, $error) {
        $this->error_stats['total_fallbacks']++;
        $this->error_stats['last_failure_time'] = current_time('mysql');

        // Track specific failure types
        switch ($method) {
            case 'orchestrator_retry':
                $this->error_stats['orchestrator_failures']++;
                break;
            case 'constraint_validation':
                $this->error_stats['constraint_failures']++;
                break;
        }

        // Update success rate
        if ($this->error_stats['total_fallbacks'] > 0) {
            $this->error_stats['fallback_success_rate'] =
                ($this->error_stats['successful_recoveries'] / $this->error_stats['total_fallbacks']) * 100;
        }

        // Save statistics
        $this->save_error_statistics();
    }

    /**
     * Update fallback statistics
     * Step 5.4 - Update comprehensive statistics
     *
     * @since 1.6.0
     * @param array $result Fallback result
     * @param float $execution_time Execution time in milliseconds
     */
    private function update_fallback_statistics($result, $execution_time) {
        // Update performance metrics
        $this->performance_metrics['fallback_count']++;
        $this->performance_metrics['total_fallback_time'] += $execution_time;
        $this->performance_metrics['avg_fallback_time'] =
            $this->performance_metrics['total_fallback_time'] / $this->performance_metrics['fallback_count'];

        if ($execution_time > $this->performance_metrics['max_fallback_time']) {
            $this->performance_metrics['max_fallback_time'] = $execution_time;
        }

        // Save performance metrics
        $this->save_performance_metrics();
    }

    /**
     * Save error statistics to WordPress options
     * Step 5.4 - Persist error statistics
     *
     * @since 1.6.0
     */
    private function save_error_statistics() {
        update_option('vd_license_fallback_stats', $this->error_stats);
    }

    /**
     * Save performance metrics to WordPress options
     * Step 5.4 - Persist performance metrics
     *
     * @since 1.6.0
     */
    private function save_performance_metrics() {
        update_option('vd_license_fallback_performance', $this->performance_metrics);
    }

    /**
     * Log fallback initiation
     * Step 5.4 - Log when fallback is initiated
     *
     * @since 1.6.0
     * @param string $license_key License key (partial for security)
     * @param string $original_method Original method that failed
     * @param Exception|null $original_error Original error
     */
    private function log_fallback_initiation($license_key, $original_method, $original_error) {
        if (!$this->fallback_config['error_reporting']) {
            return;
        }

        $partial_key = substr($license_key, 0, 8) . '...';
        $error_message = $original_error ? $original_error->getMessage() : 'Unknown error';

        error_log(sprintf(
            '[VD Fallback Manager] Initiating fallback for license %s. Original method: %s. Error: %s',
            $partial_key,
            $original_method,
            $error_message
        ));
    }

    /**
     * Log fallback completion
     * Step 5.4 - Log fallback completion with results
     *
     * @since 1.6.0
     * @param array $result Fallback result
     */
    private function log_fallback_completion($result) {
        if (!$this->fallback_config['error_reporting']) {
            return;
        }

        $status = $result['valid'] ? 'SUCCESS' : 'FAILED';
        $method = $result['fallback_method'] ?? 'none';
        $execution_time = $result['performance_metrics']['execution_time_ms'] ?? 0;

        error_log(sprintf(
            '[VD Fallback Manager] Fallback completed. Status: %s. Method: %s. Time: %sms',
            $status,
            $method,
            $execution_time
        ));
    }

    /**
     * Get fallback statistics
     * Step 5.4 - Provide fallback statistics for monitoring
     *
     * @since 1.6.0
     * @return array Comprehensive fallback statistics
     */
    public function get_fallback_statistics() {
        return array(
            'configuration' => $this->fallback_config,
            'error_statistics' => $this->error_stats,
            'performance_metrics' => $this->performance_metrics,
            'system_status' => array(
                'legacy_validator_available' => !is_null($this->legacy_validator),
                'constraint_validator_available' => !is_null($this->constraint_validator),
                'fallback_chain_length' => count($this->fallback_config['fallback_chain']),
                'last_updated' => current_time('mysql')
            )
        );
    }

    /**
     * Reset fallback statistics
     * Step 5.4 - Reset statistics for fresh monitoring
     *
     * @since 1.6.0
     */
    public function reset_fallback_statistics() {
        $this->error_stats = array(
            'orchestrator_failures' => 0,
            'constraint_failures' => 0,
            'total_fallbacks' => 0,
            'successful_recoveries' => 0,
            'last_failure_time' => null,
            'fallback_success_rate' => 100.0
        );

        $this->performance_metrics = array(
            'avg_fallback_time' => 0,
            'max_fallback_time' => 0,
            'total_fallback_time' => 0,
            'fallback_count' => 0
        );

        // Save reset statistics
        $this->save_error_statistics();
        $this->save_performance_metrics();
    }

    /**
     * Configure fallback behavior
     * Step 5.4 - Allow runtime configuration of fallback behavior
     *
     * @since 1.6.0
     * @param array $config New configuration options
     * @return bool Success status
     */
    public function configure_fallback($config) {
        if (!is_array($config)) {
            return false;
        }

        // Merge with existing configuration
        $this->fallback_config = array_merge($this->fallback_config, $config);

        // Save configuration
        update_option('vd_license_fallback_config', $this->fallback_config);

        return true;
    }
}