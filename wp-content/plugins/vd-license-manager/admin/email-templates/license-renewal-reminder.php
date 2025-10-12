<?php
/**
 * Email Template: License Renewal Reminder
 *
 * Sent to customers 7 days before license expiry
 *
 * Available variables:
 * @var string $customer_name Customer's full name
 * @var string $product_name Product name
 * @var string $license_key License key
 * @var string $expiry_date License expiration date (formatted)
 * @var int $days_remaining Days until expiry
 * @var string $renewal_url Link to product page for renewal
 * @var string $portal_url License portal URL
 * @var string $site_name WordPress site name
 * @var string $site_url WordPress site URL
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gia Hạn License - <?php echo esc_html($product_name); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        }
        .warning-box {
            background: linear-gradient(135deg, #fff4e6 0%, #ffe0b2 100%);
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .warning-box h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
            color: #e65100;
        }
        .warning-box .days-remaining {
            font-size: 32px;
            font-weight: bold;
            color: #ff6f00;
            text-align: center;
            margin: 15px 0;
        }
        .license-info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .license-info h3 {
            margin-top: 0;
            color: #667eea;
        }
        .license-key {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            background-color: #ffffff;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
        }
        .renewal-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 48px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 18px;
            margin: 25px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            color: #666666;
            font-size: 14px;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .email-body {
                padding: 20px 15px;
            }
            .renewal-button {
                padding: 14px 32px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>⏰ License Đã Hết Hạn!</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Xin chào <?php echo esc_html($customer_name); ?>,
            </div>

            <p>
                License <strong><?php echo esc_html($product_name); ?></strong> của bạn
                đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng dịch vụ.
            </p>

            <!-- Warning Box -->
            <div class="warning-box">
                <h2>⚠️ License Đã Hết Hạn</h2>
                <p style="text-align: center; margin: 15px 0 0 0; font-size: 16px;">
                    License đã hết hạn vào <strong><?php echo esc_html($expiry_date); ?></strong>
                </p>
            </div>

            <!-- License Info -->
            <div class="license-info">
                <h3>📋 Thông Tin License</h3>
                <p><strong>Sản phẩm:</strong> <?php echo esc_html($product_name); ?></p>
                <p><strong>License Key:</strong></p>
                <div class="license-key"><?php echo esc_html($license_key); ?></div>
                <p><strong>Hết hạn:</strong> <?php echo esc_html($expiry_date); ?></p>
            </div>

            <!-- Renewal Button -->
            <div style="text-align: center;">
                <a href="<?php echo esc_url($renewal_url); ?>" class="renewal-button">
                    🔄 Gia Hạn Ngay
                </a>
            </div>

            <p style="margin-top: 25px; background-color: #fff3e6; padding: 15px; border-left: 4px solid #ff9800; border-radius: 4px;">
                ⚠️ <strong>Lưu ý:</strong> License đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng dịch vụ.
            </p>

            <p>
                Bạn cũng có thể kiểm tra trạng thái license tại
                <a href="<?php echo esc_url($portal_url); ?>" style="color: #667eea;">License Portal</a>.
            </p>

            <p style="margin-top: 30px;">
                Trân trọng,<br>
                <strong><?php echo esc_html($site_name); ?> Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                Email này được gửi tự động từ hệ thống<br>
                <a href="<?php echo esc_url($site_url); ?>" style="color: #667eea;"><?php echo esc_html($site_name); ?></a>
            </p>
        </div>
    </div>
</body>
</html>