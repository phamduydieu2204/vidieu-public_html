<?php
/**
 * Encryption Service
 *
 * Handles all encryption/decryption operations for sensitive data
 * Uses AES-256-CBC with proper IV and key handling
 *
 * SECURITY NOTES:
 * - Cipher: AES-256-CBC (industry standard)
 * - IV: 16 bytes (required by cipher)
 * - Key: 32 bytes (256 bits)
 * - Format: base64(IV + encrypted_data)
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VD_LM_Encryption_Service {

    /**
     * Cipher method
     * AES-256-CBC is the industry standard for symmetric encryption
     */
    const CIPHER = 'aes-256-cbc';

    /**
     * Encryption key
     *
     * @var string Binary string of exactly 32 bytes
     */
    private $key;

    /**
     * Constructor
     * Initializes the encryption key
     */
    public function __construct() {
        $this->key = $this->get_encryption_key();
    }

    /**
     * Get or create encryption key
     *
     * Creates a new key if one doesn't exist.
     * Key is stored in wp_options table.
     *
     * @return string Binary encryption key (32 bytes)
     */
    private function get_encryption_key() {
        $key_option = get_option('vd_lm_encryption_key');

        if (!$key_option) {
            // Generate a new 32-byte (256-bit) random key
            $new_key = bin2hex(random_bytes(32)); // 64 hex chars
            update_option('vd_lm_encryption_key', $new_key, false); // Don't autoload
            error_log('VD Encryption: Generated new encryption key');
            $key_option = $new_key;
        }

        // Convert hex to binary and ensure exactly 32 bytes
        // Use SHA256 hash to guarantee 32-byte output
        $key = substr(hash('sha256', $key_option, true), 0, 32);

        return $key;
    }

    /**
     * Encrypt a value
     *
     * Process:
     * 1. Generate random 16-byte IV
     * 2. Encrypt data using AES-256-CBC
     * 3. Prepend IV to encrypted data
     * 4. Base64 encode for storage
     *
     * @param string $value Plain text value to encrypt
     * @return string Base64 encoded encrypted value, or empty string on failure
     */
    public function encrypt($value) {
        // Empty values don't need encryption
        if (empty($value)) {
            return '';
        }

        try {
            // Step 1: Generate IV
            // AES-256-CBC requires exactly 16 bytes
            $iv_length = openssl_cipher_iv_length(self::CIPHER);
            $iv = openssl_random_pseudo_bytes($iv_length);

            // Verify IV was generated correctly
            if ($iv === false || strlen($iv) !== $iv_length) {
                error_log('VD Encryption ERROR: Failed to generate IV (expected ' . $iv_length . ' bytes)');
                return '';
            }

            // Step 2: Encrypt the data
            $encrypted = openssl_encrypt(
                $value,                 // Data to encrypt
                self::CIPHER,           // Cipher method
                $this->key,             // Encryption key
                OPENSSL_RAW_DATA,       // Return raw binary (not base64)
                $iv                     // Initialization vector
            );

            // Check encryption success
            if ($encrypted === false) {
                $error = openssl_error_string();
                error_log('VD Encryption ERROR: Encryption failed - ' . $error);
                return '';
            }

            // Step 3: Combine IV + encrypted data
            // Format: [16 bytes IV][encrypted data]
            $combined = $iv . $encrypted;

            // Step 4: Base64 encode for safe storage in database
            $result = base64_encode($combined);

            // Debug logging
            error_log(sprintf(
                'VD Encryption: Successfully encrypted %d bytes → %d bytes (IV: %d bytes)',
                strlen($value),
                strlen($result),
                $iv_length
            ));

            return $result;

        } catch (Exception $e) {
            error_log('VD Encryption EXCEPTION: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Decrypt a value
     *
     * Process:
     * 1. Base64 decode the stored value
     * 2. Extract IV from first 16 bytes
     * 3. Extract encrypted data from remaining bytes
     * 4. Decrypt using AES-256-CBC
     *
     * @param string $encrypted Base64 encoded encrypted value
     * @return string Decrypted plain text, or empty string on failure
     */
    public function decrypt($encrypted) {
        // Empty values don't need decryption
        if (empty($encrypted)) {
            return '';
        }

        try {
            // Step 1: Decode from base64
            $data = base64_decode($encrypted, true); // Strict mode

            if ($data === false) {
                error_log('VD Decryption ERROR: Invalid base64 encoding');
                return '';
            }

            // Step 2: Get expected IV length
            $iv_length = openssl_cipher_iv_length(self::CIPHER);

            // Verify data is long enough to contain IV + some encrypted data
            if (strlen($data) < $iv_length + 1) {
                error_log(sprintf(
                    'VD Decryption ERROR: Data too short (%d bytes), expected at least %d bytes',
                    strlen($data),
                    $iv_length + 1
                ));
                return '';
            }

            // Step 3: Extract IV (first 16 bytes)
            $iv = substr($data, 0, $iv_length);

            // Step 4: Extract encrypted data (remaining bytes)
            $encrypted_data = substr($data, $iv_length);

            // Double-check IV length
            if (strlen($iv) !== $iv_length) {
                error_log(sprintf(
                    'VD Decryption ERROR: IV length mismatch - got %d bytes, expected %d bytes',
                    strlen($iv),
                    $iv_length
                ));
                return '';
            }

            // Step 5: Decrypt the data
            $decrypted = openssl_decrypt(
                $encrypted_data,        // Encrypted data
                self::CIPHER,           // Cipher method
                $this->key,             // Decryption key (same as encryption)
                OPENSSL_RAW_DATA,       // Input is raw binary
                $iv                     // Initialization vector
            );

            // Check decryption success
            if ($decrypted === false) {
                $error = openssl_error_string();
                error_log('VD Decryption ERROR: Decryption failed - ' . $error);
                return '';
            }

            // Debug logging
            error_log(sprintf(
                'VD Decryption: Successfully decrypted %d bytes → %d bytes',
                strlen($encrypted),
                strlen($decrypted)
            ));

            return $decrypted;

        } catch (Exception $e) {
            error_log('VD Decryption EXCEPTION: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Encrypt multiple fields in an array
     *
     * Useful for batch encryption of account data
     *
     * @param array $data Data array with fields to encrypt
     * @param array $fields List of field names to encrypt
     * @return array Data array with encrypted fields
     */
    public function encrypt_fields($data, $fields) {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $encrypted = $this->encrypt($data[$field]);
                if ($encrypted !== '') {
                    $data[$field] = $encrypted;
                } else {
                    error_log('VD Encryption: Failed to encrypt field: ' . $field);
                    // Keep original value on encryption failure
                }
            }
        }
        return $data;
    }

    /**
     * Decrypt multiple fields in an array
     *
     * Useful for batch decryption when loading account data
     *
     * @param array $data Data array with encrypted fields
     * @param array $fields List of field names to decrypt
     * @return array Data array with decrypted fields
     */
    public function decrypt_fields($data, $fields) {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $decrypted = $this->decrypt($data[$field]);
                if ($decrypted !== '') {
                    $data[$field] = $decrypted;
                } else {
                    error_log('VD Decryption: Failed to decrypt field: ' . $field);
                    // Set to empty on decryption failure
                    $data[$field] = '';
                }
            }
        }
        return $data;
    }

    /**
     * Check if a value appears to be encrypted
     *
     * Performs basic validation:
     * - Is valid base64
     * - Is long enough to contain IV + data
     *
     * @param string $value Value to check
     * @return bool True if value appears encrypted
     */
    public function is_encrypted($value) {
        if (empty($value)) {
            return false;
        }

        // Check if it's valid base64
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        // Check if it's long enough to contain IV + some data
        $iv_length = openssl_cipher_iv_length(self::CIPHER);
        return strlen($decoded) > $iv_length;
    }

    /**
     * Get encryption key info (for debugging)
     *
     * @return array Key information
     */
    public function get_key_info() {
        return array(
            'cipher' => self::CIPHER,
            'iv_length' => openssl_cipher_iv_length(self::CIPHER),
            'key_length' => strlen($this->key),
            'key_hash' => substr(hash('sha256', $this->key), 0, 16) . '...' // First 16 chars of hash only
        );
    }

    /**
     * Check if encryption is properly configured
     *
     * @return bool True if encryption is ready to use
     */
    public static function is_configured() {
        try {
            // Check if OpenSSL is available
            if (!function_exists('openssl_encrypt')) {
                return false;
            }

            // Check if cipher is supported
            if (!in_array('aes-256-cbc', openssl_get_cipher_methods())) {
                return false;
            }

            // Check if we can get IV length
            $iv_length = openssl_cipher_iv_length('aes-256-cbc');
            if ($iv_length !== 16) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Test encryption functionality
     *
     * @return bool True if encryption test passes
     */
    public static function test() {
        try {
            $service = new self();

            $test_value = 'test_encryption_' . time();
            $encrypted = $service->encrypt($test_value);
            $decrypted = $service->decrypt($encrypted);

            return ($decrypted === $test_value);

        } catch (Exception $e) {
            error_log('VD Encryption Test Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Static encrypt method for backward compatibility
     */
    public static function encrypt_static($value) {
        $service = new self();
        return $service->encrypt($value);
    }

    /**
     * Static decrypt method for backward compatibility
     */
    public static function decrypt_static($value) {
        $service = new self();
        return $service->decrypt($value);
    }
}