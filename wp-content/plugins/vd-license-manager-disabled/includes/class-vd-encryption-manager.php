<?php
defined('ABSPATH') || exit;

class VD_Encryption_Manager {

    private static $instance = null;
    private $encryption_key = null;
    private $algorithm = 'aes-256-gcm';

    private function __construct() {
        $this->init_encryption_key();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_encryption_key() {
        if (!defined('VD_ENCRYPTION_KEY')) {
            throw new Exception('VD_ENCRYPTION_KEY must be defined in wp-config.php');
        }

        $key = VD_ENCRYPTION_KEY;

        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        if (strlen($key) !== 32) {
            throw new Exception('Encryption key must be exactly 32 bytes (256 bits)');
        }

        $this->encryption_key = $key;
    }

    public function encrypt($plaintext) {
        if (empty($plaintext)) {
            return '';
        }

        try {
            $iv = random_bytes(12);
            $tag = '';

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

            return base64_encode($iv . $tag . $ciphertext);

        } catch (Exception $e) {
            error_log('VD License Manager - Encryption error: ' . $e->getMessage());
            throw new Exception('Encryption failed');
        }
    }

    public function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        try {
            $data = base64_decode($encrypted_data);

            if ($data === false || strlen($data) < 28) {
                throw new Exception('Invalid encrypted data format');
            }

            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $ciphertext = substr($data, 28);

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

    public function encrypt_provider_credentials($credentials) {
        if (!is_array($credentials)) {
            return array();
        }

        $encrypted = array();
        $sensitive_fields = array('email', 'password', 'cookies', 'two_factor_secret');

        foreach ($credentials as $field => $value) {
            if (in_array($field, $sensitive_fields) && !empty($value)) {
                $encrypted[$field] = $this->encrypt($value);
            } else {
                $encrypted[$field] = $value;
            }
        }

        return $encrypted;
    }

    public function decrypt_provider_credentials($encrypted_credentials) {
        if (!is_array($encrypted_credentials)) {
            return array();
        }

        $decrypted = array();
        $sensitive_fields = array('email', 'password', 'cookies', 'two_factor_secret');

        foreach ($encrypted_credentials as $field => $value) {
            if (in_array($field, $sensitive_fields) && !empty($value)) {
                try {
                    $decrypted[$field] = $this->decrypt($value);
                } catch (Exception $e) {
                    error_log("VD License Manager - Failed to decrypt field '$field': " . $e->getMessage());
                    $decrypted[$field] = '';
                }
            } else {
                $decrypted[$field] = $value;
            }
        }

        return $decrypted;
    }

    public function hash_device_fingerprint($fingerprint) {
        return hash('sha256', $fingerprint . $this->encryption_key);
    }

    public function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length));
    }

    public function verify_integrity($data, $stored_hash) {
        $calculated_hash = hash_hmac('sha256', $data, $this->encryption_key);
        return hash_equals($stored_hash, $calculated_hash);
    }

    public function create_integrity_hash($data) {
        return hash_hmac('sha256', $data, $this->encryption_key);
    }

    public function is_encrypted_value($value) {
        if (empty($value) || !is_string($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) >= 28;
    }

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

    public function test_encryption() {
        try {
            $test_data = 'VD License Manager Test Data ' . time();
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
}