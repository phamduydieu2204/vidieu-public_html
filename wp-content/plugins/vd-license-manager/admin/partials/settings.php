<?php
/**
 * Settings admin page template
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

<div class="vd-settings-page">
    <div class="vd-page-header">
        <h2><?php esc_html_e( 'Plugin Settings', 'vd-license-manager' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Configure global settings for VD License Manager plugin.', 'vd-license-manager' ); ?>
        </p>
    </div>

    <div class="vd-placeholder">
        <div class="vd-placeholder-content">
            <h3><?php esc_html_e( 'Settings Management Coming Soon', 'vd-license-manager' ); ?></h3>
            <p><?php esc_html_e( 'This feature will be implemented throughout the development process.', 'vd-license-manager' ); ?></p>

            <h4><?php esc_html_e( 'Planned Settings:', 'vd-license-manager' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Default device limits per license', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Rate limiting configuration', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'VPS detection settings', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Email notification preferences', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'Debug and logging options', 'vd-license-manager' ); ?></li>
                <li><?php esc_html_e( 'API endpoint configuration', 'vd-license-manager' ); ?></li>
            </ul>

            <div class="vd-mock-buttons">
                <button type="button" class="button button-primary" disabled>
                    <?php esc_html_e( 'Save Settings', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Reset to Defaults', 'vd-license-manager' ); ?>
                </button>
                <button type="button" class="button" disabled>
                    <?php esc_html_e( 'Test Email', 'vd-license-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="vd-current-config">
            <h4><?php esc_html_e( 'Current Configuration', 'vd-license-manager' ); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Plugin Version', 'vd-license-manager' ); ?></th>
                    <td><code><?php echo esc_html( VD_PLUGIN_VERSION ); ?></code></td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'WordPress Version', 'vd-license-manager' ); ?></th>
                    <td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'PHP Version', 'vd-license-manager' ); ?></th>
                    <td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Encryption Status', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php if ( VD_LM_Encryption_Service::is_configured() ) : ?>
                            <span class="vd-status-ok">✓ <?php esc_html_e( 'Configured', 'vd-license-manager' ); ?></span>
                        <?php else : ?>
                            <span class="vd-status-error">✗ <?php esc_html_e( 'Not Configured', 'vd-license-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Email Configuration', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php if ( VD_LM_Notification_Service::is_email_configured() ) : ?>
                            <span class="vd-status-ok">✓ <?php esc_html_e( 'Configured', 'vd-license-manager' ); ?></span>
                        <?php else : ?>
                            <span class="vd-status-warning">⚠ <?php esc_html_e( 'Basic Configuration', 'vd-license-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Debug Mode', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                            <span class="vd-status-warning">⚠ <?php esc_html_e( 'Enabled', 'vd-license-manager' ); ?></span>
                        <?php else : ?>
                            <span class="vd-status-ok">✓ <?php esc_html_e( 'Disabled', 'vd-license-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Plugin Directory', 'vd-license-manager' ); ?></th>
                    <td><code><?php echo esc_html( VD_PLUGIN_DIR ); ?></code></td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Plugin URL', 'vd-license-manager' ); ?></th>
                    <td><code><?php echo esc_html( VD_PLUGIN_URL ); ?></code></td>
                </tr>
            </table>

            <h4><?php esc_html_e( 'System Requirements Check', 'vd-license-manager' ); ?></h4>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'WordPress Version', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php
                        global $wp_version;
                        if ( version_compare( $wp_version, '6.0', '>=' ) ) :
                        ?>
                            <span class="vd-status-ok">✓ <?php echo esc_html( $wp_version ); ?> (<?php esc_html_e( 'Compatible', 'vd-license-manager' ); ?>)</span>
                        <?php else : ?>
                            <span class="vd-status-error">✗ <?php echo esc_html( $wp_version ); ?> (<?php esc_html_e( 'Requires 6.0+', 'vd-license-manager' ); ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'PHP Version', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) : ?>
                            <span class="vd-status-ok">✓ <?php echo esc_html( PHP_VERSION ); ?> (<?php esc_html_e( 'Compatible', 'vd-license-manager' ); ?>)</span>
                        <?php else : ?>
                            <span class="vd-status-error">✗ <?php echo esc_html( PHP_VERSION ); ?> (<?php esc_html_e( 'Requires 7.4+', 'vd-license-manager' ); ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'OpenSSL Extension', 'vd-license-manager' ); ?></th>
                    <td>
                        <?php if ( extension_loaded( 'openssl' ) ) : ?>
                            <span class="vd-status-ok">✓ <?php esc_html_e( 'Available', 'vd-license-manager' ); ?></span>
                        <?php else : ?>
                            <span class="vd-status-error">✗ <?php esc_html_e( 'Not Available', 'vd-license-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>