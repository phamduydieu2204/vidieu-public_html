<?php
/**
 * Sanitizer Utility Class
 *
 * Provides data sanitization methods for various plugin data types.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/utils
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitizer Class
 *
 * Provides comprehensive sanitization methods for plugin data.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/utils
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_Sanitizer {

    /**
     * Sanitize license key
     *
     * @since 1.0.0
     * @param string $license_key License key to sanitize
     * @return string Sanitized license key
     */
    public static function sanitize_license_key( $license_key ) {
        if ( empty( $license_key ) ) {
            return '';
        }

        // Remove whitespace and convert to uppercase
        $license_key = strtoupper( trim( $license_key ) );

        // Remove any characters that aren't alphanumeric or hyphens
        $license_key = preg_replace( '/[^A-Z0-9-]/', '', $license_key );

        // Ensure proper format (add hyphens if missing)
        if ( strlen( $license_key ) === 16 && strpos( $license_key, '-' ) === false ) {
            // No hyphens, add them: XXXXXXXXXXXXXXXX -> XXXX-XXXX-XXXX-XXXX
            $license_key = substr( $license_key, 0, 4 ) . '-' .
                          substr( $license_key, 4, 4 ) . '-' .
                          substr( $license_key, 8, 4 ) . '-' .
                          substr( $license_key, 12, 4 );
        }

        return $license_key;
    }

    /**
     * Sanitize device fingerprint
     *
     * @since 1.0.0
     * @param string $fingerprint Device fingerprint to sanitize
     * @return string Sanitized fingerprint
     */
    public static function sanitize_device_fingerprint( $fingerprint ) {
        if ( empty( $fingerprint ) ) {
            return '';
        }

        // Convert to lowercase and remove any non-hex characters
        $fingerprint = strtolower( trim( $fingerprint ) );
        $fingerprint = preg_replace( '/[^a-f0-9]/', '', $fingerprint );

        // Truncate to 64 characters (SHA256 length)
        return substr( $fingerprint, 0, 64 );
    }

    /**
     * Sanitize device token
     *
     * @since 1.0.0
     * @param string $token Device token to sanitize
     * @return string Sanitized token
     */
    public static function sanitize_device_token( $token ) {
        if ( empty( $token ) ) {
            return '';
        }

        // Convert to uppercase and remove any non-alphanumeric characters
        $token = strtoupper( trim( $token ) );
        $token = preg_replace( '/[^A-Z0-9]/', '', $token );

        // Truncate to 20 characters
        return substr( $token, 0, 20 );
    }

    /**
     * Sanitize provider name
     *
     * @since 1.0.0
     * @param string $provider Provider name to sanitize
     * @return string Sanitized provider name
     */
    public static function sanitize_provider_name( $provider ) {
        if ( empty( $provider ) ) {
            return '';
        }

        // Basic text sanitization
        $provider = sanitize_text_field( $provider );

        // Remove excessive whitespace
        $provider = preg_replace( '/\s+/', ' ', trim( $provider ) );

        // Limit length
        return substr( $provider, 0, 50 );
    }

    /**
     * Sanitize account login
     *
     * @since 1.0.0
     * @param string $login Account login to sanitize
     * @return string Sanitized login
     */
    public static function sanitize_account_login( $login ) {
        if ( empty( $login ) ) {
            return '';
        }

        // Basic text sanitization
        $login = sanitize_text_field( $login );

        // Trim whitespace
        $login = trim( $login );

        // Limit length
        return substr( $login, 0, 255 );
    }

    /**
     * Sanitize credentials array
     *
     * @since 1.0.0
     * @param array $credentials Credentials to sanitize
     * @return array Sanitized credentials
     */
    public static function sanitize_credentials( $credentials ) {
        if ( ! is_array( $credentials ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $credentials as $key => $value ) {
            // Sanitize key
            $clean_key = sanitize_key( $key );
            if ( empty( $clean_key ) ) {
                continue;
            }

            // Sanitize value
            $clean_value = is_string( $value ) ? sanitize_textarea_field( $value ) : '';
            if ( empty( $clean_value ) ) {
                continue;
            }

            // Limit key and value length
            $clean_key = substr( $clean_key, 0, 50 );
            $clean_value = substr( $clean_value, 0, 1000 );

            $sanitized[ $clean_key ] = $clean_value;
        }

        return $sanitized;
    }

    /**
     * Sanitize device name
     *
     * @since 1.0.0
     * @param string $device_name Device name to sanitize
     * @return string Sanitized device name
     */
    public static function sanitize_device_name( $device_name ) {
        if ( empty( $device_name ) ) {
            return '';
        }

        // Basic text sanitization
        $device_name = sanitize_text_field( $device_name );

        // Remove excessive whitespace
        $device_name = preg_replace( '/\s+/', ' ', trim( $device_name ) );

        // Limit length
        return substr( $device_name, 0, 100 );
    }

    /**
     * Sanitize IP address
     *
     * @since 1.0.0
     * @param string $ip IP address to sanitize
     * @return string Sanitized IP address
     */
    public static function sanitize_ip_address( $ip ) {
        if ( empty( $ip ) ) {
            return '';
        }

        // Basic sanitization
        $ip = sanitize_text_field( $ip );
        $ip = trim( $ip );

        // Validate and return clean IP
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $ip;
        }

        return '';
    }

    /**
     * Sanitize user agent string
     *
     * @since 1.0.0
     * @param string $user_agent User agent to sanitize
     * @return string Sanitized user agent
     */
    public static function sanitize_user_agent( $user_agent ) {
        if ( empty( $user_agent ) ) {
            return '';
        }

        // Basic text sanitization
        $user_agent = sanitize_textarea_field( $user_agent );

        // Remove line breaks and excessive whitespace
        $user_agent = preg_replace( '/\s+/', ' ', trim( $user_agent ) );

        // Limit length
        return substr( $user_agent, 0, 500 );
    }

    /**
     * Sanitize JSON data
     *
     * @since 1.0.0
     * @param string $json JSON string to sanitize
     * @return string Sanitized JSON string
     */
    public static function sanitize_json( $json ) {
        if ( empty( $json ) ) {
            return '';
        }

        // Basic sanitization
        $json = sanitize_textarea_field( $json );

        // Try to decode and re-encode to ensure valid JSON
        $decoded = json_decode( $json, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return wp_json_encode( $decoded );
        }

        return '';
    }

    /**
     * Sanitize status value
     *
     * @since 1.0.0
     * @param string $status Status to sanitize
     * @param array  $allowed_statuses Array of allowed status values
     * @return string Sanitized status
     */
    public static function sanitize_status( $status, $allowed_statuses = array() ) {
        if ( empty( $status ) ) {
            return '';
        }

        // Basic sanitization
        $status = sanitize_text_field( $status );
        $status = strtolower( trim( $status ) );

        if ( empty( $allowed_statuses ) ) {
            // Default allowed statuses
            $allowed_statuses = array( 'active', 'inactive', 'expired', 'suspended', 'blocked' );
        }

        // Check if status is in allowed list
        if ( in_array( $status, array_map( 'strtolower', $allowed_statuses ), true ) ) {
            return $status;
        }

        // Return first allowed status as default
        return ! empty( $allowed_statuses ) ? $allowed_statuses[0] : 'active';
    }

    /**
     * Sanitize pool name
     *
     * @since 1.0.0
     * @param string $pool_name Pool name to sanitize
     * @return string Sanitized pool name
     */
    public static function sanitize_pool_name( $pool_name ) {
        if ( empty( $pool_name ) ) {
            return '';
        }

        // Basic text sanitization
        $pool_name = sanitize_text_field( $pool_name );

        // Remove excessive whitespace
        $pool_name = preg_replace( '/\s+/', ' ', trim( $pool_name ) );

        // Limit length
        return substr( $pool_name, 0, 255 );
    }

    /**
     * Sanitize display name
     *
     * @since 1.0.0
     * @param string $display_name Display name to sanitize
     * @return string Sanitized display name
     */
    public static function sanitize_display_name( $display_name ) {
        if ( empty( $display_name ) ) {
            return '';
        }

        // Basic text sanitization
        $display_name = sanitize_text_field( $display_name );

        // Remove excessive whitespace
        $display_name = preg_replace( '/\s+/', ' ', trim( $display_name ) );

        // Limit length
        return substr( $display_name, 0, 255 );
    }

    /**
     * Sanitize internal notes
     *
     * @since 1.0.0
     * @param string $notes Internal notes to sanitize
     * @return string Sanitized notes
     */
    public static function sanitize_internal_notes( $notes ) {
        if ( empty( $notes ) ) {
            return '';
        }

        // Allow basic HTML in notes
        $allowed_html = array(
            'br'     => array(),
            'p'      => array(),
            'strong' => array(),
            'em'     => array(),
            'ul'     => array(),
            'ol'     => array(),
            'li'     => array(),
        );

        $notes = wp_kses( $notes, $allowed_html );

        // Limit length
        return substr( $notes, 0, 2000 );
    }

    /**
     * Sanitize numeric value with min/max constraints
     *
     * @since 1.0.0
     * @param mixed $value Value to sanitize
     * @param int   $min   Minimum allowed value
     * @param int   $max   Maximum allowed value
     * @param int   $default Default value if out of range
     * @return int Sanitized numeric value
     */
    public static function sanitize_numeric_range( $value, $min = 1, $max = 100, $default = 1 ) {
        $value = absint( $value );

        if ( $value < $min || $value > $max ) {
            return $default;
        }

        return $value;
    }

    /**
     * Sanitize array of IDs
     *
     * @since 1.0.0
     * @param array $ids Array of IDs to sanitize
     * @return array Sanitized array of IDs
     */
    public static function sanitize_id_array( $ids ) {
        if ( ! is_array( $ids ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $ids as $id ) {
            $clean_id = absint( $id );
            if ( $clean_id > 0 ) {
                $sanitized[] = $clean_id;
            }
        }

        return array_unique( $sanitized );
    }

    /**
     * Sanitize boolean value
     *
     * @since 1.0.0
     * @param mixed $value Value to sanitize as boolean
     * @return bool Sanitized boolean value
     */
    public static function sanitize_boolean( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_string( $value ) ) {
            $value = strtolower( trim( $value ) );
            return in_array( $value, array( 'true', '1', 'yes', 'on' ), true );
        }

        return (bool) $value;
    }

    /**
     * Deep sanitize array recursively
     *
     * @since 1.0.0
     * @param array  $data Array to sanitize
     * @param string $type Type of sanitization to apply to leaf values
     * @return array Sanitized array
     */
    public static function deep_sanitize_array( $data, $type = 'text' ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $data as $key => $value ) {
            // Sanitize key
            $clean_key = sanitize_key( $key );

            if ( is_array( $value ) ) {
                // Recursively sanitize nested arrays
                $sanitized[ $clean_key ] = self::deep_sanitize_array( $value, $type );
            } else {
                // Sanitize leaf value based on type
                switch ( $type ) {
                    case 'email':
                        $sanitized[ $clean_key ] = sanitize_email( $value );
                        break;

                    case 'url':
                        $sanitized[ $clean_key ] = esc_url_raw( $value );
                        break;

                    case 'int':
                        $sanitized[ $clean_key ] = absint( $value );
                        break;

                    case 'textarea':
                        $sanitized[ $clean_key ] = sanitize_textarea_field( $value );
                        break;

                    case 'text':
                    default:
                        $sanitized[ $clean_key ] = sanitize_text_field( $value );
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize form data based on field types
     *
     * @since 1.0.0
     * @param array $data       Form data to sanitize
     * @param array $field_types Array mapping field names to sanitization types
     * @return array Sanitized form data
     */
    public static function sanitize_form_data( $data, $field_types = array() ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $data as $field => $value ) {
            $type = isset( $field_types[ $field ] ) ? $field_types[ $field ] : 'text';

            switch ( $type ) {
                case 'license_key':
                    $sanitized[ $field ] = self::sanitize_license_key( $value );
                    break;

                case 'provider_name':
                    $sanitized[ $field ] = self::sanitize_provider_name( $value );
                    break;

                case 'account_login':
                    $sanitized[ $field ] = self::sanitize_account_login( $value );
                    break;

                case 'credentials':
                    $sanitized[ $field ] = self::sanitize_credentials( $value );
                    break;

                case 'device_name':
                    $sanitized[ $field ] = self::sanitize_device_name( $value );
                    break;

                case 'ip':
                    $sanitized[ $field ] = self::sanitize_ip_address( $value );
                    break;

                case 'email':
                    $sanitized[ $field ] = sanitize_email( $value );
                    break;

                case 'url':
                    $sanitized[ $field ] = esc_url_raw( $value );
                    break;

                case 'int':
                    $sanitized[ $field ] = absint( $value );
                    break;

                case 'textarea':
                    $sanitized[ $field ] = sanitize_textarea_field( $value );
                    break;

                case 'json':
                    $sanitized[ $field ] = self::sanitize_json( $value );
                    break;

                case 'boolean':
                    $sanitized[ $field ] = self::sanitize_boolean( $value );
                    break;

                case 'text':
                default:
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    break;
            }
        }

        return $sanitized;
    }
}