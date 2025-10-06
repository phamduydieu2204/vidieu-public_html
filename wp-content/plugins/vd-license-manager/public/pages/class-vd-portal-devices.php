<?php
/**
 * Portal devices section for VD License Manager
 *
 * Renders device list with limit tracking and cooldown management
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
 * VD Portal Devices class
 *
 * Handles device display with limits and rotation controls
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Portal_Devices {

    /**
     * Render devices section
     *
     * @since 1.0.0
     * @param array $devices Array of device objects
     * @param string $current_fp Current device fingerprint
     * @param array $limit_info Device limit information
     * @param array $cooldown_info Rotation cooldown information
     * @return string Devices HTML
     */
    public static function render_devices($devices = array(), $current_fp = '', $limit_info = array(), $cooldown_info = array()) {
        // Set defaults
        $limit_info = wp_parse_args($limit_info, array(
            'used' => 0,
            'max' => 1,
            'remaining' => 1
        ));

        $cooldown_info = wp_parse_args($cooldown_info, array(
            'active' => false,
            'ends_at' => null
        ));

        ob_start();
        ?>
        <div class="vd-devices-section">

            <?php echo self::render_limit_bar($limit_info); ?>

            <div class="device-list">
                <?php if (empty($devices)): ?>
                    <?php echo self::render_no_devices(); ?>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <?php
                        $is_current = ($device['fp'] === $current_fp);
                        echo self::render_device_card($device, $is_current);
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php echo self::render_cooldown_notice($cooldown_info); ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render device card
     *
     * @since 1.0.0
     * @param array $device Device data
     * @param bool $is_current Is current device
     * @return string Device card HTML
     */
    private static function render_device_card($device, $is_current = false) {
        $device_status = $device['status'] ?? 'unknown';
        $card_classes = "device-card {$device_status}";
        if ($is_current) {
            $card_classes .= ' current';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr($card_classes); ?>" data-device-fp="<?php echo esc_attr($device['fp']); ?>">

            <div class="device-header">
                <div class="device-icon">
                    <?php echo self::get_device_icon($device); ?>
                </div>
                <div class="device-status-badge">
                    <span class="status-badge status-<?php echo esc_attr($device_status); ?>">
                        <?php echo self::get_status_label($device_status); ?>
                    </span>
                </div>
            </div>

            <div class="device-info">
                <div class="device-primary">
                    <strong class="device-os"><?php echo esc_html($device['os'] ?? __('Unknown OS', 'vd-license-manager')); ?></strong>
                </div>
                <div class="device-secondary">
                    <span class="device-browser"><?php echo esc_html($device['browser'] ?? __('Unknown Browser', 'vd-license-manager')); ?></span>
                </div>
                <?php if ($is_current): ?>
                    <div class="device-current-badge">
                        <span class="badge current">
                            <span class="vd-icon">🔥</span> <?php echo esc_html__('Đang dùng', 'vd-license-manager'); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="device-meta">
                <?php if (!empty($device['last_used_at'])): ?>
                    <div class="last-used">
                        <span class="vd-icon">⏰</span>
                        <small><?php echo esc_html(sprintf(__('Dùng lần cuối: %s', 'vd-license-manager'), VD_DateTime::relative_time($device['last_used_at']))); ?></small>
                    </div>
                <?php endif; ?>

                <?php if (!empty($device['location'])): ?>
                    <div class="device-location">
                        <span class="vd-icon">📍</span>
                        <small><?php echo esc_html($device['location']); ?></small>
                    </div>
                <?php endif; ?>

                <?php if (!empty($device['approved_at'])): ?>
                    <div class="approved-date">
                        <span class="vd-icon">✅</span>
                        <small><?php echo esc_html(sprintf(__('Duyệt: %s', 'vd-license-manager'), VD_DateTime::format_date($device['approved_at']))); ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($device_status === 'pending'): ?>
                <div class="device-actions">
                    <small class="pending-note">
                        <span class="vd-icon">⏳</span>
                        <?php echo esc_html__('Đang chờ admin duyệt', 'vd-license-manager'); ?>
                    </small>
                </div>
            <?php elseif ($device_status === 'blocked'): ?>
                <div class="device-actions">
                    <small class="blocked-note">
                        <span class="vd-icon">🚫</span>
                        <?php echo esc_html__('Thiết bị đã bị chặn', 'vd-license-manager'); ?>
                    </small>
                </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get device icon
     *
     * @since 1.0.0
     * @param array $device Device data
     * @return string Device icon
     */
    private static function get_device_icon($device) {
        $os = strtolower($device['os'] ?? '');
        $user_agent = strtolower($device['user_agent'] ?? '');

        // Mobile devices
        if (strpos($user_agent, 'mobile') !== false ||
            strpos($user_agent, 'android') !== false ||
            strpos($user_agent, 'iphone') !== false) {
            return '<span class="device-icon-emoji">📱</span>';
        }

        // Tablets
        if (strpos($user_agent, 'tablet') !== false ||
            strpos($user_agent, 'ipad') !== false) {
            return '<span class="device-icon-emoji">📱</span>';
        }

        // Desktop/Laptop by OS
        if (strpos($os, 'windows') !== false) {
            return '<span class="device-icon-emoji">💻</span>';
        }

        if (strpos($os, 'mac') !== false) {
            return '<span class="device-icon-emoji">💻</span>';
        }

        if (strpos($os, 'linux') !== false) {
            return '<span class="device-icon-emoji">🖥️</span>';
        }

        // Default desktop
        return '<span class="device-icon-emoji">🖥️</span>';
    }

    /**
     * Get status label in Vietnamese
     *
     * @since 1.0.0
     * @param string $status Status code
     * @return string Vietnamese status label
     */
    private static function get_status_label($status) {
        $labels = array(
            'pending' => '<span class="vd-icon">⏳</span> ' . __('Chờ duyệt', 'vd-license-manager'),
            'approved' => '<span class="vd-icon">✅</span> ' . __('Đã duyệt', 'vd-license-manager'),
            'blocked' => '<span class="vd-icon">🚫</span> ' . __('Đã chặn', 'vd-license-manager'),
            'unknown' => '<span class="vd-icon">❓</span> ' . __('Không rõ', 'vd-license-manager')
        );

        return $labels[$status] ?? $labels['unknown'];
    }

    /**
     * Render device limit bar
     *
     * @since 1.0.0
     * @param array $limit_info Limit information
     * @return string Limit bar HTML
     */
    private static function render_limit_bar($limit_info) {
        $percentage = $limit_info['max'] > 0 ? ($limit_info['used'] / $limit_info['max']) * 100 : 0;
        $percentage = min(100, max(0, $percentage)); // Clamp between 0-100

        $bar_class = 'limit-bar';
        if ($percentage >= 100) {
            $bar_class .= ' full';
        } elseif ($percentage >= 80) {
            $bar_class .= ' warning';
        }

        ob_start();
        ?>
        <div class="device-limit">
            <div class="limit-header">
                <h4><?php echo esc_html__('Giới hạn thiết bị', 'vd-license-manager'); ?></h4>
                <span class="limit-count">
                    <?php echo sprintf(
                        esc_html__('%d / %d thiết bị', 'vd-license-manager'),
                        $limit_info['used'],
                        $limit_info['max']
                    ); ?>
                </span>
            </div>
            <div class="<?php echo esc_attr($bar_class); ?>">
                <div class="limit-progress" style="width: <?php echo esc_attr($percentage); ?>%" data-percentage="<?php echo esc_attr($percentage); ?>"></div>
            </div>
            <?php if ($limit_info['remaining'] > 0): ?>
                <small class="limit-remaining">
                    <span class="vd-icon">ℹ️</span>
                    <?php echo sprintf(
                        esc_html__('Còn %d thiết bị có thể thêm', 'vd-license-manager'),
                        $limit_info['remaining']
                    ); ?>
                </small>
            <?php else: ?>
                <small class="limit-full">
                    <span class="vd-icon">⚠️</span>
                    <?php echo esc_html__('Đã đạt giới hạn thiết bị', 'vd-license-manager'); ?>
                </small>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render cooldown notice
     *
     * @since 1.0.0
     * @param array $cooldown_info Cooldown information
     * @return string Cooldown notice HTML
     */
    private static function render_cooldown_notice($cooldown_info) {
        ob_start();
        ?>
        <div class="device-rotation">
            <?php if ($cooldown_info['active'] && !empty($cooldown_info['ends_at'])): ?>
                <div class="cooldown-notice">
                    <div class="cooldown-icon">
                        <span class="vd-icon">⏱️</span>
                    </div>
                    <div class="cooldown-message">
                        <strong><?php echo esc_html__('Đang trong thời gian chờ', 'vd-license-manager'); ?></strong>
                        <p>
                            <?php echo esc_html__('Bạn có thể đổi thiết bị sau:', 'vd-license-manager'); ?>
                            <span class="countdown" data-ends="<?php echo esc_attr($cooldown_info['ends_at']); ?>">
                                <?php echo esc_html(VD_DateTime::relative_time($cooldown_info['ends_at'])); ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="rotation-actions">
                    <button class="vd-request-rotation" data-action="request-rotation">
                        <span class="vd-icon">🔄</span> <?php echo esc_html__('Yêu cầu đổi thiết bị', 'vd-license-manager'); ?>
                    </button>
                    <small class="rotation-note">
                        <?php echo esc_html__('Yêu cầu sẽ được xử lý trong vài phút', 'vd-license-manager'); ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render no devices message
     *
     * @since 1.0.0
     * @return string No devices HTML
     */
    private static function render_no_devices() {
        ob_start();
        ?>
        <div class="no-devices">
            <div class="no-devices-icon">
                <span class="vd-icon">📱</span>
            </div>
            <div class="no-devices-message">
                <h4><?php echo esc_html__('Chưa có thiết bị nào', 'vd-license-manager'); ?></h4>
                <p><?php echo esc_html__('Thiết bị hiện tại sẽ được thêm tự động khi bạn truy cập.', 'vd-license-manager'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}