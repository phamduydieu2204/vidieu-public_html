<?php
/**
 * Access Logs admin page template
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

<div class="vd-logs-page">
    <div class="vd-page-header">
        <h2><?php esc_html_e( 'Access Logs', 'vd-license-manager' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Monitor license access attempts, API calls, and system events for security and analytics.', 'vd-license-manager' ); ?>
        </p>
    </div>

    <div class="vd-placeholder">
        <div class="vd-placeholder-content">
            <h3><?php esc_html_e( 'Access Logs Coming Soon', 'vd-license-manager' ); ?></h3>
            <p><?php esc_html_e( 'This feature will be implemented throughout the development process.', 'vd-license-manager' ); ?></p>

            <h4><?php esc_html_e( 'Planned Features:', 'vd-license-manager' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'License access logs with timestamps', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'API endpoint usage tracking', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Failed authentication attempts', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Device registration and access events', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Rate limiting violations', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'System errors and warnings', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Export logs for analysis', 'vd-license-manager' ); ?></li>
            </ul>

            <div class="vd-mock-buttons">
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Filter Logs', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Export CSV', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Clear Old Logs', 'vd-license-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="vd-mock-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Timestamp', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'License Key', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Device', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Result', 'vd-license-manager' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'vd-license-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="no-items">
                            <?php esc_html_e( 'No logs found. Logging will be implemented throughout development.', 'vd-license-manager' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vd-current-logs">
            <h4><?php esc_html_e( 'Recent Plugin Logs', 'vd-license-manager' ); ?></h4>
            <div class="vd-log-viewer">
                <?php
                $recent_logs = VD_Logger_Service::get_recent_logs( 20 );
                if ( ! empty( $recent_logs ) ) :
                ?>
                    <pre class="vd-log-content"><?php echo esc_html( implode( "\n", $recent_logs ) ); ?></pre>
                <?php else : ?>
                    <p><em><?php esc_html_e( 'No recent logs found.', 'vd-license-manager' ); ?></em></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>