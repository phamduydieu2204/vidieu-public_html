# Hướng dẫn tích hợp VCB-MH sạch sẽ với WooCommerce gốc

## Phân tích hiện trạng

### Files override đang can thiệp vào checkout/payment:

1. **woocommerce-vietqr-integration.php**
   - Hook vào BACS payment để hiển thị VietQR
   - Chỉ áp dụng cho payment method `bacs`
   - Tạo QR với account: 0821000013390

2. **woocommerce-ajax-checkout-bypass.php**
   - Bypass WooCommerce checkout validation
   - Hardcode payment method = `bacs` (line 88)
   - Tạo custom AJAX endpoint để skip validation

3. **woocommerce-checkout-simple-fix.php**
   - Ẩn các field không cần thiết
   - Force các giá trị mặc định cho address fields
   - Disable shipping

4. **woocommerce-thankyou-fix.php**
   - Fix duplicate content trên order-received page

5. **MU-Plugins**:
   - vcb-mh-integration.php - Bảo vệ SQL injection
   - payment-gateway-conflict-fix.php - Xử lý conflict

## Các bước để clean integration VCB-MH

### Bước 1: Backup và disable các overrides

```bash
# Backup current setup
cp -r wp-content/themes/elessi-theme-child wp-content/themes/elessi-theme-child-backup-$(date +%Y%m%d)

# Disable includes trong functions.php bằng cách comment out
```

### Bước 2: Disable các file override trong functions.php

Mở `wp-content/themes/elessi-theme-child/functions.php` và comment out các dòng sau:

```php
// require_once get_stylesheet_directory() . '/woocommerce-ajax-checkout-bypass.php';
// require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php';
// require_once get_stylesheet_directory() . '/woocommerce-checkout-simple-fix.php';
```

### Bước 3: Cấu hình payment gateways

1. **Disable BACS**:
   - WooCommerce → Settings → Payments
   - Uncheck "Enable Direct bank transfer"

2. **Enable VCB-MH**:
   - Check "Enable Vietcombank Gateway MH"
   - Click "Manage" để cấu hình

3. **Cấu hình VCB-MH**:
   - Vào menu VCB Gateway
   - Nhập số tài khoản: 0821000013390
   - Tên tài khoản: Phạm Duy Diệu
   - Prefix: "Vidieuvn"

### Bước 4: Test với checkout mặc định

1. **Restore checkout mặc định**:
   ```php
   // Trong functions.php, thêm:
   add_filter('woocommerce_checkout_fields', function($fields) {
       return $fields; // Return fields nguyên bản
   }, 99999);
   ```

2. **Test flow**:
   - Thêm sản phẩm vào giỏ
   - Checkout với đầy đủ fields
   - Chọn "Vietcombank Gateway MH"
   - Verify QR code hiển thị đúng

### Bước 5: Tùy chỉnh từng bước (nếu cần)

Nếu vẫn muốn simple checkout với 4 fields, tạo file mới:

```php
<?php
// vcb-mh-simple-checkout.php

// Chỉ giữ 4 fields cho VCB-MH payment
add_filter('woocommerce_checkout_fields', function($fields) {
    // Check if VCB-MH is selected payment method
    if (WC()->session && WC()->session->get('chosen_payment_method') === 'vcb-gateway-mh') {
        // Apply simple fields
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_state']);
    }
    return $fields;
}, 20);
```

## Files cần giữ lại

1. **vcb-mh-integration.php** (MU-Plugin) - Bảo vệ SQL injection
2. **payment-gateway-conflict-fix.php** - Debug helper
3. **vcb-mh-setup-guide.md** - Documentation

## Files có thể disable/remove

1. **woocommerce-vietqr-integration.php** - Nếu chỉ dùng VCB-MH
2. **woocommerce-ajax-checkout-bypass.php** - Nếu dùng checkout chuẩn
3. **woocommerce-checkout-simple-fix.php** - Nếu chấp nhận full fields

## Checklist kiểm tra

- [ ] Backup files hiện tại
- [ ] Disable BACS payment gateway
- [ ] Enable và config VCB-MH gateway
- [ ] Comment out các require_once trong functions.php
- [ ] Test checkout với full fields
- [ ] Verify QR code từ VCB-MH hiển thị đúng
- [ ] Test email notifications
- [ ] Monitor error logs

## Lệnh debug hữu ích

```bash
# Check active payment gateways
wp eval "
\$gateways = WC()->payment_gateways()->payment_gateways();
foreach(\$gateways as \$id => \$gateway) {
    echo \$id . ': ' . (\$gateway->enabled === 'yes' ? 'ENABLED' : 'DISABLED') . PHP_EOL;
}"

# Check recent orders payment methods
wp eval "
\$orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);
foreach(\$orders as \$order) {
    echo 'Order #' . \$order->get_id() . ': ' . \$order->get_payment_method() . PHP_EOL;
}"
```

## Rollback plan

Nếu gặp vấn đề, restore từ backup:

```bash
# Restore backup
mv wp-content/themes/elessi-theme-child wp-content/themes/elessi-theme-child-failed
mv wp-content/themes/elessi-theme-child-backup-[DATE] wp-content/themes/elessi-theme-child
```