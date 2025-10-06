<?php
/**
 * Add provider account page for VD License Manager
 *
 * Handles adding new provider accounts with comprehensive form
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
 * VD Admin Accounts Add class
 *
 * Renders add account form and handles submission
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Accounts_Add {

    /**
     * Render add account page
     *
     * @since 1.0.0
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $errors = array();
        $success = false;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vd_account_nonce'])) {
            $result = self::handle_submit();
            if ($result['success']) {
                $success = true;
            } else {
                $errors = $result['errors'];
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Add Provider Account', 'vd-license-manager'); ?></h1>

            <?php if ($success): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Account added successfully!', 'vd-license-manager'); ?></p>
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

            <form method="post" action="" class="vd-account-form">
                <?php wp_nonce_field('vd_add_account', 'vd_account_nonce'); ?>

                <!-- Basic Info Section -->
                <div class="postbox">
                    <h3 class="hndle"><span><?php echo esc_html__('Basic Information', 'vd-license-manager'); ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="provider"><?php echo esc_html__('Provider', 'vd-license-manager'); ?> *</label></th>
                                <td>
                                    <select id="provider" name="provider" required>
                                        <option value=""><?php echo esc_html__('Select Provider', 'vd-license-manager'); ?></option>
                                        <option value="helium10">Helium10</option>
                                        <option value="freepik">Freepik</option>
                                        <option value="midjourney">Midjourney</option>
                                        <option value="canva">Canva</option>
                                        <option value="other"><?php echo esc_html__('Other', 'vd-license-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="display_name"><?php echo esc_html__('Display Name', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="text" id="display_name" name="display_name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="account_login"><?php echo esc_html__('Account Login', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="text" id="account_login" name="account_login" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="capacity"><?php echo esc_html__('Capacity', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="number" id="capacity" name="capacity" min="1" max="100" value="1" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="status"><?php echo esc_html__('Status', 'vd-license-manager'); ?></label></th>
                                <td>
                                    <select id="status" name="status">
                                        <option value="1"><?php echo esc_html__('Active', 'vd-license-manager'); ?></option>
                                        <option value="0"><?php echo esc_html__('Suspended', 'vd-license-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Cookie Info Section -->
                <div class="postbox">
                    <h3 class="hndle"><span><?php echo esc_html__('Cookie Information', 'vd-license-manager'); ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="cookie_format"><?php echo esc_html__('Cookie Format', 'vd-license-manager'); ?></label></th>
                                <td>
                                    <select id="cookie_format" name="cookie_format">
                                        <option value="json">JSON</option>
                                        <option value="netscape">Netscape</option>
                                        <option value="headers">Headers</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cookie_data"><?php echo esc_html__('Cookie Data', 'vd-license-manager'); ?></label></th>
                                <td><textarea id="cookie_data" name="cookie_data" rows="5" class="large-text"></textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(__('Add Account', 'vd-license-manager'), 'primary', 'submit'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts')); ?>" class="button">
                    <?php echo esc_html__('Cancel', 'vd-license-manager'); ?>
                </a>
            </form>
        </div>

        <style>
        .vd-account-form .postbox { margin-top: 20px; }
        .vd-account-form .postbox h3 { padding: 8px 12px; margin: 0; }
        .vd-account-form .inside { padding: 6px 10px 8px; }
        </style>
        <?php
    }

    /**
     * Handle form submission
     *
     * @since 1.0.0
     * @return array Result with success status and errors
     */
    private static function handle_submit() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_account_nonce'], 'vd_add_account')) {
            return array('success' => false, 'errors' => array(__('Security check failed.', 'vd-license-manager')));
        }

        // Collect form data
        $data = array(
            'provider' => sanitize_text_field($_POST['provider']),
            'display_name' => sanitize_text_field($_POST['display_name']),
            'account_login' => sanitize_text_field($_POST['account_login']),
            'capacity' => (int) $_POST['capacity'],
            'status' => (int) $_POST['status'],
            'cookie_format' => sanitize_text_field($_POST['cookie_format']),
            'cookie_data' => sanitize_textarea_field($_POST['cookie_data'])
        );

        // Validate form
        $validation = self::validate_form($data);
        if (!$validation['valid']) {
            return array('success' => false, 'errors' => $validation['errors']);
        }

        // Prepare cookie data
        $cookie_json = '';
        if (!empty($data['cookie_data'])) {
            if ($data['cookie_format'] === 'json') {
                $cookie_json = $data['cookie_data'];
            } else {
                // Convert other formats to JSON
                $cookie_json = VD_Data::format_cookie($data['cookie_data'], $data['cookie_format'], 'json');
            }
        }

        // Insert into database
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'vd_provider_accounts',
            array(
                'name' => $data['display_name'],
                'provider' => $data['provider'],
                'account_login' => $data['account_login'],
                'capacity' => $data['capacity'],
                'is_active' => $data['status'],
                'cookie_data' => $cookie_json,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            return array('success' => false, 'errors' => array(__('Failed to save account to database.', 'vd-license-manager')));
        }

        // Redirect to accounts list with success message
        wp_redirect(admin_url('admin.php?page=vd-provider-accounts&message=' . urlencode(__('Account added successfully!', 'vd-license-manager'))));
        exit;
    }

    /**
     * Validate form data
     *
     * @since 1.0.0
     * @param array $data Form data to validate
     * @return array Validation result
     */
    private static function validate_form($data) {
        global $wpdb;
        $errors = array();

        // Required fields
        if (empty($data['provider'])) {
            $errors[] = __('Provider is required.', 'vd-license-manager');
        }

        if (empty($data['display_name'])) {
            $errors[] = __('Display name is required.', 'vd-license-manager');
        }

        if (empty($data['account_login'])) {
            $errors[] = __('Account login is required.', 'vd-license-manager');
        }

        if ($data['capacity'] < 1 || $data['capacity'] > 100) {
            $errors[] = __('Capacity must be between 1 and 100.', 'vd-license-manager');
        }

        // Check for duplicate account login
        if (!empty($data['account_login'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT account_id FROM {$wpdb->prefix}vd_provider_accounts WHERE account_login = %s",
                $data['account_login']
            ));

            if ($existing) {
                $errors[] = __('An account with this login already exists.', 'vd-license-manager');
            }
        }

        // Validate JSON cookie data if provided
        if (!empty($data['cookie_data']) && $data['cookie_format'] === 'json') {
            json_decode($data['cookie_data']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = __('Invalid JSON format in cookie data.', 'vd-license-manager');
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
}