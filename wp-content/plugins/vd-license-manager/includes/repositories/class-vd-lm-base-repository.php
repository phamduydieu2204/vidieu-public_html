<?php
/**
 * Base Repository Class
 *
 * Provides common database functionality for all repository classes.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/repositories
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base Repository Class
 *
 * Abstract base class that provides common database operations
 * and utilities for all repository classes.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/repositories
 * @author     Vidieu Team <admin@vidieu.vn>
 */
abstract class VD_LM_Base_Repository {

    /**
     * WordPress database instance
     *
     * @since  1.0.0
     * @access protected
     * @var    wpdb $wpdb WordPress database instance
     */
    protected $wpdb;

    /**
     * Table name (without prefix)
     *
     * @since  1.0.0
     * @access protected
     * @var    string $table_name Table name without prefix
     */
    protected $table_name;

    /**
     * Full table name (with prefix)
     *
     * @since  1.0.0
     * @access protected
     * @var    string $full_table_name Full table name with prefix
     */
    protected $full_table_name;

    /**
     * Primary key column name
     *
     * @since  1.0.0
     * @access protected
     * @var    string $primary_key Primary key column name
     */
    protected $primary_key = 'id';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $table_name Table name without prefix
     */
    public function __construct( $table_name ) {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->table_name = $table_name;
        $this->full_table_name = $wpdb->prefix . $table_name;
    }

    /**
     * Find record by ID
     *
     * @since 1.0.0
     * @param int $id Record ID
     * @return object|null Record object or null if not found
     */
    public function find( $id ) {
        $id = absint( $id );

        // DEBUG: Log find request
        error_log( 'VD DEBUG: Base repository find() called with ID: ' . $id );
        error_log( 'VD DEBUG: Base repository table: ' . $this->full_table_name );

        if ( $id <= 0 ) {
            error_log( 'VD DEBUG: Base repository find() - Invalid ID, returning null' );
            return null;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->full_table_name} WHERE {$this->primary_key} = %d",
            $id
        );

        // DEBUG: Log the actual query
        error_log( 'VD DEBUG: Base repository query: ' . $query );

        $result = $this->wpdb->get_row( $query );

        // DEBUG: Log raw database result
        if ( $result ) {
            error_log( 'VD DEBUG: Base repository find() - Raw DB result found' );
            error_log( 'VD DEBUG: Base repository find() - Result type: ' . gettype( $result ) );
            if ( is_object( $result ) ) {
                $props = get_object_vars( $result );
                error_log( 'VD DEBUG: Base repository find() - Result properties: ' . implode( ', ', array_keys( $props ) ) );
            }
        } else {
            error_log( 'VD DEBUG: Base repository find() - No result from database' );
            error_log( 'VD DEBUG: Base repository find() - Last error: ' . $this->wpdb->last_error );
        }

