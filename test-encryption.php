<?php
/**
 * Encryption Test Tool
 *
 * Tests the encryption service to verify it's working correctly
 *
 * @package VD_License_Manager
 */

require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-content/plugins/vd-license-manager/includes/services/class-vd-lm-encryption-service.php';

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

$encryption = new VD_LM_Encryption_Service();

// Test data
$test_values = array(
    'Simple password' => 'password123',
    'Complex password' => 'P@ssw0rd!#$%^&*()',
    'Long text' => str_repeat('Lorem ipsum dolor sit amet. ', 50),
    'Special chars' => '你好世界 مرحبا بالعالم Hello Мир 🌍',
    'Phone number' => '+84988691196',
    'Email' => 'test@example.com',
    'API Key' => 'pk_live_' . bin2hex(random_bytes(20)),
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Encryption Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .test { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .test.success { border-color: #28a745; background: #d4edda; }
        .test.error { border-color: #dc3545; background: #f8d7da; }
        .label { font-weight: bold; color: #666; }
        .value { color: #333; word-break: break-all; }
        .encrypted { color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .success-icon { color: #28a745; }
        .error-icon { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Encryption Service Test</h1>

        <h2>Configuration</h2>
        <?php
        $key_info = $encryption->get_key_info();
        ?>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Cipher Method</td>
                <td><?php echo $key_info['cipher']; ?></td>
                <td><?php echo $key_info['cipher'] === 'aes-256-cbc' ? '<span class="success-icon">✓</span>' : '<span class="error-icon">✗</span>'; ?></td>
            </tr>
            <tr>
                <td>IV Length</td>
                <td><?php echo $key_info['iv_length']; ?> bytes</td>
                <td><?php echo $key_info['iv_length'] === 16 ? '<span class="success-icon">✓ Correct</span>' : '<span class="error-icon">✗ Wrong</span>'; ?></td>
            </tr>
            <tr>
                <td>Key Length</td>
                <td><?php echo $key_info['key_length']; ?> bytes</td>
                <td><?php echo $key_info['key_length'] === 32 ? '<span class="success-icon">✓ Correct</span>' : '<span class="error-icon">✗ Wrong</span>'; ?></td>
            </tr>
            <tr>
                <td>Key Hash</td>
                <td><code><?php echo $key_info['key_hash']; ?></code></td>
                <td><span class="success-icon">✓</span></td>
            </tr>
        </table>

        <h2>Encryption/Decryption Tests</h2>

        <?php
        $all_passed = true;

        foreach ($test_values as $label => $original) {
            $encrypted = $encryption->encrypt($original);
            $decrypted = $encryption->decrypt($encrypted);

            $passed = ($decrypted === $original);
            $all_passed = $all_passed && $passed;

            $class = $passed ? 'success' : 'error';
            $icon = $passed ? '✓' : '✗';

            ?>
            <div class="test <?php echo $class; ?>">
                <div class="label"><?php echo $icon; ?> Test: <?php echo esc_html($label); ?></div>
                <div style="margin: 10px 0;">
                    <strong>Original:</strong> <span class="value"><?php echo esc_html(substr($original, 0, 100)) . (strlen($original) > 100 ? '...' : ''); ?></span><br>
                    <strong>Encrypted:</strong> <span class="encrypted"><?php echo esc_html(substr($encrypted, 0, 100)) . '...'; ?></span> (<?php echo strlen($encrypted); ?> chars)<br>
                    <strong>Decrypted:</strong> <span class="value"><?php echo esc_html(substr($decrypted, 0, 100)) . (strlen($decrypted) > 100 ? '...' : ''); ?></span><br>
                    <strong>Match:</strong> <?php echo $passed ? '<span style="color: #28a745;">YES ✓</span>' : '<span style="color: #dc3545;">NO ✗</span>'; ?>
                </div>
            </div>
            <?php
        }
        ?>

        <h2>Summary</h2>
        <div class="test <?php echo $all_passed ? 'success' : 'error'; ?>">
            <?php if ($all_passed): ?>
                <h3 style="color: #28a745;">✓ All Tests Passed!</h3>
                <p>Encryption service is working correctly.</p>
                <p><strong>Next steps:</strong></p>
                <ul>
                    <li>Delete this test file: <code>test-encryption.php</code></li>
                    <li>Try creating a new account</li>
                    <li>Check that encrypted data is saved correctly in database</li>
                    <li>Verify no decryption errors in debug.log</li>
                </ul>
            <?php else: ?>
                <h3 style="color: #dc3545;">✗ Some Tests Failed</h3>
                <p>Encryption service has issues. Check the errors above.</p>
                <p>Review <code>wp-content/debug.log</code> for more details.</p>
            <?php endif; ?>
        </div>

        <p style="margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=vd-accounts'); ?>" style="color: #007bff;">← Back to Accounts</a>
        </p>
    </div>
</body>
</html>