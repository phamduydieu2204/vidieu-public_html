<?php
/**
 * VD Table Migrator
 *
 * Temporary utility to fix the double prefix issue in database tables
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Table_Migrator class
 *
 * Handles migration from bz_bz_* tables to bz_vd_* tables
 */
class VD_Table_Migrator {

    /**
     * Single instance of the class
     *
     * @since 1.0.0
     * @var VD_Table_Migrator
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @since 1.0.0
     * @return VD_Table_Migrator Single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private to enforce singleton
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Check for incorrect table prefixes
     *
     * @since 1.0.0
     * @return array Analysis results
     */
    public function analyze_tables() {
        global $wpdb;

        $analysis = [
            'current_prefix' => $wpdb->prefix,
            'incorrect_tables' => [],
            'correct_tables' => [],
            'all_vd_tables' => [],
            'status' => 'ok'
        ];

        // Check for tables with bz_bz_ prefix (incorrect)
        $incorrect_tables = $wpdb->get_results("SHOW TABLES LIKE 'bz_bz_%'", ARRAY_N);

        foreach ($incorrect_tables as $table) {
            $analysis['incorrect_tables'][] = $table[0];
        }

        // Check for tables with bz_vd_ prefix (correct)
        $correct_tables = $wpdb->get_results("SHOW TABLES LIKE 'bz_vd_%'", ARRAY_N);

        foreach ($correct_tables as $table) {
            $analysis['correct_tables'][] = $table[0];
        }

        // Get all VD License Manager related tables
        $all_vd_tables = $wpdb->get_results("SHOW TABLES LIKE '%vd_%'", ARRAY_N);

        foreach ($all_vd_tables as $table) {
            $analysis['all_vd_tables'][] = $table[0];
        }

        // Determine status
        if (!empty($analysis['incorrect_tables'])) {
            $analysis['status'] = 'needs_migration';
        } elseif (empty($analysis['correct_tables'])) {
            $analysis['status'] = 'no_tables';
        }

        return $analysis;
    }

    /**
     * Drop incorrect tables if they exist
     *
     * @since 1.0.0
     * @return array Results
     */
    public function drop_incorrect_tables() {
        global $wpdb;

        $results = [
            'dropped_tables' => [],
            'errors' => [],
            'success' => false
        ];

        // Get tables with incorrect prefix
        $incorrect_tables = $wpdb->get_results("SHOW TABLES LIKE 'bz_bz_%'", ARRAY_N);

        if (empty($incorrect_tables)) {
            $results['success'] = true;
            $results['message'] = 'No incorrect tables found to drop.';
            return $results;
        }

        foreach ($incorrect_tables as $table) {
            $table_name = $table[0];

            try {
                $drop_result = $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");

                if ($drop_result !== false) {
                    $results['dropped_tables'][] = $table_name;
                    vd_debug_log("Dropped incorrect table: {$table_name}");
                } else {
                    $results['errors'][] = "Failed to drop table: {$table_name}";
                    vd_debug_log("Failed to drop table: {$table_name}");
                }

            } catch (Exception $e) {
                $results['errors'][] = "Exception dropping table {$table_name}: " . $e->getMessage();
                vd_debug_log("Exception dropping table {$table_name}: " . $e->getMessage());
            }
        }

        $results['success'] = (count($results['dropped_tables']) > 0 && empty($results['errors']));

        return $results;
    }

    /**
     * Create correct tables using the updated database manager
     *
     * @since 1.0.0
     * @return array Results
     */
    public function create_correct_tables() {
        if (class_exists('VD_Database_Manager')) {
            return VD_Database_Manager::create_tables();
        } else {
            return [
                'success' => false,
                'errors' => ['VD_Database_Manager class not found']
            ];
        }
    }

    /**
     * Perform complete migration
     *
     * @since 1.0.0
     * @return array Migration results
     */
    public function perform_migration() {
        $migration_results = [
            'analysis' => [],
            'drop_results' => [],
            'create_results' => [],
            'final_analysis' => [],
            'success' => false,
            'summary' => ''
        ];

        // Step 1: Analyze current state
        $migration_results['analysis'] = $this->analyze_tables();

        vd_debug_log("Table migration started. Current state: " . json_encode($migration_results['analysis']));

        // Step 2: Drop incorrect tables if they exist
        if (!empty($migration_results['analysis']['incorrect_tables'])) {
            $migration_results['drop_results'] = $this->drop_incorrect_tables();
        } else {
            $migration_results['drop_results'] = [
                'success' => true,
                'message' => 'No incorrect tables to drop'
            ];
        }

        // Step 3: Create correct tables
        $migration_results['create_results'] = $this->create_correct_tables();

        // Step 4: Final analysis
        $migration_results['final_analysis'] = $this->analyze_tables();

        // Determine overall success
        $migration_results['success'] =
            $migration_results['drop_results']['success'] &&
            $migration_results['create_results']['success'] &&
            $migration_results['final_analysis']['status'] !== 'needs_migration';

        // Create summary
        $dropped_count = count($migration_results['drop_results']['dropped_tables'] ?? []);
        $created_count = count($migration_results['create_results']['tables'] ?? []);
        $correct_count = count($migration_results['final_analysis']['correct_tables'] ?? []);

        $migration_results['summary'] = sprintf(
            'Migration completed. Dropped %d incorrect tables, created %d tables, final count: %d correct tables.',
            $dropped_count,
            $created_count,
            $correct_count
        );

        vd_debug_log("Table migration completed. " . $migration_results['summary']);

        return $migration_results;
    }

    /**
     * Get status for admin display
     *
     * @since 1.0.0
     * @return array Status information
     */
    public function get_migration_status() {
        $analysis = $this->analyze_tables();

        return [
            'needs_migration' => ($analysis['status'] === 'needs_migration'),
            'incorrect_count' => count($analysis['incorrect_tables']),
            'correct_count' => count($analysis['correct_tables']),
            'details' => $analysis
        ];
    }
}