# VietQR Integration Changelog

## Version 1.1.0 - 2025-08-30

### Cập nhật
- Thay đổi layout: QR code hiển thị ở cột 2, thông tin ngân hàng ở cột 1
- Sắp xếp lại: Số tài khoản xuống dưới tên ngân hàng trong cột 1
- Sử dụng CSS Grid cho responsive layout

### Chi tiết implementation
- Hook: `wp_footer` với JavaScript để reorganize DOM
- Grid layout 2 cột trên desktop (768px+)
- Stack layout trên mobile

---

## Version 1.0.0 - 2025-08-30

### Mục tiêu
Tích hợp mã QR VietQR vào trang cảm ơn (order-received) cho các đơn hàng thanh toán qua chuyển khoản ngân hàng (BACS).

### Tính năng
- Hiển thị mã QR VietQR tự động với số tiền và nội dung chuyển khoản
- Nút sao chép nội dung chuyển khoản một click
- Chỉ hiển thị cho đơn hàng BACS trên trang order-received
- Responsive design, tối đa 200px width (v1.1)

### Chi tiết kỹ thuật

#### Files tạo mới
- `/wp-content/themes/elessi-theme-child/woocommerce-vietqr-integration.php`

#### Files chỉnh sửa
- `/wp-content/themes/elessi-theme-child/functions.php` - Thêm require_once cho file integration

#### Hooks sử dụng (v1.1)
- `wp_footer` - Hook để inject JavaScript reorganize layout

#### Logic xử lý
1. **Kiểm tra điều kiện**: 
   - Là trang order-received (`is_order_received_page()`)
   - Payment method là 'bacs'
   - Order ID hợp lệ

2. **Tạo URL VietQR**:
   ```
   https://img.vietqr.io/image/{bank_id}-{account}-qr_only.png
   ?amount={order_total}&addInfo={VIDIEU #order_id}&accountName={name}
   ```

3. **Thông tin bank**:
   - Bank ID: vietcombank
   - Số TK: 0821000013390
   - Tên TK: PHAM DUY DIEU

### Rollback Guide
Để gỡ bỏ tính năng:

1. Xóa dòng require trong functions.php:
   ```php
   // require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php';
   ```

2. Xóa file:
   ```
   rm /wp-content/themes/elessi-theme-child/woocommerce-vietqr-integration.php
   ```

### Testing
Kiểm tra tại: https://vidieu.vn/checkout/order-received/{order_id}/?key={order_key}

Yêu cầu kiểm tra:
- [ ] QR hiển thị đúng trong section BACS
- [ ] Amount = tổng đơn hàng
- [ ] AddInfo = VIDIEU #{order_id}
- [ ] Nút copy hoạt động
- [ ] Không hiển thị với payment method khác
- [ ] Không duplicate QR
- [ ] Responsive trên mobile

### Notes
- Không sử dụng external CSS/JS
- CSS inline để tránh ảnh hưởng performance
- JavaScript inline cho copy function
- Sử dụng static variable để tránh duplicate