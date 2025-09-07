<?php
/**
 * Verify V2 Ultimate is Active and Working
 * Quick diagnostic tool
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
    <title>Verify V2 Ultimate Active</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .status-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .warn { color: #ffc107; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #e9ecef; }
        .urgent { background: #ffebee; padding: 15px; border: 2px solid #f44336; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>V2 Ultimate Status Check</h1>
    <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>

    <div class="urgent">
        <h2>⚠️ URGENT FINDINGS FROM HAR ANALYSIS</h2>
        <ul>
            <li><strong>Cart page</strong>: 107 seconds load time (43 duplicate scripts)</li>
            <li><strong>Checkout page</strong>: 118 seconds load time (53 duplicate scripts)</li>
            <li><strong>Order-received</strong>: 163 seconds load time!</li>
            <li><strong>reCAPTCHA</strong>: Loading 3-5 times instead of 1</li>
            <li><strong>Blocked domains</strong>: Still loading (127 instances)</li>
        </ul>
    </div>

    <div class="status-box">
        <h2>1. Class Loading Status</h2>
        <?php
        $classes = array(
            'Vidieu_Dup_Requests_Guard_V2_Ultimate' => 'Ultimate (Expected)',
            'Vidieu_Dup_Requests_Guard_V2_Safe' => 'Safe',
            'Vidieu_Dup_Requests_Guard_V2_Aggressive' => 'Aggressive',
            'Vidieu_Dup_Requests_Guard_V2_Stepped' => 'Stepped'
        );
        
        $loaded_class = null;
        foreach ($classes as $class => $name) {
            if (class_exists($class)) {
                $loaded_class = $class;
                break;
            }
        }
        ?>
        
        <table>
            <tr>
                <th>Check</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Active Class</td>
                <td><?php echo $loaded_class ? '<span class="pass">LOADED</span>' : '<span class="fail">NONE</span>'; ?></td>
                <td><?php echo $loaded_class ? $loaded_class : 'No optimization class loaded!'; ?></td>
            </tr>
            <tr>
                <td>Is Ultimate?</td>
                <td><?php echo ($loaded_class === 'Vidieu_Dup_Requests_Guard_V2_Ultimate') ? '<span class="pass">YES</span>' : '<span class="fail">NO</span>'; ?></td>
                <td><?php echo $loaded_class ? "Currently using: " . $classes[$loaded_class] : 'N/A'; ?></td>
            </tr>
        </table>
        
        <?php if ($loaded_class !== 'Vidieu_Dup_Requests_Guard_V2_Ultimate'): ?>
        <div class="urgent">
            <strong>⚠️ PROBLEM: V2 Ultimate is NOT active!</strong>
            <p>This explains why optimizations are not working.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="status-box">
        <h2>2. Configuration Status</h2>
        <?php
        $kill_switch = defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION;
        ?>
        <table>
            <tr>
                <td>Kill Switch</td>
                <td><?php echo $kill_switch ? '<span class="fail">ACTIVE</span>' : '<span class="pass">INACTIVE</span>'; ?></td>
                <td><?php echo $kill_switch ? 'Optimization DISABLED via wp-config!' : 'Optimization enabled'; ?></td>
            </tr>
            <?php if ($loaded_class === 'Vidieu_Dup_Requests_Guard_V2_Ultimate'): ?>
            <tr>
                <td>Basic Optimization</td>
                <td><?php echo Vidieu_Dup_Requests_Guard_V2_Ultimate::ENABLE_BASIC_OPTIMIZATION ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td>WordPress cleanup</td>
            </tr>
            <tr>
                <td>Nuclear reCAPTCHA</td>
                <td><?php echo Vidieu_Dup_Requests_Guard_V2_Ultimate::ENABLE_NUCLEAR_RECAPTCHA ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td>Should reduce to 1 instance</td>
            </tr>
            <tr>
                <td>Cart/Checkout Whitelist</td>
                <td><?php echo Vidieu_Dup_Requests_Guard_V2_Ultimate::ENABLE_CART_CHECKOUT_WHITELIST ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td>Strict whitelist</td>
            </tr>
            <tr>
                <td>Output Buffering</td>
                <td><?php echo Vidieu_Dup_Requests_Guard_V2_Ultimate::ENABLE_OUTPUT_BUFFERING ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td>Domain blocking</td>
            </tr>
            <tr>
                <td>Order-received Opt</td>
                <td><?php echo Vidieu_Dup_Requests_Guard_V2_Ultimate::ENABLE_ORDER_RECEIVED_OPT ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td>New optimization</td>
            </tr>
            <?php endif; ?>
        </table>
        
        <?php if ($kill_switch): ?>
        <div class="urgent">
            <strong>⚠️ KILL SWITCH IS ACTIVE!</strong>
            <p>Remove or set to false: <code>define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);</code> in wp-config.php</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="status-box">
        <h2>3. File Check</h2>
        <?php
        $files = array(
            'Ultimate Class' => VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2-ultimate.php',
            'Plugin Loader' => VD_HOME_PLUGIN_DIR . 'vidieu-home-sections.php',
            'MU Plugin' => WPMU_PLUGIN_DIR . '/fix-404-resources-safe.php'
        );
        ?>
        <table>
            <?php foreach ($files as $name => $path): ?>
            <tr>
                <td><?php echo $name; ?></td>
                <td><?php echo file_exists($path) ? '<span class="pass">EXISTS</span>' : '<span class="fail">MISSING</span>'; ?></td>
                <td><?php echo file_exists($path) ? 'Modified: ' . date('Y-m-d H:i:s', filemtime($path)) : $path; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="status-box">
        <h2>4. Quick Actions to Fix</h2>
        
        <?php if ($loaded_class !== 'Vidieu_Dup_Requests_Guard_V2_Ultimate'): ?>
        <div class="urgent">
            <h3>🔴 Ultimate Class Not Loading - Fix This First!</h3>
            <ol>
                <li>Check if file exists: <code><?php echo VD_HOME_PLUGIN_DIR; ?>inc/perf/class-vidieu-dup-requests-guard-v2-ultimate.php</code></li>
                <li>Check plugin loader includes Ultimate class first in the chain</li>
                <li>Look for PHP errors in debug.log</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <?php if ($kill_switch): ?>
        <div class="urgent">
            <h3>🔴 Kill Switch Active - Disable It!</h3>
            <p>Edit wp-config.php and remove or comment out:</p>
            <code>// define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);</code>
        </div>
        <?php endif; ?>
        
        <h3>After Fixing Above Issues:</h3>
        <ol>
            <li>Clear all caches</li>
            <li>Visit Cart page as admin</li>
            <li>View page source (Ctrl+U)</li>
            <li>Search for "VIDIEU V2 ULTIMATE"</li>
            <li>You should see detailed optimization log</li>
        </ol>
    </div>

    <div class="status-box">
        <h2>5. Test Links</h2>
        <p>After fixing issues above, test these pages:</p>
        <ul>
            <li><a href="<?php echo wc_get_cart_url(); ?>" target="_blank">Cart Page</a> - Should be <150 requests</li>
            <li><a href="<?php echo wc_get_checkout_url(); ?>" target="_blank">Checkout Page</a> - Should be <180 requests</li>
            <li><a href="<?php echo home_url('/checkout/order-received/'); ?>" target="_blank">Order-received</a> - Should be <160 requests</li>
        </ul>
        
        <h3>Browser Console Check:</h3>
        <div class="code">
// Check total requests
performance.getEntriesByType('resource').length

// Check reCAPTCHA instances
document.querySelectorAll('[src*="recaptcha"]').length

// Check blocked domains
['fonts.gstatic.com', 'elementor'].forEach(d => {
    var found = performance.getEntriesByType('resource').filter(r => r.name.includes(d));
    if (found.length) console.log(d + ':', found.length);
});
        </div>
    </div>

    <div class="status-box">
        <h2>6. Expected vs Actual</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Expected with Ultimate</th>
                <th>Current (from HAR)</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Cart Requests</td>
                <td><150</td>
                <td>226</td>
                <td><span class="fail">FAIL</span></td>
            </tr>
            <tr>
                <td>Cart Load Time</td>
                <td><5s</td>
                <td>107s</td>
                <td><span class="fail">CRITICAL</span></td>
            </tr>
            <tr>
                <td>Checkout Requests</td>
                <td><180</td>
                <td>222</td>
                <td><span class="fail">FAIL</span></td>
            </tr>
            <tr>
                <td>reCAPTCHA/page</td>
                <td>1</td>
                <td>3-5</td>
                <td><span class="fail">FAIL</span></td>
            </tr>
            <tr>
                <td>Duplicate Scripts</td>
                <td>0</td>
                <td>43-53</td>
                <td><span class="fail">FAIL</span></td>
            </tr>
        </table>
        
        <p><strong>Conclusion:</strong> V2 Ultimate optimizations are NOT being applied properly!</p>
    </div>
</body>
</html>