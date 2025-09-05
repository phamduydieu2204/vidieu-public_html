<?php
/**
 * Test Email Script for Vidieu.vn
 * 
 * Usage: Access this file via browser to test email functionality
 */

require_once('wp-load.php');

// Check if user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('Bạn cần đăng nhập với quyền admin để sử dụng tính năng này.');
}

$test_result = '';

if (isset($_POST['send_test'])) {
    $to = sanitize_email($_POST['test_email']);
    $subject = 'Test Email từ Vidieu.vn - ' . date('Y-m-d H:i:s');
    $message = "Đây là email test từ website Vidieu.vn\n\n";
    $message .= "Thời gian: " . date('Y-m-d H:i:s') . "\n";
    $message .= "From Email (expected): admin@vidieu.vn\n";
    $message .= "SMTP Server: smtp.zoho.com:465\n\n";
    $message .= "Nếu bạn nhận được email này, hệ thống email đang hoạt động bình thường.";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: Vidieu.vn <admin@vidieu.vn>'
    );
    
    $result = wp_mail($to, $subject, $message, $headers);
    
    if ($result) {
        $test_result = '<div style="color: green; padding: 10px; border: 1px solid green; margin: 20px 0;">
            ✓ Email đã được gửi thành công đến ' . esc_html($to) . '
        </div>';
    } else {
        global $phpmailer;
        $error_info = '';
        if (is_object($phpmailer) && !empty($phpmailer->ErrorInfo)) {
            $error_info = $phpmailer->ErrorInfo;
        }
        $test_result = '<div style="color: red; padding: 10px; border: 1px solid red; margin: 20px 0;">
            ✗ Không thể gửi email. Lỗi: ' . esc_html($error_info) . '
        </div>';
    }
}

// Get current email settings
$from_email = apply_filters('wp_mail_from', 'wordpress@' . $_SERVER['HTTP_HOST']);
$from_name = apply_filters('wp_mail_from_name', 'WordPress');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email - Vidieu.vn</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        .info-box {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-top: 0;
        }
        input[type="email"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input[type="submit"] {
            background: #007cba;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-left: 10px;
        }
        input[type="submit"]:hover {
            background: #005a87;
        }
        code {
            background: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Email - Vidieu.vn</h1>
        
        <div class="info-box">
            <h3>Thông tin cấu hình hiện tại:</h3>
            <p><strong>From Email (sau filters):</strong> <code><?php echo esc_html($from_email); ?></code></p>
            <p><strong>From Name (sau filters):</strong> <code><?php echo esc_html($from_name); ?></code></p>
            <p><strong>Expected From Email:</strong> <code>admin@vidieu.vn</code></p>
            <p><strong>SMTP Server:</strong> <code>smtp.zoho.com:465 (SSL)</code></p>
        </div>
        
        <?php echo $test_result; ?>
        
        <form method="post" action="">
            <p>
                <label><strong>Gửi test email đến:</strong></label><br><br>
                <input type="email" name="test_email" required placeholder="vidieu.amz@gmail.com" 
                       value="<?php echo isset($_POST['test_email']) ? esc_attr($_POST['test_email']) : 'vidieu.amz@gmail.com'; ?>">
                <input type="submit" name="send_test" value="Gửi Test Email">
            </p>
        </form>
        
        <div class="info-box">
            <h3>Kiểm tra thêm:</h3>
            <ul>
                <li>Đảm bảo đã cài đặt và kích hoạt plugin <strong>WP Mail SMTP</strong></li>
                <li>Cấu hình WP Mail SMTP với thông tin Zoho Mail</li>
                <li>File <code>wp-config.php</code> đã được cập nhật với constants WPMS_MAIL_FROM</li>
                <li>MU-Plugin <code>force-email-from.php</code> đã được tạo</li>
            </ul>
        </div>
        
        <p><a href="<?php echo admin_url(); ?>">← Quay lại Admin Dashboard</a></p>
    </div>
</body>
</html>