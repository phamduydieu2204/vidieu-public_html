# VCB-MH Troubleshooting Guide

## Vấn đề: VietQR cũ vẫn hiển thị thay vì VCB-MH

### Nguyên nhân gốc

1. **File `woocommerce-vietqr-integration.php` vẫn được load**
   - Hook `vidieu_reorganize_bacs_with_qr` chạy trên `wp_footer`
   - Override hiển thị của mọi payment gateway

2. **Payment method mismatch**
   - Orders cũ dùng `bacs`
   - VCB-MH dùng `vcb-gateway-mh`

3. **Hook priority conflict**
   - VietQR cũ chạy sau và override VCB-MH

## Giải pháp đã triển khai

### 1. Force VCB-MH Display (MU-Plugin)
File: `wp-content/mu-plugins/force-vcb-mh-display.php`
- Remove tất cả hooks của VietQR cũ
- Force hide elements VietQR via CSS
- Ensure VCB-MH thankyou_page chạy

### 2. Clean Functions.php
File: `functions-vcb-mh-clean.php`
- Comment out require VietQR integration
- Giữ lại các features không liên quan

### 3. Payment Gateway Conflict Fix
File: `wp-content/mu-plugins/payment-gateway-conflict-fix.php`
- Auto detect payment method
- Hide conflicting elements

## Các bước kiểm tra

### 1. Verify files đã disable
```bash
# Check functions.php
grep "woocommerce-vietqr-integration" functions.php
# Kết quả mong đợi: dòng bị comment // require_once...
```

### 2. Check payment method của order
```php
// Tạo file check-payment.php
<?php
require_once('wp-load.php');
$order = wc_get_order(7777);
echo "Payment method: " . $order->get_payment_method();
?>
```

### 3. Clear all caches
- Browser cache: Ctrl + F5
- WordPress cache (nếu có)
- CDN cache (nếu có)

### 4. Test order mới
1. Tạo order test mới
2. Chọn "Vietcombank Gateway MH" 
3. Check QR hiển thị

## Debug checklist

- [ ] File `functions.php` đã dùng version clean?
- [ ] MU-plugin `force-vcb-mh-display.php` đã active?
- [ ] Payment gateway VCB-MH enabled trong WooCommerce?
- [ ] BACS payment gateway đã disabled?
- [ ] Clear tất cả cache?
- [ ] Order mới dùng đúng payment method `vcb-gateway-mh`?

## Emergency rollback

Nếu cần quay lại VietQR cũ:
```bash
# Restore functions.php gốc
mv functions.php functions-vcb.php
mv functions-backup.php functions.php

# Disable MU plugins
mv wp-content/mu-plugins/force-vcb-mh-display.php wp-content/mu-plugins/force-vcb-mh-display.php.disabled
```

## Logs để kiểm tra

```bash
# Check error log
tail -f wp-content/debug.log | grep -E "vcb|payment|order"

# Check order payment methods
wp db query "SELECT ID, post_status FROM wp_posts WHERE ID IN (7775,7777)"
wp db query "SELECT * FROM wp_postmeta WHERE post_id IN (7775,7777) AND meta_key = '_payment_method'"
```