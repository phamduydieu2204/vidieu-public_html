<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Validator Migration Helper
 *
 * Manages the migration from monolithic VD_License_Validator to modular Facade pattern
 * Step 5.1.11 - Validator Migration Management
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @since 2025-01-03
 */
class VD_Validator_Migration {

    /**
     * Migration configuration
     *
     * @var array
     */
    private static $config = [
        'enable_facade' => true,        // Enable facade pattern
        'enable_legacy_fallback' => true, // Keep legacy validator as fallback
        'migration_mode' => 'GRADUAL',  // GRADUAL or IMMEDIATE
        'debug_mode' => true            // Enable migration debugging
    ];

    /**
     * Apply validator migration to main manager
     *
     * @param VD_License_Manager $manager Main manager instance
     * @return bool Migration success status
     */
    public static function apply_migration($manager) {
        try {
            // Load facade if enabled
            if (self::$config['enable_facade']) {
                self::load_facade();

                // Replace validator in manager
                return self::replace_validator_in_manager($manager);
            }

            return true;

        } catch (Exception $e) {
            if (self::$config['debug_mode']) {
                error_log("VD Validator Migration Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Load facade pattern validator
     */
    private static function load_facade() {
        $facade_file = plugin_dir_path(__FILE__) . 'class-vd-license-validator-facade.php';

        if (file_exists($facade_file)) {
            require_once $facade_file;
        } else {
            throw new RuntimeException("Facade file not found: {$facade_file}");
        }
    }

    /**
     * Replace validator in manager with facade
     *
     * @param VD_License_Manager $manager
     * @return bool
     */
    private static function replace_validator_in_manager($manager) {
        if (class_exists('VD_License_Validator_Facade')) {

            // Get facade instance
            $facade = VD_License_Validator_Facade::get_instance();

            // Test facade functionality before replacement
            if (self::$config['migration_mode'] === 'GRADUAL') {
                $test_result = $facade->test_facade_functionality();
                if (self::$config['debug_mode']) {
                    error_log("VD Facade Test Result: " . json_encode($test_result));
                }
            }

            // Replace validator instance via reflection (safe way)
            return self::replace_validator_instance($manager, $facade);

        } else {
            throw new RuntimeException("VD_License_Validator_Facade class not available");
        }
    }

    /**
     * Safely replace validator instance using reflection
     *
     * @param VD_License_Manager $manager
     * @param VD_License_Validator_Facade $facade
     * @return bool
     */
    private static function replace_validator_instance($manager, $facade) {
        try {
            $reflection = new ReflectionClass($manager);

            // Find license_validator property
            if ($reflection->hasProperty('license_validator')) {
                $property = $reflection->getProperty('license_validator');
                $property->setAccessible(true);

                // Store old validator for fallback if needed
                $old_validator = $property->getValue($manager);

                // Set new facade
                $property->setValue($manager, $facade);

                // Initialize facade
                $facade->init();

                if (self::$config['debug_mode']) {
                    error_log("VD Validator Migration: Successfully replaced with facade pattern");
                }

                return true;
            }

        } catch (ReflectionException $e) {
            if (self::$config['debug_mode']) {
                error_log("VD Validator Migration Reflection Error: " . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Get migration status
     *
     * @return array
     */
    public static function get_migration_status() {
        $status = [
            'config' => self::$config,
            'facade_available' => class_exists('VD_License_Validator_Facade'),
            'legacy_available' => class_exists('VD_License_Validator'),
            'migration_applied' => false,
            'facade_test_result' => null
        ];

        // Test facade if available
        if ($status['facade_available']) {
            try {
                $facade = VD_License_Validator_Facade::get_instance();
                $status['facade_test_result'] = $facade->test_facade_functionality();
                $status['migration_applied'] = true;
            } catch (Exception $e) {
                $status['facade_error'] = $e->getMessage();
            }
        }

        return $status;
    }

    /**
     * Create migration backup
     *
     * @return bool
     */
    public static function create_migration_backup() {
        $backup_dir = plugin_dir_path(__FILE__) . '../backups/validator-migration/';

        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        $timestamp = date('Y-m-d-H-i-s');
        $files_to_backup = [
            'class-vd-license-manager.php',
            'class-vd-license-validator.php'
        ];

        foreach ($files_to_backup as $file) {
            $source = plugin_dir_path(__FILE__) . $file;
            $destination = $backup_dir . $timestamp . '-' . $file;

            if (file_exists($source)) {
                copy($source, $destination);
            }
        }

        return true;
    }

    /**
     * Rollback migration
     *
     * @param VD_License_Manager $manager
     * @return bool
     */
    public static function rollback_migration($manager) {
        try {
            if (class_exists('VD_License_Validator')) {
                $legacy_validator = VD_License_Validator::get_instance();

                $reflection = new ReflectionClass($manager);
                if ($reflection->hasProperty('license_validator')) {
                    $property = $reflection->getProperty('license_validator');
                    $property->setAccessible(true);
                    $property->setValue($manager, $legacy_validator);

                    $legacy_validator->init();

                    if (self::$config['debug_mode']) {
                        error_log("VD Validator Migration: Successfully rolled back to legacy validator");
                    }

                    return true;
                }
            }
        } catch (Exception $e) {
            if (self::$config['debug_mode']) {
                error_log("VD Validator Migration Rollback Error: " . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Update migration configuration
     *
     * @param array $new_config
     */
    public static function update_config($new_config) {
        self::$config = array_merge(self::$config, $new_config);
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public static function get_config() {
        return self::$config;
    }
}