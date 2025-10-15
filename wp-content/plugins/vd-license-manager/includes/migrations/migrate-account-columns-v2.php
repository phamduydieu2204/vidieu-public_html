<?php
/**
 * Account Columns Migration - Fix Schema Inconsistency
 *
 * Migrates account table columns to use consistent naming:
 * - Ensures 'account_password' is the primary password field
 * - Ensures 'account_login' is consistent
 * - Updates current_usage calculation
 * - Provides rollback capability
 *
 * @package    VD_License_Manager
 * @subpackage Migrations
 * @since      1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Account Columns Migration Class
 */
class VD_LM_Account_Columns_Migration_V2 {

    /**
     * Migration version
     */
    const MIGRATION_VERSION = '1.1.0';

    /**
     * Migration key for tracking
     */
    const MIGRATION_KEY = 'vd_account_columns_migration_v2';

    /**
     * Run the migration
     *
     * @return array Migration result
     */
    public static function run() {
        $start_time = microtime( true );

        VD_LM_Logger_Service::info( 'Starting Account Columns Migration V2' );

        // Step 1: Backup current data
        $backup_result = self::create_backup();
        if ( is_wp_error( $backup_result ) ) {
            return array(
                'success' => false,
                'message' => 'Backup failed: ' . $backup_result->get_error_message(),
                'execution_time' => microtime( true ) - $start_time
            );
        }

        // Step 2: Analyze current schema
        $schema_analysis = self::analyze_current_schema();

        // Step 3: Fix column inconsistencies
        $column_fix_result = self::fix_column_inconsistencies( $schema_analysis );
        if ( is_wp_error( $column_fix_result ) ) {
            return array(
                'success' => false,
                'message' => 'Column fix failed: ' . $column_fix_result->get_error_message(),
                'execution_time' => microtime( true ) - $start_time,
                'backup_file' => $backup_result['backup_file']
            );
        }

        // Step 4: Update current_usage calculations
        $usage_update_result = self::update_current_usage_calculations();
        if ( is_wp_error( $usage_update_result ) ) {
            return array(
                'success' => false,
                'message' => 'Usage calculation failed: ' . $usage_update_result->get_error_message(),
                'execution_time' => microtime( true ) - $start_time,
                'backup_file' => $backup_result['backup_file']
            );
        }

        // Step 5: Mark migration as complete
        update_option( self::MIGRATION_KEY, array(
            'version' => self::MIGRATION_VERSION,
            'completed_at' => current_time( 'mysql' ),
            'execution_time' => microtime( true ) - $start_time,
            'backup_file' => $backup_result['backup_file']
        ) );

        VD_LM_Logger_Service::info( 'Account Columns Migration V2 completed successfully', array(
            'execution_time' => microtime( true ) - $start_time,
            'schema_changes' => $column_fix_result,
            'usage_updates' => $usage_update_result
        ) );

        return array(
            'success' => true,
            'message' => 'Migration completed successfully',
            'execution_time' => microtime( true ) - $start_time,
            'backup_file' => $backup_result['backup_file'],
            'schema_changes' => $column_fix_result,
            'usage_updates' => $usage_update_result
        );
    }

    /**
     * Create backup of current account data
     *
     * @return array|WP_Error Backup result
     */
    private static function create_backup() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_provider_accounts';
        $backup_file = WP_CONTENT_DIR . '/vd-account-backup-' . date( 'Y-m-d-H-i-s' ) . '.sql';

        VD_LM_Logger_Service::info( 'Creating account data backup', array(
            'table' => $table_name,
            'backup_file' => $backup_file
        ) );

        // Get table structure
        $create_table = $wpdb->get_row( "SHOW CREATE TABLE {$table_name}", ARRAY_N );
        if ( ! $create_table ) {
            return new WP_Error( 'backup_failed', 'Could not get table structure' );
        }

