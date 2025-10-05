<?php

namespace VD\LicenseManager\Infrastructure;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Validation Infrastructure
 *
 * Step 4.3.1 - Extracted from monolithic validator class to provide
 * license key extraction, context transformation, result mapping,
 * and orchestrator check counting infrastructure utilities.
 *
 * @package VD_License_Manager
 * @subpackage Infrastructure
 * @since 4.3.1
 * @author VD Team
 */
class VD_License_Validation_Infrastructure {

    /**
     * Singleton instance
     *
     * @var VD_License_Validation_Infrastructure|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '4.3.1';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'license_keys_extracted' => 0,
        'context_transformations' => 0,
        'result_mappings' => 0,
        'check_counts_calculated' => 0,
        'memory_usage' => 0
    );

    /**
     * Data transformation configuration
     *
     * @var array
     */
    private $transformation_config = array();

    /**
     * Format mapping configuration
     *
     * @var array
     */
    private $format_mapping_config = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Validation_Infrastructure
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

        // Initialize transformation configuration
        $this->init_transformation_config();

        // Initialize format mapping configuration
        $this->init_format_mapping_config();

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Validation Infrastructure: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Initialize transformation configuration
     *
     * @return void
     */
    private function init_transformation_config() {
        $this->transformation_config = array(
            'default_options' => array(
                'validation_type' => 'advanced_rules',
                'include_warnings' => true,
                'generate_report' => true,
                'framework_version' => '4.3.1'
            ),
            'license_key_fields' => array(
                'key',
                'license_key',
                'id',
                'license_id',
                'activation_key'
            ),
            'context_merge_priority' => array(
                'user_context',
                'ip_context',
                'device_context',
                'domain_context'
            ),
            'data_sanitization' => array(
                'strip_html' => true,
                'trim_whitespace' => true,
                'normalize_case' => false
            )
        );
    }

    /**
     * Initialize format mapping configuration
     *
     * @return void
     */
    private function init_format_mapping_config() {
        $this->format_mapping_config = array(
            'orchestrator_to_legacy' => array(
                'valid' => 'valid',
                'validation_pipeline' => 'validation_pipeline',
                'accumulated_errors' => 'errors',
                'validation_warnings' => 'warnings',
                'advanced_report' => 'validation_report',
                'execution_time' => 'validation_time_ms'
            ),
            'legacy_defaults' => array(
                'info' => array(),
                'framework_version' => '4.3.1-orchestrated',
                'pipeline_stages' => 0,
                'total_checks' => 0
            ),
            'result_formatting' => array(
                'round_execution_time' => true,
                'include_metadata' => true,
                'add_timestamps' => false
            )
        );
    }

    // ==========================================
    // LICENSE KEY EXTRACTION METHODS
    // ==========================================

    /**
     * Extract license key from various input formats
     *
     * Step 4.3.1 - License key extraction with format normalization
     *
     * @since 4.3.1
     * @param mixed $license License data (string, array, or object)
     * @return string Extracted license key
     */
    public function extract_license_key($license) {
        $this->status['license_keys_extracted']++;

        // Handle string input (direct license key)
        if (is_string($license)) {
            return $this->normalize_license_key($license);
        }

        // Handle array input
        if (is_array($license)) {
            return $this->extract_from_array($license);
        }

        // Handle object input
        if (is_object($license)) {
            return $this->extract_from_object($license);
        }

        // Return empty string for unsupported types
        return '';
    }

    /**
     * Extract license key from array
     *
     * @param array $license License data array
     * @return string Extracted license key
     */
    private function extract_from_array($license) {
        $key_fields = $this->transformation_config['license_key_fields'];

        foreach ($key_fields as $field) {
            if (isset($license[$field]) && !empty($license[$field])) {
                return $this->normalize_license_key($license[$field]);
            }
        }

        // Try common alternative field names
        $alternative_fields = array('license', 'lic_key', 'api_key', 'token');
        foreach ($alternative_fields as $field) {
            if (isset($license[$field]) && !empty($license[$field])) {
                return $this->normalize_license_key($license[$field]);
            }
        }

        return '';
    }

    /**
     * Extract license key from object
     *
     * @param object $license License data object
     * @return string Extracted license key
     */
    private function extract_from_object($license) {
        $key_fields = $this->transformation_config['license_key_fields'];

        foreach ($key_fields as $field) {
            if (property_exists($license, $field) && !empty($license->$field)) {
                return $this->normalize_license_key($license->$field);
            }
        }

        // Try to convert object to array and extract
        $license_array = (array) $license;
        return $this->extract_from_array($license_array);
    }

    /**
     * Normalize license key
     *
     * @param string $license_key Raw license key
     * @return string Normalized license key
     */
    private function normalize_license_key($license_key) {
        if (!is_string($license_key)) {
            return '';
        }

        $config = $this->transformation_config['data_sanitization'];

        // Strip HTML tags if configured
        if ($config['strip_html']) {
            $license_key = strip_tags($license_key);
        }

        // Trim whitespace if configured
        if ($config['trim_whitespace']) {
            $license_key = trim($license_key);
        }

        // Normalize case if configured
        if ($config['normalize_case']) {
            $license_key = strtoupper($license_key);
        }

        return $license_key;
    }

