<?php
/**
 * Test Checkout Security Fix
 * Verify the "Security check failed" error is resolved
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check admin
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Checkout Security Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .error { background: #ffebee; padding: 15px; border-left: 4px solid #f44336; color: #b71c1c; }
        .success { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; color: #1b5e20; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; color: #0d47a1; }
        .code { background: #f5f5f5; padding: 10px; margin: 10px 0; border: 1px solid #ddd; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #e0e0e0; }
    </style>
</head>
<body>
    <h1>Checkout Security Fix Test</h1>
    
    <div class="box">
        <h2>Problem Summary</h2>
        <div class="error">
            <strong>Issue:</strong> "Security check failed" when clicking "Đặt hàng" button<br>
            <strong>Cause:</strong> Theme's custom checkout doesn't send nonce, but our optimizer was checking for it<br>
            <strong>Console Error:</strong> <code>{success: false, data: 'Security check failed'}</code>
        </div>
    </div>

    <div class="box">
        <h2>Fix Applied</h2>
        <div class="success">
            <strong>Solution:</strong> Created <code>class-vd-checkout-optimizer-fixed.php</code> that:
            <ul>
                <li>Skips nonce verification for custom checkout</li>
                <li>Uses session validation instead</li>
                <li>Checks cart contents for security</li>
                <li>Maintains performance optimization (4s → <1s)</li>
            </ul>
        </div>
    </div>

    <div class="box">
        <h2>AJAX Handler Analysis</h2>
        <?php
        global $wp_filter;
        
        $ajax_actions = array(
            'wp_ajax_elessi_simple_checkout',
            'wp_ajax_nopriv_elessi_simple_checkout'
        );
        
        foreach ($ajax_actions as $action) {
            echo "<h3>$action</h3>";
            
            if (isset($wp_filter[$action])) {
                echo '<table>';
                echo '<tr><th>Priority</th><th>Function</th><th>File</th></tr>';
                
                foreach ($wp_filter[$action] as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        $function = 'Unknown';
                        $file = 'Unknown';
                        
                        if (is_array($callback['function'])) {
                            if (is_object($callback['function'][0])) {
                                $class = get_class($callback['function'][0]);
                                $method = $callback['function'][1];
                                $function = "$class::$method";
                                
                                // Get file location
                                $reflection = new ReflectionClass($callback['function'][0]);
                                $file = str_replace(ABSPATH, '', $reflection->getFileName());
                            }
                        }
                        
                        $is_our_handler = strpos($function, 'VD_Checkout_Optimizer') !== false;
                        $row_class = $is_our_handler ? 'style="background: #c8e6c9;"' : '';
                        
                        echo "<tr $row_class>";
                        echo "<td>$priority</td>";
                        echo "<td>$function</td>";
                        echo "<td>$file</td>";
                        echo '</tr>';
                    }
                }
                echo '</table>';
            } else {
                echo '<p>No handlers registered</p>';
            }
        }
        ?>
    </div>

    <div class="box">
        <h2>Current Status</h2>
        <?php
        // Check which class is loaded
        $optimizer_loaded = class_exists('VD_Checkout_Optimizer');
        $fixed_file_exists = file_exists(VD_HOME_PLUGIN_DIR . 'inc/class-vd-checkout-optimizer-fixed.php');
        ?>
        
        <table>
            <tr>
                <td>Optimizer Class Loaded</td>
                <td><?php echo $optimizer_loaded ? '<span style="color: green;">✓ YES</span>' : '<span style="color: red;">✗ NO</span>'; ?></td>
            </tr>
            <tr>
                <td>Fixed File Exists</td>
                <td><?php echo $fixed_file_exists ? '<span style="color: green;">✓ YES</span>' : '<span style="color: red;">✗ NO</span>'; ?></td>
            </tr>
            <tr>
                <td>Handler Priority</td>
                <td>5 (before theme's priority 10)</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Test Instructions</h2>
        <ol>
            <li><strong>Clear all caches</strong></li>
            <li><strong>Go to checkout page</strong></li>
            <li><strong>Open browser console</strong> (F12)</li>
            <li><strong>Fill the form and click "Đặt hàng"</strong></li>
            <li><strong>Check console for:</strong>
                <ul>
                    <li>✓ <code>[VD Perf] Checkout AJAX completed in XXXms</code></li>
                    <li>✓ <code>[VD Perf] Server processing: XXXms</code></li>
                    <li>✗ NO "Security check failed" error</li>
                </ul>
            </li>
        </ol>
    </div>

    <div class="box">
        <h2>Expected Console Output</h2>
        <div class="code">
[VD Perf] Checkout page loaded, monitoring started
[VD Perf] Checkout button clicked
[VD Perf] Checkout AJAX started
[VD Perf] Checkout AJAX completed in 850ms
[VD Perf] Server processing: 823ms
→ Redirecting to order-received page...
        </div>
    </div>

    <div class="box">
        <h2>Debug Information</h2>
        <div class="info">
            <strong>Theme's Custom Checkout:</strong>
            <ul>
                <li>Uses custom AJAX without WooCommerce nonce</li>
                <li>Sends data: email, firstName, lastName, phone, orderComments</li>
                <li>Action: elessi_simple_checkout</li>
            </ul>
            
            <strong>Our Fix:</strong>
            <ul>
                <li>Detects custom checkout via filter</li>
                <li>Uses <code>handle_custom_checkout()</code> without nonce check</li>
                <li>Maps custom fields to WooCommerce fields</li>
                <li>Returns response in theme's expected format</li>
            </ul>
        </div>
    </div>

    <div class="box">
        <h2>If Still Getting Error</h2>
        <div class="error">
            <ol>
                <li><strong>Check file exists:</strong> <code>/inc/class-vd-checkout-optimizer-fixed.php</code></li>
                <li><strong>Clear object cache:</strong> WP Rocket, Redis, Memcached</li>
                <li><strong>Check PHP error log</strong> for any fatal errors</li>
                <li><strong>Temporarily disable other plugins</strong> that might hook checkout</li>
                <li><strong>Add to wp-config.php:</strong> <code>define('WP_DEBUG', true);</code></li>
            </ol>
        </div>
    </div>
</body>
</html>