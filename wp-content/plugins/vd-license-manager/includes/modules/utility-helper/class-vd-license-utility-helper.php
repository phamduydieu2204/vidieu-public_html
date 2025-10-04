<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Utility Helper Module
 *
 * Foundation module for Phase 2B.1 - provides utility functions and helpers
 * extracted from the monolithic validator class to reduce file size.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.0
 * @author VD Team
 */
class VD_License_Utility_Helper {

    /**
     * Singleton instance
     *
     * @var VD_License_Utility_Helper|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '2B.1.0';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'loaded_components' => 0,
        'total_components' => 4,
        'memory_usage' => 0
    );

    /**
     * Component registry
     *
     * @var array
     */
    private $components = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Utility_Helper
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

        // Register components
        $this->register_components();

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Utility Helper: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    /**
     * Register module components
     *
     * @return void
     */
    private function register_components() {
        $this->components = array(
            'data_sanitizer' => array(
                'file' => 'components/class-data-sanitizer.php',
                'class' => 'VD\\LicenseManager\\UtilityHelper\\DataSanitizer',
                'loaded' => false
            ),
            'response_builder' => array(
                'file' => 'components/class-response-builder.php',
                'class' => 'VD\\LicenseManager\\UtilityHelper\\ResponseBuilder',
                'loaded' => false
            ),
            'datetime_helper' => array(
                'file' => 'components/class-datetime-helper.php',
                'class' => 'VD\\LicenseManager\\UtilityHelper\\DateTimeHelper',
                'loaded' => false
            ),
            'calculation_helper' => array(
                'file' => 'components/class-calculation-helper.php',
                'class' => 'VD\\LicenseManager\\UtilityHelper\\CalculationHelper',
                'loaded' => false
            )
        );
    }

    /**
     * Load a specific component
     *
     * @param string $component_name Component name
     * @return bool True if loaded successfully, false otherwise
     */
    public function load_component($component_name) {
        if (!isset($this->components[$component_name])) {
            return false;
        }

        if ($this->components[$component_name]['loaded']) {
            return true;
        }

        $component = $this->components[$component_name];
        $file_path = dirname(__FILE__) . '/' . $component['file'];

        if (!file_exists($file_path)) {
            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Utility Helper: Component file not found: {$file_path}");
            }
            return false;
        }

        try {
            // Load interfaces first if component needs them
            if ($component_name === 'data_sanitizer') {
                $interface_file = dirname(__FILE__) . '/interfaces/data-sanitizer-interface.php';
                if (file_exists($interface_file)) {
                    require_once $interface_file;
                }
            } elseif ($component_name === 'response_builder') {
                $interface_file = dirname(__FILE__) . '/interfaces/response-builder-interface.php';
                if (file_exists($interface_file)) {
                    require_once $interface_file;
                }
            } elseif ($component_name === 'datetime_helper') {
                $interface_file = dirname(__FILE__) . '/interfaces/datetime-helper-interface.php';
                if (file_exists($interface_file)) {
                    require_once $interface_file;
                }
            }

            require_once $file_path;
            $this->components[$component_name]['loaded'] = true;
            $this->status['loaded_components']++;

            if (defined('VD_DEBUG') && VD_DEBUG) {
                error_log("VD Utility Helper: Loaded component '{$component_name}'");
            }

            return true;
        } catch (Exception $e) {
            error_log("VD Utility Helper: Failed to load component '{$component_name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'components' => $this->components,
            'namespace' => __NAMESPACE__
        ));
    }

    /**
     * Check if module is ready
     *
     * @return bool True if ready, false otherwise
     */
    public function is_ready() {
        return $this->status['initialized'];
    }

    /**
     * Get component status
     *
     * @param string $component_name Component name
     * @return bool True if loaded, false otherwise
     */
    public function is_component_loaded($component_name) {
        return isset($this->components[$component_name]) && $this->components[$component_name]['loaded'];
    }

    /**
     * Load all components
     *
     * @return bool True if all loaded successfully, false otherwise
     */
    public function load_all_components() {
        $success = true;
        foreach (array_keys($this->components) as $component_name) {
            if (!$this->load_component($component_name)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Get DataSanitizer component
     *
     * @return object|false DataSanitizer class or false if not loaded
     */
    public function get_data_sanitizer() {
        if (!$this->is_component_loaded('data_sanitizer')) {
            $this->load_component('data_sanitizer');
        }

        if ($this->is_component_loaded('data_sanitizer')) {
            $component = $this->components['data_sanitizer'];
            if (class_exists($component['class'])) {
                return $component['class'];
            }
        }

        return false;
    }

    /**
     * Get ResponseBuilder component
     *
     * @return object|false ResponseBuilder class or false if not loaded
     */
    public function get_response_builder() {
        if (!$this->is_component_loaded('response_builder')) {
            $this->load_component('response_builder');
        }

        if ($this->is_component_loaded('response_builder')) {
            $component = $this->components['response_builder'];
            if (class_exists($component['class'])) {
                return $component['class'];
            }
        }

        return false;
    }

    /**
     * Get DateTimeHelper component
     *
     * @return object|false DateTimeHelper class or false if not loaded
     */
    public function get_datetime_helper() {
        if (!$this->is_component_loaded('datetime_helper')) {
            $this->load_component('datetime_helper');
        }

        if ($this->is_component_loaded('datetime_helper')) {
            $component = $this->components['datetime_helper'];
            if (class_exists($component['class'])) {
                return $component['class'];
            }
        }

        return false;
    }

    /**
     * Get module information for debugging
     *
     * @return array Module information
     */
    public function get_debug_info() {
        return array(
            'module' => 'Utility Helper',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'components' => $this->components,
            'memory_usage' => $this->status['memory_usage'],
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
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
            $health['errors'][] = 'Module not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 1048576) { // 1MB
            $health['warnings'][] = 'High memory usage: ' . number_format($this->status['memory_usage'] / 1024, 2) . ' KB';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Memory usage within limits';
        }

        // Check component loading
        $loaded_ratio = $this->status['loaded_components'] / $this->status['total_components'];
        if ($loaded_ratio < 0.5) {
            $health['warnings'][] = 'Less than 50% of components loaded';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Component loading ratio acceptable';
        }

        return $health;
    }
}