    // ==========================================
    // CONTEXT TRANSFORMATION METHODS
    // ==========================================

    /**
     * Transform validation context to orchestrator options
     *
     * Step 4.3.1 - Context transformation with option mapping
     *
     * @since 4.3.1
     * @param array $context Validation context
     * @param mixed $license License data
     * @return array Orchestrator options
     */
    public function transform_context_to_options($context, $license) {
        $this->status['context_transformations']++;

        $base_options = $this->transformation_config['default_options'];

        // Merge context with base options
        $options = array_merge($base_options, $context);

        // Add license data
        $options['license_data'] = $license;

        // Process priority contexts
        $priority_contexts = $this->transformation_config['context_merge_priority'];
        foreach ($priority_contexts as $priority_context) {
            if (isset($context[$priority_context])) {
                $options = $this->merge_priority_context($options, $context[$priority_context], $priority_context);
            }
        }

        // Add transformation metadata
        $options['_transformation_metadata'] = array(
            'transformed_at' => current_time('c'),
            'source_version' => self::VERSION,
            'context_keys' => array_keys($context),
            'license_type' => gettype($license)
        );

        return $options;
    }

    /**
     * Merge priority context data
     *
     * @param array $options Current options
     * @param array $priority_data Priority context data
     * @param string $context_type Context type
     * @return array Updated options
     */
    private function merge_priority_context($options, $priority_data, $context_type) {
        if (!is_array($priority_data)) {
            return $options;
        }

        // Context-specific merging logic
        switch ($context_type) {
            case 'user_context':
                $options['user_id'] = $priority_data['user_id'] ?? null;
                $options['user_roles'] = $priority_data['user_roles'] ?? array();
                break;

            case 'ip_context':
                $options['ip_address'] = $priority_data['ip_address'] ?? null;
                $options['ip_country'] = $priority_data['ip_country'] ?? null;
                break;

            case 'device_context':
                $options['device_id'] = $priority_data['device_id'] ?? null;
                $options['device_fingerprint'] = $priority_data['device_fingerprint'] ?? null;
                break;

            case 'domain_context':
                $options['domain'] = $priority_data['domain'] ?? null;
                $options['allowed_domains'] = $priority_data['allowed_domains'] ?? array();
                break;

            default:
                // Generic merge for unknown context types
                $options = array_merge($options, $priority_data);
                break;
        }

        return $options;
    }

    // ==========================================
    // RESULT MAPPING METHODS
    // ==========================================

    /**
     * Map orchestrator result to legacy format
     *
     * Step 4.3.1 - Result mapping with legacy format support
     *
     * @since 4.3.1
     * @param array $orchestrator_result Result from orchestrator
     * @return array Legacy format result
     */
    public function map_orchestrator_result_to_legacy_format($orchestrator_result) {
        $this->status['result_mappings']++;

        if (!is_array($orchestrator_result)) {
            return $this->get_default_legacy_result();
        }

        $mapping = $this->format_mapping_config['orchestrator_to_legacy'];
        $defaults = $this->format_mapping_config['legacy_defaults'];
        $formatting = $this->format_mapping_config['result_formatting'];

        $legacy_result = array();

        // Map orchestrator fields to legacy fields
        foreach ($mapping as $orchestrator_field => $legacy_field) {
            $legacy_result[$legacy_field] = $orchestrator_result[$orchestrator_field] ?? null;
        }

        // Apply defaults for missing fields
        foreach ($defaults as $field => $default_value) {
            if (!isset($legacy_result[$field])) {
                $legacy_result[$field] = $default_value;
            }
        }

        // Calculate derived fields
        $legacy_result['pipeline_stages'] = count($orchestrator_result['validation_pipeline'] ?? array());
        $legacy_result['total_checks'] = $this->count_orchestrator_checks($orchestrator_result);

        // Apply formatting rules
        if ($formatting['round_execution_time'] && isset($legacy_result['validation_time_ms'])) {
            $legacy_result['validation_time_ms'] = round((float) $legacy_result['validation_time_ms'], 2);
        }

        if ($formatting['include_metadata']) {
            $legacy_result['_mapping_metadata'] = array(
                'mapped_at' => current_time('c'),
                'mapper_version' => self::VERSION,
                'orchestrator_fields' => array_keys($orchestrator_result),
                'legacy_fields' => array_keys($legacy_result)
            );
        }

        if ($formatting['add_timestamps']) {
            $legacy_result['mapped_timestamp'] = current_time('timestamp');
        }

        return $legacy_result;
    }

