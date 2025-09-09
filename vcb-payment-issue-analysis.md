# Phân tích vấn đề hiển thị VietQR cũ thay vì VCB-MH

## Nguyên nhân chính

Order #7775 vẫn hiển thị VietQR cũ vì:

1. **Payment method mismatch**: 
   - Order #7775 sử dụng payment method `bacs` (Direct Bank Transfer)
   - Plugin VCB-MH sử dụng payment method ID `vcb-gateway-mh`
   - File `woocommerce-vietqr-integration.php` chỉ hook vào orders với payment method `bacs` (line 95)

2. **Cả 2 payment gateways đều active**:
   - BACS (Direct Bank Transfer) - hiển thị VietQR cũ
   - VCB-MH (Vietcombank Gateway MH) - hiển thị QR mới

## Giải pháp

### Option 1: Chỉ dùng VCB-MH (Recommended)
1. Vào **WooCommerce → Settings → Payments**
2. **Disable** "Direct bank transfer" (BACS)
3. **Enable** chỉ "Vietcombank Gateway MH"
4. Orders mới sẽ dùng VCB-MH và hiển thị đúng

### Option 2: Update checkout flow
File `woocommerce-ajax-checkout-bypass.php` đang hardcode payment method là `bacs` (line 88):
```php
'payment_method' => 'bacs',
```

Cần sửa thành:
```php
'payment_method' => 'vcb-gateway-mh',
```

### Option 3: Giữ cả 2 và phân biệt rõ ràng
Đã tạo `payment-gateway-conflict-fix.php` để:
- Ẩn VietQR cũ khi order dùng VCB-MH
- Ẩn VCB-MH elements khi order dùng BACS
- Log payment method để debug
- Warning admin khi cả 2 đều enabled

## Các file liên quan

1. **woocommerce-vietqr-integration.php**:
   - Chỉ apply cho payment method `bacs`
   - Hiển thị VietQR với account: 0821000013390
   - Có nút "Tôi đã chuyển khoản"

2. **vcb-mh plugin**:
   - Payment method ID: `vcb-gateway-mh`
   - Cần config số TK trong admin
   - Có SQL injection issue (đã fix trong mu-plugin)

3. **woocommerce-ajax-checkout-bypass.php**:
   - Hardcode payment = `bacs`
   - Cần update nếu muốn dùng VCB-MH

## Testing checklist

- [ ] Kiểm tra payment method của order cũ
- [ ] Test tạo order mới với VCB-MH
- [ ] Verify QR code hiển thị đúng
- [ ] Check email notifications
- [ ] Monitor SQL injection protection

## Lệnh kiểm tra nhanh

```bash
# Check order payment method
wp eval "echo 'Order 7775 payment: ' . wc_get_order(7775)->get_payment_method();"

# List enabled payment gateways  
wp eval "foreach(WC()->payment_gateways()->payment_gateways() as \$id => \$gw) { echo \$id . ': ' . (\$gw->enabled === 'yes' ? 'ON' : 'OFF') . PHP_EOL; }"
```