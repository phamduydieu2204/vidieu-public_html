<?php
/**
 * Logger Service
 *
 * Provides logging functionality for the VD License Manager plugin.
 * Handles different log levels and outputs to various destinations.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger Service Class
 *
 * Provides comprehensive logging functionality with multiple log levels
 * and configurable output destinations.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Logger_Service {

    /**
     * Log levels
     *
     * @since 1.0.0
     */
    const ERROR   = 'ERROR';
    const WARNING = 'WARNING';
    const INFO    = 'INFO';
    const DEBUG   = 'DEBUG';

    /**
     * Log an error message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array  $context Additional context data
     */
    public static function error( $message, $context = array() ) {
        self::log( self::ERROR, $message, $context );
    }

    /**
     * Log a warning message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array  $context Additional context data
     */
    public static function warning( $message, $context = array() ) {
        self::log( self::WARNING, $message, $context );
    }

    /**
     * Log an info message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array  $context Additional context data
     */
    public static function info( $message, $context = array() ) {
        if ( ! self::should_log_level( self::INFO ) ) {
            return;
        }
        self::log( self::INFO, $message, $context );
    }

    /**
     * Log a debug message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array  $context Additional context data
     */
    public static function debug( $message, $context = array() ) {
        if ( ! self::should_log_level( self::DEBUG ) ) {
            return;
        }
        self::log( self::DEBUG, $message, $context );
    }

    /**
     * Log a message with specified level
     *
     * @since  1.0.0
     * @access private
     * @param  string $level   The log level
     * @param  string $message The message to log
     * @param  array  $context Additional context data
     */
    private static function log( $level, $message, $context = array() ) {
        // Check if logging is enabled
        if ( ! self::is_logging_enabled() ) {
            return;
        }

        // Format the log entry
        $formatted_message = self::format_message( $level, $message, $context );

        // Write to log file
        self::write_to_file( $formatted_message );

        // Write to WordPress debug log if enabled
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( $formatted_message );
        }
    }

    /**
     * Format a log message
     *
     * @since  1.0.0
     * @access private
     * @param  string $level   The log level
     * @param  string $message The message to log
     * @param  array  $context Additional context data
     * @return string The formatted message
     */
    private static function format_message( $level, $message, $context = array() ) {
        $timestamp = gmdate( 'Y-m-d H:i:s' );
        $memory = self::format_bytes( memory_get_usage() );

        $formatted = sprintf(
            '[%s] [%s] [%s] %s',
            $timestamp,
            $level,
            $memory,
            $message
        );

        // Add context if provided
        if ( ! empty( $context ) ) {
            $context_json = wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
            $formatted .= ' | Context: ' . $context_json;
        }

        return $formatted . PHP_EOL;
    }

    /**
     * Write log message to file
     *
     * @since  1.0.0
     * @access private
     * @param  string $message The formatted message to write
     */
    private static function write_to_file( $message ) {
        $log_file = self::get_log_file_path();

        // Ensure log directory exists
        $log_dir = dirname( $log_file );
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        // Write to log file
        // Using error_log with FILE destination is atomic and thread-safe
        error_log( $message, 3, $log_file );

        // Rotate log file if it's too large
        self::rotate_log_if_needed( $log_file );
    }

    /**
     * Get the log file path
     *
     * @since  1.0.0
     * @access private
     * @return string The log file path
     */
    private static function get_log_file_path() {
        // Check if custom log file is defined
        if ( defined( 'VD_DEBUG_LOG' ) && ! empty( VD_DEBUG_LOG ) ) {
            return VD_DEBUG_LOG;
        }

        // Default to wp-content/vd-logs/
        return WP_CONTENT_DIR . '/vd-logs/vd-license-manager.log';
    }

    /**
     * Rotate log file if it exceeds size limit
     *
     * @since  1.0.0
     * @access private
     * @param  string $log_file The log file path
     */
    private static function rotate_log_if_needed( $log_file ) {
        // Only rotate if file exists and is larger than 10MB
        if ( ! file_exists( $log_file ) || filesize( $log_file ) < 10 * 1024 * 1024 ) {
            return;
        }

        // Create backup filename
        $backup_file = $log_file . '.' . gmdate( 'Y-m-d-H-i-s' ) . '.bak';

        // Rename current log to backup
        if ( rename( $log_file, $backup_file ) ) {
            // Optionally, you could compress the backup file here
            // gzip_file( $backup_file );

            // Clean up old backup files (keep only last 5)
            self::cleanup_old_backups( dirname( $log_file ) );
        }
    }

    /**
     * Clean up old backup log files
     *
     * @since  1.0.0
     * @access private
     * @param  string $log_dir The log directory
     */
    private static function cleanup_old_backups( $log_dir ) {
        if ( ! is_dir( $log_dir ) ) {
            return;
        }

        $backup_files = glob( $log_dir . '/*.bak' );
        if ( count( $backup_files ) <= 5 ) {
            return;
        }

        // Sort by modification time (oldest first)
        usort( $backup_files, function( $a, $b ) {
            return filemtime( $a ) - filemtime( $b );
        } );

        // Delete oldest files, keep only 5 newest
        $files_to_delete = array_slice( $backup_files, 0, -5 );
        foreach ( $files_to_delete as $file ) {
            if ( file_exists( $file ) ) {
                unlink( $file );
            }
        }
    }

    /**
     * Check if logging is enabled
     *
     * @since  1.0.0
     * @access private
     * @return bool True if logging is enabled
     */
    private static function is_logging_enabled() {
        // Check if debug mode is enabled
        if ( defined( 'VD_DEBUG' ) ) {
            return (bool) VD_DEBUG;
        }

        // Fallback to WordPress debug setting
        return defined( 'WP_DEBUG' ) && WP_DEBUG;
    }

    /**
     * Check if a specific log level should be logged
     *
     * @since  1.0.0
     * @access private
     * @param  string $level The log level to check
     * @return bool True if the level should be logged
     */
    private static function should_log_level( $level ) {
        if ( ! self::is_logging_enabled() ) {
            return false;
        }

        // Get configured log level
        $configured_level = defined( 'VD_LOG_LEVEL' ) ? strtoupper( VD_LOG_LEVEL ) : 'ERROR';

        // Define level hierarchy
        $levels = array(
            'ERROR'   => 4,
            'WARNING' => 3,
            'INFO'    => 2,
            'DEBUG'   => 1,
        );

        $current_level_value = isset( $levels[ $level ] ) ? $levels[ $level ] : 0;
        $configured_level_value = isset( $levels[ $configured_level ] ) ? $levels[ $configured_level ] : 4;

        return $current_level_value >= $configured_level_value;
    }

    /**
     * Format bytes to human readable format
     *
     * @since  1.0.0
     * @access private
     * @param  int $size The size in bytes
     * @return string Formatted size string
     */
    private static function format_bytes( $size ) {
        if ( $size < 1024 ) {
            return $size . 'B';
        } elseif ( $size < 1048576 ) {
            return round( $size / 1024, 2 ) . 'KB';
        } else {
            return round( $size / 1048576, 2 ) . 'MB';
        }
    }

    /**
     * Get recent log entries
     *
     * @since 1.0.0
     * @param int $lines Number of lines to retrieve
     * @return array Array of log entries
     */
    public static function get_recent_logs( $lines = 100 ) {
        $log_file = self::get_log_file_path();

        if ( ! file_exists( $log_file ) ) {
            return array();
        }

        // Read last N lines efficiently
        $lines_array = array();
        $fp = fopen( $log_file, 'r' );

        if ( ! $fp ) {
            return array();
        }

        // Go to end of file
        fseek( $fp, -1, SEEK_END );

        // Read backwards
        $pos = ftell( $fp );
        $lines_read = 0;
        $line = '';

        while ( $pos >= 0 && $lines_read < $lines ) {
            $char = fgetc( $fp );

            if ( $char === "\n" ) {
                if ( ! empty( trim( $line ) ) ) {
                    $lines_array[] = strrev( $line );
                    $lines_read++;
                }
                $line = '';
            } else {
                $line .= $char;
            }

            $pos--;
            fseek( $fp, $pos );
        }

        // Add the last line if any
        if ( ! empty( trim( $line ) ) ) {
            $lines_array[] = strrev( $line );
        }

        fclose( $fp );

        return array_reverse( $lines_array );
    }

    /**
     * Clear log file
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public static function clear_logs() {
        $log_file = self::get_log_file_path();

        if ( file_exists( $log_file ) ) {
            return unlink( $log_file );
        }

        return true;
    }
}