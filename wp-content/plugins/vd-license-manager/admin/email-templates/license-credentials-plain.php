<?php
/**
 * Email Template: License Delivery (Plain Text)
 *
 * Simple plain text email with license key and portal link only
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
=== 🎉 LICENSE KEY CỦA BẠN! ===

Xin chào <?php echo esc_html($customer_name); ?>,

Cảm ơn bạn đã mua <?php echo esc_html($product_name); ?>! License của bạn đã được kích hoạt thành công.

=== 🔑 LICENSE KEY CỦA BẠN ===

<?php echo esc_html($license_key); ?>


=== 📋 CÁCH SỬ DỤNG ===

1. Truy cập License Portal: <?php echo esc_url($portal_url); ?>

2. Nhập license key ở trên vào ô "Nhập License Key"
3. Xem thông tin tài khoản và bắt đầu sử dụng

=== ℹ️ THÔNG TIN LICENSE ===

* Sản phẩm: <?php echo esc_html($product_name); ?>

* Tối đa thiết bị: <?php echo esc_html($max_devices); ?> thiết bị
<?php if ($validity_days > 0): ?>
* Hiệu lực đến: <?php echo esc_html($expiry_date); ?> (<?php echo esc_html($validity_days); ?> ngày)
<?php else: ?>
* Hiệu lực: Trọn đời
<?php endif; ?>
* Mã đơn hàng: #<?php echo esc_html($order_id); ?>


=== ⚠️ LƯU Ý QUAN TRỌNG ===

* Vui lòng lưu lại license key này
* Bạn có thể truy cập portal bất cứ lúc nào bằng license key
* Nếu cần hỗ trợ, vui lòng liên hệ support với mã đơn hàng

Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi.

Trân trọng,
<?php echo esc_html($site_name); ?> Team

---
Email này được gửi đến <?php echo esc_html($customer_email); ?>

<?php echo esc_html($site_name); ?>: <?php echo esc_url($site_url); ?>