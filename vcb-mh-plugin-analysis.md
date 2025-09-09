# Phân tích Plugin VCB-MH (Vietcombank Payment Gateway)

## 1. Thông tin tổng quan

- **Tên Plugin**: Vietcombank Payment Gateway - MH
- **Version**: 2.1
- **Tác giả**: Đỗ Minh Hải
- **Mục đích**: Cổng thanh toán Vietcombank cho WooCommerce

## 2. Cấu trúc thư mục

```
vcb-mh/
├── admin/
│   └── vcb-gateway.php (Payment gateway class)
├── assets/
├── includes/
│   ├── class-vcb-mh.php (Core class)
│   ├── class-vcb-mh8.php (PHP 8.1+)
│   ├── class-vcb-mh83.php (PHP 8.3+)
│   └── class-momo-mh-en*.php (MoMo integration)
├── languages/
├── public/
│   ├── images/
│   │   └── VCB.png (Logo Vietcombank)
│   ├── js/
│   │   └── sweetalert2.all.min.js
│   └── css/
│       └── sweetalert2.css
└── vcb-mh.php (Main plugin file)
```

## 3. Chức năng chính

### 3.1 Payment Gateway cho WooCommerce
- Tích hợp như một phương thức thanh toán trong WooCommerce
- Class `Vcb_Gateway_MH` extends `WC_Payment_Gateway`

### 3.2 Tính năng thanh toán
- **QR Code Payment**: Tạo mã QR cho khách quét thanh toán
- **VietQR API**: Sử dụng API `https://api.vietqr.io/970436/` để tạo QR
- **Tự động sinh nội dung chuyển khoản**: `prefix + order_id + suffix`

### 3.3 Cấu hình linh hoạt
- Cấu hình STK nhận tiền
- Cấu hình prefix/suffix cho nội dung chuyển khoản
- Hỗ trợ quy đổi tỷ giá (currency_rate)
- Tùy chỉnh trạng thái đơn hàng sau khi tạo

### 3.4 Theo dõi giao dịch
- Database table: `vcb_gateway_transactions`
- Kiểm tra trạng thái thanh toán (`is_paid`)
- Hiển thị animation success khi thanh toán thành công

## 4. Flow hoạt động

1. **Khách chọn thanh toán**: Chọn "Vietcombank Gateway MH" tại checkout
2. **Tạo đơn hàng**: 
   - Giảm stock
   - Clear giỏ hàng
   - Chuyển đến trang Thank You
3. **Trang Thank You**:
   - Hiển thị QR code với:
     - STK: Từ config
     - Số tiền: Total order (có thể quy đổi)
     - Nội dung: `prefix + order_id + suffix`
   - Hướng dẫn 2 bước quét mã
4. **Kiểm tra thanh toán**: Query database xem đã thanh toán chưa

## 5. Các điểm cần lưu ý

### 5.1 Bảo mật
- Plugin có function `debug()` với `print_r()` - nên remove trong production
- Không thấy có validate/sanitize input ở một số chỗ

### 5.2 Tính năng đặc biệt
- Hỗ trợ cả Vietcombank và MoMo (có file class MoMo)
- Tương thích với nhiều phiên bản PHP (7.x, 8.1, 8.3)
- Sử dụng SweetAlert2 cho UI đẹp

### 5.3 Dependency
- WooCommerce (bắt buộc)
- SweetAlert2 (đi kèm)
- VietQR API (external)

## 6. Cách hoạt động với Vidieu.vn

Plugin này cho phép khách hàng:
1. Đặt hàng trên website
2. Được chuyển đến trang có mã QR
3. Mở app ngân hàng quét mã
4. Chuyển tiền với nội dung tự động
5. Admin theo dõi và xác nhận thanh toán

## 7. Khuyến nghị

1. **Bảo mật**: Remove function `debug()` 
2. **Validate**: Thêm validate/sanitize cho các input
3. **Webhook**: Có vẻ chưa implement webhook tự động cập nhật trạng thái
4. **Documentation**: README.txt chưa có thông tin chi tiết