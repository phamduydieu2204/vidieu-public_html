<?php
/**
 * Portal main layout for VD License Manager
 *
 * Renders the main portal interface with 3-column layout
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
 * VD Portal Main class
 *
 * Handles main portal layout rendering
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Portal_Main {

    /**
     * Render main portal layout
     *
     * @since 1.0.0
     * @param string $license_key License key
     * @param string $device_fp Device fingerprint
     * @return string Portal HTML
     */
    public static function render($license_key, $device_fp) {
        global $wpdb;

        // Get license data
        $license_data = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, p.post_title as product_name
             FROM {$wpdb->prefix}lmfwc_licenses l
             LEFT JOIN {$wpdb->prefix}posts p ON l.order_id = p.ID
             WHERE l.license_key = %s",
            $license_key
        ), ARRAY_A);

        if (!$license_data) {
            return '<div class="vd-error">' . __('License not found.', 'vd-license-manager') . '</div>';
        }

        ob_start();
        ?>
        <div class="vd-portal-container" data-license-key="<?php echo esc_attr($license_key); ?>" data-device-fp="<?php echo esc_attr($device_fp); ?>">

            <?php echo self::render_header($license_data); ?>

            <!-- Main Content - 3 Columns -->
            <div class="vd-portal-content">

                <!-- Left Column: History -->
                <div class="vd-column vd-history">
                    <div class="vd-column-header">
                        <h3><span class="vd-icon">📜</span> <?php echo esc_html__('Lịch sử truy cập', 'vd-license-manager'); ?></h3>
                        <button class="vd-refresh-column" data-target="history" title="<?php echo esc_attr__('Refresh history', 'vd-license-manager'); ?>">
                            <span class="vd-icon">🔄</span>
                        </button>
                    </div>
                    <div id="vd-history-timeline" class="vd-column-content">
                        <?php echo self::render_loading_placeholder('history'); ?>
                    </div>
                </div>

                <!-- Center Column: Account Info -->
                <div class="vd-column vd-account-info">
                    <div class="vd-column-header">
                        <h3><span class="vd-icon">🔑</span> <?php echo esc_html__('Thông tin tài khoản', 'vd-license-manager'); ?></h3>
                        <button class="vd-get-account" data-license="<?php echo esc_attr($license_key); ?>" title="<?php echo esc_attr__('Get account info', 'vd-license-manager'); ?>">
                            <span class="vd-icon">📥</span> <?php echo esc_html__('Lấy thông tin', 'vd-license-manager'); ?>
                        </button>
                    </div>
                    <div id="vd-account-data" class="vd-column-content">
                        <?php echo self::render_loading_placeholder('account'); ?>
                    </div>
                    <div class="vd-account-actions">
                        <button class="vd-rotate-account" data-license="<?php echo esc_attr($license_key); ?>">
                            <span class="vd-icon">🔄</span> <?php echo esc_html__('Đổi tài khoản', 'vd-license-manager'); ?>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Devices -->
                <div class="vd-column vd-devices">
                    <div class="vd-column-header">
                        <h3><span class="vd-icon">📱</span> <?php echo esc_html__('Thiết bị của tôi', 'vd-license-manager'); ?></h3>
                        <button class="vd-refresh-column" data-target="devices" title="<?php echo esc_attr__('Refresh devices', 'vd-license-manager'); ?>">
                            <span class="vd-icon">🔄</span>
                        </button>
                    </div>
                    <div id="vd-device-list" class="vd-column-content">
                        <?php echo self::render_loading_placeholder('devices'); ?>
                    </div>
                    <div class="vd-device-actions">
                        <button class="vd-add-device" data-license="<?php echo esc_attr($license_key); ?>">
                            <span class="vd-icon">➕</span> <?php echo esc_html__('Thêm thiết bị', 'vd-license-manager'); ?>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="vd-portal-footer">
                <div class="vd-footer-info">
                    <p><span class="vd-icon">ℹ️</span> <?php echo esc_html__('Thông tin có thể thay đổi. Luôn lấy phiên bản mới nhất.', 'vd-license-manager'); ?></p>
                </div>
                <div class="vd-footer-actions">
                    <button class="vd-refresh-all" title="<?php echo esc_attr__('Refresh all data', 'vd-license-manager'); ?>">
                        <span class="vd-icon">🔄</span> <?php echo esc_html__('Làm mới tất cả', 'vd-license-manager'); ?>
                    </button>
                </div>
            </div>

            <!-- Global Message Area -->
            <div id="vd-portal-messages" class="vd-messages" style="display: none;"></div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render portal header
     *
     * @since 1.0.0
     * @param array $license_data License information
     * @return string Header HTML
     */
    private static function render_header($license_data) {
        // Calculate days remaining
        $days_remaining = '';
        if (!empty($license_data['expires_at'])) {
            $expires = strtotime($license_data['expires_at']);
            $today = current_time('timestamp');
            $days_diff = ceil(($expires - $today) / DAY_IN_SECONDS);

            if ($days_diff > 0) {
                $days_remaining = sprintf(__('Còn %d ngày', 'vd-license-manager'), $days_diff);
            } elseif ($days_diff === 0) {
                $days_remaining = __('Hết hạn hôm nay', 'vd-license-manager');
            } else {
                $days_remaining = __('Đã hết hạn', 'vd-license-manager');
            }
        } else {
            $days_remaining = __('Không giới hạn', 'vd-license-manager');
        }

        ob_start();
        ?>
        <div class="vd-portal-header">
            <div class="vd-license-info">
                <div class="vd-license-key">
                    <span class="vd-label"><?php echo esc_html__('License:', 'vd-license-manager'); ?></span>
                    <span class="license-key"><?php echo esc_html($license_data['license_key']); ?></span>
                </div>
                <?php if (!empty($license_data['product_name'])): ?>
                    <div class="vd-product-name">
                        <span class="product-name"><?php echo esc_html($license_data['product_name']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="vd-expires-info">
                    <span class="expires-in <?php echo $days_remaining === __('Đã hết hạn', 'vd-license-manager') ? 'expired' : ''; ?>">
                        <?php echo esc_html($days_remaining); ?>
                    </span>
                </div>
                <div class="vd-status">
                    <span class="status-badge status-<?php echo esc_attr($license_data['status']); ?>">
                        <?php echo esc_html(ucfirst($license_data['status'])); ?>
                    </span>
                </div>
            </div>
            <div class="vd-header-actions">
                <button class="vd-logout" title="<?php echo esc_attr__('Logout from portal', 'vd-license-manager'); ?>">
                    <span class="vd-icon">🚪</span> <?php echo esc_html__('Đăng xuất', 'vd-license-manager'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render loading placeholder
     *
     * @since 1.0.0
     * @param string $type Placeholder type (history, account, devices)
     * @return string Loading HTML
     */
    private static function render_loading_placeholder($type = 'default') {
        ob_start();
        ?>
        <div class="vd-loading vd-loading-<?php echo esc_attr($type); ?>">
            <?php if ($type === 'history'): ?>
                <div class="vd-loading-item">
                    <div class="vd-loading-circle"></div>
                    <div class="vd-loading-text"></div>
                    <div class="vd-loading-date"></div>
                </div>
                <div class="vd-loading-item">
                    <div class="vd-loading-circle"></div>
                    <div class="vd-loading-text"></div>
                    <div class="vd-loading-date"></div>
                </div>
                <div class="vd-loading-item">
                    <div class="vd-loading-circle"></div>
                    <div class="vd-loading-text"></div>
                    <div class="vd-loading-date"></div>
                </div>
            <?php elseif ($type === 'account'): ?>
                <div class="vd-loading-card">
                    <div class="vd-loading-header"></div>
                    <div class="vd-loading-field"></div>
                    <div class="vd-loading-field"></div>
                    <div class="vd-loading-field"></div>
                </div>
            <?php elseif ($type === 'devices'): ?>
                <div class="vd-loading-device">
                    <div class="vd-loading-icon"></div>
                    <div class="vd-loading-info">
                        <div class="vd-loading-line"></div>
                        <div class="vd-loading-line short"></div>
                    </div>
                </div>
                <div class="vd-loading-device">
                    <div class="vd-loading-icon"></div>
                    <div class="vd-loading-info">
                        <div class="vd-loading-line"></div>
                        <div class="vd-loading-line short"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="vd-loading-spinner">
                    <span class="vd-icon">⏳</span>
                    <span class="vd-loading-message"><?php echo esc_html__('Đang tải...', 'vd-license-manager'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}