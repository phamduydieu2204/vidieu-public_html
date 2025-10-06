<?php
/**
 * Add/Edit product pool page for VD License Manager
 *
 * Handles creating and editing product pools with account assignments
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
 * VD Admin Pools Edit class
 *
 * Renders pool form and handles creation/updates
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Pools_Edit {

    /**
     * Render pool edit/add page
     *
     * @since 1.0.0
     * @param int|null $pool_id Pool ID for editing, null for adding
     */
    public static function render($pool_id = null) {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $is_edit = !empty($pool_id);
        $pool = null;
        $pool_accounts = array();

        if ($is_edit) {
            $pool = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vd_product_pools WHERE pool_id = %d",
                $pool_id
            ), ARRAY_A);

            if (!$pool) {
                wp_die(__('Pool not found.', 'vd-license-manager'));
            }

            $pool_accounts = $wpdb->get_results($wpdb->prepare(
                "SELECT poa.*, pa.name, pa.account_login, pa.capacity
                 FROM {$wpdb->prefix}vd_pool_accounts poa
                 JOIN {$wpdb->prefix}vd_provider_accounts pa ON poa.provider_account_id = pa.account_id
                 WHERE poa.pool_id = %d AND poa.is_active = 1",
                $pool_id
            ), ARRAY_A);
        }

        $errors = array();
        $success = false;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vd_pool_nonce'])) {
            $result = self::handle_save($pool_id);
            if ($result['success']) {
                $success = true;
                if (!$is_edit) {
                    wp_redirect(admin_url('admin.php?page=vd-product-pools&message=' . urlencode(__('Pool created successfully!', 'vd-license-manager'))));
                    exit;
                }
            } else {
                $errors = $result['errors'];
            }
        }

        $available_products = self::get_available_products($pool_id);
        $available_accounts = self::get_available_accounts();
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? esc_html__('Edit Product Pool', 'vd-license-manager') : esc_html__('Add Product Pool', 'vd-license-manager'); ?></h1>

            <?php if ($success): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Pool saved successfully!', 'vd-license-manager'); ?></p>
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

            <form method="post" action="" class="vd-pool-form">
                <?php wp_nonce_field('vd_save_pool', 'vd_pool_nonce'); ?>

                <!-- Basic Info Section -->
                <div class="postbox">
                    <h3 class="hndle"><span><?php echo esc_html__('Basic Information', 'vd-license-manager'); ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="product_id"><?php echo esc_html__('Product', 'vd-license-manager'); ?> *</label></th>
                                <td>
                                    <select id="product_id" name="product_id" required>
                                        <option value=""><?php echo esc_html__('Select Product', 'vd-license-manager'); ?></option>
                                        <?php foreach ($available_products as $product): ?>
                                            <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($pool['product_id'] ?? '', $product->ID); ?>>
                                                <?php echo esc_html($product->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_name"><?php echo esc_html__('Pool Name', 'vd-license-manager'); ?> *</label></th>
                                <td><input type="text" id="pool_name" name="pool_name" class="regular-text" value="<?php echo esc_attr($pool['name'] ?? ''); ?>" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('Strategy', 'vd-license-manager'); ?> *</th>
                                <td>
                                    <fieldset>
                                        <label><input type="radio" name="strategy" value="sticky" <?php checked($pool['strategy'] ?? 'sticky', 'sticky'); ?> /> <?php echo esc_html__('Sticky (Lowest usage first)', 'vd-license-manager'); ?></label><br>
                                        <label><input type="radio" name="strategy" value="weighted" <?php checked($pool['strategy'] ?? '', 'weighted'); ?> /> <?php echo esc_html__('Weighted (Based on weights)', 'vd-license-manager'); ?></label><br>
                                        <label><input type="radio" name="strategy" value="priority" <?php checked($pool['strategy'] ?? '', 'priority'); ?> /> <?php echo esc_html__('Priority (Order based)', 'vd-license-manager'); ?></label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($is_edit): ?>
                    <!-- Accounts in Pool Section -->
                    <div class="postbox">
                        <h3 class="hndle"><span><?php echo esc_html__('Accounts in Pool', 'vd-license-manager'); ?></span></h3>
                        <div class="inside">
                            <?php if (empty($pool_accounts)): ?>
                                <p><?php echo esc_html__('No accounts assigned to this pool yet.', 'vd-license-manager'); ?></p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo esc_html__('Account', 'vd-license-manager'); ?></th>
                                            <th><?php echo esc_html__('Capacity', 'vd-license-manager'); ?></th>
                                            <th><?php echo esc_html__('Weight', 'vd-license-manager'); ?></th>
                                            <th><?php echo esc_html__('Priority', 'vd-license-manager'); ?></th>
                                            <th><?php echo esc_html__('Actions', 'vd-license-manager'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pool_accounts as $account): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($account['name']); ?></strong><br>
                                                    <small><?php echo esc_html($account['account_login']); ?></small>
                                                </td>
                                                <td><?php echo esc_html($account['capacity']); ?></td>
                                                <td><?php echo esc_html($account['weight'] ?: '-'); ?></td>
                                                <td><?php echo esc_html($account['priority'] ?: '-'); ?></td>
                                                <td>
                                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=vd-product-pools&action=remove_account&pool_id=' . $pool_id . '&account_id=' . $account['provider_account_id']), 'remove_account')); ?>"
                                                       class="button button-small"
                                                       onclick="return confirm('<?php echo esc_js(__('Remove this account from pool?', 'vd-license-manager')); ?>')">
                                                        <?php echo esc_html__('Remove', 'vd-license-manager'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <h4><?php echo esc_html__('Add Accounts', 'vd-license-manager'); ?></h4>
                            <?php if (empty($available_accounts)): ?>
                                <p><?php echo esc_html__('No available accounts to add.', 'vd-license-manager'); ?></p>
                            <?php else: ?>
                                <table class="form-table">
                                    <?php foreach ($available_accounts as $account): ?>
                                        <tr>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="account_ids[]" value="<?php echo esc_attr($account['account_id']); ?>" />
                                                    <strong><?php echo esc_html($account['name']); ?></strong> (<?php echo esc_html($account['account_login']); ?>)
                                                </label>
                                            </td>
                                            <td>
                                                <label><?php echo esc_html__('Weight:', 'vd-license-manager'); ?>
                                                    <input type="number" name="weights[<?php echo esc_attr($account['account_id']); ?>]" value="1" min="1" style="width: 60px;" />
                                                </label>
                                            </td>
                                            <td>
                                                <label><?php echo esc_html__('Priority:', 'vd-license-manager'); ?>
                                                    <input type="number" name="priorities[<?php echo esc_attr($account['account_id']); ?>]" value="1" min="1" style="width: 60px;" />
                                                </label>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php submit_button($is_edit ? __('Update Pool', 'vd-license-manager') : __('Create Pool', 'vd-license-manager'), 'primary', 'submit'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-pools')); ?>" class="button">
                    <?php echo esc_html__('Cancel', 'vd-license-manager'); ?>
                </a>
            </form>
        </div>

        <style>
        .vd-pool-form .postbox { margin-top: 20px; }
        .vd-pool-form .postbox h3 { padding: 8px 12px; margin: 0; }
        .vd-pool-form .inside { padding: 6px 10px 8px; }
        .vd-pool-form fieldset label { display: block; margin-bottom: 5px; }
        </style>
        <?php
    }

    /**
     * Handle pool save (create/update)
     *
     * @since 1.0.0
     * @param int|null $pool_id Pool ID for update, null for create
     * @return array Result with success status and errors
     */
    private static function handle_save($pool_id = null) {
        global $wpdb;

        // Verify nonce
        if (!wp_verify_nonce($_POST['vd_pool_nonce'], 'vd_save_pool')) {
            return array('success' => false, 'errors' => array(__('Security check failed.', 'vd-license-manager')));
        }

        // Collect form data
        $data = array(
            'product_id' => (int) $_POST['product_id'],
            'pool_name' => sanitize_text_field($_POST['pool_name']),
            'strategy' => sanitize_text_field($_POST['strategy'])
        );

        // Validate required fields
        $errors = array();
        if (empty($data['product_id'])) {
            $errors[] = __('Product is required.', 'vd-license-manager');
        }
        if (empty($data['pool_name'])) {
            $errors[] = __('Pool name is required.', 'vd-license-manager');
        }
        if (!in_array($data['strategy'], array('sticky', 'weighted', 'priority'))) {
            $errors[] = __('Invalid strategy selected.', 'vd-license-manager');
        }

        // Check product uniqueness
        if (!empty($data['product_id'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vd_product_pools WHERE product_id = %d AND pool_id != %d",
                $data['product_id'],
                $pool_id ?: 0
            ));

            if ($existing > 0) {
                $errors[] = __('This product already has a pool assigned.', 'vd-license-manager');
            }
        }

        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        // Save pool
        $pool_data = array(
            'name' => $data['pool_name'],
            'product_id' => $data['product_id'],
            'strategy' => $data['strategy'],
            'updated_at' => current_time('mysql')
        );

        if ($pool_id) {
            // Update existing pool
            $result = $wpdb->update(
                $wpdb->prefix . 'vd_product_pools',
                $pool_data,
                array('pool_id' => $pool_id),
                array('%s', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new pool
            $pool_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                $wpdb->prefix . 'vd_product_pools',
                $pool_data,
                array('%s', '%d', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            return array('success' => false, 'errors' => array(__('Failed to save pool.', 'vd-license-manager')));
        }

        // Handle account assignments for edit mode
        if ($pool_id && !empty($_POST['account_ids'])) {
            self::handle_add_accounts($pool_id);
        }

        return array('success' => true, 'errors' => array());
    }

    /**
     * Handle adding accounts to pool
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID
     */
    private static function handle_add_accounts($pool_id) {
        global $wpdb;

        $account_ids = array_map('intval', $_POST['account_ids']);
        $weights = $_POST['weights'] ?? array();
        $priorities = $_POST['priorities'] ?? array();

        foreach ($account_ids as $account_id) {
            $wpdb->insert(
                $wpdb->prefix . 'vd_pool_accounts',
                array(
                    'pool_id' => $pool_id,
                    'provider_account_id' => $account_id,
                    'weight' => (int) ($weights[$account_id] ?? 1),
                    'priority' => (int) ($priorities[$account_id] ?? 1),
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%d', '%s')
            );
        }
    }

    /**
     * Get available products (not in other pools)
     *
     * @since 1.0.0
     * @param int|null $exclude_pool_id Pool ID to exclude
     * @return array Available products
     */
    private static function get_available_products($exclude_pool_id = null) {
        global $wpdb;

        $used_products = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}vd_product_pools WHERE pool_id != %d",
            $exclude_pool_id ?: 0
        ));

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        if (!empty($used_products)) {
            $args['post__not_in'] = $used_products;
        }

        $products = get_posts($args);
        return array_map('get_post', $products);
    }

    /**
     * Get available accounts (not in pool yet)
     *
     * @since 1.0.0
     * @return array Available accounts
     */
    private static function get_available_accounts() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT account_id, name, account_login, capacity
             FROM {$wpdb->prefix}vd_provider_accounts
             WHERE is_active = 1
             ORDER BY name ASC",
            ARRAY_A
        );
    }
}