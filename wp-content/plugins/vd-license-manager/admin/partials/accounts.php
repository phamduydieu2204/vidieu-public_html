<?php
/**
 * Provider Accounts admin page template
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

<div class="vd-accounts-page">
    <div class="vd-page-header">
        <h2><?php esc_html_e( 'Provider Accounts Management', 'vd-license-manager' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Manage provider account credentials (Netflix, Spotify, Helium10, etc.) that will be shared with customers via license keys.', 'vd-license-manager' ); ?>
        </p>
    </div>

    <div class="vd-placeholder">
        <div class="vd-placeholder-content">
            <h3><?php esc_html_e( 'Accounts Management Coming Soon', 'vd-license-manager' ); ?></h3>
            <p><?php esc_html_e( 'This feature will be implemented in Day 2-3 of development.', 'vd-license-manager' ); ?></p>

            <h4><?php esc_html_e( 'Planned Features:', 'vd-license-manager' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Add provider accounts with encrypted credentials', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Support for multiple credential types (username/password, cookies, API keys)', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Assign accounts to product pools', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Set account expiration dates', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Monitor account status and usage', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Bulk import/export functionality', 'vd-license-manager' ); ?></li>
            </ul>

            <div class="vd-mock-buttons">
                <button type="button" class="button button-primary" disabled>
                    <?php esc_html_e( 'Add New Account', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Import Accounts', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Test Encryption', 'vd-license-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="vd-mock-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Provider', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Account Login', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Pool', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Expires', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'vd-license-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="no-items">
                            <?php esc_html_e( 'No accounts found. Database tables will be created in Day 2-3.', 'vd-license-manager' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vd-encryption-status">
            <h4><?php esc_html_e( 'Encryption Status', 'vd-license-manager' ); ?></h4>
            <?php if ( VD_Encryption_Service::is_configured() ) : ?>
                <p class="vd-status-ok">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Encryption is properly configured and ready for use.', 'vd-license-manager' ); ?>
                </p>
                <?php if ( VD_Encryption_Service::test() ) : ?>
                    <p class="vd-status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'Encryption test passed - credentials will be safely encrypted.', 'vd-license-manager' ); ?>
                    </p>
                <?php else : ?>
                    <p class="vd-status-error">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e( 'Encryption test failed - please check your configuration.', 'vd-license-manager' ); ?>
                    </p>
                <?php endif; ?>
            <?php else : ?>
                <p class="vd-status-error">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e( 'Encryption is not configured. Please set VD_ENCRYPTION_KEY in wp-config.php.', 'vd-license-manager' ); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>