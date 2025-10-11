<?php
/**
 * Device Management admin page template
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

<div class="vd-devices-page">
    <div class="vd-page-header">
        <h2><?php esc_html_e( 'Device Management', 'vd-license-manager' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Monitor and manage customer devices accessing shared accounts through license keys.', 'vd-license-manager' ); ?>
        </p>
    </div>

    <div class="vd-placeholder">
        <div class="vd-placeholder-content">
            <h3><?php esc_html_e( 'Device Management Coming Soon', 'vd-license-manager' ); ?></h3>
            <p><?php esc_html_e( 'This feature will be implemented in Day 4-5 of development.', 'vd-license-manager' ); ?></p>

            <h4><?php esc_html_e( 'Planned Features:', 'vd-license-manager' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'View all registered customer devices', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Device fingerprinting and tracking', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'VPS detection and blocking', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Device limit enforcement per license', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Manual device blocking/unblocking', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Device rotation and management', 'vd-license-manager' ); ?></li>
            </ul>

            <div class="vd-mock-buttons">
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Block Device', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Force Rotation', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Export Devices', 'vd-license-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="vd-mock-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Device ID', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'License Key', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Device Name', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'VPS Status', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Last Access', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'vd-license-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="no-items">
                            <?php esc_html_e( 'No devices found. Device tracking will be implemented in Day 4-5.', 'vd-license-manager' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>