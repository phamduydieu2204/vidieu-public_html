<?php
/**
 * Product Pools admin page template
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="vd-pools-page">
    <div class="vd-page-header">
        <h2><?php esc_html_e( 'Product Pools Management', 'vd-license-manager' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Product pools group provider accounts together and are assigned to WooCommerce products. When a customer purchases a product, a license is assigned an account from the corresponding pool.', 'vd-license-manager' ); ?>
        </p>
    </div>

    <div class="vd-placeholder">
        <div class="vd-placeholder-content">
            <h3><?php esc_html_e( 'Pools Management Coming Soon', 'vd-license-manager' ); ?></h3>
            <p><?php esc_html_e( 'This feature will be implemented in Day 2-3 of development.', 'vd-license-manager' ); ?></p>

            <h4><?php esc_html_e( 'Planned Features:', 'vd-license-manager' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Create and manage product pools', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Assign WooCommerce products to pools', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Set pool capacity limits', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Monitor pool usage and availability', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Configure pool assignment strategies', 'vd-license-manager' ); ?></li>
            </ul>

            <div class="vd-mock-buttons">
                <button type="button" class="button button-primary" disabled>
                    <?php esc_html_e( 'Add New Pool', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Import Pools', 'vd-license-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="vd-mock-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Pool Name', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Product', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Capacity', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Assigned', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'vd-license-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="no-items">
                            <?php esc_html_e( 'No pools found. Database tables will be created in Day 2-3.', 'vd-license-manager' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>