        // Get all data
        $data_rows = $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );
        if ( $wpdb->last_error ) {
            return new WP_Error( 'backup_failed', 'Could not retrieve table data: ' . $wpdb->last_error );
        }

        // Generate backup SQL
        $backup_sql = "-- VD Account Backup - " . date( 'Y-m-d H:i:s' ) . "\n";
        $backup_sql .= "-- Table: {$table_name}\n\n";
        $backup_sql .= "DROP TABLE IF EXISTS {$table_name}_backup;\n";
        $backup_sql .= str_replace( "CREATE TABLE `{$table_name}`", "CREATE TABLE `{$table_name}_backup`", $create_table[1] ) . ";\n\n";

        if ( ! empty( $data_rows ) ) {
            foreach ( $data_rows as $row ) {
                $values = array();
                foreach ( $row as $value ) {
                    $values[] = is_null( $value ) ? 'NULL' : "'" . esc_sql( $value ) . "'";
                }
                $backup_sql .= "INSERT INTO {$table_name}_backup VALUES (" . implode( ',', $values ) . ");\n";
            }
        }

        // Write backup file
        $backup_written = file_put_contents( $backup_file, $backup_sql );
        if ( $backup_written === false ) {
            return new WP_Error( 'backup_failed', 'Could not write backup file' );
        }

        VD_LM_Logger_Service::info( 'Backup created successfully', array(
            'backup_file' => $backup_file,
            'backup_size' => filesize( $backup_file ),
            'rows_backed_up' => count( $data_rows )
        ) );

        return array(
            'backup_file' => $backup_file,
            'backup_size' => filesize( $backup_file ),
            'rows_backed_up' => count( $data_rows )
        );
    }

    /**
     * Analyze current schema structure
     *
     * @return array Schema analysis
     */
    private static function analyze_current_schema() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vd_provider_accounts';

        // Get current columns
        $columns = $wpdb->get_results( "DESCRIBE {$table_name}", ARRAY_A );

        $analysis = array(
            'table_name' => $table_name,
            'columns' => array(),
            'missing_columns' => array(),
            'issues' => array()
        );

        // Map current columns
        foreach ( $columns as $column ) {
            $analysis['columns'][ $column['Field'] ] = $column;
        }

        // Check for required columns
        $required_columns = array(
            'id' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
            'provider' => 'VARCHAR(100) NOT NULL',
            'account_login' => 'VARCHAR(255) NOT NULL',
            'display_name' => 'VARCHAR(255) NULL',
            'account_password' => 'LONGTEXT NULL',
            'capacity' => 'INT(11) NOT NULL DEFAULT 1',
            'current_usage' => 'INT(11) NOT NULL DEFAULT 0',
            'status' => 'ENUM(\'active\',\'inactive\',\'suspended\') NOT NULL DEFAULT \'active\'',
            'expires_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        );

        foreach ( $required_columns as $column_name => $expected_type ) {
            if ( ! isset( $analysis['columns'][ $column_name ] ) ) {
                $analysis['missing_columns'][ $column_name ] = $expected_type;
            }
        }

        // Check for legacy columns that conflict
        if ( isset( $analysis['columns']['login_password'] ) ) {
            $analysis['issues'][] = 'Legacy login_password column found (should be account_password)';
        }

        // Check if current_usage needs recalculation
        $zero_usage_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE current_usage = 0" );
        if ( $zero_usage_count > 0 ) {
            $analysis['issues'][] = "{$zero_usage_count} accounts have current_usage = 0 (may need recalculation)";
        }

        VD_LM_Logger_Service::info( 'Schema analysis completed', $analysis );

        return $analysis;
    }

    /**
     * Fix column inconsistencies
     *
     * @param array $schema_analysis Schema analysis result
     * @return array|WP_Error Fix result
     */
    private static function fix_column_inconsistencies( $schema_analysis ) {
        global $wpdb;

        $table_name = $schema_analysis['table_name'];
        $changes_made = array();

        VD_LM_Logger_Service::info( 'Starting column fixes', array(
            'missing_columns' => $schema_analysis['missing_columns'],
            'issues' => $schema_analysis['issues']
        ) );

        // Add missing columns
        foreach ( $schema_analysis['missing_columns'] as $column_name => $column_type ) {
            $add_column_sql = "ALTER TABLE {$table_name} ADD COLUMN {$column_name} {$column_type}";

            VD_LM_Logger_Service::info( 'Adding missing column', array(
                'column' => $column_name,
                'type' => $column_type,
                'sql' => $add_column_sql
            ) );

            $result = $wpdb->query( $add_column_sql );

            if ( $wpdb->last_error ) {
                return new WP_Error( 'column_add_failed', "Failed to add column {$column_name}: " . $wpdb->last_error );
            }

            $changes_made[] = "Added column: {$column_name}";
        }

        // Handle legacy login_password column
        if ( isset( $schema_analysis['columns']['login_password'] ) ) {
            VD_LM_Logger_Service::info( 'Migrating login_password to account_password' );

            // If account_password doesn't exist, rename login_password to account_password
            if ( ! isset( $schema_analysis['columns']['account_password'] ) ) {
                $rename_sql = "ALTER TABLE {$table_name} CHANGE COLUMN login_password account_password LONGTEXT NULL COMMENT 'Login password (encrypted)'";
                $result = $wpdb->query( $rename_sql );

                if ( $wpdb->last_error ) {
                    return new WP_Error( 'column_rename_failed', 'Failed to rename login_password: ' . $wpdb->last_error );
                }

                $changes_made[] = 'Renamed login_password to account_password';
            } else {
                // Both exist, copy data and drop login_password
                $copy_sql = "UPDATE {$table_name} SET account_password = login_password WHERE account_password IS NULL AND login_password IS NOT NULL";
                $wpdb->query( $copy_sql );

                $drop_sql = "ALTER TABLE {$table_name} DROP COLUMN login_password";
                $wpdb->query( $drop_sql );

                $changes_made[] = 'Merged login_password into account_password and dropped legacy column';
            }
        }

        // Ensure proper column types
        $column_updates = array(
            'account_password' => "ALTER TABLE {$table_name} MODIFY COLUMN account_password LONGTEXT NULL COMMENT 'Login password (encrypted)'",
            'current_usage' => "ALTER TABLE {$table_name} MODIFY COLUMN current_usage INT(11) NOT NULL DEFAULT 0 COMMENT 'Current number of active licenses'",
            'capacity' => "ALTER TABLE {$table_name} MODIFY COLUMN capacity INT(11) NOT NULL DEFAULT 1 COMMENT 'Maximum licenses this account can serve'"
        );

        foreach ( $column_updates as $column => $sql ) {
            if ( isset( $schema_analysis['columns'][ $column ] ) ) {
                $result = $wpdb->query( $sql );
                if ( $wpdb->last_error ) {
                    VD_LM_Logger_Service::warning( "Failed to update column {$column}", array(
                        'error' => $wpdb->last_error,
                        'sql' => $sql
                    ) );
                } else {
                    $changes_made[] = "Updated column type: {$column}";
                }
            }
        }

        VD_LM_Logger_Service::info( 'Column fixes completed', array(
            'changes_made' => $changes_made
        ) );

        return $changes_made;
    }

    /**
     * Update current_usage calculations
     *
     * @return array|WP_Error Update result
     */
    private static function update_current_usage_calculations() {
        global $wpdb;

        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';
        $licenses_table = $wpdb->prefix . 'vd_license_keys';

        VD_LM_Logger_Service::info( 'Starting current_usage recalculation' );

        // Check if license keys table exists
        $licenses_table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $licenses_table
        ) ) === $licenses_table;

        if ( ! $licenses_table_exists ) {
            VD_LM_Logger_Service::info( 'License keys table not found, skipping usage calculation' );
            return array(
                'message' => 'License keys table not found, usage calculation skipped',
                'accounts_updated' => 0
            );
        }

        // Recalculate current_usage for all accounts
        $update_sql = "
            UPDATE {$accounts_table} a
            SET current_usage = (
                SELECT COUNT(*)
                FROM {$licenses_table} l
                WHERE l.account_id = a.id
                AND l.status = 'active'
            )
        ";

        VD_LM_Logger_Service::info( 'Executing usage recalculation', array(
            'sql' => $update_sql
        ) );

        $result = $wpdb->query( $update_sql );

        if ( $wpdb->last_error ) {
            return new WP_Error( 'usage_calculation_failed', 'Failed to update current_usage: ' . $wpdb->last_error );
        }

        // Verify results
        $verification_query = "
            SELECT
                COUNT(*) as total_accounts,
                SUM(current_usage) as total_usage,
                MAX(current_usage) as max_usage,
                AVG(current_usage) as avg_usage
            FROM {$accounts_table}
        ";

        $verification = $wpdb->get_row( $verification_query, ARRAY_A );

        VD_LM_Logger_Service::info( 'Usage recalculation completed', array(
            'accounts_updated' => $result,
            'verification' => $verification
        ) );

        return array(
            'accounts_updated' => $result,
            'verification' => $verification
        );
    }

    /**
     * Check if migration has been run
     *
     * @return bool
     */
    public static function is_migration_complete() {
        $migration_data = get_option( self::MIGRATION_KEY );
        return ! empty( $migration_data ) && $migration_data['version'] === self::MIGRATION_VERSION;
    }

    /**
     * Rollback migration
     *
     * @param string $backup_file Backup file path
     * @return array|WP_Error Rollback result
     */
    public static function rollback( $backup_file ) {
        if ( ! file_exists( $backup_file ) ) {
            return new WP_Error( 'backup_not_found', 'Backup file not found: ' . $backup_file );
        }

        global $wpdb;

        VD_LM_Logger_Service::info( 'Starting migration rollback', array(
            'backup_file' => $backup_file
        ) );

        // Execute backup SQL
        $backup_sql = file_get_contents( $backup_file );
        if ( $backup_sql === false ) {
            return new WP_Error( 'backup_read_failed', 'Could not read backup file' );
        }

        // Execute SQL
        $queries = explode( ';', $backup_sql );
        $executed = 0;
        $errors = array();

        foreach ( $queries as $query ) {
            $query = trim( $query );
            if ( empty( $query ) || strpos( $query, '--' ) === 0 ) {
                continue;
            }

            $result = $wpdb->query( $query );
            if ( $wpdb->last_error ) {
                $errors[] = $wpdb->last_error;
            } else {
                $executed++;
            }
        }

        // Remove migration flag
        delete_option( self::MIGRATION_KEY );

        VD_LM_Logger_Service::info( 'Migration rollback completed', array(
            'queries_executed' => $executed,
            'errors' => $errors
        ) );

        return array(
            'success' => empty( $errors ),
            'queries_executed' => $executed,
            'errors' => $errors
        );
    }

    /**
     * Get migration status
     *
     * @return array Migration status
     */
    public static function get_status() {
        $migration_data = get_option( self::MIGRATION_KEY );

        if ( empty( $migration_data ) ) {
            return array(
                'status' => 'not_run',
                'message' => 'Migration has not been executed'
            );
        }

        return array(
            'status' => 'completed',
            'version' => $migration_data['version'],
            'completed_at' => $migration_data['completed_at'],
            'execution_time' => $migration_data['execution_time'],
            'backup_file' => $migration_data['backup_file']
        );
    }
}

// Auto-run migration if called directly
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // WP-CLI command
    if ( isset( $argv[1] ) && $argv[1] === 'run' ) {
        $result = VD_LM_Account_Columns_Migration_V2::run();
        WP_CLI::line( 'Migration result: ' . wp_json_encode( $result ) );
    }
} elseif ( isset( $_GET['vd_run_migration'] ) && $_GET['vd_run_migration'] === 'account_columns_v2' ) {
    // Web interface (for testing only)
    if ( current_user_can( 'manage_options' ) ) {
        $result = VD_LM_Account_Columns_Migration_V2::run();
        wp_die( '<pre>' . wp_json_encode( $result, JSON_PRETTY_PRINT ) . '</pre>' );
    }
}
?>