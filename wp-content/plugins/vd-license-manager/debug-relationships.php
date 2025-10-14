<?php
/**
 * Debug script to analyze Products ↔ Pools ↔ Accounts relationships
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo '<style>
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.highlight { background-color: #fff3cd; }
.success { background-color: #d4edda; }
.danger { background-color: #f8d7da; }
</style>';

echo '<h1>🔗 Products ↔ Pools ↔ Accounts Relationships Debug</h1>';

// 1. Table Counts
echo '<h2>📊 Table Counts</h2>';
echo '<table>';
echo '<tr><th>Table Name</th><th>Row Count</th><th>Status</th></tr>';

$tables = [
    'bz_vd_pools' => 'Pools',
    'bz_vd_provider_accounts' => 'Provider Accounts',
    'bz_vd_product_pools' => 'Product ↔ Pool Mappings',
    'bz_vd_pool_accounts' => 'Pool ↔ Account Mappings',
    'bz_vd_license_keys' => 'License Keys',
    'bz_vd_product_share_configs' => 'Product Share Configs'
];

foreach ($tables as $table => $name) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $status = $count > 0 ? '<span class="success">✅ Has Data</span>' : '<span class="danger">❌ Empty</span>';
    echo "<tr><td>{$name}</td><td>{$count}</td><td>{$status}</td></tr>";
}
echo '</table>';

// 2. Pools Analysis
echo '<h2>🏊 Pools Analysis</h2>';
$pools = $wpdb->get_results("
    SELECT p.*,
           COUNT(pa.account_id) as account_count,
           COUNT(pp.product_id) as product_count
    FROM bz_vd_pools p
    LEFT JOIN bz_vd_pool_accounts pa ON p.id = pa.pool_id AND pa.status = 'active'
    LEFT JOIN bz_vd_product_pools pp ON p.id = pp.pool_id
    GROUP BY p.id
", ARRAY_A);

if (!empty($pools)) {
    echo '<table>';
    echo '<tr><th>Pool ID</th><th>Name</th><th>Status</th><th>Accounts</th><th>Products</th><th>Created</th></tr>';
    foreach ($pools as $pool) {
        echo "<tr>";
        echo "<td>{$pool['id']}</td>";
        echo "<td>{$pool['name']}</td>";
        echo "<td>{$pool['status']}</td>";
        echo "<td>{$pool['account_count']}</td>";
        echo "<td>{$pool['product_count']}</td>";
        echo "<td>{$pool['created_at']}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No pools found</p>';
}

// 3. Provider Accounts Analysis
echo '<h2>👤 Provider Accounts Analysis</h2>';
$accounts = $wpdb->get_results("
    SELECT a.*,
           COUNT(pa.pool_id) as pool_count,
           COUNT(l.id) as license_count
    FROM bz_vd_provider_accounts a
    LEFT JOIN bz_vd_pool_accounts pa ON a.id = pa.account_id AND pa.status = 'active'
    LEFT JOIN bz_vd_license_keys l ON a.id = l.account_id AND l.status = 'active'
    GROUP BY a.id
", ARRAY_A);

if (!empty($accounts)) {
    echo '<table>';
    echo '<tr><th>Account ID</th><th>Provider</th><th>Login</th><th>Display Name</th><th>Capacity</th><th>Current Usage</th><th>Pools</th><th>Active Licenses</th><th>Status</th></tr>';
    foreach ($accounts as $account) {
        $usage_class = $account['current_usage'] >= $account['capacity'] ? 'danger' : 'success';
        echo "<tr>";
        echo "<td>{$account['id']}</td>";
        echo "<td>{$account['provider']}</td>";
        echo "<td>{$account['account_login']}</td>";
        echo "<td>{$account['display_name']}</td>";
        echo "<td>{$account['capacity']}</td>";
        echo "<td class='{$usage_class}'>{$account['current_usage']}</td>";
        echo "<td>{$account['pool_count']}</td>";
        echo "<td>{$account['license_count']}</td>";
        echo "<td>{$account['status']}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No provider accounts found</p>';
}

// 4. Product ↔ Pool Mappings
echo '<h2>🛍️ Product ↔ Pool Mappings</h2>';
$product_pools = $wpdb->get_results("
    SELECT pp.*,
           pr.post_title as product_name,
           p.name as pool_name,
           p.status as pool_status
    FROM bz_vd_product_pools pp
    LEFT JOIN wp_posts pr ON pp.product_id = pr.ID
    LEFT JOIN bz_vd_pools p ON pp.pool_id = p.id
    ORDER BY pp.product_id, pp.priority
", ARRAY_A);

if (!empty($product_pools)) {
    echo '<table>';
    echo '<tr><th>Product ID</th><th>Product Name</th><th>Pool ID</th><th>Pool Name</th><th>Priority</th><th>Pool Status</th><th>Assigned At</th></tr>';
    foreach ($product_pools as $mapping) {
        echo "<tr>";
        echo "<td>{$mapping['product_id']}</td>";
        echo "<td>{$mapping['product_name']}</td>";
        echo "<td>{$mapping['pool_id']}</td>";
        echo "<td>{$mapping['pool_name']}</td>";
        echo "<td>{$mapping['priority']}</td>";
        echo "<td>{$mapping['pool_status']}</td>";
        echo "<td>{$mapping['assigned_at']}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No product-pool mappings found</p>';
}

// 5. Pool ↔ Account Mappings
echo '<h2>🔗 Pool ↔ Account Mappings</h2>';
$pool_accounts = $wpdb->get_results("
    SELECT pa.*,
           p.name as pool_name,
           a.provider, a.account_login, a.display_name, a.capacity, a.current_usage
    FROM bz_vd_pool_accounts pa
    LEFT JOIN bz_vd_pools p ON pa.pool_id = p.id
    LEFT JOIN bz_vd_provider_accounts a ON pa.account_id = a.id
    ORDER BY pa.pool_id, pa.weight DESC
", ARRAY_A);

if (!empty($pool_accounts)) {
    echo '<table>';
    echo '<tr><th>Pool</th><th>Account</th><th>Provider</th><th>Login</th><th>Weight</th><th>Primary</th><th>Status</th><th>Capacity</th><th>Usage</th></tr>';
    foreach ($pool_accounts as $mapping) {
        $usage_class = $mapping['current_usage'] >= $mapping['capacity'] ? 'danger' : 'success';
        $primary = $mapping['is_primary'] ? '⭐ Yes' : 'No';
        echo "<tr>";
        echo "<td>{$mapping['pool_name']} (#{$mapping['pool_id']})</td>";
        echo "<td>{$mapping['display_name']} (#{$mapping['account_id']})</td>";
        echo "<td>{$mapping['provider']}</td>";
        echo "<td>{$mapping['account_login']}</td>";
        echo "<td>{$mapping['weight']}</td>";
        echo "<td>{$primary}</td>";
        echo "<td>{$mapping['status']}</td>";
        echo "<td>{$mapping['capacity']}</td>";
        echo "<td class='{$usage_class}'>{$mapping['current_usage']}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No pool-account mappings found</p>';
}

// 6. License Assignments
echo '<h2>🎫 License Assignments (Recent 20)</h2>';
$licenses = $wpdb->get_results("
    SELECT l.id, l.license_key_plain, l.customer_email, l.status,
           pr.post_title as product_name,
           p.name as pool_name,
           a.provider, a.display_name as account_name,
           l.assigned_at
    FROM bz_vd_license_keys l
    LEFT JOIN wp_posts pr ON l.product_id = pr.ID
    LEFT JOIN bz_vd_pools p ON l.pool_id = p.id
    LEFT JOIN bz_vd_provider_accounts a ON l.account_id = a.id
    ORDER BY l.created_at DESC
    LIMIT 20
", ARRAY_A);

if (!empty($licenses)) {
    echo '<table>';
    echo '<tr><th>License</th><th>Customer</th><th>Product</th><th>Pool</th><th>Account</th><th>Provider</th><th>Status</th><th>Assigned</th></tr>';
    foreach ($licenses as $license) {
        $assignment_status = $license['account_name'] ? '<span class="success">✅ Assigned</span>' : '<span class="danger">❌ Not Assigned</span>';
        echo "<tr>";
        echo "<td>{$license['license_key_plain']}</td>";
        echo "<td>{$license['customer_email']}</td>";
        echo "<td>{$license['product_name']}</td>";
        echo "<td>{$license['pool_name']}</td>";
        echo "<td>{$license['account_name']}</td>";
        echo "<td>{$license['provider']}</td>";
        echo "<td>{$assignment_status}</td>";
        echo "<td>{$license['assigned_at']}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No licenses found</p>';
}

// 7. Configuration Check
echo '<h2>⚙️ Product Share Configurations</h2>';
$configs = $wpdb->get_results("
    SELECT c.*, pr.post_title as product_name
    FROM bz_vd_product_share_configs c
    LEFT JOIN wp_posts pr ON c.product_id = pr.ID
", ARRAY_A);

if (!empty($configs)) {
    echo '<table>';
    echo '<tr><th>Product</th><th>Max Devices</th><th>Reset Days</th><th>Max Requests/Day</th><th>Assignment Rule</th><th>Active</th></tr>';
    foreach ($configs as $config) {
        $active = $config['is_active'] ? '✅ Yes' : '❌ No';
        echo "<tr>";
        echo "<td>{$config['product_name']} (#{$config['product_id']})</td>";
        echo "<td>{$config['max_devices_per_license']}</td>";
        echo "<td>{$config['device_reset_days']}</td>";
        echo "<td>{$config['max_requests_per_day']}</td>";
        echo "<td>{$config['pool_assignment_rule']}</td>";
        echo "<td>{$active}</td>";
        echo "</tr>";
    }
    echo '</table>';
} else {
    echo '<p class="danger">❌ No product configurations found</p>';
}

// 8. Recommendations
echo '<h2>💡 Recommendations</h2>';
echo '<ul>';

// Check for products without pool assignments
$unassigned_products = $wpdb->get_results("
    SELECT pr.ID, pr.post_title
    FROM wp_posts pr
    WHERE pr.post_type = 'product'
    AND pr.post_status = 'publish'
    AND pr.ID NOT IN (SELECT DISTINCT product_id FROM bz_vd_product_pools)
    LIMIT 10
", ARRAY_A);

if (!empty($unassigned_products)) {
    echo '<li class="danger"><strong>Products without pool assignments:</strong><ul>';
    foreach ($unassigned_products as $product) {
        echo "<li>Product #{$product['ID']}: {$product['post_title']}</li>";
    }
    echo '</ul></li>';
}

// Check for pools without accounts
$empty_pools = $wpdb->get_results("
    SELECT p.id, p.name
    FROM bz_vd_pools p
    WHERE p.id NOT IN (SELECT DISTINCT pool_id FROM bz_vd_pool_accounts WHERE status = 'active')
", ARRAY_A);

if (!empty($empty_pools)) {
    echo '<li class="danger"><strong>Pools without accounts:</strong><ul>';
    foreach ($empty_pools as $pool) {
        echo "<li>Pool #{$pool['id']}: {$pool['name']}</li>";
    }
    echo '</ul></li>';
}

// Check for accounts at capacity
$full_accounts = $wpdb->get_results("
    SELECT id, provider, account_login, capacity, current_usage
    FROM bz_vd_provider_accounts
    WHERE current_usage >= capacity AND status = 'active'
", ARRAY_A);

if (!empty($full_accounts)) {
    echo '<li class="highlight"><strong>Accounts at full capacity:</strong><ul>';
    foreach ($full_accounts as $account) {
        echo "<li>Account #{$account['id']}: {$account['provider']} - {$account['account_login']} ({$account['current_usage']}/{$account['capacity']})</li>";
    }
    echo '</ul></li>';
}

echo '</ul>';

echo '<p><strong>Analysis completed.</strong> <a href="/wp-admin/admin.php?page=vd-license-manager">Go to VD License Manager</a></p>';
?>