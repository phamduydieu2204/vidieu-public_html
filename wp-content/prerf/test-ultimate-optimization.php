<?php
/**
 * Test V2 Ultimate Optimization
 * Comprehensive testing for all routes
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check admin
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

// Test routes
$test_routes = array(
    'Home' => home_url('/'),
    'Product' => home_url('/product/elessi-woocommerce-ajax-wordpress-theme/'),
    'Post' => home_url('/tin-tuc/'),
    'Contact' => home_url('/contact/'),
    'Cart' => wc_get_cart_url(),
    'Checkout' => wc_get_checkout_url(),
    'Order-received' => home_url('/checkout/order-received/')
);

// Target metrics
$targets = array(
    'Home' => array('requests' => 120, '404' => 0, 'recaptcha' => 1),
    'Product' => array('requests' => 120, '404' => 0, 'recaptcha' => 1),
    'Post' => array('requests' => 100, '404' => 0, 'recaptcha' => 1),
    'Contact' => array('requests' => 100, '404' => 0, 'recaptcha' => 1),
    'Cart' => array('requests' => 150, '404' => 0, 'recaptcha' => 1),
    'Checkout' => array('requests' => 180, '404' => 0, 'recaptcha' => 1),
    'Order-received' => array('requests' => 160, '404' => 0, 'recaptcha' => 1)
);

// Output header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test V2 Ultimate Optimization</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; background: #f5f5f5; border: 1px solid #ddd; }
        .route-test { margin: 15px 0; padding: 15px; background: #fff; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .warn { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #e9ecef; }
        .metric { display: inline-block; margin: 0 10px; }
        .command { background: #000; color: #0f0; padding: 10px; font-family: monospace; }
        .log-output { background: #f8f9fa; padding: 10px; font-size: 12px; white-space: pre-wrap; }
        .focus-route { background: #e7f3ff; }
    </style>
</head>
<body>
    <h1>V2 Ultimate Optimization Test Suite</h1>
    <p>Version: 2.4.0 Ultimate | Date: <?php echo date('Y-m-d H:i:s'); ?></p>

    <?php
    // Check if Ultimate class is loaded
    $ultimate_loaded = class_exists('Vidieu_Dup_Requests_Guard_V2_Ultimate');
    ?>

    <div class="test-section">
        <h2>1. System Status</h2>
        
        <table>
            <tr>
                <th>Check</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Ultimate Class Loaded</td>
                <td><?php echo $ultimate_loaded ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>'; ?></td>
                <td><?php echo $ultimate_loaded ? 'Vidieu_Dup_Requests_Guard_V2_Ultimate' : 'Class not found'; ?></td>
            </tr>
            <tr>
                <td>Kill Switch</td>
                <td><?php 
                    $kill_switch = defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION;
                    echo $kill_switch ? '<span class="warn">ACTIVE</span>' : '<span class="pass">INACTIVE</span>'; 
                ?></td>
                <td><?php echo $kill_switch ? 'Optimization disabled via wp-config' : 'Optimization enabled'; ?></td>
            </tr>
            <tr>
                <td>MU Plugin (404 fix)</td>
                <td><?php 
                    $mu_exists = file_exists(WPMU_PLUGIN_DIR . '/fix-404-resources-safe.php');
                    echo $mu_exists ? '<span class="pass">EXISTS</span>' : '<span class="warn">NOT FOUND</span>'; 
                ?></td>
                <td><?php echo $mu_exists ? WPMU_PLUGIN_DIR . '/fix-404-resources-safe.php' : 'MU plugin not deployed'; ?></td>
            </tr>
        </table>

        <?php if ($ultimate_loaded): ?>
        <h3>Feature Flags:</h3>
        <table>
            <?php
            $features = array(
                'ENABLE_BASIC_OPTIMIZATION' => 'Basic WordPress cleanup',
                'ENABLE_NUCLEAR_RECAPTCHA' => 'Nuclear reCAPTCHA blocking',
                'ENABLE_CART_CHECKOUT_WHITELIST' => 'Cart/Checkout strict whitelist',
                'ENABLE_OUTPUT_BUFFERING' => 'Output buffer domain stripping',
                'ENABLE_ORDER_RECEIVED_OPT' => 'Order-received optimization'
            );
            
            foreach ($features as $const => $desc):
                $enabled = constant('Vidieu_Dup_Requests_Guard_V2_Ultimate::' . $const);
            ?>
            <tr>
                <td><?php echo $const; ?></td>
                <td><?php echo $enabled ? '<span class="pass">ON</span>' : '<span class="fail">OFF</span>'; ?></td>
                <td><?php echo $desc; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>2. Route Analysis</h2>
        <p>Click on each route to test. Focus routes (Cart/Checkout/Order-received) are highlighted.</p>

        <?php 
        foreach ($test_routes as $route => $url):
            $is_focus = in_array($route, array('Cart', 'Checkout', 'Order-received'));
            $target = $targets[$route];
        ?>
        <div class="route-test <?php echo $is_focus ? 'focus-route' : ''; ?>">
            <h3><?php echo $route; ?> <?php echo $is_focus ? '<span class="warn">[FOCUS]</span>' : ''; ?></h3>
            <p>URL: <a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a></p>
            
            <div class="metrics">
                <span class="metric">Target Requests: <strong>&lt;<?php echo $target['requests']; ?></strong></span>
                <span class="metric">Target 404s: <strong><?php echo $target['404']; ?></strong></span>
                <span class="metric">Target reCAPTCHA: <strong><?php echo $target['recaptcha']; ?></strong></span>
            </div>

            <?php if ($is_focus): ?>
            <div style="margin-top: 10px; padding: 10px; background: #fff3cd;">
                <strong>Admin Logging Available:</strong> Visit this page while logged in as admin and view page source. 
                Search for "VIDIEU V2 ULTIMATE" to see detailed optimization log.
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="test-section">
        <h2>3. Browser Console Tests</h2>
        <p>Run these commands in browser console on each page:</p>

        <h3>Basic Metrics:</h3>
        <div class="command">
// Total resource count
console.log('Total resources:', performance.getEntriesByType('resource').length);

// Breakdown by type
var resources = performance.getEntriesByType('resource');
var types = { js: 0, css: 0, img: 0, font: 0, other: 0 };
resources.forEach(r => {
    if (r.name.includes('.js')) types.js++;
    else if (r.name.includes('.css')) types.css++;
    else if (r.name.match(/\.(jpg|jpeg|png|gif|webp|svg)/)) types.img++;
    else if (r.name.match(/\.(woff|woff2|ttf|eot)/)) types.font++;
    else types.other++;
});
console.table(types);
        </div>

        <h3>Check Blocked Domains:</h3>
        <div class="command">
// Check if blocked domains are still loading
var blocked = ['elementor', 'yith', 'revslider', 'instagram', 'facebook', 'google-analytics'];
var stillLoading = {};

performance.getEntriesByType('resource').forEach(r => {
    blocked.forEach(b => {
        if (r.name.includes(b)) {
            stillLoading[b] = (stillLoading[b] || 0) + 1;
        }
    });
});

console.log('Blocked domains still loading:');
console.table(stillLoading);
        </div>

        <h3>reCAPTCHA Check:</h3>
        <div class="command">
// Count reCAPTCHA instances
var recaptcha = performance.getEntriesByType('resource')
    .filter(r => r.name.includes('recaptcha') || r.name.includes('grecaptcha'));
console.log('reCAPTCHA resources:', recaptcha.length);
recaptcha.forEach(r => console.log(' -', r.name));

// Check script tags
var scriptTags = document.querySelectorAll('script[src*="recaptcha"], script[src*="grecaptcha"]');
console.log('reCAPTCHA script tags:', scriptTags.length);
        </div>

        <h3>Domain Analysis (Admin only):</h3>
        <div class="command">
// Get domain breakdown (requires admin login)
if (window.vidieuPerfDomains) {
    console.log('Domain analysis available:');
    console.table(window.vidieuPerfDomains);
} else {
    console.log('Domain analysis not available. Login as admin and reload.');
}
        </div>
    </div>

    <div class="test-section">
        <h2>4. Functional Tests</h2>

        <h3>Cart Page Tests:</h3>
        <ol>
            <li>Add product to cart</li>
            <li>Go to cart page</li>
            <li>Update quantity - should update via AJAX</li>
            <li>Remove item - should update via AJAX</li>
            <li>Apply coupon code - should work</li>
            <li>Check mini-cart updates</li>
        </ol>

        <h3>Checkout Page Tests:</h3>
        <ol>
            <li>Fill billing details</li>
            <li>Toggle ship to different address</li>
            <li>Select payment method</li>
            <li>Check if payment fields load</li>
            <li>Place order (test mode)</li>
        </ol>

        <h3>Order-received Page Tests:</h3>
        <ol>
            <li>Verify thank you message displays</li>
            <li>Check order details table</li>
            <li>Verify customer details show</li>
            <li>Check if tracking pixels work (if any)</li>
        </ol>

        <h3>Contact Page Tests:</h3>
        <ol>
            <li>Fill contact form</li>
            <li>Check reCAPTCHA appears (only 1)</li>
            <li>Submit form</li>
            <li>Verify submission works</li>
        </ol>
    </div>

    <div class="test-section">
        <h2>5. Performance Checklist</h2>
        
        <table>
            <tr>
                <th>Route</th>
                <th>Requests</th>
                <th>404s</th>
                <th>reCAPTCHA</th>
                <th>Functional</th>
                <th>Overall</th>
            </tr>
            <?php foreach ($test_routes as $route => $url): ?>
            <tr>
                <td><?php echo $route; ?></td>
                <td><input type="checkbox"> &lt;<?php echo $targets[$route]['requests']; ?></td>
                <td><input type="checkbox"> <?php echo $targets[$route]['404']; ?></td>
                <td><input type="checkbox"> <?php echo $targets[$route]['recaptcha']; ?></td>
                <td><input type="checkbox"> Works</td>
                <td><input type="checkbox"> <strong>PASS</strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="test-section">
        <h2>6. Quick Actions</h2>
        
        <h3>If optimization breaks something:</h3>
        <ol>
            <li>Add to wp-config.php: <code>define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);</code></li>
            <li>Clear all caches</li>
            <li>Test again</li>
        </ol>

        <h3>View detailed logs:</h3>
        <ol>
            <li>Login as admin</li>
            <li>Visit Cart, Checkout, or Order-received page</li>
            <li>View page source (Ctrl+U)</li>
            <li>Search for "VIDIEU V2 ULTIMATE"</li>
        </ol>

        <h3>Check specific feature:</h3>
        <p>Edit class file and set specific constant to false:</p>
        <code>const ENABLE_CART_CHECKOUT_WHITELIST = false;</code>
    </div>

    <div class="test-section">
        <h2>7. Expected Admin Log Output</h2>
        <div class="log-output">
===== VIDIEU V2 ULTIMATE - ENHANCED ADMIN LOG =====
Page Type: Cart
Timestamp: 2025-09-06 10:30:45

WHITELIST APPLICATION:
 - Hook: wp_enqueue_scripts
 - Priority: 9999
 - Scripts Before: 162
 - Scripts After: 20
 - Scripts Removed: 142
 - Styles Before: 32
 - Styles After: 6
 - Styles Removed: 26

REMOVED SCRIPTS (142 total):
  [Plugin: elementor] (8):
    - elementor-frontend
    - elementor-dialog
    ...

HOOK EXECUTION TIMELINE:
 - 10:30:45 | wp_enqueue_scripts (priority: 9999)
 - 10:30:46 | wp_print_scripts (priority: 1)
 - 10:30:46 | wp_print_styles (priority: 1)
 - 10:30:47 | wp_print_footer_scripts (priority: 9999)

SUMMARY STATISTICS:
 - Total Elements Removed: 168
 - Scripts Removed: 142
 - Styles Removed: 26
 - Output Buffer Elements: 45
===== END ADMIN LOG =====
        </div>
    </div>
</body>
</html>