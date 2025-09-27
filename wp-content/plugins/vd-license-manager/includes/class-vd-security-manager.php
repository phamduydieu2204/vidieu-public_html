<?php
/**
 * VD Security Manager
 *
 * Comprehensive security features for VD License Manager
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Security_Manager class
 *
 * Handles input validation, authentication, IP whitelisting, session management
 */
class VD_Security_Manager {

    /**
     * Singleton instance
     *
     * @var VD_Security_Manager
     */
    private static $instance = null;

    /**
     * Security configuration
     *
     * @var array
     */
    private $config = [];

    /**
     * IP whitelist cache
     *
     * @var array
     */
    private $ip_whitelist = [];

    /**
     * Rate limit cache
     *
     * @var array
     */
    private $rate_limits = [];

    /**
     * Get singleton instance
     *
     * @return VD_Security_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_config();
        $this->setup_hooks();
    }

    /**
     * Initialize security configuration
     *
     * @since 1.0.0
     */
    private function init_config() {
        $this->config = array_merge([
            'enable_ip_whitelist' => false,
            'max_login_attempts' => 5,
            'lockout_duration' => 3600, // 1 hour
            'session_timeout' => 7200,  // 2 hours
            'csrf_protection' => true,
            'require_https' => false,
            'security_headers' => true,
            'rate_limit_enabled' => true,
            'rate_limit_requests' => 100,
            'rate_limit_window' => 3600
        ], get_option('vd_security_config', []));
    }

    /**
     * Setup WordPress hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Security headers
        if ($this->config['security_headers']) {
            add_action('send_headers', [$this, 'add_security_headers']);
        }

        // HTTPS enforcement
        if ($this->config['require_https']) {
            add_action('init', [$this, 'enforce_https']);
        }

        // Admin security
        add_action('admin_init', [$this, 'validate_admin_access']);

        // AJAX security
        add_action('wp_ajax_vd_validate_nonce', [$this, 'handle_nonce_validation']);
        add_action('wp_ajax_nopriv_vd_validate_nonce', [$this, 'handle_nonce_validation']);

        // Login attempt tracking
        add_action('wp_login_failed', [$this, 'track_failed_login']);
        add_filter('authenticate', [$this, 'check_login_attempts'], 30, 3);
    }

    /**
     * Add security headers
     *
     * @since 1.0.0
     */
    public function add_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');

