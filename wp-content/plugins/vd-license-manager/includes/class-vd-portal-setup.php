<?php
/**
 * VD Portal Setup - Clean Implementation
 *
 * Handles portal page creation and shortcode registration
 * Optimized for two-column layout rendering
 *
 * @package    VD_License_Manager
 * @subpackage Includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VD_Portal_Setup {

    /**
     * Constructor - Register hooks
     */
    public function __construct() {
        add_shortcode('vd_license_portal', array($this, 'render_portal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init', array($this, 'create_portal_page'));
    }

    /**
     * Create portal page if not exists
     */
    public function create_portal_page() {
        // Only create once
        if (get_option('vd_portal_created')) {
            return;
        }

        $page_id = wp_insert_post(array(
            'post_title'   => 'License Portal',
            'post_name'    => 'license-portal',
            'post_content' => '[vd_license_portal]',
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ));

        if ($page_id) {
            update_option('vd_portal_page_id', $page_id);
            update_option('vd_portal_created', true);
        }
    }

    /**
     * Enqueue assets only on portal page
     */
    public function enqueue_assets() {
        global $post;

        if (!is_page() || !$post || !has_shortcode($post->post_content, 'vd_license_portal')) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'vd-portal',
            VD_PLUGIN_URL . 'public/css/portal.css',
            array(),
            VD_PLUGIN_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'vd-portal',
            VD_PLUGIN_URL . 'public/js/portal.js',
            array('jquery'),
            VD_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('vd-portal', 'vdPortal', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('vd_portal_nonce')
        ));
    }

    /**
     * Render portal shortcode - Clean implementation
     */
    public function render_portal($atts) {
        $atts = shortcode_atts(array(
            'title'    => __('License Portal', 'vd-license-manager'),
            'subtitle' => __('Enter your license key to access your account', 'vd-license-manager')
        ), $atts);

        ob_start();
        ?>
        <!-- VD Portal - Two Column Layout -->
        <div class="vd-portal-wrapper">

            <!-- Header -->
            <div class="vd-portal-header">
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <!-- Two Column Container -->
            <div class="vd-portal-container">

                <!-- LEFT PANEL (60%) -->
                <div class="vd-portal-main">

                    <!-- License Input -->
                    <div class="vd-card">
                        <h2>🔑 Enter License Key</h2>
                        <form id="vd-license-form">
                            <?php wp_nonce_field('vd_portal_action', 'vd_nonce'); ?>
                            <div class="vd-form-group">
                                <label for="license-key">License Key:</label>
                                <input type="text"
                                       id="license-key"
                                       name="license_key"
                                       placeholder="XXXX-XXXX-XXXX-XXXX"
                                       pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                       required>
                            </div>
                            <button type="submit" class="vd-btn vd-btn-primary">
                                🔓 Access License
                            </button>
                        </form>
                    </div>

                    <!-- License Info (Hidden initially) -->
                    <div id="license-info" class="vd-card" style="display:none;">
                        <h2>📋 License Information</h2>
                        <div class="vd-info-grid">
                            <div class="vd-info-item">
                                <span class="label">License:</span>
                                <span class="value" id="display-license">-</span>
                            </div>
                            <div class="vd-info-item">
                                <span class="label">Status:</span>
                                <span class="value" id="display-status">-</span>
                            </div>
                            <div class="vd-info-item">
                                <span class="label">Expires:</span>
                                <span class="value" id="display-expires">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Account Credentials (Hidden initially) -->
                    <div id="credentials" class="vd-card" style="display:none;">
                        <h2>🔑 Account Access</h2>
                        <div id="credentials-list"></div>
                    </div>

                </div>
                <!-- END LEFT PANEL -->

                <!-- RIGHT SIDEBAR (40%) -->
                <div class="vd-portal-sidebar">

                    <!-- Tabs -->
                    <div class="vd-tabs">
                        <div class="vd-tab-buttons">
                            <button class="vd-tab-btn active" data-tab="devices">📱 Devices</button>
                            <button class="vd-tab-btn" data-tab="history">📊 History</button>
                        </div>

                        <!-- Devices Tab -->
                        <div id="tab-devices" class="vd-tab-content active">
                            <div class="vd-card">
                                <h3>Connected Devices</h3>
                                <div id="device-list" class="vd-empty-state">
                                    <p>📱 No devices connected</p>
                                </div>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div id="tab-history" class="vd-tab-content">
                            <div class="vd-card">
                                <h3>Access History</h3>
                                <div id="history-list" class="vd-empty-state">
                                    <p>📊 No access history</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- END RIGHT SIDEBAR -->

            </div>
            <!-- END TWO COLUMN CONTAINER -->

        </div>
        <!-- END PORTAL WRAPPER -->
        <?php
        return ob_get_clean();
    }
}

// Initialize
new VD_Portal_Setup();