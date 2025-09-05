# Hướng dẫn cấu hình WP Mail SMTP đúng cách

## 1. Trong WP Mail SMTP Settings:

### Tab: Settings → From Email
- **From Email**: `admin@vidieu.vn` 
- **From Name**: `Vidieu.vn`
- ✅ **Force From Email** (QUAN TRỌNG!)
- ✅ **Force From Name**

### Tab: Settings → Other SMTP
- **SMTP Host**: `smtp.zoho.com`
- **Encryption**: SSL
- **SMTP Port**: 465
- **SMTP Username**: `admin@vidieu.vn`
- **SMTP Password**: `aLfXgVfdcXmV`

## 2. Trong WooCommerce Settings:

### WooCommerce → Settings → Emails → Email sender options
- **"From" name**: `Vidieu.vn`
- **"From" address**: `admin@vidieu.vn`

## 3. Kiểm tra và xóa mu-plugin (nếu đã cấu hình đúng):

```bash
# Backup trước
mv wp-content/mu-plugins/woocommerce-email-fix.php wp-content/mu-plugins/woocommerce-email-fix.php.backup

# Hoặc xóa luôn
rm wp-content/mu-plugins/woocommerce-email-fix.php
```

## Lưu ý quan trọng:

- **Force From Email** trong WP Mail SMTP là tính năng quan trọng nhất
- Khi bật Force From Email, mọi plugin khác (kể cả WooCommerce) sẽ phải dùng email này
- Không cần mu-plugin nếu đã bật Force From Email

## Test sau khi cấu hình:
1. WP Mail SMTP → Tools → Email Test
2. Thử đặt hàng test trên website