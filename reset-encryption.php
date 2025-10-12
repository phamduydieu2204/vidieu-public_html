<?php
/**
 * Emergency Encryption Reset Tool
 *
 * ⚠️ WARNING: This will DELETE ALL encrypted data in the database!
 *
 * Use this tool ONLY when:
 * - Encryption is broken and data cannot be decrypted
 * - You are willing to re-enter all account credentials
 * - You have backed up account information elsewhere
 *
 * What this does:
 * - Sets all encrypted fields to NULL in bz_vd_provider_accounts table
 * - Does NOT delete accounts (only clears encrypted credentials)
 * - Account basic info (provider, login, status) remains intact
 *
 * After running:
 * - You can create new accounts with fixed encryption
 * - Or edit existing accounts to re-enter credentials
 *
 * @package VD_License_Manager
 * @version 1.0.0
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Security check - must be admin
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized - You must be an administrator to access this tool.');
}

// Check confirmation parameter
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes_reset_all_encrypted_data';

if (!$confirmed) {
    // Show warning page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🚨 Emergency Encryption Reset</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                line-height: 1.6;
                color: #333;
                background: #f5f5f5;
                padding: 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #dc3545;
                margin-bottom: 20px;
                font-size: 28px;
            }
            h2 {
                margin: 30px 0 15px;
                color: #495057;
                font-size: 20px;
            }
            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .danger {
                background: #f8d7da;
                border-left: 4px solid #dc3545;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .info {
                background: #d1ecf1;
                border-left: 4px solid #0c5460;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
            ul {
                margin: 15px 0;
                padding-left: 30px;
            }
            li {
                margin: 8px 0;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                border-radius: 4px;
                cursor: pointer;
                border: none;
                margin: 10px 10px 10px 0;
                transition: all 0.3s;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            .btn-danger:hover {
                background: #c82333;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-secondary:hover {
                background: #545b62;
            }
            .actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2px solid #dee2e6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚨 Emergency Encryption Reset</h1>

            <div class="danger">
                <h2>⚠️ CRITICAL WARNING</h2>
                <p><strong>This action will PERMANENTLY DELETE ALL encrypted data including:</strong></p>
                <ul>
                    <li>✗ Account passwords</li>
                    <li>✗ Session cookies</li>
                    <li>✗ Phone recovery numbers</li>
                    <li>✗ Email recovery addresses</li>
                    <li>✗ Security questions & answers</li>
                    <li>✗ Backup codes</li>
                    <li>✗ Two-factor authentication secrets</li>
                    <li>✗ API keys</li>
                    <li>✗ Secret keys</li>
                    <li>✗ API tokens</li>
                </ul>
                <p style="margin-top: 15px;"><strong>⚠️ THIS ACTION CANNOT BE UNDONE!</strong></p>
            </div>

            <div class="info">
                <h2>ℹ️ What will be preserved:</h2>
                <ul>
                    <li>✓ Account basic information (provider name, account login, display name)</li>
                    <li>✓ Account status and capacity settings</li>
                    <li>✓ Expiration dates</li>
                    <li>✓ Notes</li>
                    <li>✓ All license assignments</li>
                </ul>
                <p><strong>You will need to re-enter all passwords and credentials after reset.</strong></p>
            </div>

            <div class="warning">
                <h2>📋 Use this tool ONLY if:</h2>
                <ul>
                    <li>Encryption is broken (IV errors in logs)</li>
                    <li>You cannot decrypt existing data</li>
                    <li>You have backed up all account credentials</li>
                    <li>You are willing to re-enter all passwords and API keys</li>
                </ul>
            </div>

            <div class="actions">
                <p style="font-size: 18px; margin-bottom: 20px;">
                    <strong>Are you absolutely sure you want to continue?</strong>
                </p>

                <form method="get" style="display: inline;">
                    <input type="hidden" name="confirm" value="yes_reset_all_encrypted_data">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('FINAL WARNING: This will delete all encrypted data. Are you 100% sure?');">
                        🗑️ Yes, DELETE ALL Encrypted Data
                    </button>
                </form>

                <a href="<?php echo admin_url('admin.php?page=vd-accounts'); ?>" class="btn btn-secondary">
                    ← Cancel and Go Back
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// User confirmed - proceed with reset
global $wpdb;
$table = $wpdb->prefix . 'vd_provider_accounts';

// List of encrypted fields
$encrypted_fields = array(
    'account_password',
    'cookies',
    'phone_recovery',
    'email_recovery',
    'security_answer',
    'backup_codes',
    'two_factor_secret',
    'api_key',
    'secret_key',
    'api_token'
);

// Count affected accounts before reset
$count_query = "SELECT COUNT(*) FROM {$table} WHERE ";
$conditions = array();
foreach ($encrypted_fields as $field) {
    $conditions[] = "{$field} IS NOT NULL AND {$field} != ''";
}
$count_query .= '(' . implode(' OR ', $conditions) . ')';
$affected_count = $wpdb->get_var($count_query);

// Reset each encrypted field to NULL
$errors = array();
foreach ($encrypted_fields as $field) {
    $result = $wpdb->query(
        "UPDATE {$table} SET {$field} = NULL WHERE {$field} IS NOT NULL"
    );

    if ($result === false) {
        $errors[] = "Failed to reset field: {$field} - " . $wpdb->last_error;
    }
}

// Log the action
error_log(sprintf(
    'VD Encryption Reset: Cleared %d encrypted fields from %d accounts',
    count($encrypted_fields),
    $affected_count
));

// Show results page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($errors) ? '✅ Reset Complete' : '⚠️ Reset Completed with Errors'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 20px; }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        ul { margin: 15px 0; padding-left: 30px; }
        li { margin: 8px 0; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 10px 0 0;
        }
        .btn:hover { background: #0056b3; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (empty($errors)): ?>
            <h1>✅ Reset Complete!</h1>

            <div class="success">
                <p><strong>All encrypted data has been successfully cleared.</strong></p>
                <ul>
                    <li>Affected accounts: <strong><?php echo $affected_count; ?></strong></li>
                    <li>Fields reset: <strong><?php echo count($encrypted_fields); ?></strong></li>
                    <li>Status: <strong>Success</strong></li>
                </ul>
            </div>

            <div class="warning">
                <h2>📝 Next Steps:</h2>
                <ol>
                    <li><strong>Delete this file</strong> for security:
                        <code>reset-encryption.php</code>
                    </li>
                    <li>Go to your accounts and re-enter credentials</li>
                    <li>Test creating a new account with the fixed encryption</li>
                    <li>Verify no more decryption errors in logs</li>
                </ol>
            </div>

            <a href="<?php echo admin_url('admin.php?page=vd-accounts'); ?>" class="btn">
                → View Accounts
            </a>
            <a href="<?php echo admin_url('admin.php?page=vd-accounts&action=add'); ?>" class="btn">
                → Create New Account
            </a>

        <?php else: ?>
            <h1>⚠️ Reset Completed with Errors</h1>

            <div class="error">
                <p><strong>Some fields could not be reset:</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="warning">
                <p>Check <code>wp-content/debug.log</code> for more details.</p>
            </div>

            <a href="<?php echo admin_url('admin.php?page=vd-accounts'); ?>" class="btn">
                → View Accounts
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
exit;