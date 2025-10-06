<?php
/**
 * Portal history section for VD License Manager
 *
 * Renders timeline of access and fetch events
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
 * VD Portal History class
 *
 * Handles history timeline display with filtering
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Portal_History {

    /**
     * Render history timeline
     *
     * @since 1.0.0
     * @param array $timeline_data Array of timeline events
     * @return string Timeline HTML
     */
    public static function render_timeline($timeline_data = array()) {
        ob_start();
        ?>
        <div class="vd-history-section">

            <!-- Filter Buttons -->
            <div class="history-filters">
                <button class="filter-btn active" data-filter="all" title="<?php echo esc_attr__('Show all events', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📋</span> <?php echo esc_html__('Tất cả', 'vd-license-manager'); ?>
                </button>
                <button class="filter-btn" data-filter="fetch" title="<?php echo esc_attr__('Show fetch events', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📥</span> <?php echo esc_html__('Lấy thông tin', 'vd-license-manager'); ?>
                </button>
                <button class="filter-btn" data-filter="access" title="<?php echo esc_attr__('Show access events', 'vd-license-manager'); ?>">
                    <span class="vd-icon">🔍</span> <?php echo esc_html__('Xác thực', 'vd-license-manager'); ?>
                </button>
                <button class="filter-btn" data-filter="error" title="<?php echo esc_attr__('Show error events', 'vd-license-manager'); ?>">
                    <span class="vd-icon">⚠️</span> <?php echo esc_html__('Lỗi', 'vd-license-manager'); ?>
                </button>
            </div>

            <!-- Timeline Container -->
            <div class="timeline-container">
                <?php if (empty($timeline_data)): ?>
                    <?php echo self::render_empty_timeline(); ?>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($timeline_data as $event): ?>
                            <?php echo self::render_event_item($event); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($timeline_data) >= 20): ?>
                        <div class="timeline-load-more">
                            <button class="load-more" data-offset="<?php echo count($timeline_data); ?>" data-action="load-more-history">
                                <span class="vd-icon">⬇️</span> <?php echo esc_html__('Xem thêm...', 'vd-license-manager'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single timeline event
     *
     * @since 1.0.0
     * @param array $event Event data
     * @return string Event HTML
     */
    private static function render_event_item($event) {
        $event_type = $event['type'] ?? 'unknown';
        $event_status = $event['status'] ?? 'unknown';
        $event_classes = "timeline-item {$event_type} {$event_status}";

        ob_start();
        ?>
        <div class="<?php echo esc_attr($event_classes); ?>" data-event-type="<?php echo esc_attr($event_type); ?>" data-event-status="<?php echo esc_attr($event_status); ?>">

            <div class="timeline-icon">
                <?php echo self::get_event_icon($event); ?>
            </div>

            <div class="timeline-content">
                <div class="timeline-header">
                    <div class="event-time">
                        <?php
                        if (!empty($event['timestamp'])) {
                            echo esc_html(VD_DateTime::relative_time($event['timestamp']));
                        } else {
                            echo esc_html__('Unknown time', 'vd-license-manager');
                        }
                        ?>
                    </div>
                    <span class="status-badge status-<?php echo esc_attr($event_status); ?>">
                        <?php echo esc_html(self::get_status_text($event_status)); ?>
                    </span>
                </div>

                <div class="timeline-details">
                    <?php if (!empty($event['device'])): ?>
                        <div class="event-device">
                            <span class="vd-icon">📱</span>
                            <?php echo self::format_device_info($event['device']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['location'])): ?>
                        <div class="event-location">
                            <span class="vd-icon">📍</span>
                            <?php echo esc_html($event['location']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($event_type === 'fetch' && !empty($event['fields'])): ?>
                        <div class="event-fields">
                            <span class="vd-icon">🔑</span>
                            <span class="fields-label"><?php echo esc_html__('Lấy:', 'vd-license-manager'); ?></span>
                            <span class="fields-list"><?php echo esc_html(implode(', ', $event['fields'])); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['ip_address'])): ?>
                        <div class="event-ip">
                            <span class="vd-icon">🌐</span>
                            <?php echo esc_html($event['ip_address']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['message'])): ?>
                        <div class="event-message">
                            <span class="vd-icon">💬</span>
                            <?php echo esc_html($event['message']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get event icon based on type and status
     *
     * @since 1.0.0
     * @param array $event Event data
     * @return string Icon HTML
     */
    private static function get_event_icon($event) {
        $type = $event['type'] ?? 'unknown';
        $status = $event['status'] ?? 'unknown';

        $icons = array(
            'fetch_success' => '✅',
            'fetch_failed' => '❌',
            'fetch_error' => '⚠️',
            'access_success' => '🔍',
            'access_failed' => '❌',
            'access_blocked' => '🚫',
            'verify_success' => '✅',
            'verify_failed' => '❌',
            'rotate_success' => '🔄',
            'rotate_failed' => '❌',
            'unknown' => '❓'
        );

        $icon_key = $type . '_' . $status;
        $icon = $icons[$icon_key] ?? $icons['unknown'];

        return '<span class="timeline-icon-emoji">' . $icon . '</span>';
    }

    /**
     * Format device information
     *
     * @since 1.0.0
     * @param array $device Device data
     * @return string Formatted device info
     */
    private static function format_device_info($device) {
        if (empty($device)) {
            return '<span class="device-unknown">' . __('Unknown device', 'vd-license-manager') . '</span>';
        }

        $device_icon = self::get_device_icon($device);
        $os = $device['os'] ?? __('Unknown OS', 'vd-license-manager');
        $browser = $device['browser'] ?? __('Unknown Browser', 'vd-license-manager');

        return sprintf(
            '<span class="device-info">%s <span class="device-os">%s</span> - <span class="device-browser">%s</span></span>',
            $device_icon,
            esc_html($os),
            esc_html($browser)
        );
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
        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false || strpos($user_agent, 'iphone') !== false) {
            return '📱';
        }

        // Tablets
        if (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            return '📱';
        }

        // Desktop/Laptop
        if (strpos($os, 'windows') !== false) {
            return '💻';
        }

        if (strpos($os, 'mac') !== false) {
            return '💻';
        }

        if (strpos($os, 'linux') !== false) {
            return '🖥️';
        }

        // Default
        return '🖥️';
    }

    /**
     * Get status text in Vietnamese
     *
     * @since 1.0.0
     * @param string $status Status code
     * @return string Vietnamese status text
     */
    private static function get_status_text($status) {
        $status_texts = array(
            'success' => __('Thành công', 'vd-license-manager'),
            'failed' => __('Thất bại', 'vd-license-manager'),
            'error' => __('Lỗi', 'vd-license-manager'),
            'blocked' => __('Bị chặn', 'vd-license-manager'),
            'pending' => __('Đang xử lý', 'vd-license-manager'),
            'unknown' => __('Không rõ', 'vd-license-manager')
        );

        return $status_texts[$status] ?? $status_texts['unknown'];
    }

    /**
     * Render empty timeline message
     *
     * @since 1.0.0
     * @return string Empty timeline HTML
     */
    private static function render_empty_timeline() {
        ob_start();
        ?>
        <div class="timeline-empty">
            <div class="empty-icon">
                <span class="vd-icon">📜</span>
            </div>
            <div class="empty-message">
                <h4><?php echo esc_html__('Chưa có hoạt động nào', 'vd-license-manager'); ?></h4>
                <p><?php echo esc_html__('Lịch sử truy cập và lấy thông tin sẽ hiển thị ở đây.', 'vd-license-manager'); ?></p>
            </div>
            <div class="empty-actions">
                <button class="vd-refresh-history" data-action="refresh-history">
                    <span class="vd-icon">🔄</span> <?php echo esc_html__('Làm mới', 'vd-license-manager'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}