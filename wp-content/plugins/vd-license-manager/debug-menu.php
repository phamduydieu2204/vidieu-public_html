<?php
/**
 * Debug script to check if License Sync menu is registered
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo '<h1>Debug: License Sync Menu Check</h1>';

global $menu, $submenu;

echo '<h2>Main Menu Items:</h2>';
echo '<pre>';
foreach ($menu as $item) {
    if (strpos($item[2], 'vd') !== false) {
        print_r($item);
    }
}
echo '</pre>';

echo '<h2>VD License Manager Submenus:</h2>';
echo '<pre>';
if (isset($submenu['vd-license-manager'])) {
    print_r($submenu['vd-license-manager']);
} else {
    echo 'No submenus found for vd-license-manager';
}
echo '</pre>';

echo '<h2>All Admin Pages with "vd" or "license":</h2>';
echo '<pre>';
foreach ($submenu as $parent => $items) {
    if (strpos($parent, 'vd') !== false || strpos($parent, 'license') !== false) {
        echo "Parent: $parent\n";
        print_r($items);
        echo "\n";
    }
}
echo '</pre>';

echo '<h2>Test Links:</h2>';
echo '<ul>';
echo '<li><a href="/wp-admin/admin.php?page=vd-license-manager">Main VD License Manager</a></li>';
echo '<li><a href="/wp-admin/admin.php?page=vd-license-sync">License Sync (should work)</a></li>';
echo '</ul>';

echo '<h2>Direct Class Check:</h2>';
if (class_exists('VD_License_Sync_Admin')) {
    echo '✅ VD_License_Sync_Admin class exists<br>';
} else {
    echo '❌ VD_License_Sync_Admin class NOT found<br>';
}

echo '<h2>Hook Check:</h2>';
$hooks_registered = has_action('admin_menu', ['VD_License_Sync_Admin', 'add_admin_menu']);
if ($hooks_registered) {
    echo '✅ admin_menu hook registered<br>';
} else {
    echo '❌ admin_menu hook NOT registered<br>';
}

echo '<p><strong>Expected URL:</strong> <code>https://vidieu.vn/wp-admin/admin.php?page=vd-license-sync</code></p>';
?>