<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utility Helper Interface
 *
 * Defines the contract for utility helper modules in the VD License Manager system.
 * This interface ensures consistent implementation across all utility components.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.0
 */
interface UtilityHelperInterface {

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status();

    /**
     * Check if module is ready
     *
     * @return bool True if ready, false otherwise
     */
    public function is_ready();

    /**
     * Load a specific component
     *
     * @param string $component_name Component name
     * @return bool True if loaded successfully, false otherwise
     */
    public function load_component($component_name);

    /**
     * Check if component is loaded
     *
     * @param string $component_name Component name
     * @return bool True if loaded, false otherwise
     */
    public function is_component_loaded($component_name);

    /**
     * Module health check
     *
     * @return array Health check results
     */
    public function health_check();
}