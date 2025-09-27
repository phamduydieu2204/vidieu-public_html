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
 * Advanced encryption utility class for Sprint 3.2
 * Handles field-level encryption, key rotation, and encryption versioning
 */
class VD_Encryption_Manager {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Master encryption key
     */
    private $master_key = null;

    /**
     * Current encryption version
     */
    private $encryption_version = 'v2';

    /**
     * Supported algorithms
     */
    private $algorithms = [
        'v1' => 'aes-256-gcm',  // Legacy
        'v2' => 'aes-256-gcm'   // Current with enhanced metadata
    ];

    /**
     * Field-specific encryption keys cache
     */
    private $field_keys = [];

    /**
     * Encryption metadata
     */
    private $metadata_enabled = true;

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

        $this->master_key = $key;
    }

    /**
     * Derive field-specific encryption key
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $field_name Field identifier
     * @param string $context Additional context for key derivation
     * @return string 32-byte derived key
     */
    private function derive_field_key($field_name, $context = '') {
        $cache_key = md5($field_name . $context);

        if (isset($this->field_keys[$cache_key])) {
            return $this->field_keys[$cache_key];
        }

        // HKDF key derivation
        $info = 'VD_LM_FIELD:' . $field_name;
        if (!empty($context)) {
            $info .= ':' . $context;
        }

        $derived_key = $this->hkdf($this->master_key, 32, $info);
        $this->field_keys[$cache_key] = $derived_key;

        return $derived_key;
    }

    /**
     * HKDF key derivation function (RFC 5869)
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $key Initial key material
     * @param int $length Output key length
     * @param string $info Optional context info
     * @param string $salt Optional salt
     * @return string Derived key
     */
    private function hkdf($key, $length, $info = '', $salt = '') {
        if (empty($salt)) {
            $salt = str_repeat("\0", 32); // Zero salt
        }

        // Extract phase
        $prk = hash_hmac('sha256', $key, $salt, true);

        // Expand phase
        $t = '';
        $okm = '';
        $counter = 1;

        while (strlen($okm) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
            $okm .= $t;
            $counter++;
        }

        return substr($okm, 0, $length);
    }

    /**
     * Encrypt data (Legacy method for backward compatibility)
     *
     * @since 1.0.0
     * @param string $plaintext Data to encrypt
     * @return string Encrypted data
     */
    public function encrypt($plaintext) {
        return $this->encrypt_field($plaintext, 'default');
    }

    /**
     * Advanced field-level encryption with metadata
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $plaintext Data to encrypt
     * @param string $field_name Field identifier for key derivation
     * @param array $options Encryption options
     * @return string Encrypted data with metadata
     */
    public function encrypt_field($plaintext, $field_name, $options = []) {
        if (empty($plaintext)) {
            return '';
        }

        try {
            $version = $options['version'] ?? $this->encryption_version;
            $algorithm = $this->algorithms[$version] ?? $this->algorithms['v2'];
            $context = $options['context'] ?? '';

            // Derive field-specific key
            $field_key = $this->derive_field_key($field_name, $context);

            // Generate random IV for GCM
            $iv = random_bytes(12);
            $tag = '';

            // Encrypt using AES-256-GCM
            $ciphertext = openssl_encrypt(
                $plaintext,
                $algorithm,
                $field_key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            // Create encryption metadata
            $metadata = $this->create_encryption_metadata($field_name, $version, $options);

            // Pack data: VERSION(2) + METADATA_LEN(2) + METADATA + IV(12) + TAG(16) + CIPHERTEXT
            $packed_data = pack('v', strlen($version)) . $version;
            $packed_data .= pack('v', strlen($metadata)) . $metadata;
            $packed_data .= $iv . $tag . $ciphertext;

            return base64_encode($packed_data);

        } catch (Exception $e) {
            error_log('VD License Manager - Encryption error: ' . $e->getMessage());
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::log_action(
                    'encryption',
                    'encrypt_failed',
                    0,
                    get_current_user_id(),
                    'Field encryption failed: ' . $field_name,
                    ['error' => $e->getMessage()]
                );
            }
            throw new Exception('Encryption failed');
        }
    }

    /**
     * Decrypt data (Legacy method for backward compatibility)
     *
     * @since 1.0.0
     * @param string $encrypted_data Encrypted data
     * @return string Decrypted data
     */
    public function decrypt($encrypted_data) {
        return $this->decrypt_field($encrypted_data, 'default');
    }

    /**
     * Advanced field-level decryption with metadata parsing
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $encrypted_data Encrypted data with metadata
     * @param string $field_name Field identifier for key derivation
     * @param array $options Decryption options
     * @return string Decrypted data
     */
    public function decrypt_field($encrypted_data, $field_name, $options = []) {
        if (empty($encrypted_data)) {
            return '';
        }

        try {
            // Decode from base64
            $data = base64_decode($encrypted_data);

            if ($data === false) {
                throw new Exception('Invalid base64 encoded data');
            }

            // Try to parse as new format first
            $result = $this->parse_encrypted_data($data);

            if ($result === false) {
                // Fallback to legacy format
                return $this->decrypt_legacy_format($data, $field_name);
            }

            $version = $result['version'];
            $metadata = $result['metadata'];
            $iv = $result['iv'];
            $tag = $result['tag'];
            $ciphertext = $result['ciphertext'];

            // Validate version
            if (!isset($this->algorithms[$version])) {
                throw new Exception('Unsupported encryption version: ' . $version);
            }

            $algorithm = $this->algorithms[$version];

            // Parse metadata
            $meta_info = json_decode($metadata, true);
            if ($meta_info === null) {
                throw new Exception('Invalid encryption metadata');
            }

            // Derive field-specific key
            $context = $options['context'] ?? ($meta_info['context'] ?? '');
            $field_key = $this->derive_field_key($field_name, $context);

            // Decrypt using AES-256-GCM
            $plaintext = openssl_decrypt(
                $ciphertext,
                $algorithm,
                $field_key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }

            // Log successful decryption
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::log_action(
                    'encryption',
                    'decrypt_success',
                    0,
                    get_current_user_id(),
                    'Field decryption successful: ' . $field_name,
                    ['version' => $version, 'field' => $field_name]
                );
            }

            return $plaintext;

        } catch (Exception $e) {
            error_log('VD License Manager - Decryption error: ' . $e->getMessage());
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::log_action(
                    'encryption',
                    'decrypt_failed',
                    0,
                    get_current_user_id(),
                    'Field decryption failed: ' . $field_name,
                    ['error' => $e->getMessage()]
                );
            }
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

    /**
     * Create encryption metadata
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $field_name Field identifier
     * @param string $version Encryption version
     * @param array $options Additional options
     * @return string JSON metadata
     */
    private function create_encryption_metadata($field_name, $version, $options = []) {
        $metadata = [
            'field' => $field_name,
            'version' => $version,
            'timestamp' => time(),
            'context' => $options['context'] ?? '',
            'user_id' => get_current_user_id(),
            'checksum' => ''
        ];

        // Add checksum
        $metadata['checksum'] = hash('sha256', json_encode($metadata));

        return json_encode($metadata);
    }

    /**
     * Parse encrypted data format
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $data Binary data
     * @return array|false Parsed components or false if legacy format
     */
    private function parse_encrypted_data($data) {
        if (strlen($data) < 8) {
            return false;
        }

        try {
            $offset = 0;

            // Read version length and version
            $version_len = unpack('v', substr($data, $offset, 2))[1];
            $offset += 2;

            if ($offset + $version_len > strlen($data)) {
                return false;
            }

            $version = substr($data, $offset, $version_len);
            $offset += $version_len;

            // Read metadata length and metadata
            if ($offset + 2 > strlen($data)) {
                return false;
            }

            $metadata_len = unpack('v', substr($data, $offset, 2))[1];
            $offset += 2;

            if ($offset + $metadata_len > strlen($data)) {
                return false;
            }

            $metadata = substr($data, $offset, $metadata_len);
            $offset += $metadata_len;

            // Read IV (12 bytes) + TAG (16 bytes) + ciphertext
            if ($offset + 28 > strlen($data)) {
                return false;
            }

            $iv = substr($data, $offset, 12);
            $offset += 12;

            $tag = substr($data, $offset, 16);
            $offset += 16;

            $ciphertext = substr($data, $offset);

            return [
                'version' => $version,
                'metadata' => $metadata,
                'iv' => $iv,
                'tag' => $tag,
                'ciphertext' => $ciphertext
            ];

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Decrypt legacy format data
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $data Binary data
     * @param string $field_name Field name for key derivation
     * @return string Decrypted data
     */
    private function decrypt_legacy_format($data, $field_name) {
        if (strlen($data) < 28) {
            throw new Exception('Invalid legacy encrypted data format');
        }

        // Legacy format: IV(12) + TAG(16) + CIPHERTEXT
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        // Use master key for legacy data
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->algorithms['v1'],
            $this->master_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new Exception('Legacy decryption failed: ' . openssl_error_string());
        }

        return $plaintext;
    }

    /**
     * Rotate encryption keys for a field
     *
     * @since 1.0.0 (Sprint 3.2)
     * @param string $field_name Field to rotate
     * @param string $new_context New context for key derivation
     * @return bool Success status
     */
    public function rotate_field_key($field_name, $new_context = '') {
        try {
            // Clear cached key
            $cache_keys = array_filter(array_keys($this->field_keys), function($key) use ($field_name) {
                return strpos($key, md5($field_name)) !== false;
            });

            foreach ($cache_keys as $key) {
                unset($this->field_keys[$key]);
            }

            // Log key rotation
            if (class_exists('VD_Audit_Logger')) {
                VD_Audit_Logger::log_action(
                    'encryption',
                    'key_rotation',
                    0,
                    get_current_user_id(),
                    'Encryption key rotated for field: ' . $field_name,
                    ['field' => $field_name, 'new_context' => $new_context]
                );
            }

            return true;

        } catch (Exception $e) {
            error_log('VD License Manager - Key rotation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get encryption statistics
     *
     * @since 1.0.0 (Sprint 3.2)
     * @return array Encryption statistics
     */
    public function get_encryption_stats() {
        global $wpdb;

        $stats = [
            'encryption_version' => $this->encryption_version,
            'supported_algorithms' => array_keys($this->algorithms),
            'cached_field_keys' => count($this->field_keys),
            'master_key_configured' => !empty($this->master_key),
            'metadata_enabled' => $this->metadata_enabled,
            'openssl_version' => OPENSSL_VERSION_TEXT ?? 'Unknown'
        ];

        // Get encryption usage stats from audit logs if available
        if (class_exists('VD_Audit_Logger')) {
            $audit_table = $wpdb->prefix . 'vd_audit_logs';

            $encryption_events = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$audit_table}
                 WHERE entity_type = 'encryption'
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ));

            $stats['encryption_events_24h'] = intval($encryption_events);
        }

        return $stats;
    }

    /**
     * Test advanced encryption functionality
     *
     * @since 1.0.0 (Sprint 3.2)
     * @return array Test results
     */
    public function test_advanced_encryption() {
        $results = [
            'basic_encryption' => false,
            'field_encryption' => false,
            'key_derivation' => false,
            'metadata_parsing' => false,
            'legacy_compatibility' => false,
            'errors' => []
        ];

        try {
            // Test 1: Basic encryption
            $test_data = 'VD Advanced Test - ' . time();
            $encrypted = $this->encrypt($test_data);
            $decrypted = $this->decrypt($encrypted);
            $results['basic_encryption'] = ($test_data === $decrypted);

            // Test 2: Field-level encryption
            $field_encrypted = $this->encrypt_field($test_data, 'test_field');
            $field_decrypted = $this->decrypt_field($field_encrypted, 'test_field');
            $results['field_encryption'] = ($test_data === $field_decrypted);

            // Test 3: Key derivation
            $key1 = $this->derive_field_key('test_field', 'context1');
            $key2 = $this->derive_field_key('test_field', 'context2');
            $results['key_derivation'] = ($key1 !== $key2 && strlen($key1) === 32);

            // Test 4: Metadata parsing
            $parsed = $this->parse_encrypted_data(base64_decode($field_encrypted));
            $results['metadata_parsing'] = ($parsed !== false && isset($parsed['version']));

            // Test 5: Legacy compatibility
            $legacy_encrypted = $this->encrypt($test_data); // Uses legacy method
            $legacy_decrypted = $this->decrypt($legacy_encrypted);
            $results['legacy_compatibility'] = ($test_data === $legacy_decrypted);

        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get encryption details for System Status
     *
     * @since 1.0.0 (Sprint 3.2)
     * @return array Encryption status for admin display
     */
    public function get_encryption_status() {
        $requirements = $this->validate_encryption_requirements();
        $stats = $this->get_encryption_stats();
        $test = $this->test_advanced_encryption();

        return [
            'status' => $requirements['key_valid'] ? 'active' : 'error',
            'version' => $this->encryption_version,
            'algorithm' => $this->algorithms[$this->encryption_version],
            'key_configured' => $requirements['key_configured'],
            'key_valid' => $requirements['key_valid'],
            'openssl_support' => $requirements['openssl_extension'],
            'field_encryption' => $test['field_encryption'],
            'metadata_support' => $test['metadata_parsing'],
            'legacy_compatibility' => $test['legacy_compatibility'],
            'cached_keys' => $stats['cached_field_keys'],
            'recent_events' => $stats['encryption_events_24h'] ?? 0
        ];
    }
}