        return $result ? $this->format_result( $result ) : null;
    }

    /**
     * Find records by criteria
     *
     * @since 1.0.0
     * @param array $criteria Search criteria
     * @param array $args     Additional arguments (limit, offset, order_by, order)
     * @return array Array of record objects
     */
    public function find_by( $criteria = array(), $args = array() ) {
        $where_clauses = array();
        $where_values = array();

        // Build WHERE clauses
        foreach ( $criteria as $column => $value ) {
            if ( $value === null ) {
                $where_clauses[] = "`{$column}` IS NULL";
            } else {
                $where_clauses[] = "`{$column}` = %s";
                $where_values[] = $value;
            }
        }

        // Base query
        $sql = "SELECT * FROM {$this->full_table_name}";

        // Add WHERE clause
        if ( ! empty( $where_clauses ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        // Add ORDER BY
        $order_by = isset( $args['order_by'] ) ? sanitize_key( $args['order_by'] ) : $this->primary_key;
        $order = isset( $args['order'] ) && strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY `{$order_by}` {$order}";

        // Add LIMIT and OFFSET
        if ( isset( $args['limit'] ) ) {
            $limit = absint( $args['limit'] );
            $offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        // Prepare and execute query
        if ( ! empty( $where_values ) ) {
            $prepared_sql = $this->wpdb->prepare( $sql, ...$where_values );
        } else {
            $prepared_sql = $sql;
        }

        $results = $this->wpdb->get_results( $prepared_sql );

        return array_map( array( $this, 'format_result' ), $results );
    }

    /**
     * Find first record by criteria
     *
     * @since 1.0.0
     * @param array $criteria Search criteria
     * @return object|null First matching record or null
     */
    public function find_one_by( $criteria = array() ) {
        $results = $this->find_by( $criteria, array( 'limit' => 1 ) );

        return ! empty( $results ) ? $results[0] : null;
    }

    /**
     * Count records by criteria
     *
     * @since 1.0.0
     * @param array $criteria Search criteria
     * @return int Number of matching records
     */
    public function count_by( $criteria = array() ) {
        $where_clauses = array();
        $where_values = array();

        // Build WHERE clauses
        foreach ( $criteria as $column => $value ) {
            if ( $value === null ) {
                $where_clauses[] = "`{$column}` IS NULL";
            } else {
                $where_clauses[] = "`{$column}` = %s";
                $where_values[] = $value;
            }
        }

        // Base query
        $sql = "SELECT COUNT(*) FROM {$this->full_table_name}";

        // Add WHERE clause
        if ( ! empty( $where_clauses ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        // Prepare and execute query
        if ( ! empty( $where_values ) ) {
            $prepared_sql = $this->wpdb->prepare( $sql, ...$where_values );
        } else {
            $prepared_sql = $sql;
        }

        return absint( $this->wpdb->get_var( $prepared_sql ) );
    }

    /**
     * Insert new record
     *
     * @since 1.0.0
     * @param array $data Record data
     * @return int|false Insert ID on success, false on failure
     */
    public function insert( $data ) {
        // Add timestamps if they don't exist
        $data = $this->add_timestamps( $data, 'insert' );

        // Insert record
        $result = $this->wpdb->insert(
            $this->full_table_name,
            $data
        );

        if ( false === $result ) {
            VD_LM_Logger_Service::error( 'Database insert failed', array(
                'table' => $this->table_name,
                'data' => $data,
                'error' => $this->wpdb->last_error,
            ) );
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Update record by ID
     *
     * @since 1.0.0
     * @param int   $id   Record ID
     * @param array $data Update data
     * @return bool True on success, false on failure
     */
    public function update( $id, $data ) {
        $id = absint( $id );

        if ( $id <= 0 ) {
            return false;
        }

        // Add updated timestamp
        $data = $this->add_timestamps( $data, 'update' );

        // Update record
        $result = $this->wpdb->update(
            $this->full_table_name,
            $data,
            array( $this->primary_key => $id )
        );

        if ( false === $result ) {
            VD_LM_Logger_Service::error( 'Database update failed', array(
                'table' => $this->table_name,
                'id' => $id,
                'data' => $data,
                'error' => $this->wpdb->last_error,
            ) );
            return false;
        }

        return true;
    }

    /**
     * Delete record by ID
     *
     * @since 1.0.0
     * @param int $id Record ID
     * @return bool True on success, false on failure
     */
    public function delete( $id ) {
        $id = absint( $id );

        if ( $id <= 0 ) {
            return false;
        }

        $result = $this->wpdb->delete(
            $this->full_table_name,
            array( $this->primary_key => $id ),
            array( '%d' )
        );

        if ( false === $result ) {
            VD_LM_Logger_Service::error( 'Database delete failed', array(
                'table' => $this->table_name,
                'id' => $id,
                'error' => $this->wpdb->last_error,
            ) );
            return false;
        }

        return $result > 0;
    }

    /**
     * Check if record exists
     *
     * @since 1.0.0
     * @param int $id Record ID
     * @return bool True if exists, false otherwise
     */
    public function exists( $id ) {
        $id = absint( $id );

        if ( $id <= 0 ) {
            return false;
        }

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->full_table_name} WHERE {$this->primary_key} = %d",
                $id
            )
        );

        return absint( $count ) > 0;
    }

    /**
     * Get all records
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Array of record objects
     */
    public function get_all( $args = array() ) {
        return $this->find_by( array(), $args );
    }

    /**
     * Get total count of all records
     *
     * @since 1.0.0
     * @return int Total record count
     */
    public function get_total_count() {
        return $this->count_by( array() );
    }

    /**
     * Count records with optional criteria
     *
     * @since 1.0.0
     * @param array $criteria Optional search criteria
     * @return int Number of matching records
     */
    public function count( $criteria = array() ) {
        return $this->count_by( $criteria );
    }

    /**
     * Execute custom query
     *
     * @since 1.0.0
     * @param string $sql    SQL query
     * @param array  $params Query parameters
     * @return mixed Query result
     */
    protected function query( $sql, $params = array() ) {
        if ( ! empty( $params ) ) {
            $sql = $this->wpdb->prepare( $sql, ...$params );
        }

        return $this->wpdb->get_results( $sql );
    }

    /**
     * Format database result
     *
     * Override this method in child classes to format specific data types.
     *
     * @since 1.0.0
     * @param object $result Raw database result
     * @return object Formatted result
     */
    protected function format_result( $result ) {
        if ( ! is_object( $result ) ) {
            return $result;
        }

        // Convert numeric strings to integers for ID fields
        if ( isset( $result->{$this->primary_key} ) ) {
            $result->{$this->primary_key} = absint( $result->{$this->primary_key} );
        }

        return $result;
    }

    /**
     * Add timestamps to data array
     *
     * @since  1.0.0
     * @access private
     * @param  array  $data      Data array
     * @param  string $operation Operation type ('insert' or 'update')
     * @return array Modified data array
     */
    private function add_timestamps( $data, $operation ) {
        $current_time = current_time( 'mysql' );

        if ( $operation === 'insert' && ! isset( $data['created_at'] ) ) {
            $data['created_at'] = $current_time;
        }

        if ( ! isset( $data['updated_at'] ) ) {
            $data['updated_at'] = $current_time;
        }

        return $data;
    }

    /**
     * Get table name
     *
     * @since 1.0.0
     * @return string Table name without prefix
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Get full table name
     *
     * @since 1.0.0
     * @return string Full table name with prefix
     */
    public function get_full_table_name() {
        return $this->full_table_name;
    }

    /**
     * Get primary key column name
     *
     * @since 1.0.0
     * @return string Primary key column name
     */
    public function get_primary_key() {
        return $this->primary_key;
    }

    /**
     * Check if table exists
     *
     * @since 1.0.0
     * @return bool True if table exists
     */
    public function table_exists() {
        $table_name = $this->full_table_name;

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );

        return $result === $table_name;
    }

    /**
     * Begin database transaction
     *
     * @since 1.0.0
     */
    public function begin_transaction() {
        $this->wpdb->query( 'START TRANSACTION' );
    }

    /**
     * Commit database transaction
     *
     * @since 1.0.0
     */
    public function commit() {
        $this->wpdb->query( 'COMMIT' );
    }

    /**
     * Rollback database transaction
     *
     * @since 1.0.0
     */
    public function rollback() {
        $this->wpdb->query( 'ROLLBACK' );
    }
}