# Hướng dẫn cấu hình VCB-MH Payment Gateway

## 1. Kích hoạt Plugin

1. Vào **Plugins** → **Installed Plugins**
2. Tìm "Vietcombank Payment Gateway - MH"
3. Click **Activate**

## 2. Cấu hình Gateway

### 2.1 Vào WooCommerce Settings
1. **WooCommerce** → **Settings** → **Payments**
2. Tìm "Vietcombank Gateway MH"
3. Click **Manage**

### 2.2 Cấu hình cơ bản
- **Enable/Disable**: ✅ Tích chọn để bật
- **Title**: "Thanh toán qua Vietcombank"
- **Description**: "Quét mã QR để thanh toán qua App ngân hàng"

### 2.3 Cấu hình tài khoản (Quan trọng!)

Vào menu **VCB Gateway** trong admin:

1. **Thông tin tài khoản**:
   - **Số điện thoại**: Số tài khoản VCB của bạn
   - **Tên tài khoản**: Tên chủ tài khoản
   
2. **Cấu hình nội dung chuyển khoản**:
   - **Prefix**: "DH" (hoặc tùy chỉnh)
   - **Suffix**: "" (có thể để trống)
   - Kết quả: Nội dung CK sẽ là "DH[MÃ_ĐƠN_HÀNG]"

3. **Cấu hình khác**:
   - **Order Status sau khi tạo**: "On Hold" (Chờ xử lý)
   - **Currency Rate**: 1 (nếu dùng VND)

4. **Thông báo**:
   - **Payment Gateway Label**: "Thanh toán Vietcombank"
   - **Method Description**: "Quét mã QR để chuyển khoản"
   - **Order Completed**: "Đơn hàng đã được thanh toán thành công!"

## 3. Test thanh toán

1. Tạo đơn hàng test
2. Chọn phương thức "Thanh toán qua Vietcombank"
3. Sau khi đặt hàng, sẽ thấy:
   - Mã QR để quét
   - Thông tin chuyển khoản
   - Hướng dẫn 2 bước

## 4. Kiểm tra hoạt động

### 4.1 Database
Plugin sẽ tạo 2 bảng:
- `vcb_gateway_transactions`: Lưu giao dịch
- `vcb_gateway_cron`: Lưu cron jobs

### 4.2 Sync Transactions
- Plugin có chức năng sync giao dịch
- Cần cấu hình thông tin đăng nhập VCB (nếu có API)

## 5. Lưu ý quan trọng

### 5.1 Bảo mật
- MU-Plugin `vcb-mh-integration.php` đã được tạo để:
  - Bảo vệ SQL injection
  - Thêm logging
  - Enhance UX

### 5.2 Email thông báo
- Khi khách thanh toán xong, admin sẽ nhận email
- Khách hàng vẫn nhận email order như bình thường

### 5.3 Xử lý manual
- Hiện tại cần xác nhận thanh toán thủ công
- Check trong **VCB Gateway** → **Transactions**

## 6. Troubleshooting

### Không thấy payment method
- Kiểm tra plugin đã active
- Clear cache
- Check WooCommerce → Settings → Payments

### QR code không hiển thị
- Kiểm tra đã cấu hình số TK
- Check console errors
- Đảm bảo VietQR API hoạt động

### Payment không update
- Cần manual check và update order status
- Hoặc setup cron job để auto sync

## 7. Testing Checklist

- [ ] Plugin activated
- [ ] Payment method enabled
- [ ] Account info configured
- [ ] Test order với VCB payment
- [ ] QR code hiển thị đúng
- [ ] Nội dung CK đúng format
- [ ] Email notification hoạt động