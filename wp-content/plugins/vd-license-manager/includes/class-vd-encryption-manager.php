<?php
/**
 * VD License Manager - Encryption Manager
 *
 * Handles AES-256-GCM encryption for sensitive data
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * VD_Encryption_Manager class
 *
 * Simple encryption utility class for Sprint 2.1
 */
class VD_Encryption_Manager {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Encryption key
     */
    private $encryption_key = null;

    /**
     * Algorithm
     */
    private $algorithm = 'aes-256-gcm';

    /**
     * Private constructor
     */
    private function __construct() {
        $this->init_encryption_key();
    }

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize encryption key
     */
    private function init_encryption_key() {
        if (!defined('VD_ENCRYPTION_KEY')) {
            throw new Exception('VD_ENCRYPTION_KEY must be defined in wp-config.php');
        }

        $key = VD_ENCRYPTION_KEY;

        // Handle base64 encoded keys
        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        // Validate key length
        if (strlen($key) !== 32) {
            throw new Exception('Encryption key must be exactly 32 bytes (256 bits)');
        }

        $this->encryption_key = $key;
    }

    /**
     * Encrypt data
     */
    public function encrypt($plaintext) {
        if (empty($plaintext)) {
            return '';
        }

        try {
            // Generate random IV for GCM
            $iv = random_bytes(12);
            $tag = '';

            // Encrypt using AES-256-GCM
            $ciphertext = openssl_encrypt(
                $plaintext,
                $this->algorithm,
                $this->encryption_key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            // Return base64 encoded: IV + TAG + CIPHERTEXT
            return base64_encode($iv . $tag . $ciphertext);

        } catch (Exception $e) {
            error_log('VD License Manager - Encryption error: ' . $e->getMessage());
            throw new Exception('Encryption failed');
        }
    }

    /**
     * Decrypt data
     */
    public function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        try {
            // Decode from base64
            $data = base64_decode($encrypted_data);

            if ($data === false || strlen($data) < 28) {
                throw new Exception('Invalid encrypted data format');
            }

            // Extract components: IV (12) + TAG (16) + CIPHERTEXT
            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $ciphertext = substr($data, 28);

            // Decrypt using AES-256-GCM
            $plaintext = openssl_decrypt(
                $ciphertext,
                $this->algorithm,
                $this->encryption_key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }

            return $plaintext;

        } catch (Exception $e) {
            error_log('VD License Manager - Decryption error: ' . $e->getMessage());
            throw new Exception('Decryption failed');
        }
    }

    /**
     * Test encryption functionality
     */
    public function test_encryption() {
        try {
            $test_data = 'VD License Manager Test - ' . time();
            $encrypted = $this->encrypt($test_data);
            $decrypted = $this->decrypt($encrypted);

            return array(
                'success' => ($test_data === $decrypted),
                'original' => $test_data,
                'encrypted' => $encrypted,
                'decrypted' => $decrypted,
                'length_original' => strlen($test_data),
                'length_encrypted' => strlen($encrypted)
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Validate encryption requirements
     */
    public function validate_encryption_requirements() {
        $requirements = array(
            'openssl_extension' => extension_loaded('openssl'),
            'algorithm_support' => in_array($this->algorithm, openssl_get_cipher_methods()),
            'key_configured' => defined('VD_ENCRYPTION_KEY'),
            'key_valid' => false,
            'random_bytes' => function_exists('random_bytes')
        );

        if ($requirements['key_configured']) {
            try {
                $this->init_encryption_key();
                $requirements['key_valid'] = true;
            } catch (Exception $e) {
                $requirements['key_error'] = $e->getMessage();
            }
        }

        return $requirements;
    }
}