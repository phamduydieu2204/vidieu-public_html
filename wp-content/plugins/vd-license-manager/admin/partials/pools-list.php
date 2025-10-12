<?php
/**
 * Admin Pools List Template
 *
 * Template for displaying and managing pools in the admin area.
 * Includes pool listing, add/edit forms, and CRUD operations.
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Pools Management', 'vd-license-manager'); ?>
    </h1>

    <?php
    // Display admin notices
    settings_errors('vd_pools');

    // Check if we're editing a pool
    $is_editing = isset($editing_pool) && !empty($editing_pool);
    $form_title = $is_editing ? __('Edit Pool', 'vd-license-manager') : __('Add New Pool', 'vd-license-manager');
    ?>

    <hr class="wp-header-end">

    <div class="vd-pools-container">

        <!-- Add/Edit Pool Form -->
        <div class="vd-pool-form-section">
            <div class="card">
                <h2 class="title"><?php echo esc_html($form_title); ?></h2>

                <form method="post" action="">
                    <?php wp_nonce_field('vd_pools_action', 'vd_pools_nonce'); ?>

                    <?php if ($is_editing): ?>
                        <input type="hidden" name="action" value="update_pool">
                        <input type="hidden" name="pool_id" value="<?php echo esc_attr($editing_pool['id']); ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create_pool">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="pool_name"><?php _e('Pool Name', 'vd-license-manager'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="pool_name"
                                    name="pool_name"
                                    value="<?php echo $is_editing ? esc_attr($editing_pool['name']) : ''; ?>"
                                    class="regular-text"
                                    required
                                >
                                <p class="description"><?php _e('Enter a descriptive name for this pool.', 'vd-license-manager'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="pool_description"><?php _e('Description', 'vd-license-manager'); ?></label>
                            </th>
                            <td>
                                <textarea
                                    id="pool_description"
                                    name="pool_description"
                                    rows="4"
                                    class="large-text"
                                ><?php echo $is_editing ? esc_textarea($editing_pool['description']) : ''; ?></textarea>
                                <p class="description"><?php _e('Optional description for this pool.', 'vd-license-manager'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="pool_status"><?php _e('Status', 'vd-license-manager'); ?></label>
                            </th>
                            <td>
                                <select id="pool_status" name="pool_status">
                                    <option value="active" <?php echo ($is_editing && $editing_pool['status'] === 'active') ? 'selected' : ''; ?>>
                                        <?php _e('Active', 'vd-license-manager'); ?>
                                    </option>
                                    <option value="inactive" <?php echo ($is_editing && $editing_pool['status'] === 'inactive') ? 'selected' : ''; ?>>
                                        <?php _e('Inactive', 'vd-license-manager'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('Active pools can be assigned to products and used for license assignment.', 'vd-license-manager'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <?php submit_button(
                            $is_editing ? __('Update Pool', 'vd-license-manager') : __('Create Pool', 'vd-license-manager'),
                            'primary',
                            'submit',
                            false
                        ); ?>

                        <?php if ($is_editing): ?>
                            <a href="<?php echo admin_url('admin.php?page=vd-pools'); ?>" class="button button-secondary">
                                <?php _e('Cancel', 'vd-license-manager'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>

        <!-- Pools List -->
        <div class="vd-pools-list-section">
            <div class="card">
                <h2 class="title"><?php _e('Existing Pools', 'vd-license-manager'); ?></h2>

                <?php if (empty($pools)): ?>
                    <div class="vd-no-pools">
                        <p><?php _e('No pools have been created yet.', 'vd-license-manager'); ?></p>
                        <p><?php _e('Create your first pool using the form above.', 'vd-license-manager'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-name">
                                        <?php _e('Pool Name', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-description">
                                        <?php _e('Description', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-status">
                                        <?php _e('Status', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-accounts">
                                        <?php _e('Accounts', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-products">
                                        <?php _e('Products', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-created">
                                        <?php _e('Created', 'vd-license-manager'); ?>
                                    </th>
                                    <th scope="col" class="manage-column column-actions">
                                        <?php _e('Actions', 'vd-license-manager'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pools as $pool): ?>
                                    <?php
                                    // Get account count for this pool
                                    $account_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->prefix}vd_pool_accounts WHERE pool_id = %d",
                                        $pool['id']
                                    ));

                                    // Get product count for this pool
                                    $product_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->prefix}vd_product_pools WHERE pool_id = %d",
                                        $pool['id']
                                    ));

                                    $status_class = $pool['status'] === 'active' ? 'status-active' : 'status-inactive';
                                    $status_text = $pool['status'] === 'active' ? __('Active', 'vd-license-manager') : __('Inactive', 'vd-license-manager');
                                    ?>
                                    <tr>
                                        <td class="column-name">
                                            <strong><?php echo esc_html($pool['name']); ?></strong>
                                        </td>
                                        <td class="column-description">
                                            <?php
                                            if (!empty($pool['description'])) {
                                                echo esc_html(wp_trim_words($pool['description'], 10));
                                            } else {
                                                echo '<em>' . __('No description', 'vd-license-manager') . '</em>';
                                            }
                                            ?>
                                        </td>
                                        <td class="column-status">
                                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                                <?php echo esc_html($status_text); ?>
                                            </span>
                                        </td>
                                        <td class="column-accounts">
                                            <span class="count-badge">
                                                <?php echo absint($account_count); ?>
                                            </span>
                                        </td>
                                        <td class="column-products">
                                            <span class="count-badge">
                                                <?php echo absint($product_count); ?>
                                            </span>
                                        </td>
                                        <td class="column-created">
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($pool['created_at']))); ?>
                                        </td>
                                        <td class="column-actions">
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo admin_url('admin.php?page=vd-pools&edit=' . $pool['id']); ?>">
                                                        <?php _e('Edit', 'vd-license-manager'); ?>
                                                    </a>
                                                </span>

                                                <?php if ($account_count == 0 && $product_count == 0): ?>
                                                    <span class="trash"> |
                                                        <a href="<?php echo wp_nonce_url(
                                                            admin_url('admin.php?page=vd-pools&action=delete_pool&id=' . $pool['id']),
                                                            'delete_pool_' . $pool['id']
                                                        ); ?>"
                                                        class="submitdelete"
                                                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this pool?', 'vd-license-manager'); ?>')">
                                                            <?php _e('Delete', 'vd-license-manager'); ?>
                                                        </a>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="trash"> |
                                                        <span class="delete-disabled" title="<?php esc_attr_e('Cannot delete pool with assigned accounts or products', 'vd-license-manager'); ?>">
                                                            <?php _e('Delete', 'vd-license-manager'); ?>
                                                        </span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.vd-pools-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-top: 20px;
}

.vd-pool-form-section .card,
.vd-pools-list-section .card {
    padding: 20px;
}

.required {
    color: #d63638;
}

.status-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #00a32a;
    color: white;
}

.status-inactive {
    background: #dba617;
    color: white;
}

.count-badge {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.vd-no-pools {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.delete-disabled {
    color: #a7aaad;
    cursor: not-allowed;
}

@media (max-width: 782px) {
    .vd-pools-container {
        grid-template-columns: 1fr;
    }

    .table-wrap {
        overflow-x: auto;
    }
}
</style>