<?php
/**
 * Test Aggressive Whitelist Implementation
 * 
 * Run this to verify whitelist effectiveness
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check admin
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

// Get test URLs
$test_urls = array(
    'cart' => wc_get_cart_url(),
    'checkout' => wc_get_checkout_url()
);

// Output header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Aggressive Whitelist</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; background: #f5f5f5; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .warn { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #e9ecef; }
        pre { background: #fff; padding: 10px; overflow-x: auto; }
        .command { background: #000; color: #0f0; padding: 10px; }
    </style>
</head>
<body>
    <h1>Test Aggressive Whitelist Implementation</h1>
    <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>

    <div class="test-section">
        <h2>1. Current Status Check</h2>
        <?php
        // Check if aggressive class is loaded
        $aggressive_loaded = class_exists('Vidieu_Dup_Requests_Guard_V2_Aggressive');
        echo '<p>Aggressive class loaded: ' . ($aggressive_loaded ? '<span class="pass">YES</span>' : '<span class="fail">NO</span>') . '</p>';
        
        // Check feature flags
        if ($aggressive_loaded) {
            $flags = array(
                'ENABLE_BASIC_OPTIMIZATION' => Vidieu_Dup_Requests_Guard_V2_Aggressive::ENABLE_BASIC_OPTIMIZATION,
                'ENABLE_NUCLEAR_RECAPTCHA' => Vidieu_Dup_Requests_Guard_V2_Aggressive::ENABLE_NUCLEAR_RECAPTCHA,
                'ENABLE_CART_CHECKOUT_WHITELIST' => Vidieu_Dup_Requests_Guard_V2_Aggressive::ENABLE_CART_CHECKOUT_WHITELIST,
                'ENABLE_OUTPUT_BUFFERING' => Vidieu_Dup_Requests_Guard_V2_Aggressive::ENABLE_OUTPUT_BUFFERING
            );
            
            echo '<h3>Feature Flags:</h3>';
            echo '<table>';
            foreach ($flags as $flag => $value) {
                echo '<tr>';
                echo '<td>' . $flag . '</td>';
                echo '<td>' . ($value ? '<span class="pass">ENABLED</span>' : '<span class="fail">DISABLED</span>') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>2. Quick Resource Count (via cURL)</h2>
        <?php
        foreach ($test_urls as $page => $url) {
            echo '<h3>' . ucfirst($page) . ' Page</h3>';
            echo '<p>URL: <a href="' . $url . '" target="_blank">' . $url . '</a></p>';
            
            // Get page content
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? '');
            $content = curl_exec($ch);
            curl_close($ch);
            
            if ($content) {
                // Count scripts and styles
                preg_match_all('/<script[^>]*src=[\'"][^\'"]+[\'"]/i', $content, $scripts);
                preg_match_all('/<link[^>]*stylesheet[^>]*>/i', $content, $styles);
                
                $script_count = count($scripts[0]);
                $style_count = count($styles[0]);
                
                echo '<table>';
                echo '<tr><th>Resource Type</th><th>Count</th><th>Target</th><th>Status</th></tr>';
                
                // Scripts
                $script_target = ($page == 'cart') ? 25 : 30;
                $script_status = $script_count <= $script_target ? 'pass' : 'fail';
                echo '<tr>';
                echo '<td>Scripts</td>';
                echo '<td>' . $script_count . '</td>';
                echo '<td>&lt;= ' . $script_target . '</td>';
                echo '<td class="' . $script_status . '">' . strtoupper($script_status) . '</td>';
                echo '</tr>';
                
                // Styles
                $style_target = 15;
                $style_status = $style_count <= $style_target ? 'pass' : 'fail';
                echo '<tr>';
                echo '<td>Styles</td>';
                echo '<td>' . $style_count . '</td>';
                echo '<td>&lt;= ' . $style_target . '</td>';
                echo '<td class="' . $style_status . '">' . strtoupper($style_status) . '</td>';
                echo '</tr>';
                
                // Estimate total
                $estimated_total = $script_count + $style_count + 50; // +50 for images/fonts/ajax
                $total_target = ($page == 'cart') ? 150 : 180;
                $total_status = $estimated_total <= $total_target ? 'pass' : 'warn';
                echo '<tr>';
                echo '<td>Estimated Total</td>';
                echo '<td>~' . $estimated_total . '</td>';
                echo '<td>&lt;= ' . $total_target . '</td>';
                echo '<td class="' . $total_status . '">' . strtoupper($total_status) . '</td>';
                echo '</tr>';
                
                echo '</table>';
                
                // Check for blocked domains
                $blocked_found = array();
                $block_domains = array('elementor', 'instagram', 'yith', 'revslider', 'uael');
                foreach ($block_domains as $domain) {
                    if (stripos($content, $domain) !== false) {
                        $blocked_found[] = $domain;
                    }
                }
                
                if (!empty($blocked_found)) {
                    echo '<p class="warn">⚠️ Found blocked domains in output: ' . implode(', ', $blocked_found) . '</p>';
                } else {
                    echo '<p class="pass">✓ No blocked domains found in output</p>';
                }
            } else {
                echo '<p class="fail">Failed to fetch page content</p>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>3. Browser Console Test Commands</h2>
        <p>Run these in browser console on Cart/Checkout pages:</p>
        
        <div class="command">
// Count all resources
performance.getEntriesByType('resource').length

// Count by type
var r = performance.getEntriesByType('resource');
var types = {};
r.forEach(x => {
    var ext = x.name.split('?')[0].split('.').pop();
    types[ext] = (types[ext] || 0) + 1;
});
console.table(types);

// Find blocked domains still loading
var blocked = ['elementor','yith','instagram','revslider'];
var found = r.filter(x => blocked.some(b => x.name.includes(b)));
console.log('Blocked domains still loading:', found.length);
found.forEach(x => console.log(x.name));
        </div>
    </div>

    <div class="test-section">
        <h2>4. Manual Verification Steps</h2>
        <ol>
            <li>Open Cart page in Incognito mode</li>
            <li>Open DevTools Network tab</li>
            <li>Disable cache in DevTools</li>
            <li>Reload page</li>
            <li>Check total request count at bottom</li>
            <li>Filter by "js" and "css" to see counts</li>
            <li>Look for any 404 errors</li>
            <li>Search for "recaptcha" - should see only 1</li>
        </ol>
        
        <h3>Expected Results:</h3>
        <table>
            <tr><th>Page</th><th>Total Requests</th><th>Scripts</th><th>Styles</th></tr>
            <tr><td>Cart</td><td>&lt; 150</td><td>&lt; 25</td><td>&lt; 15</td></tr>
            <tr><td>Checkout</td><td>&lt; 180</td><td>&lt; 30</td><td>&lt; 15</td></tr>
        </table>
    </div>

    <div class="test-section">
        <h2>5. Debug Output Location</h2>
        <p>View page source and search for:</p>
        <ul>
            <li><code>&lt;!-- ===== Vidieu V2 Aggressive Whitelist Report =====</code></li>
            <li><code>&lt;!-- Vidieu Performance Stats --></code></li>
            <li><code>[Vidieu Performance]</code> in browser console</li>
        </ul>
    </div>
</body>
</html>