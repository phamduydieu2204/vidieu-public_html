<?php

namespace VD\LicenseManager\UserAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License User Context Analyzer
 *
 * Phase 4.1.1 - Extracted from monolithic validator class to provide
 * comprehensive user context analysis, account classification, and engagement metrics.
 *
 * @package VD_License_Manager
 * @subpackage UserAnalytics
 * @since 4.1.1
 * @author VD Team
 */
class VD_License_User_Context_Analyzer {

    /**
     * Singleton instance
     *
     * @var VD_License_User_Context_Analyzer|null
     */
    private static $instance = null;

    /**
     * Module version
     *
     * @var string
     */
    const VERSION = '4.1.1';

    /**
     * Module status
     *
     * @var array
     */
    private $status = array(
        'initialized' => false,
        'accounts_analyzed' => 0,
        'permission_checks_performed' => 0,
        'login_frequency_calculations' => 0,
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
     * @return VD_License_User_Context_Analyzer
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

        // Mark as initialized
        $this->status['initialized'] = true;
        $this->status['memory_usage'] = memory_get_usage() - $start_memory;

        // Debug logging
        if (defined('VD_DEBUG') && VD_DEBUG) {
            error_log("VD User Context Analyzer: Module initialized (Memory: {$this->status['memory_usage']} bytes)");
        }
    }

    // ==========================================
    // USER ACCOUNT CLASSIFICATION METHODS
    // ==========================================

    /**
     * Categorize account age based on registration date
     *
     * Phase 4.1.1 - User account age classification
     *
     * @since 4.1.1
     * @param int $days Account age in days
     * @return string Account age category
     */
    public function categorize_account_age($days) {
        if ($days < 30) return 'new';
        if ($days < 90) return 'recent';
        if ($days < 365) return 'established';
        if ($days < 1095) return 'veteran';
        return 'long_term';
    }

    /**
     * Determine user permission level based on capabilities
     *
     * Phase 4.1.1 - User permission analysis
     *
     * @since 4.1.1
     * @param WP_User $user WordPress user object
     * @return string Permission level
     */
    public function determine_permission_level($user) {
        if (user_can($user, 'manage_options')) return 'administrator';
        if (user_can($user, 'manage_woocommerce')) return 'shop_manager';
        if (user_can($user, 'vd_manage_licenses')) return 'license_manager';
        if (user_can($user, 'edit_posts')) return 'editor';
        if (user_can($user, 'read')) return 'subscriber';
        return 'no_access';
    }

    /**
     * Calculate login frequency for user
     *
     * Phase 4.1.1 - Login pattern analysis
     *
     * @since 4.1.1
     * @param int $user_id User ID
     * @return string Login frequency category
     */
    public function calculate_login_frequency($user_id) {
        $login_count = get_user_meta($user_id, 'vd_login_count', true) ?: 0;
        $user = get_user_by('ID', $user_id);

        if (!$user || !$user->user_registered) return 'unknown';

        $registered_date = new \DateTime($user->user_registered);
        $now = new \DateTime();
        $days_since_registration = $now->diff($registered_date)->days;

        if ($days_since_registration < 1) return 'new_user';

        $logins_per_day = $login_count / $days_since_registration;

        if ($logins_per_day > 3) return 'very_frequent';
        if ($logins_per_day > 1) return 'frequent';
        if ($logins_per_day > 0.5) return 'regular';
        if ($logins_per_day > 0.1) return 'occasional';
        return 'rare';
    }

    // ==========================================
    // USER ENGAGEMENT METRICS METHODS
    // ==========================================

