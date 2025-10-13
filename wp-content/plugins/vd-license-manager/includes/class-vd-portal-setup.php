<?php
/**
 * VD Portal Setup - Clean Implementation
 *
 * Handles portal page creation and shortcode registration
 * Optimized for two-column layout rendering
 *
 * @package    VD_License_Manager
 * @subpackage Includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_Portal_Setup {

    /**
     * Constructor - Register hooks
     */
    public function __construct() {
        add_shortcode('vd_license_portal', array($this, 'render_portal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init', array($this, 'create_portal_page'));
    }

    /**
     * Create portal page if not exists
     */
    public function create_portal_page() {
        // Only create once
        if (get_option('vd_portal_created')) {
            return;
        }

        $page_id = wp_insert_post(array(
            'post_title'   => 'Cổng Thông Tin License',
            'post_name'    => 'license-portal',
            'post_content' => '[vd_license_portal]',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ));

        if ($page_id) {
            update_option('vd_portal_page_id', $page_id);
            update_option('vd_portal_created', true);
        }
    }

    /**
     * Enqueue assets only on portal page
     */
    public function enqueue_assets() {
        global $post;

        if (!is_page() || !$post || !has_shortcode($post->post_content, 'vd_license_portal')) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'vd-portal',
            VD_PLUGIN_URL . 'public/css/portal.css',
            array(),
            VD_PLUGIN_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'vd-portal',
            VD_PLUGIN_URL . 'public/js/portal.js',
            array('jquery'),
            VD_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('vd-portal', 'vdPortal', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('vd_portal_nonce')
        ));
    }

    /**
     * Render portal shortcode - Clean implementation
     */
    public function render_portal($atts) {
        $atts = shortcode_atts(array(
            'title'    => 'Cổng Thông Tin License',
            'subtitle' => 'Nhập mã license để truy cập thông tin tài khoản của bạn'
        ), $atts);

        ob_start();
        ?>
        <!-- VD Portal - Two Column Layout -->
        <div class="vd-portal-wrapper">

            <!-- Header -->
            <div class="vd-portal-header">
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <!-- Two Column Container -->
            <div class="vd-portal-container">

                <!-- LEFT PANEL (60%) -->
                <div class="vd-portal-main">

                    <!-- License Input -->
                    <div class="vd-card">
                        <h2>🔑 Nhập Mã License</h2>
                        <form id="vd-license-form">
                            <?php wp_nonce_field('vd_portal_action', 'vd_nonce'); ?>
                            <div class="vd-form-group">
                                <label for="license-key">Mã License:</label>
                                <input type="text"
                                       id="license-key"
                                       name="license_key"
                                       placeholder="Nhập mã license của bạn"
                                       pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                       required>
                                <small class="vd-help-text">Mã license đã được gửi đến email của bạn sau khi đặt hàng</small>
                            </div>
                            <button type="submit" class="vd-btn vd-btn-primary" id="vd-submit-btn">
                                <span class="vd-btn-text">🔓 Truy Cập</span>
                                <span class="vd-btn-loading" style="display: none;">⏳ Đang xử lý...</span>
                            </button>
                        </form>

                        <!-- Error Message -->
                        <div id="vd-error-message" class="vd-error" style="display: none;">
                            <strong>Lỗi:</strong> <span id="vd-error-text"></span>
                        </div>
                    </div>

                    <!-- License Info (Hidden initially) -->
                    <div id="license-info" class="vd-card" style="display:none;">
                        <h2>📋 Thông Tin License</h2>
                        <div class="vd-info-grid">
                            <div class="vd-info-item">
                                <span class="label">Mã License:</span>
                                <span class="value" id="display-license">-</span>
                            </div>
                            <div class="vd-info-item">
                                <span class="label">Trạng thái:</span>
                                <span class="value" id="display-status">-</span>
                            </div>
                            <div class="vd-info-item">
                                <span class="label">Hết hạn:</span>
                                <span class="value" id="display-expires">-</span>
                            </div>
                            <div class="vd-info-item">
                                <span class="label">Sản phẩm:</span>
                                <span class="value" id="display-product">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Account Credentials (Hidden initially) -->
                    <div id="credentials" class="vd-card" style="display:none;">
                        <h2>🔑 Thông Tin Tài Khoản</h2>
                        <div id="credentials-list"></div>
                        <div id="credentials-empty" class="vd-empty-state" style="display:none;">
                            <p>Không có thông tin tài khoản để hiển thị.</p>
                        </div>
                    </div>

                    <!-- Usage Info (Hidden initially) -->
                    <div id="usage-info" class="vd-card" style="display:none;">
                        <h4>ℹ️ Thông Tin Sử Dụng</h4>
                        <ul class="vd-usage-list">
                            <li><strong>Thiết bị:</strong> <span id="usage-device-count">0/0</span></li>
                            <li><strong>Yêu cầu hôm nay:</strong> <span id="usage-request-count">0/0</span></li>
                            <li><strong>Đặt lại sau:</strong> <span id="usage-reset-time">-</span></li>
                        </ul>
                    </div>

                    <!-- Action buttons (Hidden initially) -->
                    <div id="actions" class="vd-actions" style="display:none;">
                        <button class="vd-btn vd-btn-secondary" id="new-license-btn">← Nhập mã khác</button>
                        <button class="vd-btn vd-btn-outline" id="refresh-btn">🔄 Làm mới</button>
                    </div>

                </div>
                <!-- END LEFT PANEL -->

                <!-- RIGHT SIDEBAR (40%) -->
                <div class="vd-portal-sidebar">

                    <!-- Tabs -->
                    <div class="vd-tabs">
                        <div class="vd-tab-buttons">
                            <button class="vd-tab-btn active" data-tab="devices">📱 Thiết Bị</button>
                            <button class="vd-tab-btn" data-tab="history">📊 Lịch Sử</button>
                        </div>

                        <!-- Devices Tab -->
                        <div id="tab-devices" class="vd-tab-content active">
                            <div class="vd-card">
                                <h3>Thiết Bị Đã Kết Nối</h3>
                                <div id="device-list" class="vd-empty-state">
                                    <p>📱 Nhập mã license để xem thiết bị</p>
                                </div>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div id="tab-history" class="vd-tab-content">
                            <div class="vd-card">
                                <h3>Lịch Sử Truy Cập</h3>
                                <div id="history-list" class="vd-empty-state">
                                    <p>📊 Chưa có lịch sử truy cập</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- END RIGHT SIDEBAR -->

            </div>
            <!-- END TWO COLUMN CONTAINER -->

        </div>
        <!-- END PORTAL WRAPPER -->
        <?php
        return ob_get_clean();
    }
}

// Initialize
new VD_Portal_Setup();