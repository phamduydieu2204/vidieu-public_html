# Vidieu License API Plugin

## Mô tả
Plugin WordPress tùy chỉnh tạo REST API endpoint để xác thực license từ Google Apps Script.

## Endpoint
`POST /wp-json/vidieu/v1/validate-license`

## Request Format
```json
{
  "license_key": "PPC-TVN1-Z6N5-ADTV-NJG1",
  "email": "user@email.com",
  "allowed_product_ids": [8107, 8108, 8109],
  "source": "gas_ppc_amazon"
}
```

## Response Format

### Thành công
```json
{
  "success": true,
  "status": "success",
  "message": "Xác thực thành công.",
  "data": {
    "license_key": "PPC-TVN1-Z6N5-ADTV-NJG1",
    "product_id": 8107,
    "expires_at": "2025-10-20 08:16:15",
    "activation_data": [...]
  }
}
```

### Lỗi
```json
{
  "success": false,
  "status": "error",
  "message": "License không tồn tại trên hệ thống.",
  "data": null
}
```

### Cảnh báo
```json
{
  "success": false,
  "status": "warning",
  "message": "License đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng.",
  "data": { license_data }
}
```

## Logic xử lý

1. **Validate Input**: Kiểm tra license_key, email, allowed_product_ids
2. **Check Prefix**: License key phải bắt đầu với "PPC"
3. **Get License Data**: Gọi LMfWC API để lấy thông tin license
4. **Check Product ID**: Kiểm tra product_id có trong allowed_product_ids
5. **Check Expiry**: Kiểm tra ngày hết hạn
6. **Handle Activation**:
   - Nếu chưa activate → Tự động activate với email hiện tại
   - Nếu đã activate → So khớp email

## Status Types
- `success`: Xác thực thành công
- `error`: Lỗi nghiêm trọng (không cho phép tiếp tục)
- `warning`: Cảnh báo nhưng vẫn có thể sử dụng

## Cài đặt
1. Upload thư mục `vidieu-license-api` vào `/wp-content/plugins/`
2. Kích hoạt plugin trong WordPress Admin
3. Endpoint sẽ tự động hoạt động tại `/wp-json/vidieu/v1/validate-license`

## Logging
Plugin sẽ ghi log các request và response vào WordPress error log để debug.

## Tác giả
Vidieu.vn - Phát triển bởi Claude Code