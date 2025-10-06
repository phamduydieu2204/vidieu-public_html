<?php
/**
 * Edit provider account page for VD License Manager
 *
 * Handles editing existing provider accounts with usage statistics
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
 * VD Admin Accounts Edit class
 *
 * Renders edit account form and handles updates/deletion
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Accounts_Edit {

    /**
     * Render edit account page
     *
     * @since 1.0.0
     * @param int $account_id Account ID to edit
     */
    public static function render($account_id) {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;

        // Load account data
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_id = %d",
            $account_id
        ), ARRAY_A);

        if (!$account) {
            wp_die(__('Account not found.', 'vd-license-manager'));
        }

        $errors = array();
        $success = false;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'update') {
                    $result = self::handle_update($account_id);
                } elseif ($_POST['action'] === 'delete') {
                    $result = self::handle_delete($account_id);
                }

                if ($result['success']) {
                    $success = true;
                    if ($_POST['action'] === 'update') {
                        // Reload account data after update
                        $account = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_id = %d",
                            $account_id
                        ), ARRAY_A);
                    }
                } else {
                    $errors = $result['errors'];
                }
            }
        }

        $licenses = self::get_licenses_using_account($account_id);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Edit Provider Account', 'vd-license-manager'); ?></h1>

            <?php if ($success): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Account updated successfully!', 'vd-license-manager'); ?></p>
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
                <?php wp_nonce_field('vd_edit_account', 'vd_account_nonce'); ?>
                <input type="hidden" name="action" value="update" />

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
                                        <option value="helium10" <?php selected($account['provider'], 'helium10'); ?>>Helium10</option>
                                        <option value="freepik" <?php selected($account['provider'], 'freepik'); ?>>Freepik</option>
                                        <option value="midjourney" <?php selected($account['provider'], 'midjourney'); ?>>Midjourney</option>
                                        <option value="canva" <?php selected($account['provider'], 'canva'); ?>>Canva</option>
                                        <option value="other" <?php selected($account['provider'], 'other'); ?>><?php echo esc_html__('Other', 'vd-license-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="display_name"><?php echo esc_html__('Display Name', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="text" id="display_name" name="display_name" class="regular-text" value="<?php echo esc_attr($account['name']); ?>" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="account_login"><?php echo esc_html__('Account Login', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="text" id="account_login" name="account_login" class="regular-text" value="<?php echo esc_attr($account['account_login']); ?>" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="capacity"><?php echo esc_html__('Capacity', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="number" id="capacity" name="capacity" min="1" max="100" value="<?php echo esc_attr($account['capacity']); ?>" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="status"><?php echo esc_html__('Status', 'vd-license-manager'); ?></label></th>
                                <td>
                                    <select id="status" name="status">
                                        <option value="1" <?php selected($account['is_active'], 1); ?>><?php echo esc_html__('Active', 'vd-license-manager'); ?></option>
                                        <option value="0" <?php selected($account['is_active'], 0); ?>><?php echo esc_html__('Suspended', 'vd-license-manager'); ?></option>
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
                                <th scope="row"><label for="cookie_data"><?php echo esc_html__('Cookie Data', 'vd-license-manager'); ?></label></th>
                                <td><textarea id="cookie_data" name="cookie_data" rows="5" class="large-text"><?php echo esc_textarea($account['cookie_data']); ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="submit-buttons">
                    <?php submit_button(__('Update Account', 'vd-license-manager'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts')); ?>" class="button">
                        <?php echo esc_html__('Cancel', 'vd-license-manager'); ?>
                    </a>
                </div>
            </form>

            <!-- Usage Statistics -->
            <div class="postbox" style="margin-top: 20px;">
                <h3 class="hndle"><span><?php echo esc_html__('Usage Statistics', 'vd-license-manager'); ?></span></h3>
                <div class="inside">
                    <?php
                    $total_assignments = count($licenses);
                    $active_assignments = count(array_filter($licenses, function($license) {
                        return $license['status'] === 'active';
                    }));
                    $usage_percentage = $account['capacity'] > 0 ? round(($active_assignments / $account['capacity']) * 100) : 0;
                    ?>
                    <p><strong><?php echo esc_html__('Active Assignments:', 'vd-license-manager'); ?></strong> <?php echo $active_assignments; ?>/<?php echo $account['capacity']; ?> (<?php echo $usage_percentage; ?>%)</p>
                    <p><strong><?php echo esc_html__('Total Assignments:', 'vd-license-manager'); ?></strong> <?php echo $total_assignments; ?></p>
                </div>
            </div>

            <!-- Licenses Using This Account -->
            <div class="postbox" style="margin-top: 20px;">
                <h3 class="hndle"><span><?php echo esc_html__('Licenses Using This Account', 'vd-license-manager'); ?></span></h3>
                <div class="inside">
                    <?php if (empty($licenses)): ?>
                        <p><?php echo esc_html__('No licenses are currently using this account.', 'vd-license-manager'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('License Key', 'vd-license-manager'); ?></th>
                                    <th><?php echo esc_html__('Status', 'vd-license-manager'); ?></th>
                                    <th><?php echo esc_html__('Assigned Date', 'vd-license-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($licenses as $license): ?>
                                    <tr>
                                        <td><?php echo esc_html(substr($license['license_key'], 0, 20) . '...'); ?></td>
                                        <td>
                                            <span style="color: <?php echo $license['status'] === 'active' ? '#46b450' : '#dc3232'; ?>;">
                                                <?php echo esc_html(ucfirst($license['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(VD_DateTime::format_datetime($license['assigned_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Delete Account -->
            <?php if ($active_assignments === 0): ?>
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('vd_edit_account', 'vd_account_nonce'); ?>
                    <input type="hidden" name="action" value="delete" />
                    <input type="submit" class="button button-secondary" value="<?php echo esc_attr__('Delete Account', 'vd-license-manager'); ?>"
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this account? This action cannot be undone.', 'vd-license-manager')); ?>')" />
                </form>
            <?php else: ?>
                <p style="margin-top: 20px; color: #dc3232;">
                    <strong><?php echo esc_html__('Cannot delete:', 'vd-license-manager'); ?></strong>
                    <?php echo sprintf(esc_html__('This account has %d active license assignments.', 'vd-license-manager'), $active_assignments); ?>
                </p>
            <?php endif; ?>
        </div>

        <style>
        .submit-buttons { margin-top: 10px; }
        .submit-buttons .button { margin-right: 10px; }
        .vd-account-form .postbox { margin-top: 20px; }
        .vd-account-form .postbox h3 { padding: 8px 12px; margin: 0; }
        .vd-account-form .inside { padding: 6px 10px 8px; }
        </style>
        <?php
    }

    /**
     * Handle account update
     *
     * @since 1.0.0
     * @param int $account_id Account ID to update
     * @return array Result with success status and errors
     */
    private static function handle_update($account_id) {
        global $wpdb;

        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_account_nonce'], 'vd_edit_account')) {
            return array('success' => false, 'errors' => array(__('Security check failed.', 'vd-license-manager')));
        }

        // Get original account data
        $original = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_provider_accounts WHERE account_id = %d",
            $account_id
        ), ARRAY_A);

        // Collect form data
        $data = array(
            'provider' => sanitize_text_field($_POST['provider']),
            'display_name' => sanitize_text_field($_POST['display_name']),
            'account_login' => sanitize_text_field($_POST['account_login']),
            'capacity' => (int) $_POST['capacity'],
            'status' => (int) $_POST['status'],
            'cookie_data' => sanitize_textarea_field($_POST['cookie_data'])
        );

        $update_data = array(
            'name' => $data['display_name'],
            'provider' => $data['provider'],
            'account_login' => $data['account_login'],
            'capacity' => $data['capacity'],
            'is_active' => $data['status'],
            'cookie_data' => $data['cookie_data'],
            'updated_at' => current_time('mysql')
        );

        // Check if cookie data changed
        if ($original['cookie_data'] !== $data['cookie_data']) {
            $update_data['cookie_updated_at'] = current_time('mysql');
        }

        // Update database
        $result = $wpdb->update(
            $wpdb->prefix . 'vd_provider_accounts',
            $update_data,
            array('account_id' => $account_id),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return array('success' => false, 'errors' => array(__('Failed to update account.', 'vd-license-manager')));
        }

        return array('success' => true, 'errors' => array());
    }

    /**
     * Handle account deletion
     *
     * @since 1.0.0
     * @param int $account_id Account ID to delete
     * @return array Result with success status and errors
     */
    private static function handle_delete($account_id) {
        global $wpdb;

        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_account_nonce'], 'vd_edit_account')) {
            return array('success' => false, 'errors' => array(__('Security check failed.', 'vd-license-manager')));
        }

        // Check for active assignments
        $active_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vd_cookie_assignments WHERE provider_account_id = %d AND status = 'active'",
            $account_id
        ));

        if ($active_count > 0) {
            return array('success' => false, 'errors' => array(
                sprintf(__('Cannot delete account with %d active license assignments.', 'vd-license-manager'), $active_count)
            ));
        }

        // Delete account
        $result = $wpdb->delete(
            $wpdb->prefix . 'vd_provider_accounts',
            array('account_id' => $account_id),
            array('%d')
        );

        if ($result === false) {
            return array('success' => false, 'errors' => array(__('Failed to delete account.', 'vd-license-manager')));
        }

        // Redirect to accounts list
        wp_redirect(admin_url('admin.php?page=vd-provider-accounts&message=' . urlencode(__('Account deleted successfully!', 'vd-license-manager'))));
        exit;
    }

    /**
     * Get licenses using this account
     *
     * @since 1.0.0
     * @param int $account_id Account ID
     * @return array License assignments
     */
    private static function get_licenses_using_account($account_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT ca.assignment_id, ca.license_id, ca.status, ca.assigned_at, l.license_key
             FROM {$wpdb->prefix}vd_cookie_assignments ca
             LEFT JOIN {$wpdb->prefix}lmfwc_licenses l ON ca.license_id = l.id
             WHERE ca.provider_account_id = %d
             ORDER BY ca.assigned_at DESC",
            $account_id
        ), ARRAY_A);
    }
}