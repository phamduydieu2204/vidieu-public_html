<?php
/**
 * Share Configs List View
 *
 * Displays all WooCommerce products with their share configuration settings.
 * Admin can configure max devices, validity days, max requests, and VPS settings.
 *
 * @package    VD_License_Manager
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

defined('ABSPATH') || exit;
?>

<div class="wrap vd-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Product Share Configurations', 'vd-license-manager'); ?>
    </h1>

    <hr class="wp-header-end">

    <?php settings_errors('vd_share_configs'); ?>

    <p class="description">
        <?php esc_html_e('Configure license sharing settings for each WooCommerce product. These settings control how customers can access and share their licenses.', 'vd-license-manager'); ?>
    </p>

    <?php if (empty($products)): ?>
        <div class="notice notice-warning">
            <p>
                <?php esc_html_e('No WooCommerce products found. Please create products first.', 'vd-license-manager'); ?>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="button button-secondary">
                    <?php esc_html_e('Create Product', 'vd-license-manager'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>

        <form method="post" id="vd-share-configs-form">
            <?php wp_nonce_field('vd_lm_share_config_action', 'vd_lm_nonce'); ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 80px;">
                            <?php esc_html_e('ID', 'vd-license-manager'); ?>
                        </th>
                        <th scope="col">
                            <?php esc_html_e('Product Name', 'vd-license-manager'); ?>
                        </th>
                        <th scope="col" style="width: 120px;">
                            <?php esc_html_e('Max Devices', 'vd-license-manager'); ?>
                            <span class="dashicons dashicons-info-outline"
                                  title="Maximum number of devices that can use this license"></span>
                        </th>
                        <th scope="col" style="width: 120px;">
                            <?php esc_html_e('Validity (Days)', 'vd-license-manager'); ?>
                            <span class="dashicons dashicons-info-outline"
                                  title="How long the license is valid after purchase"></span>
                        </th>
                        <th scope="col" style="width: 140px;">
                            <?php esc_html_e('Max Requests/Day', 'vd-license-manager'); ?>
                            <span class="dashicons dashicons-info-outline"
                                  title="Maximum API requests per day per license"></span>
                        </th>
                        <th scope="col" style="width: 100px;">
                            <?php esc_html_e('Allow VPS', 'vd-license-manager'); ?>
                            <span class="dashicons dashicons-info-outline"
                                  title="Allow access from VPS/Cloud servers"></span>
                        </th>
                        <th scope="col" style="width: 150px;">
                            <?php esc_html_e('Actions', 'vd-license-manager'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product):
                        $product_id = $product->get_id();
                        $config = isset($configs[$product_id]) ? $configs[$product_id] : null;
                        $has_config = !empty($config);

                        // Default values
                        $max_devices = $has_config ? $config['max_devices'] : 2;
                        $validity_days = $has_config ? $config['validity_days'] : 30;
                        $max_requests = $has_config ? $config['max_requests_per_day'] : 100;
                        $allow_vps = $has_config ? (bool)$config['allow_vps'] : false;
                        $config_id = $has_config ? $config['id'] : 0;
                    ?>
                    <tr id="product-<?php echo esc_attr($product_id); ?>"
                        class="<?php echo $has_config ? 'configured' : 'not-configured'; ?>">

                        <td>
                            <strong><?php echo esc_html($product_id); ?></strong>
                        </td>

                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')); ?>" target="_blank">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </strong>
                            <?php if ($product->get_sku()): ?>
                                <br><small class="description">SKU: <?php echo esc_html($product->get_sku()); ?></small>
                            <?php endif; ?>
                            <br><small class="description">
                                <?php printf(esc_html__('Price: %s', 'vd-license-manager'), wc_price($product->get_price())); ?>
                            </small>
                            <?php if (!$has_config): ?>
                                <br><span class="description" style="color: #d63638;">
                                    <?php esc_html_e('Not configured', 'vd-license-manager'); ?>
                                </span>
                            <?php else: ?>
                                <br><span class="description" style="color: #00a32a;">
                                    <?php esc_html_e('Configured', 'vd-license-manager'); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <input type="number"
                                   name="configs[<?php echo esc_attr($product_id); ?>][max_devices]"
                                   value="<?php echo esc_attr($max_devices); ?>"
                                   min="1"
                                   max="10"
                                   class="small-text"
                                   style="width: 60px;">
                        </td>

                        <td>
                            <input type="number"
                                   name="configs[<?php echo esc_attr($product_id); ?>][validity_days]"
                                   value="<?php echo esc_attr($validity_days); ?>"
                                   min="1"
                                   max="3650"
                                   class="small-text"
                                   style="width: 80px;">
                        </td>

                        <td>
                            <input type="number"
                                   name="configs[<?php echo esc_attr($product_id); ?>][max_requests_per_day]"
                                   value="<?php echo esc_attr($max_requests); ?>"
                                   min="10"
                                   max="10000"
                                   class="small-text"
                                   style="width: 80px;">
                        </td>

                        <td>
                            <label class="vd-toggle-switch">
                                <input type="checkbox"
                                       name="configs[<?php echo esc_attr($product_id); ?>][allow_vps]"
                                       value="1"
                                       <?php checked($allow_vps, true); ?>>
                                <span class="vd-toggle-slider"></span>
                            </label>
                        </td>

                        <td>
                            <button type="button"
                                    class="button button-small vd-save-config"
                                    data-product-id="<?php echo esc_attr($product_id); ?>">
                                <?php esc_html_e('Save', 'vd-license-manager'); ?>
                            </button>

                            <?php if ($has_config && $config_id): ?>
                                <a href="<?php echo esc_url(wp_nonce_url(
                                    admin_url('admin.php?page=vd-share-configs&action=delete_config&id=' . $config_id),
                                    'delete_config_' . $config_id
                                )); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this config?', 'vd-license-manager'); ?>');">
                                    <?php esc_html_e('Delete', 'vd-license-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <div class="vd-config-info" style="margin-top: 20px;">
            <h3><?php esc_html_e('Configuration Guide', 'vd-license-manager'); ?></h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div>
                    <h4><?php esc_html_e('Settings Explanation', 'vd-license-manager'); ?></h4>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><strong><?php esc_html_e('Max Devices:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('Recommended: 2-3 for individual users, 5-10 for teams.', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Validity Days:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('Common values: 30 (monthly), 365 (yearly), 3650 (lifetime).', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Max Requests/Day:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('Prevents abuse. Recommended: 100-1000 depending on usage.', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Allow VPS:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('Enable if customers may use VPS/Cloud servers. Disable to block VPS access.', 'vd-license-manager'); ?>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4><?php esc_html_e('Common Configurations', 'vd-license-manager'); ?></h4>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><strong><?php esc_html_e('Personal License:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('2 devices, 365 days, 100 requests/day, VPS: No', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Team License:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('5 devices, 365 days, 500 requests/day, VPS: Yes', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Enterprise License:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('10 devices, 365 days, 1000 requests/day, VPS: Yes', 'vd-license-manager'); ?>
                        </li>
                        <li><strong><?php esc_html_e('Trial License:', 'vd-license-manager'); ?></strong>
                            <?php esc_html_e('1 device, 7 days, 50 requests/day, VPS: No', 'vd-license-manager'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
.vd-toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.vd-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.vd-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.vd-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.vd-toggle-switch input:checked + .vd-toggle-slider {
    background-color: #2271b1;
}

.vd-toggle-switch input:checked + .vd-toggle-slider:before {
    transform: translateX(26px);
}

tr.configured {
    background-color: #f0f9ff;
}

tr.not-configured {
    background-color: #fff9f0;
}

.dashicons-info-outline {
    color: #999;
    cursor: help;
    font-size: 16px;
    vertical-align: middle;
}

.vd-config-info {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.vd-config-info h3 {
    margin-top: 0;
    color: #1d2327;
}

.vd-config-info h4 {
    margin-bottom: 10px;
    color: #2271b1;
}

.vd-config-info ul li {
    margin-bottom: 8px;
    line-height: 1.4;
}

/* Loading states */
.vd-save-config:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.vd-save-config.saving {
    background-color: #f0f0f1;
    color: #50575e;
}

.vd-save-config.saved {
    background-color: #00a32a;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Will be enhanced with AJAX functionality
    console.log('VD Share Configs: Page loaded');
});
</script>