    /**
     * Get user comment count for engagement analysis
     *
     * Phase 4.1.1 - User engagement metrics
     *
     * @since 4.1.1
     * @param int $user_id User ID
     * @return int Comment count
     */
    public function get_user_comment_count($user_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = '1'",
            $user_id
        ));

        return (int) $count;
    }

    /**
     * Get user last activity timestamp
     *
     * Phase 4.1.1 - Activity tracking
     *
     * @since 4.1.1
     * @param int $user_id User ID
     * @return string Last activity timestamp
     */
    public function get_user_last_activity($user_id) {
        $last_activity = get_user_meta($user_id, 'vd_last_activity', true);
        if ($last_activity) return $last_activity;

        // Fallback to last login
        $last_login = get_user_meta($user_id, 'vd_last_login', true);
        if ($last_login) return $last_login;

        // Fallback to user registration
        $user = get_user_by('ID', $user_id);
        return $user ? $user->user_registered : 'unknown';
    }

    /**
     * Get user ecommerce activity (WooCommerce integration)
     *
     * Phase 4.1.1 - E-commerce behavior analysis
     *
     * @since 4.1.1
     * @param int $user_id User ID
     * @return array Ecommerce activity data
     */
    public function get_user_ecommerce_activity($user_id) {
        if (!class_exists('WooCommerce')) {
            return array('woocommerce_not_active' => true);
        }

        $customer = new \WC_Customer($user_id);

        return array(
            'total_orders' => $customer->get_order_count(),
            'total_spent' => $customer->get_total_spent(),
            'avatar_url' => $customer->get_avatar_url(),
            'last_order_date' => $customer->get_last_order() ? $customer->get_last_order()->get_date_created() : null,
            'is_paying_customer' => $customer->get_is_paying_customer()
        );
    }

    // ==========================================
    // COMPREHENSIVE USER ANALYSIS METHODS
    // ==========================================

    /**
     * Generate comprehensive user context analysis
     *
     * Phase 4.1.1 - Comprehensive user analysis
     *
     * @since 4.1.1
     * @param int $user_id User ID
     * @param array $options Analysis options
     * @return array Comprehensive user context
     */
    public function generate_user_context_analysis($user_id, $options = array()) {
        $analysis_start = microtime(true);

        $default_options = array(
            'include_account_classification' => true,
            'include_permission_analysis' => true,
            'include_engagement_metrics' => true,
            'include_ecommerce_data' => true
        );
        $options = array_merge($default_options, $options);

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return array(
                'error' => 'User not found',
                'user_id' => $user_id
            );
        }

        $context = array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'account_classification' => array(),
            'permission_analysis' => array(),
            'engagement_metrics' => array(),
            'ecommerce_data' => array(),
            'metadata' => array(
                'generated_at' => current_time('c'),
                'module_version' => self::VERSION,
                'analysis_method' => 'generate_user_context_analysis'
            )
        );

        // Account classification
        if ($options['include_account_classification']) {
            $registered_date = new \DateTime($user->user_registered);
            $now = new \DateTime();
            $account_age_days = $now->diff($registered_date)->days;

            $context['account_classification'] = array(
                'registration_date' => $user->user_registered,
                'account_age_days' => $account_age_days,
                'account_age_category' => $this->categorize_account_age($account_age_days),
                'login_frequency' => $this->calculate_login_frequency($user_id)
            );
        }

        // Permission analysis
        if ($options['include_permission_analysis']) {
            $context['permission_analysis'] = array(
                'permission_level' => $this->determine_permission_level($user),
                'user_roles' => $user->roles,
                'capabilities_count' => count($user->allcaps)
            );
        }

        // Engagement metrics
        if ($options['include_engagement_metrics']) {
            $context['engagement_metrics'] = array(
                'comment_count' => $this->get_user_comment_count($user_id),
                'last_activity' => $this->get_user_last_activity($user_id),
                'posts_count' => count_user_posts($user_id)
            );
        }

        // E-commerce data
        if ($options['include_ecommerce_data']) {
            $context['ecommerce_data'] = $this->get_user_ecommerce_activity($user_id);
        }

        $analysis_end = microtime(true);
        $context['metadata']['analysis_time_ms'] = round(($analysis_end - $analysis_start) * 1000, 2);

        $this->status['accounts_analyzed']++;

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
            $health['errors'][] = 'User Context Analyzer not initialized';
            $health['status'] = 'error';
        } else {
            $health['checks'][] = 'Module initialized successfully';
        }

        // Check WooCommerce integration
        if (!class_exists('WooCommerce')) {
            $health['warnings'][] = 'WooCommerce not available - e-commerce features limited';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks'][] = 'WooCommerce integration available';
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
            'module' => 'User Context Analyzer',
            'version' => self::VERSION,
            'namespace' => __NAMESPACE__,
            'status' => $this->status,
            'accounts_analyzed' => $this->status['accounts_analyzed'],
            'permission_checks_performed' => $this->status['permission_checks_performed'],
            'login_frequency_calculations' => $this->status['login_frequency_calculations'],
            'memory_usage' => $this->status['memory_usage'],
            'woocommerce_available' => class_exists('WooCommerce'),
            'initialized_at' => current_time('Y-m-d H:i:s'),
            'file_path' => __FILE__
        );
    }
}