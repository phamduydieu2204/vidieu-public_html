<?php
/**
 * PSR-4 style autoloader for VD License Manager
 *
 * @package VD_License_Manager
 * @subpackage Core
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Loader class - PSR-4 autoloader
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Loader {

    /**
     * Class paths mapping
     *
     * @since 1.0.0
     * @var array
     */
    private $class_paths = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->register_class_paths();
        $this->register_autoloader();
    }

    /**
     * Register class paths for autoloading
     *
     * @since 1.0.0
     */
    private function register_class_paths() {
        $base_path = VD_PLUGIN_PATH;

        $this->class_paths = array(
            // Core classes
            'VD_Activator'              => $base_path . 'includes/class-vd-activator.php',
            'VD_Deactivator'            => $base_path . 'includes/class-vd-deactivator.php',

            // Database classes
            'VD_DB_Core'                => $base_path . 'includes/database/class-vd-db-core.php',
            'VD_DB_Devices'             => $base_path . 'includes/database/class-vd-db-devices.php',
            'VD_DB_Logs'                => $base_path . 'includes/database/class-vd-db-logs.php',

            // Repository classes
            'VD_Accounts_Repository'    => $base_path . 'includes/repositories/class-vd-accounts-repository.php',

            // Validator classes
            'VD_Account_Validator'      => $base_path . 'includes/validators/class-vd-account-validator.php',

            // Helper classes
            'VD_Security'               => $base_path . 'includes/helpers/class-vd-security.php',
            'VD_Data'                   => $base_path . 'includes/helpers/class-vd-data.php',
            'VD_DateTime'               => $base_path . 'includes/helpers/class-vd-datetime.php',

            // Core logic classes
            'VD_License_Verify'         => $base_path . 'includes/core/class-vd-license-verify.php',
            'VD_Rate_Limit'             => $base_path . 'includes/core/class-vd-rate-limit.php',
            'VD_Assignment'             => $base_path . 'includes/core/class-vd-assignment.php',
            'VD_Assignment_Strategies'  => $base_path . 'includes/core/class-vd-assignment-strategies.php',

            // API classes
            'VD_REST_API'               => $base_path . 'includes/api/class-vd-rest-api.php',
            'VD_API_Device_Verify'      => $base_path . 'includes/api/class-vd-api-device-verify.php',
            'VD_API_Account_Info'       => $base_path . 'includes/api/class-vd-api-account-info.php',
            'VD_API_History'            => $base_path . 'includes/api/class-vd-api-history.php',
            'VD_API_Device_Rotate'      => $base_path . 'includes/api/class-vd-api-device-rotate.php',

            // Admin classes
            'VD_Admin_Dashboard'        => $base_path . 'admin/pages/class-vd-admin-dashboard.php',
            'VD_Admin_Accounts_List'    => $base_path . 'admin/pages/class-vd-admin-accounts-list.php',
            'VD_Admin_Accounts_Add'     => $base_path . 'admin/pages/class-vd-admin-accounts-add.php',
            'VD_Admin_Accounts_Edit'    => $base_path . 'admin/pages/class-vd-admin-accounts-edit.php',
            'VD_Admin_Pools'            => $base_path . 'admin/pages/class-vd-admin-pools.php',
            'VD_Admin_Devices'          => $base_path . 'admin/pages/class-vd-admin-devices.php',

            // Admin account pages
            'VD_Admin_Provider_Accounts' => $base_path . 'admin/pages/class-vd-admin-provider-accounts.php',
            'VD_Accounts_List_Table'     => $base_path . 'admin/pages/accounts/class-vd-accounts-list-table.php',
            'VD_Accounts_List_View'      => $base_path . 'admin/pages/accounts/class-vd-accounts-list-view.php',
            'VD_Accounts_Form_View'      => $base_path . 'admin/pages/accounts/class-vd-accounts-form-view.php',
            'VD_Accounts_Form_Handler'   => $base_path . 'admin/pages/accounts/class-vd-accounts-form-handler.php',

            // Public classes
            'VD_Portal_Main'            => $base_path . 'public/pages/class-vd-portal-main.php',
            'VD_Portal_Account_Info'    => $base_path . 'public/pages/class-vd-portal-account-info.php'
        );
    }

    /**
     * Register the autoloader with SPL
     *
     * @since 1.0.0
     */
    private function register_autoloader() {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Autoload class files
     *
     * @since 1.0.0
     * @param string $class_name The class name to load
     */
    public function autoload($class_name) {
        // Only autoload VD_ prefixed classes
        if (strpos($class_name, 'VD_') !== 0) {
            return;
        }

        // Check if class path is registered
        if (isset($this->class_paths[$class_name])) {
            $file_path = $this->class_paths[$class_name];

            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }

        // Fallback: Try to construct path from class name
        $this->autoload_fallback($class_name);
    }

    /**
     * Fallback autoloader using naming convention
     *
     * @since 1.0.0
     * @param string $class_name The class name to load
     */
    private function autoload_fallback($class_name) {
        // Convert class name to file name
        $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';

        // Search directories
        $search_paths = array(
            VD_PLUGIN_PATH . 'includes/',
            VD_PLUGIN_PATH . 'includes/database/',
            VD_PLUGIN_PATH . 'includes/repositories/',
            VD_PLUGIN_PATH . 'includes/validators/',
            VD_PLUGIN_PATH . 'includes/helpers/',
            VD_PLUGIN_PATH . 'includes/core/',
            VD_PLUGIN_PATH . 'includes/api/',
            VD_PLUGIN_PATH . 'admin/pages/',
            VD_PLUGIN_PATH . 'admin/pages/accounts/',
            VD_PLUGIN_PATH . 'public/pages/'
        );

        foreach ($search_paths as $path) {
            $full_path = $path . $file_name;
            if (file_exists($full_path)) {
                require_once $full_path;
                return;
            }
        }
    }

    /**
     * Get registered class paths
     *
     * @since 1.0.0
     * @return array
     */
    public function get_class_paths() {
        return $this->class_paths;
    }

    /**
     * Add new class path
     *
     * @since 1.0.0
     * @param string $class_name The class name
     * @param string $file_path The file path
     */
    public function add_class_path($class_name, $file_path) {
        $this->class_paths[$class_name] = $file_path;
    }
}