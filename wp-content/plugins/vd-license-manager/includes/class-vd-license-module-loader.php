<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Module Loader
 *
 * PSR-4 compatible autoloader for VD License Manager modules
 * Handles dynamic loading of micro-modules to reduce memory usage
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */
class VD_License_Module_Loader {

    /**
     * Singleton instance
     *
     * @var VD_License_Module_Loader|null
     */
    private static $instance = null;

    /**
     * Module registry
     *
     * @var array
     */
    private $modules = array();

    /**
     * Loaded modules cache
     *
     * @var array
     */
    private $loaded_modules = array();

    /**
     * Module base path
     *
     * @var string
     */
    private $base_path = '';

    /**
     * Loading statistics
     *
     * @var array
     */
    private $stats = array(
        'total_modules' => 0,
        'loaded_modules' => 0,
        'load_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->base_path = plugin_dir_path(__FILE__) . 'modules/';
        $this->init_module_registry();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Module_Loader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module registry
     *
     * @return void
     */
    private function init_module_registry() {
        $this->modules = array(
            // Format modules
            'format.pattern_validator' => array(
                'file' => 'format/class-vd-license-pattern-validator.php',
                'class' => 'VD_License_Pattern_Validator',
                'namespace' => 'VD\\LicenseManager\\Format',
                'dependencies' => array(),
                'priority' => 1
            ),
            'format.checksum_validator' => array(
                'file' => 'format/class-vd-license-checksum-validator.php',
                'class' => 'VD_License_Checksum_Validator',
                'namespace' => 'VD\\LicenseManager\\Format',
                'dependencies' => array('format.pattern_validator'),
                'priority' => 2
            ),

            // Database modules
            'database.query_manager' => array(
                'file' => 'database/class-vd-license-query-manager.php',
                'class' => 'VD_License_Query_Manager',
                'namespace' => 'VD\\LicenseManager\\Database',
                'dependencies' => array(),
                'priority' => 3
            ),
            'database.lmfwc_adapter' => array(
                'file' => 'database/class-vd-license-lmfwc-adapter.php',
                'class' => 'VD_License_LMfWC_Adapter',
                'namespace' => 'VD\\LicenseManager\\Database',
                'dependencies' => array('database.query_manager'),
                'priority' => 4
            ),
            'database.cache_manager' => array(
                'file' => 'database/class-vd-license-cache-manager.php',
                'class' => 'VD_License_Cache_Manager',
                'namespace' => 'VD\\LicenseManager\\Database',
                'dependencies' => array(),
                'priority' => 5
            ),

            // Status modules
            'status.enum' => array(
                'file' => 'status/class-vd-license-status-enum.php',
                'class' => 'VD_License_Status_Enum',
                'namespace' => 'VD\\LicenseManager\\Status',
                'dependencies' => array(),
                'priority' => 6
            ),
            'status.transition' => array(
                'file' => 'status/class-vd-license-status-transition.php',
                'class' => 'VD_License_Status_Transition',
                'namespace' => 'VD\\LicenseManager\\Status',
                'dependencies' => array('status.enum'),
                'priority' => 7
            ),
            'status.business' => array(
                'file' => 'status/class-vd-license-status-business.php',
                'class' => 'VD_License_Status_Business',
                'namespace' => 'VD\\LicenseManager\\Status',
                'dependencies' => array('status.transition', 'status.enum'),
                'priority' => 8
            ),

            // Rule modules
            'rules.activation' => array(
                'file' => 'rules/class-vd-license-rule-activation.php',
                'class' => 'VD_License_Rule_Activation',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('status.business'),
                'priority' => 9
            ),
            // Step 2.2.1: Expiry Core Module
            'rules.expiry_core' => array(
                'file' => 'rules/class-vd-license-rule-expiry-core.php',
                'class' => 'VD_License_Rule_Expiry_Core',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('status.business'),
                'priority' => 10
            ),
            // Step 2.2.2: Expiry Automation Module
            'rules.expiry_automation' => array(
                'file' => 'rules/class-vd-license-rule-expiry-automation.php',
                'class' => 'VD_License_Rule_Expiry_Automation',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('rules.expiry_core', 'status.business'),
                'priority' => 11
            ),
            // Step 2.2.3: Expiry Escalation Module
            'rules.expiry_escalation' => array(
                'file' => 'rules/class-vd-license-rule-expiry-escalation.php',
                'class' => 'VD_License_Rule_Expiry_Escalation',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('rules.expiry_automation', 'rules.expiry_core'),
                'priority' => 12
            ),
            // Step 2.2.4: Constraint Validation Module
            'rules.constraint_validation' => array(
                'file' => 'rules/class-vd-license-rule-constraint-validation.php',
                'class' => 'VD_License_Rule_Constraint_Validation',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('status.business'),
                'priority' => 13
            ),
            // Step 2.2.5: Usage Rules Module
            'rules.usage' => array(
                'file' => 'rules/class-vd-license-rule-usage.php',
                'class' => 'VD_License_Rule_Usage',
                'namespace' => 'VD\\LicenseManager\\Rules',
                'dependencies' => array('rules.activation'),
                'priority' => 14
            ),

            // Step 3.1: Security Validator Module
            'security.validator' => array(
                'file' => 'security/class-vd-license-security-validator.php',
                'class' => 'VD\\LicenseManager\\Security\\VD_License_Security_Validator',
                'namespace' => 'VD\\LicenseManager\\Security',
                'dependencies' => array(),
                'priority' => 15
            ),

            // Step 3.2.1: Security Event Core Logger Module
            'security.event_logger' => array(
                'file' => 'security/class-vd-license-security-event-logger.php',
                'class' => 'VD\\LicenseManager\\Security\\Event\\VD_License_Security_Event_Logger',
                'namespace' => 'VD\\LicenseManager\\Security\\Event',
                'dependencies' => array('security.validator'),
                'priority' => 16
            ),

            // Step 3.2.2: Security Threat Detector Module
            'security.threat_detector' => array(
                'file' => 'security/class-vd-license-security-threat-detector.php',
                'class' => 'VD\\LicenseManager\\Security\\Detection\\VD_License_Security_Threat_Detector',
                'namespace' => 'VD\\LicenseManager\\Security\\Detection',
                'dependencies' => array('security.validator', 'security.event_logger'),
                'priority' => 17
            ),
            // Step 3.2.3: Security Privacy Manager Module
            'security.privacy_manager' => array(
                'file' => 'security/class-vd-license-security-privacy-manager.php',
                'class' => 'VD\\LicenseManager\\Security\\Privacy\\VD_License_Security_Privacy_Manager',
                'namespace' => 'VD\\LicenseManager\\Security\\Privacy',
                'dependencies' => array('security.event_logger'),
                'priority' => 18
            ),
            // Step 3.2.4: Security Storage Manager Module
            'security.storage_manager' => array(
                'file' => 'security/class-vd-license-security-storage-manager.php',
                'class' => 'VD\\LicenseManager\\Security\\Storage\\VD_License_Security_Storage_Manager',
                'namespace' => 'VD\\LicenseManager\\Security\\Storage',
                'dependencies' => array('security.event_logger', 'security.privacy_manager'),
                'priority' => 19
            ),
            // Step 3.2.5: Security Report Generator Module
            'security.report_generator' => array(
                'file' => 'security/class-vd-license-security-report-generator.php',
                'class' => 'VD\\LicenseManager\\Security\\Reports\\VD_License_Security_Report_Generator',
                'namespace' => 'VD\\LicenseManager\\Security\\Reports',
                'dependencies' => array('security.event_logger', 'security.storage_manager', 'security.privacy_manager'),
                'priority' => 20
            ),
            // Step 3.2.6: Security Integration Hub Module
            'security.integration_hub' => array(
                'file' => 'security/class-vd-license-security-integration-hub.php',
                'class' => 'VD\\LicenseManager\\Security\\Integration\\VD_License_Security_Integration_Hub',
                'namespace' => 'VD\\LicenseManager\\Security\\Integration',
                'dependencies' => array('security.event_logger', 'security.storage_manager', 'security.privacy_manager', 'security.threat_detector', 'security.report_generator'),
                'priority' => 21
            ),

            // Step 4.1: REST API Framework Module
            'api.framework' => array(
                'file' => 'api/class-vd-license-api-framework.php',
                'class' => 'VD\\LicenseManager\\API\\VD_License_API_Framework',
                'namespace' => 'VD\\LicenseManager\\API',
                'dependencies' => array('security.validator'),
                'priority' => 22
            ),

            // Step 4.2: Webhook System Module
            'api.webhook_system' => array(
                'file' => 'api/class-vd-license-webhook-system.php',
                'class' => 'VD\\LicenseManager\\API\\VD_License_Webhook_System',
                'namespace' => 'VD\\LicenseManager\\API',
                'dependencies' => array('api.framework'),
                'priority' => 23
            ),

            // Step 4.3: Third-party Integrations Module
            'integration.manager' => array(
                'file' => 'integration/class-vd-license-integration-manager.php',
                'class' => 'VD\\LicenseManager\\Integration\\VD_License_Integration_Manager',
                'namespace' => 'VD\\LicenseManager\\Integration',
                'dependencies' => array('api.framework', 'security.validator'),
                'priority' => 24
            ),

            // Phase 2B.1: Utility Helper Module (Foundation)
            'utility.helper' => array(
                'file' => 'utility-helper/class-vd-license-utility-helper.php',
                'class' => 'VD\\LicenseManager\\UtilityHelper\\VD_License_Utility_Helper',
                'namespace' => 'VD\\LicenseManager\\UtilityHelper',
                'dependencies' => array(),
                'priority' => 1
            ),

            // Phase 3.2: Security Audit & Context Enhancement
            'security.audit_logger' => array(
                'file' => 'security-audit/class-vd-license-security-audit-logger.php',
                'class' => 'VD\\LicenseManager\\SecurityAudit\\VD_License_Security_Audit_Logger',
                'namespace' => 'VD\\LicenseManager\\SecurityAudit',
                'dependencies' => array(),
                'priority' => 2
            ),
            'security.context_enhancer' => array(
                'file' => 'security-audit/class-vd-license-context-enhancer.php',
                'class' => 'VD\\LicenseManager\\SecurityAudit\\VD_License_Context_Enhancer',
                'namespace' => 'VD\\LicenseManager\\SecurityAudit',
                'dependencies' => array('security.audit_logger'),
                'priority' => 3
            )
        );

        $this->stats['total_modules'] = count($this->modules);
    }

    /**
     * Load a specific module
     *
     * @param string $module_id Module identifier
     * @return object|false Module instance or false on failure
     */
    public function load_module($module_id) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        if (isset($this->loaded_modules[$module_id])) {
            return $this->loaded_modules[$module_id];
        }

        if (!isset($this->modules[$module_id])) {
            return false;
        }

        $module_config = $this->modules[$module_id];

        // Load dependencies first
        foreach ($module_config['dependencies'] as $dependency) {
            $this->load_module($dependency);
        }

        // Construct file path
        $file_path = $this->base_path . $module_config['file'];

        if (!file_exists($file_path)) {
            return false;
        }

        try {
            require_once $file_path;

            $class_name = $module_config['class'];
            if (!class_exists($class_name)) {
                return false;
            }

            // Instantiate module
            if (method_exists($class_name, 'get_instance')) {
                $instance = $class_name::get_instance();
            } else {
                $instance = new $class_name();
            }

            $this->loaded_modules[$module_id] = $instance;
            $this->stats['loaded_modules']++;

            // Update statistics
            $this->stats['load_time'] += (microtime(true) - $start_time) * 1000;
            $this->stats['memory_usage'] += memory_get_usage() - $start_memory;

            // Debug logging
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Module Loader: Loaded module '{$module_id}' ({$class_name})");
            }

            return $instance;

        } catch (Exception $e) {
            error_log("VD Module Loader: Failed to load module '{$module_id}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load multiple modules
     *
     * @param array $module_ids Array of module identifiers
     * @return array Array of loaded modules
     */
    public function load_modules($module_ids) {
        $loaded = array();
        foreach ($module_ids as $module_id) {
            $module = $this->load_module($module_id);
            if ($module) {
                $loaded[$module_id] = $module;
            }
        }
        return $loaded;
    }

    /**
     * Get loaded module
     *
     * @param string $module_id Module identifier
     * @return object|false Module instance or false if not loaded
     */
    public function get_module($module_id) {
        return isset($this->loaded_modules[$module_id]) ? $this->loaded_modules[$module_id] : false;
    }

    /**
     * Check if module is loaded
     *
     * @param string $module_id Module identifier
     * @return bool True if loaded, false otherwise
     */
    public function is_loaded($module_id) {
        return isset($this->loaded_modules[$module_id]);
    }

    /**
     * Get module registry
     *
     * @return array Module registry
     */
    public function get_registry() {
        return $this->modules;
    }

    /**
     * Get loading statistics
     *
     * @return array Loading statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get module dependency tree
     *
     * @param string $module_id Module identifier
     * @return array Dependency tree
     */
    public function get_dependency_tree($module_id) {
        if (!isset($this->modules[$module_id])) {
            return array();
        }

        $tree = array();
        $module_config = $this->modules[$module_id];

        foreach ($module_config['dependencies'] as $dependency) {
            $tree[$dependency] = $this->get_dependency_tree($dependency);
        }

        return $tree;
    }

    /**
     * Initialize all core modules
     *
     * @return bool True on success, false on failure
     */
    public function init_core_modules() {
        $core_modules = array(
            'format.pattern_validator',
            'format.checksum_validator',
            'database.query_manager'
        );

        $loaded = $this->load_modules($core_modules);
        return count($loaded) === count($core_modules);
    }

    /**
     * Get module loading order by priority
     *
     * @return array Modules sorted by priority
     */
    public function get_loading_order() {
        $modules = $this->modules;
        uasort($modules, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return array_keys($modules);
    }

    /**
     * Preload modules based on usage patterns
     *
     * @param array $usage_patterns Usage patterns array
     * @return void
     */
    public function preload_modules($usage_patterns = array()) {
        $default_patterns = array(
            'format.pattern_validator',
            'format.checksum_validator'
        );

        $modules_to_load = !empty($usage_patterns) ? $usage_patterns : $default_patterns;
        $this->load_modules($modules_to_load);
    }
}