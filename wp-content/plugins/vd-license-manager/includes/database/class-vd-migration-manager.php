<?php
/**
 * Migration Manager
 *
 * Manages database schema migrations for VD License Manager.
 * Handles version tracking and incremental updates.
 *
 * @package    VD_License_Manager
 * @subpackage Database
 * @since      1.0.0
 * @author     VidieuVN <dev@vidieu.vn>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database Migration Manager
 *
 * Tracks and executes database schema migrations to ensure
 * the database structure stays current with plugin updates.
 *
 * @since 1.0.0
 */
class VD_Migration_Manager {

    /**
     * Option key for storing current migration version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION_OPTION = 'vd_lm_db_version';

    /**
     * Current plugin database version
     *
     * @since 1.0.0
     * @var string
     */
    const CURRENT_VERSION = '1.0.1';

    /**
     * Run pending migrations
     *
     * Checks current database version and runs any pending migrations
     * to bring the database schema up to date.
     *
     * @since 1.0.0
     * @access public
     * @return bool True on success, false on failure
     */
    public static function run_migrations() {
        $current_version = get_option( self::VERSION_OPTION, '0.0.0' );
        $target_version = self::CURRENT_VERSION;

        VD_LM_Logger_Service::info( 'Starting migrations', [
            'current_version' => $current_version,
            'target_version' => $target_version,
        ] );

        // No migrations needed
        if ( version_compare( $current_version, $target_version, '>=' ) ) {
            VD_LM_Logger_Service::info( 'Database is up to date' );
            return true;
        }

        // Run migrations in order
        $migrations = self::get_migrations();
        $success = true;

        foreach ( $migrations as $version => $migration ) {
            if ( version_compare( $current_version, $version, '<' ) ) {
                VD_LM_Logger_Service::info( "Running migration: {$version}" );

                if ( self::run_migration( $migration ) ) {
                    update_option( self::VERSION_OPTION, $version );
                    VD_LM_Logger_Service::info( "Migration completed: {$version}" );
                } else {
                    VD_LM_Logger_Service::error( "Migration failed: {$version}" );
                    $success = false;
                    break;
                }
            }
        }

        if ( $success ) {
            VD_LM_Logger_Service::info( 'All migrations completed successfully' );
        }

        return $success;
    }

    /**
     * Get available migrations
     *
     * Returns array of available database migrations.
     * Each migration is a callable that modifies the database schema.
     *
     * @since 1.0.0
     * @access private
     * @return array Array of version => callable migrations
     */
    private static function get_migrations() {
        return [
            '1.0.1' => [ __CLASS__, 'migration_1_0_1' ],
        ];
    }

    /**
     * Run a single migration
     *
     * Executes a single migration function with proper error handling
     * and transaction support.
     *
     * @since 1.0.0
     * @access private
     * @param callable $migration Migration function to execute
     * @return bool True on success, false on failure
     */
    private static function run_migration( $migration ) {
        global $wpdb;

        if ( ! is_callable( $migration ) ) {
            VD_LM_Logger_Service::error( 'Invalid migration: not callable' );
            return false;
        }

        // Start transaction
        $wpdb->query( 'START TRANSACTION' );

        try {
            // Execute migration
            $result = call_user_func( $migration );

            if ( $result ) {
                $wpdb->query( 'COMMIT' );
                return true;
            } else {
                $wpdb->query( 'ROLLBACK' );
                return false;
            }

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            VD_LM_Logger_Service::error( 'Migration exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] );
            return false;
        }
    }

    /**
     * Migration 1.0.1: Add priority field to product_pools table
     *
     * Adds priority field to bz_vd_product_pools to support
     * Entity-based UI where pools can be ordered by priority
     * for each product.
     *
     * @since 1.0.1
     * @access private
     * @return bool True on success, false on failure
     */
    private static function migration_1_0_1() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_product_pools';

        VD_LM_Logger_Service::info( "Running migration 1.0.1: Add priority field to {$table_name}" );

        // Check if priority field already exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'priority'",
                DB_NAME,
                $table_name
            )
        );

        if ( ! empty( $column_exists ) ) {
            VD_LM_Logger_Service::info( 'Priority field already exists, skipping' );
            return true;
        }

        // Add priority field
        $sql = "ALTER TABLE {$table_name}
                ADD COLUMN priority int(11) NOT NULL DEFAULT 1
                COMMENT 'Pool priority for product (1=highest priority)'
                AFTER pool_id";

        $result = $wpdb->query( $sql );

        if ( false === $result ) {
            VD_LM_Logger_Service::error( 'Failed to add priority field', [
                'table' => $table_name,
                'error' => $wpdb->last_error,
            ] );
            return false;
        }

        // Add index for priority field
        $index_sql = "CREATE INDEX idx_priority ON {$table_name} (priority)";
        $index_result = $wpdb->query( $index_sql );

        if ( false === $index_result ) {
            VD_LM_Logger_Service::warning( 'Failed to add priority index', [
                'table' => $table_name,
                'error' => $wpdb->last_error,
            ] );
            // Don't fail migration for index creation failure
        }

        VD_LM_Logger_Service::info( "Priority field added successfully to {$table_name}" );
        return true;
    }

    /**
     * Get current database version
     *
     * @since 1.0.0
     * @access public
     * @return string Current database version
     */
    public static function get_current_version() {
        return get_option( self::VERSION_OPTION, '0.0.0' );
    }

    /**
     * Check if migrations are needed
     *
     * @since 1.0.0
     * @access public
     * @return bool True if migrations are needed
     */
    public static function needs_migration() {
        $current_version = self::get_current_version();
        return version_compare( $current_version, self::CURRENT_VERSION, '<' );
    }

    /**
     * Force run migrations (for debugging)
     *
     * Forces re-running of all migrations. Use with caution.
     *
     * @since 1.0.0
     * @access public
     * @return bool True on success
     */
    public static function force_migrations() {
        delete_option( self::VERSION_OPTION );
        return self::run_migrations();
    }
}