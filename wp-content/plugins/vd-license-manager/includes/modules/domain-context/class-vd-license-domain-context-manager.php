<?php

namespace VD\LicenseManager\DomainContext;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Domain Context Manager Module
 *
 * Phase 3.3 - Extracted from monolithic validator class to provide
 * comprehensive domain context, session management, and user behavior analysis.
 *
 * @package VD_License_Manager
 * @subpackage DomainContext
 * @since 3.3.0
 * @author VD Team
 */
class VD_License_Domain_Context_Manager {

    /**
     * Singleton instance
     *
     * @var VD_License_Domain_Context_Manager|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '3.3.0';

    /**
     * Activation rules reference for IP detection
     *
     * @var object|null
     */
    private $activation_rules = null;

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'session_contexts_processed' => 0,
        'page_views_tracked' => 0,
        'behavior_analyses_performed' => 0,
        'memory_usage' => 0
    );

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module();
    }

    /**
     * Get singleton instance
     *
     * @return VD_License_Domain_Context_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize module
     *
     * @return void
     */
    private function init_module() {
        $start_memory = memory_get_usage();

        // Try to get activation rules reference for IP detection
        if (class_exists('VD_License_Activation_Rules')) {
            try {
                $this->activation_rules = VD_License_Activation_Rules::get_instance();
            } catch (Exception $e) {
                // Silent fail - will use fallback methods
            }
        }

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD Domain Context Manager: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    // ==========================================
    // CLIENT & SESSION CONTEXT METHODS
    // ==========================================

    /**
     * Get client IP for anonymous users
     *
     * Phase 3.3 - Client IP detection with fallback mechanisms
     *
     * @since 3.3.0
     * @return string Client IP address
     */
    public function get_client_ip_for_anonymous() {
        if ($this->activation_rules && method_exists($this->activation_rules, 'detect_client_ip')) {
            return $this->activation_rules->detect_client_ip();
        }

        // Fallback IP detection
        $ip_sources = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_sources as $source) {
            if (!empty($_SERVER[$source])) {
                $ip = $_SERVER[$source];
                // Handle comma-separated IPs (proxy chains)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Estimate session duration for logged-in users
     *
     * Phase 3.3 - Session duration calculation
     *
     * @since 3.3.0
     * @return int Session duration in seconds
     */
    public function estimate_session_duration() {
        if (!is_user_logged_in()) return 0;

        $current_session = wp_get_session_token();
        if (!$current_session) return 0;

        $session_manager = WP_Session_Tokens::get_instance(get_current_user_id());
        $sessions = $session_manager->get_all();

        if (isset($sessions[$current_session])) {
            return time() - $sessions[$current_session]['login'];
        }

        return 0;
    }

    /**
     * Estimate anonymous session duration
     *
     * Phase 3.3 - Anonymous user session tracking
     *
     * @since 3.3.0
     * @return int Estimated session duration in seconds
     */
    public function estimate_anonymous_session_duration() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION['vd_session_start'])) {
            $_SESSION['vd_session_start'] = time();
            return 0;
        }

        $duration = time() - $_SESSION['vd_session_start'];
        $this->status['session_contexts_processed']++;

        return $duration;
    }

    // ==========================================
    // PAGE & NAVIGATION CONTEXT METHODS
    // ==========================================

    /**
     * Get landing page for current session
     *
     * Phase 3.3 - Landing page tracking
     *
     * @since 3.3.0
     * @return string Landing page URL
     */
    public function get_landing_page() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION['vd_landing_page'])) {
            $_SESSION['vd_landing_page'] = $_SERVER['REQUEST_URI'] ?? '';
        }

        return $_SESSION['vd_landing_page'];
    }

    /**
     * Get visited pages for anonymous user
     *
     * Phase 3.3 - Page visit tracking
     *
     * @since 3.3.0
     * @return array Visited pages
     */
    public function get_visited_pages_anonymous() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION['vd_visited_pages'])) {
            $_SESSION['vd_visited_pages'] = array();
        }

        $current_page = $_SERVER['REQUEST_URI'] ?? '';
        if (!in_array($current_page, $_SESSION['vd_visited_pages'])) {
            $_SESSION['vd_visited_pages'][] = $current_page;
            $this->status['page_views_tracked']++;
        }

        return $_SESSION['vd_visited_pages'];
    }

    /**
     * Get time on site for anonymous user
     *
     * Phase 3.3 - Time tracking wrapper
     *
     * @since 3.3.0
     * @return int Time on site in seconds
     */
    public function get_time_on_site_anonymous() {
        return $this->estimate_anonymous_session_duration();
    }

    /**
     * Get anonymous page views count
     *
     * Phase 3.3 - Page view counter
     *
     * @since 3.3.0
     * @return int Number of page views
     */
    public function get_anonymous_page_views() {
        $visited_pages = $this->get_visited_pages_anonymous();
        return count($visited_pages);
    }

    // ==========================================
    // USER BEHAVIOR ANALYSIS METHODS
    // ==========================================

    /**
     * Calculate bounce risk for anonymous users
     *
     * Phase 3.3 - Bounce risk assessment
     *
     * @since 3.3.0
     * @return string Risk level (high, medium, low)
     */
    public function calculate_bounce_risk() {
        $page_views = $this->get_anonymous_page_views();
        $session_duration = $this->estimate_anonymous_session_duration();

        if ($page_views === 1 && $session_duration < 30) return 'high';
        if ($page_views < 3 && $session_duration < 60) return 'medium';

        $this->status['behavior_analyses_performed']++;
        return 'low';
    }

    /**
     * Calculate anonymous engagement score
     *
     * Phase 3.3 - User engagement scoring
     *
     * @since 3.3.0
     * @return int Engagement score (0-100)
     */
    public function calculate_anonymous_engagement() {
        $page_views = $this->get_anonymous_page_views();
        $session_duration = $this->estimate_anonymous_session_duration();

        $score = 0;
        $score += min($page_views * 10, 50); // Max 50 points for page views
        $score += min($session_duration / 60 * 5, 50); // Max 50 points for time

        $this->status['behavior_analyses_performed']++;
        return min(100, $score);
    }

    /**
     * Check anonymous cart status
     *
     * Phase 3.3 - WooCommerce cart analysis
     *
     * @since 3.3.0
     * @return array Cart status information
     */
    public function check_anonymous_cart_status() {
        if (!class_exists('WooCommerce')) {
            return array(
                'woocommerce_not_active' => true,
                'has_items' => false,
                'item_count' => 0,
                'cart_total' => 0
            );
        }

        $cart = WC()->cart;
        if (!$cart) {
            return array(
                'cart_not_available' => true,
                'has_items' => false,
                'item_count' => 0,
                'cart_total' => 0
            );
        }

        return array(
            'has_items' => !$cart->is_empty(),
            'item_count' => $cart->get_cart_contents_count(),
            'cart_total' => $cart->get_total(''),
            'cart_subtotal' => $cart->get_subtotal(),
            'needs_payment' => $cart->needs_payment()
        );
    }

    /**
     * Analyze purchase intent for anonymous users
     *
     * Phase 3.3 - Purchase intent analysis
     *
     * @since 3.3.0
     * @return string Intent level (high, medium, low)
     */
    public function analyze_purchase_intent_anonymous() {
        $cart_status = $this->check_anonymous_cart_status();
        $visited_pages = $this->get_visited_pages_anonymous();

        if ($cart_status['has_items']) return 'high';

        // Check if user visited product or checkout pages
        $product_pages = 0;
        foreach ($visited_pages as $page) {
            if (strpos($page, '/product/') !== false ||
                strpos($page, '/shop/') !== false ||
                strpos($page, '/cart/') !== false ||
                strpos($page, '/checkout/') !== false) {
                $product_pages++;
            }
        }

        if ($product_pages >= 3) return 'high';
        if ($product_pages >= 1) return 'medium';

        $this->status['behavior_analyses_performed']++;
        return 'low';
    }

    // ==========================================
    // COMPREHENSIVE DOMAIN CONTEXT GENERATION
    // ==========================================

    /**
     * Generate comprehensive domain context
     *
     * Phase 3.3 - Main context generation method
     *
     * @since 3.3.0
     * @param array $options Context generation options
     * @return array Comprehensive domain context
     */
    public function generate_domain_context($options = array()) {
        $generation_start = microtime(true);

        $default_options = array(
            'include_session_data' => true,
            'include_navigation_data' => true,
            'include_behavior_analysis' => true,
            'include_cart_analysis' => true
        );
        $options = array_merge($default_options, $options);

        $context = array(
            'client_info' => array(),
            'session_data' => array(),
            'navigation_data' => array(),
            'behavior_analysis' => array(),
            'metadata' => array(
                'generated_at' => current_time('c'),
                'module_version' => self::VERSION,
                'generation_method' => 'generate_domain_context'
            )
        );

        // Client information
        $context['client_info'] = array(
            'ip_address' => $this->get_client_ip_for_anonymous(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        // Session data
        if ($options['include_session_data']) {
            $context['session_data'] = array(
                'session_duration' => $this->estimate_anonymous_session_duration(),
                'logged_in_duration' => $this->estimate_session_duration(),
                'session_id' => session_id(),
                'is_logged_in' => is_user_logged_in()
            );
        }

        // Navigation data
        if ($options['include_navigation_data']) {
            $context['navigation_data'] = array(
                'landing_page' => $this->get_landing_page(),
                'visited_pages' => $this->get_visited_pages_anonymous(),
                'page_views' => $this->get_anonymous_page_views(),
                'time_on_site' => $this->get_time_on_site_anonymous()
            );
        }

        // Behavior analysis
        if ($options['include_behavior_analysis']) {
            $context['behavior_analysis'] = array(
                'bounce_risk' => $this->calculate_bounce_risk(),
                'engagement_score' => $this->calculate_anonymous_engagement(),
                'purchase_intent' => $this->analyze_purchase_intent_anonymous()
            );
        }

        // Cart analysis (if WooCommerce active)
        if ($options['include_cart_analysis']) {
            $context['cart_analysis'] = $this->check_anonymous_cart_status();
        }

        $generation_end = microtime(true);
        $context['metadata']['generation_time_ms'] = round(($generation_end - $generation_start) * 1000, 2);

        return $context;
    }

    // ==========================================
    // MODULE STATUS & HEALTH METHODS
    // ==========================================

    /**
     * Get module status
     *
     * @return array Module status information
     */
    public function get_status() {
        return array_merge($this->status, array(
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'activation_rules_available' => $this->activation_rules !== null,
            'woocommerce_available' => class_exists('WooCommerce')
        ));
    }

    /**
     * Module health check
     *
     * @return array Health check results
     */
    public function health_check() {
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
            'warnings' => array(),
            'errors' => array()
        );

        // Check if initialized
        if (!$this->status['initialized']) {
            $health['errors'][] = 'Domain Context Manager not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check session functionality
        if (session_status() === PHP_SESSION_DISABLED) {
            $health['warnings'][] = 'PHP sessions are disabled';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Session functionality available';
        }

        // Check activation rules integration
        if ($this->activation_rules === null) {
            $health['warnings'][] = 'Activation Rules integration not available';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Activation Rules integration active';
        }

        // Check memory usage
        if ($this->status['memory_usage'] > 262144) { // 256KB
            $health['warnings'][] = 'High memory usage: ' . number_format($this->status['memory_usage'] / 1024, 2) . ' KB';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'Memory usage within limits';
        }

        return $health;
    }

    /**
     * Get debug information
     *
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'module' => 'Domain Context Manager',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'session_contexts_processed' => $this->status['session_contexts_processed'],
            'page_views_tracked' => $this->status['page_views_tracked'],
            'behavior_analyses_performed' => $this->status['behavior_analyses_performed'],
            'memory_usage' => $this->status['memory_usage'],
            'activation_rules_available' => $this->activation_rules !== null,
            'woocommerce_available' => class_exists('WooCommerce'),
            'session_status' => session_status(),
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}