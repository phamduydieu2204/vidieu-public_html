<?php
/**
 * Encryption Service
 *
 * Handles encryption and decryption of sensitive data using AES-256-CBC.
 * Used for encrypting account passwords, API keys, and other credentials.
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
 * Encryption Service Class
 *
 * Provides secure encryption and decryption functionality for the plugin.
 * Uses AES-256-CBC encryption with the key defined in VD_ENCRYPTION_KEY constant.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Encryption_Service {

    /**
     * Encryption method
     *
     * @since  1.0.0
     * @access private
     * @var    string $method The encryption method to use
     */
    private static $method = 'AES-256-CBC';

    /**
     * Encrypt a string value
     *
     * @since  1.0.0
     * @param  string $value The string to encrypt
     * @return string|false  The encrypted string or false on failure
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        // Check if OpenSSL is available
        if ( ! extension_loaded( 'openssl' ) ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: OpenSSL extension is required for encryption' );
            }
            return false;
        }

        // Check if encryption key is defined
        if ( ! defined( 'VD_ENCRYPTION_KEY' ) || empty( VD_ENCRYPTION_KEY ) ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: VD_ENCRYPTION_KEY constant is not defined' );
            }
            return false;
        }

        try {
            // Get the encryption key
            $key = self::get_encryption_key();
            if ( ! $key ) {
                return false;
            }

            // Generate a random IV
            $iv_length = openssl_cipher_iv_length( self::$method );
            $iv = openssl_random_pseudo_bytes( $iv_length );

            // Encrypt the data
            $encrypted = openssl_encrypt( $value, self::$method, $key, 0, $iv );

            if ( false === $encrypted ) {
                if ( function_exists( 'error_log' ) ) {
                    error_log( 'VD License Manager: Encryption failed - ' . openssl_error_string() );
                }
                return false;
            }

            // Combine IV and encrypted data, then base64 encode
            return base64_encode( $encrypted . '::' . $iv );

        } catch ( Exception $e ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: Encryption exception - ' . $e->getMessage() );
            }
            return false;
        }
    }

    /**
     * Decrypt a string value
     *
     * @since  1.0.0
     * @param  string $value The encrypted string to decrypt
     * @return string|false  The decrypted string or false on failure
     */
    public static function decrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        // Check if OpenSSL is available
        if ( ! extension_loaded( 'openssl' ) ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: OpenSSL extension is required for decryption' );
            }
            return false;
        }

        // Check if encryption key is defined
        if ( ! defined( 'VD_ENCRYPTION_KEY' ) || empty( VD_ENCRYPTION_KEY ) ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: VD_ENCRYPTION_KEY constant is not defined' );
            }
            return false;
        }

        try {
            // Get the encryption key
            $key = self::get_encryption_key();
            if ( ! $key ) {
                return false;
            }

            // Base64 decode the value
            $data = base64_decode( $value, true );
            if ( false === $data ) {
                return false;
            }

            // Split the encrypted data and IV
            $parts = explode( '::', $data, 2 );
            if ( count( $parts ) !== 2 ) {
                return false;
            }

            list( $encrypted_data, $iv ) = $parts;

            // Decrypt the data
            $decrypted = openssl_decrypt( $encrypted_data, self::$method, $key, 0, $iv );

            if ( false === $decrypted ) {
                if ( function_exists( 'error_log' ) ) {
                    error_log( 'VD License Manager: Decryption failed - ' . openssl_error_string() );
                }
                return false;
            }

            return $decrypted;

        } catch ( Exception $e ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: Decryption exception - ' . $e->getMessage() );
            }
            return false;
        }
    }

    /**
     * Get the encryption key from the constant
     *
     * @since  1.0.0
     * @access private
     * @return string|false The encryption key or false on failure
     */
    private static function get_encryption_key() {
        $key_string = VD_ENCRYPTION_KEY;

        // Check if the key is base64 encoded (starts with 'base64:')
        if ( strpos( $key_string, 'base64:' ) === 0 ) {
            $key = base64_decode( substr( $key_string, 7 ), true );
            if ( false === $key ) {
                if ( function_exists( 'error_log' ) ) {
                    error_log( 'VD License Manager: Invalid base64 encryption key' );
                }
                return false;
            }
        } else {
            $key = $key_string;
        }

        // Validate key length (AES-256 requires 32 bytes)
        if ( strlen( $key ) !== 32 ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'VD License Manager: Encryption key must be 32 bytes for AES-256' );
            }
            return false;
        }

        return $key;
    }

    /**
     * Generate a new encryption key
     *
     * This is a utility method for generating new encryption keys.
     * The generated key should be added to wp-config.php.
     *
     * @since  1.0.0
     * @return string The base64 encoded encryption key
     */
    public static function generate_key() {
        if ( ! extension_loaded( 'openssl' ) ) {
            return false;
        }

        $key = openssl_random_pseudo_bytes( 32 );
        return 'base64:' . base64_encode( $key );
    }

    /**
     * Check if encryption is properly configured
     *
     * @since  1.0.0
     * @return bool True if encryption is properly configured
     */
    public static function is_configured() {
        // Check if OpenSSL is available
        if ( ! extension_loaded( 'openssl' ) ) {
            return false;
        }

        // Check if encryption key is defined
        if ( ! defined( 'VD_ENCRYPTION_KEY' ) || empty( VD_ENCRYPTION_KEY ) ) {
            return false;
        }

        // Check if key is valid
        $key = self::get_encryption_key();
        return false !== $key;
    }

    /**
     * Test encryption/decryption functionality
     *
     * @since  1.0.0
     * @return bool True if encryption is working properly
     */
    public static function test() {
        if ( ! self::is_configured() ) {
            return false;
        }

        $test_string = 'VD License Manager Test String ' . wp_generate_password( 16, false );
        $encrypted = self::encrypt( $test_string );

        if ( false === $encrypted ) {
            return false;
        }

        $decrypted = self::decrypt( $encrypted );

        return $test_string === $decrypted;
    }
}