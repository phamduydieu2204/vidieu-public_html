<?php
/**
 * Direct access to License Sync functionality
 * Use this if admin menu is not working
 */

// Include WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Include the License Sync Admin class
require_once('admin/class-vd-license-sync-admin.php');

// Create instance and render page directly
$sync_admin = new VD_License_Sync_Admin();

// Handle AJAX if needed
if (isset($_POST['action']) && $_POST['action'] === 'vd_sync_licenses') {
    $sync_admin->ajax_sync_licenses();
    exit;
}

// Render the page
echo '<style>body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; margin: 20px; }</style>';
echo '<h1>🔧 Direct Access - License Sync</h1>';
echo '<p><strong>Note:</strong> This is direct access. For normal use, go to: <a href="/wp-admin/admin.php?page=vd-license-sync">WP Admin → VD License Manager → License Sync</a></p>';
echo '<hr>';

$sync_admin->render_page();
?>