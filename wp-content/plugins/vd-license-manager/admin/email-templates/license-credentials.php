<?php
/**
 * Email Template: License Delivery
 *
 * Simple email with license key and portal link only
 * Customer accesses portal to view account credentials
 *
 * Available variables:
 * @var string $customer_name Customer's full name
 * @var string $customer_email Customer's email
 * @var string $product_name Product name
 * @var string $license_key License key from LMfWC
 * @var int $max_devices Maximum devices allowed
 * @var int $validity_days License validity in days
 * @var string $expiry_date License expiration date (formatted)
 * @var string $portal_url License portal URL
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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Key - <?php echo esc_html($product_name); ?></title>
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
        }
        .license-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
            text-align: center;
        }
        .license-box h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .license-key {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: bold;
            background-color: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 6px;
            letter-spacing: 2px;
            word-break: break-all;
        }
        .portal-button {
            display: inline-block;
            background-color: #667eea;
            color: white !important;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .instructions {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            margin-top: 0;
            color: #667eea;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        .instructions li {
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .info-box li {
            margin-bottom: 8px;
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
            .license-key {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>🎉 License Key Của Bạn!</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Xin chào <?php echo esc_html($customer_name); ?>,
            </div>

            <p>
                Cảm ơn bạn đã mua <strong><?php echo esc_html($product_name); ?></strong>!
                License của bạn đã được kích hoạt thành công.
            </p>

            <!-- License Key Box -->
            <div class="license-box">
                <h2>🔑 LICENSE KEY CỦA BẠN</h2>
                <div class="license-key"><?php echo esc_html($license_key); ?></div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h3>📋 Cách Sử Dụng</h3>
                <ol>
                    <li>Click vào nút bên dưới để truy cập License Portal</li>
                    <li>Nhập license key ở trên vào ô "Nhập License Key"</li>
                    <li>Xem thông tin tài khoản và bắt đầu sử dụng</li>
                </ol>
            </div>

            <!-- Portal Button -->
            <div style="text-align: center;">
                <a href="<?php echo esc_url($portal_url); ?>" class="portal-button">
                    Truy Cập License Portal
                </a>
            </div>

            <!-- License Info -->
            <div class="info-box">
                <h3 style="margin-top: 0; color: #1976d2;">ℹ️ Thông Tin License</h3>
                <ul>
                    <li><strong>Sản phẩm:</strong> <?php echo esc_html($product_name); ?></li>
                    <li><strong>Tối đa thiết bị:</strong> <?php echo esc_html($max_devices); ?> thiết bị</li>
                    <?php if ($validity_days > 0): ?>
                    <li><strong>Hiệu lực đến:</strong> <?php echo esc_html($expiry_date); ?> (<?php echo esc_html($validity_days); ?> ngày)</li>
                    <?php else: ?>
                    <li><strong>Hiệu lực:</strong> Trọn đời</li>
                    <?php endif; ?>
                    <li><strong>Mã đơn hàng:</strong> #<?php echo esc_html($order_id); ?></li>
                </ul>
            </div>

            <!-- Important Notes -->
            <div class="info-box" style="background-color: #fff3cd; border-left-color: #ffc107;">
                <p style="margin: 0;"><strong>⚠️ Lưu ý quan trọng:</strong></p>
                <ul>
                    <li>Vui lòng lưu lại license key này</li>
                    <li>Bạn có thể truy cập portal bất cứ lúc nào bằng license key</li>
                    <li>Nếu cần hỗ trợ, vui lòng liên hệ support với mã đơn hàng</li>
                </ul>
            </div>

            <p style="margin-top: 30px;">
                Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi.
            </p>

            <p style="margin-top: 20px;">
                Trân trọng,<br>
                <strong><?php echo esc_html($site_name); ?> Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                Email này được gửi đến <?php echo esc_html($customer_email); ?><br>
                <a href="<?php echo esc_url($site_url); ?>" style="color: #667eea;"><?php echo esc_html($site_name); ?></a>
            </p>
        </div>
    </div>
</body>
</html>