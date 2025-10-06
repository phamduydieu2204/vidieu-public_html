<?php
/**
 * Portal account info section for VD License Manager
 *
 * Renders account information with conditional fields
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
 * VD Portal Account Info class
 *
 * Handles account information display with various field types
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Portal_Account_Info {

    /**
     * Render account info section
     *
     * @since 1.0.0
     * @param array $account_info Account information data
     * @param string $instructions Usage instructions
     * @return string Account info HTML
     */
    public static function render_section($account_info, $instructions = '') {
        if (empty($account_info)) {
            return self::render_no_account();
        }

        ob_start();
        ?>
        <div class="vd-account-section">

            <?php if (!empty($instructions)): ?>
                <div class="vd-instructions">
                    <p class="instructions"><?php echo wp_kses_post($instructions); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($account_info['cookie'])): ?>
                <?php echo self::render_cookie_field($account_info['cookie']); ?>
            <?php endif; ?>

            <?php if (isset($account_info['login_email'])): ?>
                <?php echo self::render_credential_field(__('📧 Email đăng nhập', 'vd-license-manager'), $account_info['login_email'], 'email'); ?>
            <?php endif; ?>

            <?php if (isset($account_info['login_password'])): ?>
                <?php echo self::render_credential_field(__('🔒 Mật khẩu', 'vd-license-manager'), $account_info['login_password'], 'password'); ?>
            <?php endif; ?>

            <?php if (isset($account_info['totp_secret'])): ?>
                <?php echo self::render_totp_field($account_info['totp_secret']); ?>
            <?php endif; ?>

            <?php if (isset($account_info['recovery_email'])): ?>
                <?php echo self::render_credential_field(__('📮 Email khôi phục', 'vd-license-manager'), $account_info['recovery_email'], 'recovery'); ?>
            <?php endif; ?>

            <?php if (isset($account_info['recovery_phone'])): ?>
                <?php echo self::render_credential_field(__('📞 Số điện thoại khôi phục', 'vd-license-manager'), $account_info['recovery_phone'], 'phone'); ?>
            <?php endif; ?>

            <div class="vd-account-actions">
                <button class="vd-refresh-info" data-action="refresh-account">
                    <span class="vd-icon">🔄</span> <?php echo esc_html__('Làm mới thông tin', 'vd-license-manager'); ?>
                </button>
                <button class="vd-copy-all" data-action="copy-all">
                    <span class="vd-icon">📋</span> <?php echo esc_html__('Copy tất cả', 'vd-license-manager'); ?>
                </button>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render cookie field
     *
     * @since 1.0.0
     * @param array $cookie_data Cookie information
     * @return string Cookie field HTML
     */
    private static function render_cookie_field($cookie_data) {
        $format = $cookie_data['format'] ?? 'json';
        $value = $cookie_data['value'] ?? '';
        $updated_at = $cookie_data['updated_at'] ?? '';

        ob_start();
        ?>
        <div class="field-group cookie-field">
            <div class="field-header">
                <label class="field-label">
                    <span class="vd-icon">🍪</span>
                    <?php echo esc_html(sprintf(__('Cookie (%s)', 'vd-license-manager'), strtoupper($format))); ?>
                </label>
                <?php if (!empty($updated_at)): ?>
                    <small class="field-meta">
                        <?php echo esc_html(sprintf(__('Cập nhật: %s', 'vd-license-manager'), VD_DateTime::format_datetime($updated_at))); ?>
                    </small>
                <?php endif; ?>
            </div>

            <div class="field-content">
                <textarea readonly class="vd-cookie-data" data-field="cookie"><?php echo esc_textarea($value); ?></textarea>
            </div>

            <div class="field-actions">
                <button class="vd-copy" data-copy="cookie" title="<?php echo esc_attr__('Copy cookie', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📋</span> <?php echo esc_html__('Copy', 'vd-license-manager'); ?>
                </button>
                <button class="vd-download" data-type="cookie" data-format="<?php echo esc_attr($format); ?>" title="<?php echo esc_attr__('Download cookie file', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📥</span> <?php echo esc_html__('Tải file', 'vd-license-manager'); ?>
                </button>
                <button class="vd-import-browser" data-type="cookie" title="<?php echo esc_attr__('Import to browser', 'vd-license-manager'); ?>">
                    <span class="vd-icon">🌐</span> <?php echo esc_html__('Import', 'vd-license-manager'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render credential field
     *
     * @since 1.0.0
     * @param string $label Field label
     * @param string $value Field value
     * @param string $type Field type (email, password, recovery, phone)
     * @return string Credential field HTML
     */
    private static function render_credential_field($label, $value, $type) {
        $input_type = ($type === 'password') ? 'password' : 'text';
        $is_readonly = ($type === 'recovery' || $type === 'phone');
        $field_id = 'vd-field-' . $type;

        ob_start();
        ?>
        <div class="field-group <?php echo esc_attr($type); ?>-field">
            <div class="field-header">
                <label class="field-label" for="<?php echo esc_attr($field_id); ?>">
                    <?php echo $label; ?>
                </label>
            </div>

            <div class="field-content">
                <?php if ($is_readonly): ?>
                    <span class="field-value"><?php echo esc_html($value); ?></span>
                <?php else: ?>
                    <input type="<?php echo esc_attr($input_type); ?>"
                           readonly
                           value="<?php echo esc_attr($value); ?>"
                           id="<?php echo esc_attr($field_id); ?>"
                           data-field="<?php echo esc_attr($type); ?>"
                           class="vd-credential-input">
                <?php endif; ?>
            </div>

            <div class="field-actions">
                <?php if ($type === 'password'): ?>
                    <button class="vd-toggle-password" data-target="<?php echo esc_attr($field_id); ?>" title="<?php echo esc_attr__('Show/hide password', 'vd-license-manager'); ?>">
                        <span class="vd-icon show-icon">👁</span>
                        <span class="vd-icon hide-icon" style="display: none;">🙈</span>
                        <span class="show-text"><?php echo esc_html__('Hiện', 'vd-license-manager'); ?></span>
                        <span class="hide-text" style="display: none;"><?php echo esc_html__('Ẩn', 'vd-license-manager'); ?></span>
                    </button>
                <?php endif; ?>

                <?php if (!$is_readonly): ?>
                    <button class="vd-copy" data-copy="<?php echo esc_attr($type); ?>" title="<?php echo esc_attr(sprintf(__('Copy %s', 'vd-license-manager'), $type)); ?>">
                        <span class="vd-icon">📋</span> <?php echo esc_html__('Copy', 'vd-license-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render TOTP field
     *
     * @since 1.0.0
     * @param string $secret TOTP secret
     * @return string TOTP field HTML
     */
    private static function render_totp_field($secret) {
        ob_start();
        ?>
        <div class="field-group totp-field">
            <div class="field-header">
                <label class="field-label">
                    <span class="vd-icon">🔢</span> <?php echo esc_html__('Mã 2FA', 'vd-license-manager'); ?>
                </label>
            </div>

            <div class="field-content">
                <input type="text"
                       readonly
                       value="<?php echo esc_attr($secret); ?>"
                       id="vd-totp-secret"
                       data-field="totp"
                       class="vd-totp-secret">

                <div class="vd-totp-current">
                    <div class="totp-code-display">
                        <span class="current-code" data-totp-secret="<?php echo esc_attr($secret); ?>">
                            <?php echo esc_html__('Loading...', 'vd-license-manager'); ?>
                        </span>
                        <span class="countdown-timer">30s</span>
                    </div>
                    <div class="totp-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            </div>

            <div class="field-actions">
                <button class="vd-copy" data-copy="totp-secret" title="<?php echo esc_attr__('Copy TOTP secret', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📋</span> <?php echo esc_html__('Copy Secret', 'vd-license-manager'); ?>
                </button>
                <button class="vd-copy" data-copy="totp-code" title="<?php echo esc_attr__('Copy current code', 'vd-license-manager'); ?>">
                    <span class="vd-icon">🔑</span> <?php echo esc_html__('Copy Code', 'vd-license-manager'); ?>
                </button>
                <button class="vd-qr-code" data-secret="<?php echo esc_attr($secret); ?>" title="<?php echo esc_attr__('Show QR code', 'vd-license-manager'); ?>">
                    <span class="vd-icon">📱</span> <?php echo esc_html__('QR Code', 'vd-license-manager'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render no account message
     *
     * @since 1.0.0
     * @return string No account HTML
     */
    private static function render_no_account() {
        ob_start();
        ?>
        <div class="vd-no-account">
            <div class="no-account-icon">
                <span class="vd-icon">⚠️</span>
            </div>
            <div class="no-account-message">
                <h4><?php echo esc_html__('Chưa có tài khoản được gán', 'vd-license-manager'); ?></h4>
                <p><?php echo esc_html__('Vui lòng liên hệ hỗ trợ để được gán tài khoản.', 'vd-license-manager'); ?></p>
            </div>
            <div class="no-account-actions">
                <button class="vd-request-account" data-action="request-account">
                    <span class="vd-icon">📞</span> <?php echo esc_html__('Yêu cầu tài khoản', 'vd-license-manager'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get field type icon
     *
     * @since 1.0.0
     * @param string $type Field type
     * @return string Icon
     */
    private static function get_field_icon($type) {
        $icons = array(
            'cookie' => '🍪',
            'email' => '📧',
            'password' => '🔒',
            'totp' => '🔢',
            'recovery' => '📮',
            'phone' => '📞'
        );

        return $icons[$type] ?? '📄';
    }
}