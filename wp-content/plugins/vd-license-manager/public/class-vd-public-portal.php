<?php
/**
 * Public portal base class for VD License Manager
 *
 * Handles customer portal login and session management
 *
 * @package VD_License_Manager
 * @subpackage Public
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Public Portal class
 *
 * Manages customer portal with shortcode integration
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Public_Portal {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_shortcode('vd_license_portal', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_vd_portal_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_vd_portal_login', array($this, 'handle_login'));
        add_action('wp_ajax_vd_portal_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_vd_portal_logout', array($this, 'handle_logout'));
        add_action('init', array($this, 'start_session'));
    }

    /**
     * Start PHP session
     *
     * @since 1.0.0
     */
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Render portal shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Portal HTML
     */
    public function render($atts) {
        $atts = shortcode_atts(array(
            'title' => __('License Portal', 'vd-license-manager'),
            'style' => 'default'
        ), $atts, 'vd_license_portal');

        ob_start();

        // Check if user has active session
        $license_key = $_SESSION['vd_license_key'] ?? '';
        $device_fp = $_SESSION['vd_device_fp'] ?? '';

        if (!empty($license_key) && !empty($device_fp)) {
            // Verify session is still valid
            $license_result = VD_License_Verify::verify_license($license_key);
            if ($license_result['success']) {
                $device_result = VD_License_Verify::verify_device($license_result['license_id'], $device_fp);
                if ($device_result['success']) {
                    $this->render_main_portal($license_result['license_id'], $license_key);
                } else {
                    $this->clear_session();
                    $this->render_login_form($atts);
                }
            } else {
                $this->clear_session();
                $this->render_login_form($atts);
            }
        } else {
            $this->render_login_form($atts);
        }

        return ob_get_clean();
    }

    /**
     * Render login form
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     */
    private function render_login_form($atts) {
        ?>
        <div id="vd-portal-container" class="vd-portal-login">
            <div class="vd-portal-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html__('Enter your license key to access your account information.', 'vd-license-manager'); ?></p>
            </div>

            <form id="vd-login-form" class="vd-login-form">
                <?php wp_nonce_field('vd_portal_login', 'vd_portal_nonce'); ?>

                <div class="vd-form-group">
                    <label for="vd-license-key"><?php echo esc_html__('Nhập License Key', 'vd-license-manager'); ?></label>
                    <input type="text" id="vd-license-key" name="license_key" placeholder="<?php echo esc_attr__('XXXX-XXXX-XXXX-XXXX', 'vd-license-manager'); ?>" required />
                </div>

                <div class="vd-form-group">
                    <button type="submit" id="vd-login-btn" class="vd-btn vd-btn-primary">
                        <span class="vd-btn-text"><?php echo esc_html__('Truy cập', 'vd-license-manager'); ?></span>
                        <span class="vd-btn-loading" style="display: none;"><?php echo esc_html__('Đang xử lý...', 'vd-license-manager'); ?></span>
                    </button>
                </div>

                <div id="vd-login-message" class="vd-message" style="display: none;"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Render main portal interface
     *
     * @since 1.0.0
     * @param int $license_id License ID
     * @param string $license_key License key
     */
    private function render_main_portal($license_id, $license_key) {
        global $wpdb;

        // Get license information
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmfwc_licenses WHERE id = %d",
            $license_id
        ), ARRAY_A);

        // Get assigned account
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT ca.*, pa.name, pa.provider
             FROM {$wpdb->prefix}vd_cookie_assignments ca
             LEFT JOIN {$wpdb->prefix}vd_provider_accounts pa ON ca.provider_account_id = pa.account_id
             WHERE ca.license_id = %d AND ca.status = 'active'
             ORDER BY ca.assigned_at DESC LIMIT 1",
            $license_id
        ), ARRAY_A);

        ?>
        <div id="vd-portal-container" class="vd-portal-main">
            <div class="vd-portal-header">
                <h2><?php echo esc_html__('Thông tin tài khoản', 'vd-license-manager'); ?></h2>
                <button id="vd-logout-btn" class="vd-btn vd-btn-secondary">
                    <?php echo esc_html__('Đăng xuất', 'vd-license-manager'); ?>
                </button>
            </div>

            <div class="vd-portal-content">
                <div class="vd-license-info">
                    <h3><?php echo esc_html__('License Information', 'vd-license-manager'); ?></h3>
                    <div class="vd-info-grid">
                        <div class="vd-info-item">
                            <span class="vd-label"><?php echo esc_html__('License Key:', 'vd-license-manager'); ?></span>
                            <span class="vd-value"><?php echo esc_html($license_key); ?></span>
                        </div>
                        <div class="vd-info-item">
                            <span class="vd-label"><?php echo esc_html__('Status:', 'vd-license-manager'); ?></span>
                            <span class="vd-value vd-status-<?php echo esc_attr($license['status']); ?>">
                                <?php echo esc_html(ucfirst($license['status'])); ?>
                            </span>
                        </div>
                        <div class="vd-info-item">
                            <span class="vd-label"><?php echo esc_html__('Valid Until:', 'vd-license-manager'); ?></span>
                            <span class="vd-value">
                                <?php
                                if ($license['expires_at']) {
                                    echo esc_html(VD_DateTime::format_date($license['expires_at']));
                                } else {
                                    echo esc_html__('Lifetime', 'vd-license-manager');
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($assignment): ?>
                    <div class="vd-account-info">
                        <h3><?php echo esc_html__('Assigned Account', 'vd-license-manager'); ?></h3>
                        <div class="vd-info-grid">
                            <div class="vd-info-item">
                                <span class="vd-label"><?php echo esc_html__('Provider:', 'vd-license-manager'); ?></span>
                                <span class="vd-value"><?php echo esc_html(ucfirst($assignment['provider'])); ?></span>
                            </div>
                            <div class="vd-info-item">
                                <span class="vd-label"><?php echo esc_html__('Account Name:', 'vd-license-manager'); ?></span>
                                <span class="vd-value"><?php echo esc_html($assignment['name']); ?></span>
                            </div>
                            <div class="vd-info-item">
                                <span class="vd-label"><?php echo esc_html__('Assigned Date:', 'vd-license-manager'); ?></span>
                                <span class="vd-value"><?php echo esc_html(VD_DateTime::format_datetime($assignment['assigned_at'])); ?></span>
                            </div>
                        </div>

                        <div class="vd-account-actions">
                            <button id="vd-get-account-btn" class="vd-btn vd-btn-primary" data-license="<?php echo esc_attr($license_key); ?>">
                                <?php echo esc_html__('Lấy thông tin tài khoản', 'vd-license-manager'); ?>
                            </button>
                            <button id="vd-rotate-account-btn" class="vd-btn vd-btn-warning" data-license="<?php echo esc_attr($license_key); ?>">
                                <?php echo esc_html__('Đổi tài khoản', 'vd-license-manager'); ?>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="vd-no-account">
                        <p><?php echo esc_html__('No account assigned yet. Please contact support.', 'vd-license-manager'); ?></p>
                    </div>
                <?php endif; ?>

                <div id="vd-portal-message" class="vd-message" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX login
     *
     * @since 1.0.0
     */
    public function handle_login() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_portal_nonce'], 'vd_portal_login')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vd-license-manager')));
        }

        $license_key = sanitize_text_field($_POST['license_key']);
        $device_fp = sanitize_text_field($_POST['device_fp']);

        if (empty($license_key) || empty($device_fp)) {
            wp_send_json_error(array('message' => __('License key and device fingerprint are required.', 'vd-license-manager')));
        }

        // Verify license
        $license_result = VD_License_Verify::verify_license($license_key);
        if (!$license_result['success']) {
            wp_send_json_error(array('message' => $license_result['message']));
        }

        // Verify device
        $device_result = VD_License_Verify::verify_device($license_result['license_id'], $device_fp);
        if (!$device_result['success']) {
            wp_send_json_error(array('message' => $device_result['message']));
        }

        // Set session
        $_SESSION['vd_license_key'] = $license_key;
        $_SESSION['vd_device_fp'] = $device_fp;
        $_SESSION['vd_license_id'] = $license_result['license_id'];
        $_SESSION['vd_login_time'] = current_time('timestamp');

        wp_send_json_success(array(
            'message' => __('Login successful!', 'vd-license-manager'),
            'redirect' => true
        ));
    }

    /**
     * Handle logout
     *
     * @since 1.0.0
     */
    public function handle_logout() {
        $this->clear_session();

        if (wp_doing_ajax()) {
            wp_send_json_success(array(
                'message' => __('Logged out successfully.', 'vd-license-manager'),
                'redirect' => true
            ));
        } else {
            wp_redirect(wp_get_referer() ?: home_url());
            exit;
        }
    }

    /**
     * Clear session data
     *
     * @since 1.0.0
     */
    private function clear_session() {
        unset($_SESSION['vd_license_key']);
        unset($_SESSION['vd_device_fp']);
        unset($_SESSION['vd_license_id']);
        unset($_SESSION['vd_login_time']);
    }

    /**
     * Enqueue portal assets
     *
     * @since 1.0.0
     */
    public function enqueue_assets() {
        global $post;

        // Only enqueue on pages with shortcode
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'vd_license_portal')) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'vd-portal-css',
            VD_PLUGIN_URL . 'public/css/vd-portal.css',
            array(),
            VD_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'vd-fingerprint-js',
            VD_PLUGIN_URL . 'public/js/vd-fingerprint.js',
            array('jquery'),
            VD_VERSION,
            true
        );

        wp_enqueue_script(
            'vd-portal-js',
            VD_PLUGIN_URL . 'public/js/vd-portal-main.js',
            array('jquery', 'vd-fingerprint-js'),
            VD_VERSION,
            true
        );

        // Localize script
        wp_localize_script('vd-portal-js', 'vdPortal', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vd/v1/'),
            'nonce' => wp_create_nonce('vd_portal_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'vd-license-manager'),
                'error' => __('An error occurred. Please try again.', 'vd-license-manager'),
                'success' => __('Success!', 'vd-license-manager'),
                'confirm_logout' => __('Are you sure you want to logout?', 'vd-license-manager'),
                'confirm_rotate' => __('Are you sure you want to rotate your account?', 'vd-license-manager')
            )
        ));
    }
}