            if ($this->config['require_https']) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }

    /**
     * Enforce HTTPS
     *
     * @since 1.0.0
     */
    public function enforce_https() {
        if (!is_ssl() && !wp_doing_ajax() && !wp_doing_cron()) {
            if (vd_is_admin()) {
                $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                wp_redirect($redirect_url, 301);
                exit;
            }
        }
    }

    /**
     * Validate admin access
     *
     * @since 1.0.0
     */
    public function validate_admin_access() {
        if (!vd_is_admin()) {
            return;
        }

        // Check if accessing VD License Manager pages
        $page = $_GET['page'] ?? '';
        if (strpos($page, 'vd-license') !== false) {
            // Validate session
            $this->validate_session();

            // Check IP whitelist
            if ($this->config['enable_ip_whitelist']) {
                $this->validate_ip_access();
            }

            // Rate limiting
            if ($this->config['rate_limit_enabled']) {
                $this->check_rate_limit();
            }
        }
    }

    /**
     * Validate input data
     *
     * @since 1.0.0
     * @param mixed $data Data to validate
     * @param string $type Validation type
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate_input($data, $type = 'text', $options = []) {
        $result = [
            'valid' => false,
            'sanitized' => null,
            'errors' => []
        ];

        try {
            switch ($type) {
                case 'license_key':
                    $result = $this->validate_license_key($data);
                    break;

                case 'device_fp':
                    $result = $this->validate_device_fingerprint($data);
                    break;

                case 'email':
                    $result = $this->validate_email($data);
                    break;

                case 'ip_address':
                    $result = $this->validate_ip_address($data);
                    break;

                case 'json':
                    $result = $this->validate_json($data, $options);
                    break;

                case 'nonce':
                    $result = $this->validate_nonce($data, $options);
                    break;

                case 'text':
                default:
                    $result = $this->validate_text($data, $options);
                    break;
            }
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            vd_debug_log('Security validation error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Validate license key
     *
     * @since 1.0.0
     * @param string $license_key
     * @return array
     */
    private function validate_license_key($license_key) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        if (empty($license_key)) {
            $result['errors'][] = 'License key is required';
            return $result;
        }

        // Sanitize
        $sanitized = strtoupper(trim($license_key));
        $sanitized = preg_replace('/[^A-Z0-9\-]/', '', $sanitized);

        // Validate format
        if (!preg_match('/^[A-Z0-9\-]{8,64}$/', $sanitized)) {
            $result['errors'][] = 'Invalid license key format';
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $sanitized;
        return $result;
    }

    /**
     * Validate device fingerprint
     *
     * @since 1.0.0
     * @param string $device_fp
     * @return array
     */
    private function validate_device_fingerprint($device_fp) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        if (empty($device_fp)) {
            $result['errors'][] = 'Device fingerprint is required';
            return $result;
        }

        // Sanitize
        $sanitized = strtolower(trim($device_fp));

        // Validate SHA-256 hash format
        if (!preg_match('/^[a-f0-9]{64}$/', $sanitized)) {
            $result['errors'][] = 'Invalid device fingerprint format';
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $sanitized;
        return $result;
    }

    /**
     * Validate email address
     *
     * @since 1.0.0
     * @param string $email
     * @return array
     */
    private function validate_email($email) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        if (empty($email)) {
            $result['errors'][] = 'Email is required';
            return $result;
        }

        // Sanitize
        $sanitized = sanitize_email(trim($email));

        // Validate
        if (!is_email($sanitized)) {
            $result['errors'][] = 'Invalid email format';
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $sanitized;
        return $result;
    }

    /**
     * Validate IP address
     *
     * @since 1.0.0
     * @param string $ip
     * @return array
     */
    private function validate_ip_address($ip) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        if (empty($ip)) {
            $result['errors'][] = 'IP address is required';
            return $result;
        }

        // Sanitize
        $sanitized = trim($ip);

        // Validate IPv4 or IPv6
        if (!filter_var($sanitized, FILTER_VALIDATE_IP)) {
            $result['errors'][] = 'Invalid IP address format';
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $sanitized;
        return $result;
    }

    /**
     * Validate JSON data
     *
     * @since 1.0.0
     * @param string $json
     * @param array $options
     * @return array
     */
    private function validate_json($json, $options = []) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        if (empty($json)) {
            $result['errors'][] = 'JSON data is required';
            return $result;
        }

        // Parse JSON
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['errors'][] = 'Invalid JSON format: ' . json_last_error_msg();
            return $result;
        }

        // Validate structure if schema provided
        if (isset($options['schema'])) {
            $validation = $this->validate_json_schema($decoded, $options['schema']);
            if (!$validation['valid']) {
                $result['errors'] = array_merge($result['errors'], $validation['errors']);
                return $result;
            }
        }

        $result['valid'] = true;
        $result['sanitized'] = $decoded;
        return $result;
    }

    /**
     * Validate text input
     *
     * @since 1.0.0
     * @param string $text
     * @param array $options
     * @return array
     */
    private function validate_text($text, $options = []) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        $min_length = $options['min_length'] ?? 0;
        $max_length = $options['max_length'] ?? 1000;
        $required = $options['required'] ?? false;

        if ($required && empty($text)) {
            $result['errors'][] = 'Text is required';
            return $result;
        }

        if (empty($text) && !$required) {
            $result['valid'] = true;
            $result['sanitized'] = '';
            return $result;
        }

        // Sanitize
        $sanitized = sanitize_text_field(trim($text));

        // Length validation
        if (strlen($sanitized) < $min_length) {
            $result['errors'][] = "Text must be at least {$min_length} characters";
            return $result;
        }

        if (strlen($sanitized) > $max_length) {
            $result['errors'][] = "Text must not exceed {$max_length} characters";
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $sanitized;
        return $result;
    }

    /**
     * Validate nonce
     *
     * @since 1.0.0
     * @param string $nonce
     * @param array $options
     * @return array
     */
    private function validate_nonce($nonce, $options = []) {
        $result = ['valid' => false, 'sanitized' => null, 'errors' => []];

        $action = $options['action'] ?? 'vd_default_action';

        if (empty($nonce)) {
            $result['errors'][] = 'Nonce is required';
            return $result;
        }

        if (!wp_verify_nonce($nonce, $action)) {
            $result['errors'][] = 'Security check failed';
            return $result;
        }

        $result['valid'] = true;
        $result['sanitized'] = $nonce;
        return $result;
    }

    /**
     * Check rate limiting
     *
     * @since 1.0.0
     * @param string $identifier
     * @param int $limit
     * @param int $window
     * @return array
     */
    public function check_rate_limit($identifier = null, $limit = null, $window = null) {
        if (!$this->config['rate_limit_enabled']) {
            return ['allowed' => true, 'remaining' => 999];
        }

        $identifier = $identifier ?: $this->get_client_identifier();
        $limit = $limit ?: $this->config['rate_limit_requests'];
        $window = $window ?: $this->config['rate_limit_window'];

        $cache_key = 'vd_rate_limit_' . md5($identifier);
        $current_time = time();
        $window_start = $current_time - $window;

        // Get current rate limit data
        $rate_data = get_transient($cache_key);
        if (!$rate_data || !is_array($rate_data)) {
            $rate_data = ['count' => 0, 'window_start' => $current_time];
        }

        // Reset if window expired
        if ($rate_data['window_start'] < $window_start) {
            $rate_data = ['count' => 0, 'window_start' => $current_time];
        }

        // Check limit
        if ($rate_data['count'] >= $limit) {
            $this->log_security_event('rate_limit_exceeded', [
                'identifier' => $identifier,
                'count' => $rate_data['count'],
                'limit' => $limit
            ]);

            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $rate_data['window_start'] + $window
            ];
        }

        // Increment counter
        $rate_data['count']++;
        set_transient($cache_key, $rate_data, $window);

        return [
            'allowed' => true,
            'remaining' => $limit - $rate_data['count'],
            'reset_time' => $rate_data['window_start'] + $window
        ];
    }

    /**
     * Get client identifier for rate limiting
     *
     * @since 1.0.0
     * @return string
     */
    private function get_client_identifier() {
        $ip = $this->get_client_ip();
        $user_id = get_current_user_id();

        if ($user_id) {
            return 'user_' . $user_id;
        }

        return 'ip_' . md5($ip);
    }

    /**
     * Get client IP address
     *
     * @since 1.0.0
     * @return string
     */
    public function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                $ip = trim($ip);

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Validate session
     *
     * @since 1.0.0
     */
    private function validate_session() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $session_key = 'vd_session_' . $user_id;
        $session_data = get_user_meta($user_id, $session_key, true);

        if (empty($session_data)) {
            // Create new session
            $this->create_user_session($user_id);
            return;
        }

        // Check session timeout
        if (isset($session_data['last_activity'])) {
            $inactive_time = time() - $session_data['last_activity'];
            if ($inactive_time > $this->config['session_timeout']) {
                $this->destroy_user_session($user_id);
                wp_logout();
                wp_redirect(wp_login_url());
                exit;
            }
        }

        // Update last activity
        $session_data['last_activity'] = time();
        update_user_meta($user_id, $session_key, $session_data);
    }

    /**
     * Create user session
     *
     * @since 1.0.0
     * @param int $user_id
     */
    private function create_user_session($user_id) {
        $session_data = [
            'created' => time(),
            'last_activity' => time(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        update_user_meta($user_id, 'vd_session_' . $user_id, $session_data);
    }

    /**
     * Destroy user session
     *
     * @since 1.0.0
     * @param int $user_id
     */
    private function destroy_user_session($user_id) {
        delete_user_meta($user_id, 'vd_session_' . $user_id);
    }

    /**
     * Validate IP access
     *
     * @since 1.0.0
     */
    private function validate_ip_access() {
        $client_ip = $this->get_client_ip();
        $whitelist = $this->get_ip_whitelist();

        if (!empty($whitelist) && !$this->ip_in_whitelist($client_ip, $whitelist)) {
            $this->log_security_event('ip_blocked', [
                'ip' => $client_ip,
                'whitelist' => $whitelist
            ]);

            wp_die(__('Access denied from your IP address.', VD_LM_TEXT_DOMAIN));
        }
    }

    /**
     * Get IP whitelist
     *
     * @since 1.0.0
     * @return array
     */
    private function get_ip_whitelist() {
        if (empty($this->ip_whitelist)) {
            $this->ip_whitelist = get_option('vd_ip_whitelist', []);
        }
        return $this->ip_whitelist;
    }

    /**
     * Check if IP is in whitelist
     *
     * @since 1.0.0
     * @param string $ip
     * @param array $whitelist
     * @return bool
     */
    private function ip_in_whitelist($ip, $whitelist) {
        foreach ($whitelist as $allowed_ip) {
            if ($ip === $allowed_ip) {
                return true;
            }

            // Check CIDR notation
            if (strpos($allowed_ip, '/') !== false) {
                if ($this->ip_in_cidr($ip, $allowed_ip)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @since 1.0.0
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function ip_in_cidr($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Track failed login attempts
     *
     * @since 1.0.0
     * @param string $username
     */
    public function track_failed_login($username) {
        $ip = $this->get_client_ip();
        $cache_key = 'vd_login_attempts_' . md5($ip);

        $attempts = get_transient($cache_key);
        $attempts = $attempts ? $attempts + 1 : 1;

        set_transient($cache_key, $attempts, $this->config['lockout_duration']);

        $this->log_security_event('login_failed', [
            'username' => $username,
            'ip' => $ip,
            'attempts' => $attempts
        ]);
    }

    /**
     * Check login attempts
     *
     * @since 1.0.0
     * @param WP_User|WP_Error|null $user
     * @param string $username
     * @param string $password
     * @return WP_User|WP_Error|null
     */
    public function check_login_attempts($user, $username, $password) {
        if (empty($username) || empty($password)) {
            return $user;
        }

        $ip = $this->get_client_ip();
        $cache_key = 'vd_login_attempts_' . md5($ip);
        $attempts = get_transient($cache_key);

        if ($attempts && $attempts >= $this->config['max_login_attempts']) {
            $this->log_security_event('login_blocked', [
                'username' => $username,
                'ip' => $ip,
                'attempts' => $attempts
            ]);

            return new WP_Error(
                'too_many_attempts',
                sprintf(
                    __('Too many failed login attempts. Please try again in %d minutes.', VD_LM_TEXT_DOMAIN),
                    $this->config['lockout_duration'] / 60
                )
            );
        }

        return $user;
    }

    /**
     * Handle nonce validation AJAX
     *
     * @since 1.0.0
     */
    public function handle_nonce_validation() {
        $nonce = $_POST['nonce'] ?? '';
        $action = $_POST['action_name'] ?? 'vd_default_action';

        $validation = $this->validate_input($nonce, 'nonce', ['action' => $action]);

        if ($validation['valid']) {
            wp_send_json_success(['message' => 'Nonce valid']);
        } else {
            wp_send_json_error(['message' => implode(', ', $validation['errors'])]);
        }
    }

    /**
     * Log security event
     *
     * @since 1.0.0
     * @param string $event_type
     * @param array $data
     */
    private function log_security_event($event_type, $data = []) {
        if (class_exists('VD_Audit_Logger')) {
            VD_Audit_Logger::log_action(
                'security',
                $event_type,
                0,
                get_current_user_id(),
                sprintf('Security event: %s', $event_type),
                array_merge($data, [
                    'ip' => $this->get_client_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'timestamp' => time()
                ])
            );
        }

        vd_debug_log("Security Event [{$event_type}]: " . json_encode($data));
    }

    /**
     * Get security status
     *
     * @since 1.0.0
     * @return array
     */
    public function get_security_status() {
        return [
            'encryption_key_valid' => vd_is_encryption_key_valid(),
            'https_enabled' => is_ssl(),
            'security_headers_enabled' => $this->config['security_headers'],
            'rate_limiting_enabled' => $this->config['rate_limit_enabled'],
            'ip_whitelist_enabled' => $this->config['enable_ip_whitelist'],
            'session_timeout' => $this->config['session_timeout'],
            'max_login_attempts' => $this->config['max_login_attempts'],
            'current_ip' => $this->get_client_ip()
        ];
    }

    /**
     * Update security configuration
     *
     * @since 1.0.0
     * @param array $new_config
     * @return bool
     */
    public function update_security_config($new_config) {
        $this->config = array_merge($this->config, $new_config);
        return update_option('vd_security_config', $this->config);
    }

    /**
     * Generate secure token
     *
     * @since 1.0.0
     * @param int $length
     * @return string
     */
    public function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Validate JSON schema (basic implementation)
     *
     * @since 1.0.0
     * @param array $data
     * @param array $schema
     * @return array
     */
    private function validate_json_schema($data, $schema) {
        $result = ['valid' => true, 'errors' => []];

        foreach ($schema as $field => $rules) {
            if ($rules['required'] && !isset($data[$field])) {
                $result['errors'][] = "Required field '{$field}' is missing";
                continue;
            }

            if (isset($data[$field]) && isset($rules['type'])) {
                $field_type = gettype($data[$field]);
                if ($field_type !== $rules['type']) {
                    $result['errors'][] = "Field '{$field}' must be of type {$rules['type']}";
                }
            }
        }

        $result['valid'] = empty($result['errors']);
        return $result;
    }
}