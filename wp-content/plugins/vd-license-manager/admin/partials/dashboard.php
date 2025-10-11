<?php
/**
 * Dashboard admin page template
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

<div class="vd-dashboard">
    <div class="vd-dashboard-stats">
        <h2><?php esc_html_e( 'Overview', 'vd-license-manager' ); ?></h2>

        <div class="vd-stats-grid">
            <div class="vd-stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label"><?php esc_html_e( 'Active Licenses', 'vd-license-manager' ); ?></div>
            </div>

            <div class="vd-stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label"><?php esc_html_e( 'Product Pools', 'vd-license-manager' ); ?></div>
            </div>

            <div class="vd-stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label"><?php esc_html_e( 'Provider Accounts', 'vd-license-manager' ); ?></div>
            </div>

            <div class="vd-stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label"><?php esc_html_e( 'Registered Devices', 'vd-license-manager' ); ?></div>
            </div>
        </div>
    </div>

    <div class="vd-dashboard-quick-actions">
        <h3><?php esc_html_e( 'Quick Actions', 'vd-license-manager' ); ?></h3>

        <div class="vd-quick-actions-grid">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-pools' ) ); ?>" class="vd-quick-action">
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e( 'Manage Pools', 'vd-license-manager' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-accounts' ) ); ?>" class="vd-quick-action">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e( 'Add Account', 'vd-license-manager' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-licenses' ) ); ?>" class="vd-quick-action">
                <span class="dashicons dashicons-admin-network"></span>
                <?php esc_html_e( 'View Licenses', 'vd-license-manager' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-settings' ) ); ?>" class="vd-quick-action">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Settings', 'vd-license-manager' ); ?>
            </a>
        </div>
    </div>

    <div class="vd-dashboard-system-status">
        <h3><?php esc_html_e( 'System Status', 'vd-license-manager' ); ?></h3>

        <table class="vd-status-table">
            <tr>
                <td><?php esc_html_e( 'Plugin Version', 'vd-license-manager' ); ?></td>
                <td><strong><?php echo esc_html( VD_PLUGIN_VERSION ); ?></strong></td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'WordPress Version', 'vd-license-manager' ); ?></td>
                <td><strong><?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong></td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'PHP Version', 'vd-license-manager' ); ?></td>
                <td><strong><?php echo esc_html( PHP_VERSION ); ?></strong></td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'WooCommerce', 'vd-license-manager' ); ?></td>
                <td>
                    <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                        <span class="vd-status-ok">✓ <?php esc_html_e( 'Active', 'vd-license-manager' ); ?></span>
                    <?php else : ?>
                        <span class="vd-status-error">✗ <?php esc_html_e( 'Not Active', 'vd-license-manager' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'License Manager for WooCommerce', 'vd-license-manager' ); ?></td>
                <td>
                    <?php if ( function_exists( 'lmfwc_get_license' ) ) : ?>
                        <span class="vd-status-ok">✓ <?php esc_html_e( 'Active', 'vd-license-manager' ); ?></span>
                    <?php else : ?>
                        <span class="vd-status-error">✗ <?php esc_html_e( 'Not Active', 'vd-license-manager' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'Encryption', 'vd-license-manager' ); ?></td>
                <td>
                    <?php if ( VD_LM_Encryption_Service::is_configured() ) : ?>
                        <span class="vd-status-ok">✓ <?php esc_html_e( 'Configured', 'vd-license-manager' ); ?></span>
                    <?php else : ?>
                        <span class="vd-status-error">✗ <?php esc_html_e( 'Not Configured', 'vd-license-manager' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>