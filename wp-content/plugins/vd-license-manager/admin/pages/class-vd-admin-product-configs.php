<?php
/**
 * Product share configuration page for VD License Manager
 *
 * Handles configuration of what information to share for each product
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Admin Product Configs class
 *
 * Manages product sharing configurations
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Product_Configs {

    /**
     * Render product configs page
     *
     * @since 1.0.0
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $action = $_GET['action'] ?? 'list';
        $product_id = $_GET['product_id'] ?? null;

        switch ($action) {
            case 'edit':
                if ($product_id) {
                    self::render_edit($product_id);
                } else {
                    self::render_list();
                }
                break;
            default:
                self::render_list();
                break;
        }
    }

    /**
     * Render product list with configuration status
     *
     * @since 1.0.0
     */
    private static function render_list() {
        global $wpdb;

        // Get all WooCommerce products
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Get existing configurations
        $configs = $wpdb->get_results(
            "SELECT product_id, share_fields FROM {$wpdb->prefix}vd_product_share_configs",
            ARRAY_A
        );

        $config_map = array();
        foreach ($configs as $config) {
            $fields = json_decode($config['share_fields'], true);
            $config_map[$config['product_id']] = $fields;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Product Share Configurations', 'vd-license-manager'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?php echo esc_html__('Product', 'vd-license-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Share Type', 'vd-license-manager'); ?></th>
                        <th style="width: 30%;"><?php echo esc_html__('Actions', 'vd-license-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="3"><?php echo esc_html__('No products found.', 'vd-license-manager'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $fields = $config_map[$product->ID] ?? array();
                            $share_type = self::get_share_type_label($fields);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($product->post_title); ?></strong><br>
                                    <small>ID: <?php echo esc_html($product->ID); ?></small>
                                </td>
                                <td>
                                    <?php if (empty($fields)): ?>
                                        <span style="color: #999;"><?php echo esc_html__('Not configured', 'vd-license-manager'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #46b450;"><?php echo esc_html($share_type); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-configs&action=edit&product_id=' . $product->ID)); ?>"
                                       class="button button-primary">
                                        <?php echo esc_html__('Configure', 'vd-license-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render edit configuration form
     *
     * @since 1.0.0
     * @param int $product_id Product ID to configure
     */
    private static function render_edit($product_id) {
        global $wpdb;

        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'product') {
            wp_die(__('Product not found.', 'vd-license-manager'));
        }

        // Get existing configuration
        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_product_share_configs WHERE product_id = %d",
            $product_id
        ), ARRAY_A);

        $current_fields = $config ? json_decode($config['share_fields'], true) : array();
        $current_instructions = $config['instructions'] ?? '';

        $errors = array();
        $success = false;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vd_config_nonce'])) {
            $result = self::handle_save($product_id);
            if ($result['success']) {
                $success = true;
                // Reload config
                $config = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vd_product_share_configs WHERE product_id = %d",
                    $product_id
                ), ARRAY_A);
                $current_fields = $config ? json_decode($config['share_fields'], true) : array();
                $current_instructions = $config['instructions'] ?? '';
            } else {
                $errors = $result['errors'];
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Configure Product Share', 'vd-license-manager'); ?></h1>

            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;"><?php echo esc_html($product->post_title); ?></h3>
                <p><strong><?php echo esc_html__('Product ID:', 'vd-license-manager'); ?></strong> <?php echo esc_html($product_id); ?></p>
            </div>

            <?php if ($success): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Configuration saved successfully!', 'vd-license-manager'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="vd-config-form">
                <?php wp_nonce_field('vd_save_config', 'vd_config_nonce'); ?>

                <div class="postbox">
                    <h3 class="hndle"><span><?php echo esc_html__('Share Fields', 'vd-license-manager'); ?></span></h3>
                    <div class="inside">
                        <p><?php echo esc_html__('Select which information to share with customers:', 'vd-license-manager'); ?></p>
                        <div class="vd-field-options">
                            <label><input type="checkbox" name="share_fields[]" value="cookie" <?php checked(in_array('cookie', $current_fields)); ?> /> 🍪 Cookie</label><br>
                            <label><input type="checkbox" name="share_fields[]" value="login_email" <?php checked(in_array('login_email', $current_fields)); ?> /> 📧 Email đăng nhập</label><br>
                            <label><input type="checkbox" name="share_fields[]" value="login_password" <?php checked(in_array('login_password', $current_fields)); ?> /> 🔒 Mật khẩu đăng nhập</label><br>
                            <label><input type="checkbox" name="share_fields[]" value="totp_secret" <?php checked(in_array('totp_secret', $current_fields)); ?> /> 🔢 Mã 2FA (TOTP Secret)</label><br>
                            <label><input type="checkbox" name="share_fields[]" value="recovery_email" <?php checked(in_array('recovery_email', $current_fields)); ?> /> 📮 Email khôi phục</label><br>
                            <label><input type="checkbox" name="share_fields[]" value="recovery_phone" <?php checked(in_array('recovery_phone', $current_fields)); ?> /> 📞 Số điện thoại khôi phục</label>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><span><?php echo esc_html__('Customer Instructions', 'vd-license-manager'); ?></span></h3>
                    <div class="inside">
                        <div style="margin-bottom: 10px;">
                            <label for="instruction_template"><?php echo esc_html__('Template:', 'vd-license-manager'); ?></label>
                            <select id="instruction_template" onchange="applyTemplate(this.value)">
                                <option value=""><?php echo esc_html__('Select template...', 'vd-license-manager'); ?></option>
                                <option value="cookie"><?php echo esc_html__('Cookie Usage', 'vd-license-manager'); ?></option>
                                <option value="userpass"><?php echo esc_html__('Username/Password', 'vd-license-manager'); ?></option>
                                <option value="userpass_2fa"><?php echo esc_html__('Username/Password + 2FA', 'vd-license-manager'); ?></option>
                                <option value="full"><?php echo esc_html__('Complete Access', 'vd-license-manager'); ?></option>
                            </select>
                        </div>
                        <?php
                        wp_editor($current_instructions, 'instructions', array(
                            'textarea_name' => 'instructions',
                            'media_buttons' => false,
                            'textarea_rows' => 10,
                            'teeny' => true
                        ));
                        ?>
                    </div>
                </div>

                <div class="submit-buttons">
                    <?php submit_button(__('Save Configuration', 'vd-license-manager'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-configs')); ?>" class="button">
                        <?php echo esc_html__('Back to List', 'vd-license-manager'); ?>
                    </a>
                </div>
            </form>
        </div>

        <script>
        function applyTemplate(template) {
            var templates = {
                'cookie': 'Sử dụng cookie được cung cấp để đăng nhập tự động vào tài khoản. Import cookie vào trình duyệt của bạn.',
                'userpass': 'Sử dụng email và mật khẩu được cung cấp để đăng nhập vào tài khoản.',
                'userpass_2fa': 'Sử dụng email, mật khẩu và mã 2FA để đăng nhập. Dùng ứng dụng xác thực để tạo mã từ TOTP secret.',
                'full': 'Thông tin đầy đủ của tài khoản bao gồm cookie, thông tin đăng nhập, 2FA và thông tin khôi phục.'
            };

            if (templates[template]) {
                if (typeof tinymce !== 'undefined' && tinymce.get('instructions')) {
                    tinymce.get('instructions').setContent(templates[template]);
                } else {
                    document.getElementById('instructions').value = templates[template];
                }
            }
        }
        </script>

        <style>
        .vd-field-options label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .submit-buttons .button {
            margin-right: 10px;
        }
        .postbox {
            margin-top: 20px;
        }
        .postbox h3 {
            padding: 8px 12px;
            margin: 0;
        }
        .postbox .inside {
            padding: 6px 10px 8px;
        }
        </style>
        <?php
    }

    /**
     * Handle configuration save
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     * @return array Result with success status and errors
     */
    private static function handle_save($product_id) {
        global $wpdb;

        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_config_nonce'], 'vd_save_config')) {
            return array('success' => false, 'errors' => array(__('Security check failed.', 'vd-license-manager')));
        }

        // Get form data
        $share_fields = $_POST['share_fields'] ?? array();
        $instructions = wp_kses_post($_POST['instructions'] ?? '');

        // Validate
        if (empty($share_fields)) {
            return array('success' => false, 'errors' => array(__('Please select at least one field to share.', 'vd-license-manager')));
        }

        // Sanitize fields
        $allowed_fields = array('cookie', 'login_email', 'login_password', 'totp_secret', 'recovery_email', 'recovery_phone');
        $share_fields = array_intersect($share_fields, $allowed_fields);

        // Save configuration
        $config_data = array(
            'product_id' => $product_id,
            'share_fields' => wp_json_encode($share_fields),
            'instructions' => $instructions,
            'updated_at' => current_time('mysql')
        );

        // Check if config exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT config_id FROM {$wpdb->prefix}vd_product_share_configs WHERE product_id = %d",
            $product_id
        ));

        if ($existing) {
            // Update existing
            $result = $wpdb->update(
                $wpdb->prefix . 'vd_product_share_configs',
                $config_data,
                array('product_id' => $product_id),
                array('%d', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new
            $config_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                $wpdb->prefix . 'vd_product_share_configs',
                $config_data,
                array('%d', '%s', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            return array('success' => false, 'errors' => array(__('Failed to save configuration.', 'vd-license-manager')));
        }

        return array('success' => true, 'errors' => array());
    }

    /**
     * Get share type label from fields
     *
     * @since 1.0.0
     * @param array $fields Share fields
     * @return string Share type label
     */
    private static function get_share_type_label($fields) {
        if (empty($fields)) {
            return __('None', 'vd-license-manager');
        }

        if (in_array('cookie', $fields) && count($fields) === 1) {
            return __('Cookie Only', 'vd-license-manager');
        }

        if (in_array('login_email', $fields) && in_array('login_password', $fields)) {
            if (in_array('totp_secret', $fields)) {
                return __('Login + 2FA', 'vd-license-manager');
            } else {
                return __('Login Credentials', 'vd-license-manager');
            }
        }

        if (count($fields) >= 4) {
            return __('Full Access', 'vd-license-manager');
        }

        return sprintf(__('%d fields', 'vd-license-manager'), count($fields));
    }
}