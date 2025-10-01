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