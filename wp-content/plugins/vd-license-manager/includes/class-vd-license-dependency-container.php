<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Manager Dependency Injection Container
 *
 * Simple dependency injection container for managing module dependencies
 * Handles service registration, resolution, and lifecycle management
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */
class VD_License_Dependency_Container {

    /**
     * Singleton instance
     *
     * @var VD_License_Dependency_Container|null
     */
    private static $instance = null;

    /**
     * Service definitions
     *
     * @var array
     */
    private $services = array();

    /**
     * Service instances (singletons)
     *
     * @var array
     */
    private $instances = array();

    /**
     * Service factories
     *
     * @var array
     */
    private $factories = array();

    /**
     * Dependency resolution stack (for circular dependency detection)
     *
     * @var array
     */
    private $resolution_stack = array();

    /**
     * Container statistics
     *
     * @var array
     */
    private $stats = array(
        'services_registered' => 0,
        'instances_created' => 0,
        'resolution_time' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_core_services();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Dependency_Container
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize core service definitions
     *
     * @return void
     */
    private function init_core_services() {
        // Register module loader
        $this->register('module_loader', function() {
            return VD_License_Module_Loader::get_instance();
        });

        // Register format modules
        $this->register('format.pattern_validator', function($container) {
            return $container->get('module_loader')->load_module('format.pattern_validator');
        });

        $this->register('format.checksum_validator', function($container) {
            return $container->get('module_loader')->load_module('format.checksum_validator');
        });

        // Register database modules
        $this->register('database.query_manager', function($container) {
            return $container->get('module_loader')->load_module('database.query_manager');
        });

        $this->register('database.lmfwc_adapter', function($container) {
            $adapter = $container->get('module_loader')->load_module('database.lmfwc_adapter');
            $query_manager = $container->get('database.query_manager');
            $adapter->set_query_manager($query_manager);
            return $adapter;
        });

        $this->register('database.cache_manager', function($container) {
            return $container->get('module_loader')->load_module('database.cache_manager');
        });

        // Register status modules
        $this->register('status.enum', function($container) {
            return $container->get('module_loader')->load_module('status.enum');
        });

        $this->register('status.transition', function($container) {
            $status_transition = $container->get('module_loader')->load_module('status.transition');
            $status_enum = $container->get('status.enum');
            if ($status_transition && $status_enum) {
                $status_transition->set_status_enum($status_enum);
            }
            return $status_transition;
        });

        $this->register('status.business', function($container) {
            $status_business = $container->get('module_loader')->load_module('status.business');
            $status_enum = $container->get('status.enum');
            $status_transition = $container->get('status.transition');
            if ($status_business) {
                if ($status_enum) {
                    $status_business->set_status_enum($status_enum);
                }
                if ($status_transition) {
                    $status_business->set_status_transition($status_transition);
                }
            }
            return $status_business;
        });

        // Register rule modules
        $this->register('rules.activation', function($container) {
            $activation_rules = $container->get('module_loader')->load_module('rules.activation');
            $status_business = $container->get('status.business');
            if ($activation_rules && $status_business) {
                $activation_rules->set_status_business($status_business);
            }
            return $activation_rules;
        });

        // Step 2.2.1: Register Expiry Core module
        $this->register('rules.expiry_core', function($container) {
            $expiry_core = $container->get('module_loader')->load_module('rules.expiry_core');
            $status_business = $container->get('status.business');
            if ($expiry_core && $status_business) {
                $expiry_core->set_status_business($status_business);
            }
            return $expiry_core;
        });

        // Step 2.2.2: Register Expiry Automation module
        $this->register('rules.expiry_automation', function($container) {
            $expiry_automation = $container->get('module_loader')->load_module('rules.expiry_automation');
            $expiry_core = $container->get('rules.expiry_core');
            $status_business = $container->get('status.business');
            if ($expiry_automation) {
                if ($expiry_core) {
                    $expiry_automation->set_expiry_core($expiry_core);
                }
                if ($status_business) {
                    $expiry_automation->set_status_business($status_business);
                }
            }
            return $expiry_automation;
        });

        // Step 2.2.3: Register Expiry Escalation module
        $this->register('rules.expiry_escalation', function($container) {
            $expiry_escalation = $container->get('module_loader')->load_module('rules.expiry_escalation');
            $expiry_automation = $container->get('rules.expiry_automation');
            $expiry_core = $container->get('rules.expiry_core');
            if ($expiry_escalation) {
                if ($expiry_automation) {
                    $expiry_escalation->set_expiry_automation($expiry_automation);
                }
                if ($expiry_core) {
                    $expiry_escalation->set_expiry_core($expiry_core);
                }
            }
            return $expiry_escalation;
        });

        // Step 2.2.4: Register Constraint Validation module
        $this->register('rules.constraint_validation', function($container) {
            $constraint_validation = $container->get('module_loader')->load_module('rules.constraint_validation');
            $status_business = $container->get('status.business');
            if ($constraint_validation && $status_business) {
                $constraint_validation->set_status_business($status_business);
            }
            return $constraint_validation;
        });

        // Step 2.2.5: Register Usage Rules module
        $this->register('rules.usage', function($container) {
            $usage_rules = $container->get('module_loader')->load_module('rules.usage');
            $activation_rules = $container->get('rules.activation');
            if ($usage_rules && $activation_rules) {
                $usage_rules->set_activation_rules($activation_rules);
            }
            return $usage_rules;
        });

        // Step 3.1: Register Security Validator module
        $this->register('security.validator', function($container) {
            return $container->get('module_loader')->load_module('security.validator');
        });

        // Step 3.2.1: Register Security Event Core Logger module
        $this->register('security.event_logger', function($container) {
            $event_logger = $container->get('module_loader')->load_module('security.event_logger');
            $security_validator = $container->get('security.validator');
            if ($event_logger && $security_validator) {
                $event_logger->set_security_validator($security_validator);
            }
            return $event_logger;
        });

        // Step 3.2.2: Register Security Threat Detector module
        $this->register('security.threat_detector', function($container) {
            $threat_detector = $container->get('module_loader')->load_module('security.threat_detector');
            $security_validator = $container->get('security.validator');
            $event_logger = $container->get('security.event_logger');

            if ($threat_detector) {
                if ($security_validator) {
                    $threat_detector->set_security_validator($security_validator);
                }
                if ($event_logger) {
                    $threat_detector->set_event_logger($event_logger);
                }
            }
            return $threat_detector;
        });

        // Step 3.2.3: Register Security Privacy Manager module
        $this->register('security.privacy_manager', function($container) {
            $privacy_manager = $container->get('module_loader')->load_module('security.privacy_manager');
            $event_logger = $container->get('security.event_logger');

            if ($privacy_manager && $event_logger) {
                $privacy_manager->set_event_logger($event_logger);
            }
            return $privacy_manager;
        });

        // Step 3.2.4: Register Security Storage Manager module
        $this->register('security.storage_manager', function($container) {
            $storage_manager = $container->get('module_loader')->load_module('security.storage_manager');
            $event_logger = $container->get('security.event_logger');
            $privacy_manager = $container->get('security.privacy_manager');

            if ($storage_manager) {
                if ($event_logger) {
                    $storage_manager->set_event_logger($event_logger);
                }
                if ($privacy_manager) {
                    $storage_manager->set_privacy_manager($privacy_manager);
                }
            }
            return $storage_manager;
        });
    }

    /**
     * Register a service
     *
     * @param string $id Service identifier
     * @param callable $factory Service factory function
     * @param bool $singleton Whether to create as singleton
     * @return void
     */
    public function register($id, $factory, $singleton = true) {
        if (!is_callable($factory)) {
            throw new InvalidArgumentException("Service factory for '{$id}' must be callable");
        }

        $this->services[$id] = array(
            'factory' => $factory,
            'singleton' => $singleton,
            'created' => false
        );

        if ($singleton) {
            $this->factories[$id] = $factory;
        }

        $this->stats['services_registered']++;
    }

    /**
     * Get a service instance
     *
     * @param string $id Service identifier
     * @return mixed Service instance
     * @throws Exception If service not found or circular dependency detected
     */
    public function get($id) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        try {
            // Check for circular dependencies
            if (in_array($id, $this->resolution_stack)) {
                throw new Exception("Circular dependency detected: " . implode(' -> ', $this->resolution_stack) . " -> {$id}");
            }

            // Return existing singleton instance
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }

            // Check if service is registered
            if (!isset($this->services[$id])) {
                throw new Exception("Service '{$id}' is not registered");
            }

            $service_config = $this->services[$id];

            // Add to resolution stack
            $this->resolution_stack[] = $id;

            try {
                // Create instance
                $factory = $service_config['factory'];
                $instance = $factory($this);

                // Store singleton instance
                if ($service_config['singleton']) {
                    $this->instances[$id] = $instance;
                    $this->services[$id]['created'] = true;
                }

                $this->stats['instances_created']++;

                // Update statistics
                $this->stats['resolution_time'] += (microtime(true) - $start_time) * 1000;
                $this->stats['memory_usage'] += memory_get_usage() - $start_memory;

                // Debug logging
                if (defined('VD_DEBUG') && VD_DEBUG) {
                    error_log("VD Container: Resolved service '{$id}'");
                }

                return $instance;

            } finally {
                // Remove from resolution stack
                array_pop($this->resolution_stack);
            }

        } catch (Exception $e) {
            error_log("VD Container: Failed to resolve service '{$id}': " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if service is registered
     *
     * @param string $id Service identifier
     * @return bool True if registered, false otherwise
     */
    public function has($id) {
        return isset($this->services[$id]);
    }

    /**
     * Check if service instance exists
     *
     * @param string $id Service identifier
     * @return bool True if instance exists, false otherwise
     */
    public function exists($id) {
        return isset($this->instances[$id]);
    }

    /**
     * Remove a service
     *
     * @param string $id Service identifier
     * @return void
     */
    public function remove($id) {
        unset($this->services[$id], $this->instances[$id], $this->factories[$id]);
    }

    /**
     * Get all registered service IDs
     *
     * @return array Array of service IDs
     */
    public function get_service_ids() {
        return array_keys($this->services);
    }

    /**
     * Get all created instances
     *
     * @return array Array of service instances
     */
    public function get_instances() {
        return $this->instances;
    }

    /**
     * Get container statistics
     *
     * @return array Container statistics
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get service configuration
     *
     * @param string $id Service identifier
     * @return array|null Service configuration or null if not found
     */
    public function get_service_config($id) {
        return isset($this->services[$id]) ? $this->services[$id] : null;
    }

    /**
     * Resolve dependencies for a service
     *
     * @param string $id Service identifier
     * @return array Array of dependency instances
     */
    public function resolve_dependencies($id) {
        if (!isset($this->services[$id])) {
            return array();
        }

        $dependencies = array();
        $module_loader = $this->get('module_loader');
        $registry = $module_loader->get_registry();

        if (isset($registry[$id])) {
            foreach ($registry[$id]['dependencies'] as $dependency_id) {
                $dependencies[$dependency_id] = $this->get($dependency_id);
            }
        }

        return $dependencies;
    }

    /**
     * Initialize container with default services
     *
     * @return bool True on success, false on failure
     */
    public function initialize() {
        try {
            // Preload core services
            $core_services = array(
                'module_loader',
                'format.pattern_validator',
                'format.checksum_validator',
                'database.query_manager',
                'database.lmfwc_adapter',
                'database.cache_manager',
                'status.enum',
                'status.transition',
                'status.business',
                'rules.activation',
                'rules.expiry_core',
                'rules.expiry_automation',
                'rules.expiry_escalation',
                'rules.constraint_validation',
                'rules.usage'
            );

            foreach ($core_services as $service_id) {
                $this->get($service_id);
            }

            return true;

        } catch (Exception $e) {
            error_log("VD Container: Initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get container status
     *
     * @return array Container status information
     */
    public function get_status() {
        return array(
            'initialized' => !empty($this->instances),
            'services_count' => count($this->instances),
            'factories_count' => count($this->factories),
            'registered_services' => array_keys($this->services),
            'created_instances' => array_keys($this->instances),
            'memory_usage' => $this->stats['memory_usage'],
            'resolution_time' => $this->stats['resolution_time']
        );
    }

    /**
     * Clear all instances (for testing)
     *
     * @return void
     */
    public function clear_instances() {
        $this->instances = array();
        $this->resolution_stack = array();
        $this->stats['instances_created'] = 0;
    }

    /**
     * Validate container configuration
     *
     * @return array Validation results
     */
    public function validate() {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );

        foreach ($this->services as $id => $config) {
            // Check if factory is callable
            if (!is_callable($config['factory'])) {
                $results['valid'] = false;
                $results['errors'][] = "Service '{$id}' has invalid factory";
            }

            // Check for potential circular dependencies
            try {
                $this->resolve_dependencies($id);
            } catch (Exception $e) {
                $results['valid'] = false;
                $results['errors'][] = "Service '{$id}' has dependency issues: " . $e->getMessage();
            }
        }

        return $results;
    }
}