<?php
/**
 * Validator Utility Class
 *
 * Provides data validation methods for various plugin data types.
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
 * Validator Class
 *
 * Provides comprehensive validation methods for plugin data.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/utils
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Validator {

    /**
     * Validate license key format
     *
     * @since 1.0.0
     * @param string $license_key License key to validate
     * @return bool True if valid format
     */
    public static function is_valid_license_key( $license_key ) {
        if ( empty( $license_key ) || ! is_string( $license_key ) ) {
            return false;
        }

        // Remove any whitespace
        $license_key = trim( $license_key );

        // Check format: XXXX-XXXX-XXXX-XXXX (19 characters total)
        $pattern = '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';

        return preg_match( $pattern, strtoupper( $license_key ) );
    }

    /**
     * Validate email address
     *
     * @since 1.0.0
     * @param string $email Email address to validate
     * @return bool True if valid email
     */
    public static function is_valid_email( $email ) {
        return ! empty( $email ) && is_email( $email );
    }

    /**
     * Validate URL
     *
     * @since 1.0.0
     * @param string $url URL to validate
     * @return bool True if valid URL
     */
    public static function is_valid_url( $url ) {
        return ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Validate IP address
     *
     * @since 1.0.0
     * @param string $ip IP address to validate
     * @return bool True if valid IP address
     */
    public static function is_valid_ip( $ip ) {
        return ! empty( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
    }

    /**
     * Validate device fingerprint
     *
     * @since 1.0.0
     * @param string $fingerprint Device fingerprint to validate
     * @return bool True if valid fingerprint
     */
    public static function is_valid_device_fingerprint( $fingerprint ) {
        if ( empty( $fingerprint ) || ! is_string( $fingerprint ) ) {
            return false;
        }

        // Should be a 64-character hexadecimal string (SHA256)
        return preg_match( '/^[a-f0-9]{64}$/i', $fingerprint );
    }

    /**
     * Validate device token
     *
     * @since 1.0.0
     * @param string $token Device token to validate
     * @return bool True if valid token
     */
    public static function is_valid_device_token( $token ) {
        if ( empty( $token ) || ! is_string( $token ) ) {
            return false;
        }

        // Should be a 20-character alphanumeric string
        return preg_match( '/^[A-Z0-9]{20}$/', strtoupper( $token ) );
    }

    /**
     * Validate product ID
     *
     * @since 1.0.0
     * @param mixed $product_id Product ID to validate
     * @return bool True if valid product ID
     */
    public static function is_valid_product_id( $product_id ) {
        if ( empty( $product_id ) ) {
            return false;
        }

        $product_id = absint( $product_id );

        return $product_id > 0 && get_post_type( $product_id ) === 'product';
    }

    /**
     * Validate pool capacity
     *
     * @since 1.0.0
     * @param mixed $capacity Pool capacity to validate
     * @return bool True if valid capacity
     */
    public static function is_valid_pool_capacity( $capacity ) {
        $capacity = absint( $capacity );

        return $capacity >= 1 && $capacity <= 1000;
    }

    /**
     * Validate provider name
     *
     * @since 1.0.0
     * @param string $provider Provider name to validate
     * @return bool True if valid provider name
     */
    public static function is_valid_provider_name( $provider ) {
        if ( empty( $provider ) || ! is_string( $provider ) ) {
            return false;
        }

        $provider = trim( $provider );

        // 2-50 characters, alphanumeric and spaces only
        return preg_match( '/^[A-Za-z0-9\s]{2,50}$/', $provider );
    }

    /**
     * Validate account login
     *
     * @since 1.0.0
     * @param string $login Account login to validate
     * @return bool True if valid login
     */
    public static function is_valid_account_login( $login ) {
        if ( empty( $login ) || ! is_string( $login ) ) {
            return false;
        }

        $login = trim( $login );

        // Could be email or username, 3-255 characters
        return strlen( $login ) >= 3 && strlen( $login ) <= 255;
    }

    /**
     * Validate JSON string
     *
     * @since 1.0.0
     * @param string $json JSON string to validate
     * @return bool True if valid JSON
     */
    public static function is_valid_json( $json ) {
        if ( empty( $json ) || ! is_string( $json ) ) {
            return false;
        }

        json_decode( $json );

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate date string
     *
     * @since 1.0.0
     * @param string $date Date string to validate
     * @param string $format Expected date format
     * @return bool True if valid date
     */
    public static function is_valid_date( $date, $format = 'Y-m-d H:i:s' ) {
        if ( empty( $date ) || ! is_string( $date ) ) {
            return false;
        }

        $datetime = DateTime::createFromFormat( $format, $date );

        return $datetime !== false && $datetime->format( $format ) === $date;
    }

    /**
     * Validate status value
     *
     * @since 1.0.0
     * @param string $status Status to validate
     * @param array  $allowed_statuses Array of allowed status values
     * @return bool True if valid status
     */
    public static function is_valid_status( $status, $allowed_statuses = array() ) {
        if ( empty( $status ) || ! is_string( $status ) ) {
            return false;
        }

        if ( empty( $allowed_statuses ) ) {
            // Default allowed statuses
            $allowed_statuses = array( 'active', 'inactive', 'expired', 'suspended', 'blocked' );
        }

        return in_array( strtolower( $status ), array_map( 'strtolower', $allowed_statuses ), true );
    }

    /**
     * Validate device limits
     *
     * @since 1.0.0
     * @param mixed $limit Device limit to validate
     * @return bool True if valid limit
     */
    public static function is_valid_device_limit( $limit ) {
        $limit = absint( $limit );

        return $limit >= 1 && $limit <= 10;
    }

    /**
     * Validate rate limit
     *
     * @since 1.0.0
     * @param mixed $limit Rate limit to validate
     * @return bool True if valid rate limit
     */
    public static function is_valid_rate_limit( $limit ) {
        $limit = absint( $limit );

        return $limit >= 1 && $limit <= 1000;
    }

    /**
     * Validate time window (in seconds)
     *
     * @since 1.0.0
     * @param mixed $window Time window to validate
     * @return bool True if valid time window
     */
    public static function is_valid_time_window( $window ) {
        $window = absint( $window );

        // Between 60 seconds (1 minute) and 86400 seconds (24 hours)
        return $window >= 60 && $window <= 86400;
    }

    /**
     * Validate credentials array
     *
     * @since 1.0.0
     * @param array $credentials Credentials array to validate
     * @return bool True if valid credentials structure
     */
    public static function is_valid_credentials( $credentials ) {
        if ( ! is_array( $credentials ) || empty( $credentials ) ) {
            return false;
        }

        // Check that all values are strings
        foreach ( $credentials as $key => $value ) {
            if ( ! is_string( $key ) || ! is_string( $value ) ) {
                return false;
            }

            // Key should be reasonable length
            if ( strlen( $key ) > 50 ) {
                return false;
            }

            // Value should not be empty and reasonable length
            if ( empty( $value ) || strlen( $value ) > 1000 ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate user agent string
     *
     * @since 1.0.0
     * @param string $user_agent User agent to validate
     * @return bool True if valid user agent
     */
    public static function is_valid_user_agent( $user_agent ) {
        if ( empty( $user_agent ) || ! is_string( $user_agent ) ) {
            return false;
        }

        $user_agent = trim( $user_agent );

        // Should be between 10 and 500 characters
        return strlen( $user_agent ) >= 10 && strlen( $user_agent ) <= 500;
    }

    /**
     * Validate device name
     *
     * @since 1.0.0
     * @param string $device_name Device name to validate
     * @return bool True if valid device name
     */
    public static function is_valid_device_name( $device_name ) {
        if ( empty( $device_name ) || ! is_string( $device_name ) ) {
            return false;
        }

        $device_name = trim( $device_name );

        // 1-100 characters, printable characters only
        return strlen( $device_name ) >= 1 &&
               strlen( $device_name ) <= 100 &&
               ctype_print( $device_name );
    }

    /**
     * Validate multiple values at once
     *
     * @since 1.0.0
     * @param array $data Array of data to validate
     * @param array $rules Array of validation rules
     * @return array Array of validation results
     */
    public static function validate_multiple( $data, $rules ) {
        $results = array(
            'valid' => true,
            'errors' => array(),
        );

        foreach ( $rules as $field => $rule ) {
            $value = isset( $data[ $field ] ) ? $data[ $field ] : null;
            $valid = false;

            switch ( $rule ) {
                case 'license_key':
                    $valid = self::is_valid_license_key( $value );
                    break;

                case 'email':
                    $valid = self::is_valid_email( $value );
                    break;

                case 'url':
                    $valid = self::is_valid_url( $value );
                    break;

                case 'ip':
                    $valid = self::is_valid_ip( $value );
                    break;

                case 'product_id':
                    $valid = self::is_valid_product_id( $value );
                    break;

                case 'provider_name':
                    $valid = self::is_valid_provider_name( $value );
                    break;

                case 'account_login':
                    $valid = self::is_valid_account_login( $value );
                    break;

                case 'required':
                    $valid = ! empty( $value );
                    break;

                default:
                    $valid = true; // Unknown rule, assume valid
                    break;
            }

            if ( ! $valid ) {
                $results['valid'] = false;
                $results['errors'][ $field ] = sprintf(
                    'Invalid %s: %s',
                    $field,
                    is_scalar( $value ) ? $value : gettype( $value )
                );
            }
        }

        return $results;
    }

    /**
     * Sanitize and validate input data
     *
     * @since 1.0.0
     * @param mixed  $value Input value to sanitize and validate
     * @param string $type  Type of sanitization/validation to apply
     * @return mixed Sanitized value or false if invalid
     */
    public static function sanitize_and_validate( $value, $type ) {
        switch ( $type ) {
            case 'license_key':
                $value = sanitize_text_field( $value );
                return self::is_valid_license_key( $value ) ? strtoupper( $value ) : false;

            case 'email':
                $value = sanitize_email( $value );
                return self::is_valid_email( $value ) ? $value : false;

            case 'url':
                $value = esc_url_raw( $value );
                return self::is_valid_url( $value ) ? $value : false;

            case 'ip':
                $value = sanitize_text_field( $value );
                return self::is_valid_ip( $value ) ? $value : false;

            case 'int':
                return absint( $value );

            case 'text':
                return sanitize_text_field( $value );

            case 'textarea':
                return sanitize_textarea_field( $value );

            case 'json':
                $value = sanitize_textarea_field( $value );
                return self::is_valid_json( $value ) ? $value : false;

            default:
                return sanitize_text_field( $value );
        }
    }
}