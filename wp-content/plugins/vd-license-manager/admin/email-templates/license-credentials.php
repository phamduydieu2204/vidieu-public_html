<?php
/**
 * Email Template: License Credentials
 *
 * Sent to customers after successful license assignment
 * Contains account login credentials and usage instructions
 *
 * Available variables:
 * @var string $customer_name Customer's full name
 * @var string $customer_email Customer's email
 * @var string $product_name Product name
 * @var string $license_key License key from LMfWC
 * @var string $account_login Account username/email
 * @var string $account_password Decrypted account password
 * @var int $max_devices Maximum devices allowed
 * @var int $validity_days License validity in days
 * @var string $expiry_date License expiration date (formatted)
 * @var int $max_requests_per_day API rate limit
 * @var string $order_id WooCommerce order ID
 * @var string $site_name WordPress site name
 * @var string $site_url WordPress site URL
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your <?php echo esc_html($product_name); ?> Account Credentials</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333333;
        }
        .intro-text {
            margin-bottom: 25px;
            color: #666666;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .credentials-box h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #333333;
        }
        .credential-item {
            margin-bottom: 15px;
        }
        .credential-label {
            font-weight: 600;
            color: #555555;
            display: block;
            margin-bottom: 5px;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #333333;
            background-color: #ffffff;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            word-break: break-all;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #1976d2;
        }
        .info-list {
            margin: 0;
            padding-left: 20px;
        }
        .info-list li {
            margin-bottom: 8px;
            color: #555555;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 0;
            color: #856404;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            color: #666666;
            font-size: 14px;
        }
        .support-link {
            color: #667eea;
            text-decoration: none;
        }
        .support-link:hover {
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .email-body {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>🎉 Your Account Is Ready!</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Hi <?php echo esc_html($customer_name); ?>,
            </div>

            <div class="intro-text">
                Thank you for your purchase! Your <strong><?php echo esc_html($product_name); ?></strong> account has been activated and is ready to use.
            </div>

            <!-- Account Credentials -->
            <div class="credentials-box">
                <h2>🔑 Your Account Credentials</h2>

                <div class="credential-item">
                    <span class="credential-label">License Key:</span>
                    <div class="credential-value"><?php echo esc_html($license_key); ?></div>
                </div>

                <div class="credential-item">
                    <span class="credential-label">Account Login:</span>
                    <div class="credential-value"><?php echo esc_html($account_login); ?></div>
                </div>

                <div class="credential-item">
                    <span class="credential-label">Account Password:</span>
                    <div class="credential-value"><?php echo esc_html($account_password); ?></div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="info-box">
                <h3>📋 Account Details</h3>
                <ul class="info-list">
                    <li><strong>Maximum Devices:</strong> <?php echo esc_html($max_devices); ?> device<?php echo $max_devices > 1 ? 's' : ''; ?></li>
                    <?php if ($validity_days > 0): ?>
                    <li><strong>Valid Until:</strong> <?php echo esc_html($expiry_date); ?> (<?php echo esc_html($validity_days); ?> days)</li>
                    <?php else: ?>
                    <li><strong>Validity:</strong> Lifetime</li>
                    <?php endif; ?>
                    <li><strong>Daily API Limit:</strong> <?php echo number_format($max_requests_per_day); ?> requests per day</li>
                    <li><strong>Order ID:</strong> #<?php echo esc_html($order_id); ?></li>
                </ul>
            </div>

            <!-- Important Notice -->
            <div class="warning-box">
                <p>
                    <strong>⚠️ Important:</strong> Please keep these credentials safe and do not share them with anyone.
                    You are responsible for all activity under this account.
                </p>
            </div>

            <!-- Getting Started -->
            <div class="info-box">
                <h3>🚀 Getting Started</h3>
                <ul class="info-list">
                    <li>Use the credentials above to log in to your <?php echo esc_html($product_name); ?> account</li>
                    <li>You can use this account on up to <?php echo esc_html($max_devices); ?> device<?php echo $max_devices > 1 ? 's' : ''; ?> simultaneously</li>
                    <li>If you encounter any issues, please contact our support team</li>
                </ul>
            </div>

            <p style="margin-top: 30px; color: #666666;">
                If you have any questions or need assistance, please don't hesitate to
                <a href="<?php echo esc_url($site_url); ?>/contact" class="support-link">contact our support team</a>.
            </p>

            <p style="margin-top: 20px; color: #666666;">
                Best regards,<br>
                <strong>The <?php echo esc_html($site_name); ?> Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                This email was sent to <?php echo esc_html($customer_email); ?><br>
                <a href="<?php echo esc_url($site_url); ?>" class="support-link"><?php echo esc_html($site_name); ?></a>
            </p>
        </div>
    </div>
</body>
</html>