    /**
     * Get default legacy result structure
     *
     * @return array Default legacy result
     */
    private function get_default_legacy_result() {
        return array(
            'valid' => false,
            'validation_pipeline' => array(),
            'errors' => array('Invalid orchestrator result'),
            'warnings' => array(),
            'info' => array(),
            'validation_report' => array(),
            'validation_time_ms' => 0,
            'framework_version' => '4.3.1-error',
            'pipeline_stages' => 0,
            'total_checks' => 0
        );
    }

    // ==========================================
    // ORCHESTRATOR CHECK COUNTING METHODS
    // ==========================================

    /**
     * Count orchestrator validation checks
     *
     * Step 4.3.1 - Orchestrator check counting with statistics
     *
     * @since 4.3.1
     * @param array $orchestrator_result Result from orchestrator
     * @return int Total number of checks performed
     */
    public function count_orchestrator_checks($orchestrator_result) {
        $this->status['check_counts_calculated']++;

        if (!is_array($orchestrator_result)) {
            return 0;
        }

        $total = 0;
        $pipeline = $orchestrator_result['validation_pipeline'] ?? array();

        foreach ($pipeline as $stage => $stage_data) {
            $checks_in_stage = $this->count_checks_in_stage($stage_data, $stage);
            $total += $checks_in_stage;
        }

        return $total;
    }

    /**
     * Count checks in a single validation stage
     *
     * @param mixed $stage_data Stage data
     * @param string $stage_name Stage name
     * @return int Number of checks in stage
     */
    private function count_checks_in_stage($stage_data, $stage_name) {
        // Handle array stage data
        if (is_array($stage_data)) {
            // Explicit check count
            if (isset($stage_data['checks_performed'])) {
                return (int) $stage_data['checks_performed'];
            }

            // Count based on sub-stages or operations
            if (isset($stage_data['operations'])) {
                return count($stage_data['operations']);
            }

            // Count based on validation results
            if (isset($stage_data['validations'])) {
                return count($stage_data['validations']);
            }

            // Count based on array size for generic data
            if (!empty($stage_data)) {
                return count($stage_data);
            }
        }

        // Handle object stage data
        if (is_object($stage_data)) {
            $stage_array = (array) $stage_data;
            return $this->count_checks_in_stage($stage_array, $stage_name);
        }

        // Default based on stage name patterns
        return $this->get_default_check_count_by_stage($stage_name);
    }

    /**
     * Get default check count based on stage name
     *
     * @param string $stage_name Stage name
     * @return int Default check count
     */
    private function get_default_check_count_by_stage($stage_name) {
        $stage_defaults = array(
            'basic_validation' => 3,
            'advanced_validation' => 5,
            'business_rules' => 4,
            'security_validation' => 6,
            'temporal_validation' => 2,
            'policy_validation' => 3,
            'final_validation' => 1
        );

        // Check for exact match
        if (isset($stage_defaults[$stage_name])) {
            return $stage_defaults[$stage_name];
        }

        // Check for partial matches
        foreach ($stage_defaults as $pattern => $count) {
            if (strpos($stage_name, $pattern) !== false) {
                return $count;
            }
        }

        // Default to 1 check per unknown stage
        return 1;
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    /**
     * Get validation statistics
     *
     * @return array Validation statistics
     */
    public function get_validation_statistics() {
        return array(
            'license_keys_extracted' => $this->status['license_keys_extracted'],
            'context_transformations' => $this->status['context_transformations'],
            'result_mappings' => $this->status['result_mappings'],
            'check_counts_calculated' => $this->status['check_counts_calculated'],
            'total_operations' => array_sum(array(
                $this->status['license_keys_extracted'],
                $this->status['context_transformations'],
                $this->status['result_mappings'],
                $this->status['check_counts_calculated']
            ))
        );
    }

    /**
     * Reset statistics
     *
     * @return void
     */
    public function reset_statistics() {
        $this->status['license_keys_extracted'] = 0;
        $this->status['context_transformations'] = 0;
        $this->status['result_mappings'] = 0;
        $this->status['check_counts_calculated'] = 0;
    }

    // ==========================================
    // MODULE STATUS & HEALTH METHODS
    // ==========================================

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'transformation_config_loaded' => !empty($this->transformation_config),
            'format_mapping_config_loaded' => !empty($this->format_mapping_config)
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
            $health['errors'][] = 'Validation Infrastructure not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check transformation configuration
        if (empty($this->transformation_config)) {
            $health['warnings'][] = 'Transformation configuration is empty';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Transformation configuration loaded';
        }

        // Check format mapping configuration
        if (empty($this->format_mapping_config)) {
            $health['warnings'][] = 'Format mapping configuration is empty';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Format mapping configuration loaded';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 262144) { // 256KB
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
            'module' => 'Validation Infrastructure',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'statistics' => $this->get_validation_statistics(),
            'configurations' => array(
                'transformation_config_keys' => array_keys($this->transformation_config),
                'format_mapping_config_keys' => array_keys($this->format_mapping_config),
                'license_key_fields' => $this->transformation_config['license_key_fields'] ?? array(),
                'default_options' => $this->transformation_config['default_options'] ?? array()
            ),
            'memory_usage' => $this->status['memory_usage'],
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}