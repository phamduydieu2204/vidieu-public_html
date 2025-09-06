# KHẨN CẤP: Khắc phục lỗi 500 Internal Server Error

## Nguyên nhân
File `class-vidieu-dup-requests-guard-v2-aggressive.php` có lỗi syntax với class constants.

## Giải pháp nhanh

### Option 1: Sử dụng Safe Version (ĐÃ CẬP NHẬT)
Plugin đã được cập nhật để sử dụng phiên bản safe thay vì aggressive.

### Option 2: Disable hoàn toàn (nếu vẫn lỗi)
Thêm vào `wp-config.php`:
```php
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
```

### Option 3: Rename file tạm thời
```bash
cd /wp-content/plugins/vidieu-home-sections/inc/perf/
mv class-vidieu-dup-requests-guard-v2-aggressive.php class-vidieu-dup-requests-guard-v2-aggressive.php.disabled
```

## Chi tiết lỗi đã sửa

### Lỗi 1: Line 72 & 797
```php
// SAI:
if (defined(self::DISABLE_OPTIMIZATION) && constant(self::DISABLE_OPTIMIZATION))

// ĐÚNG:
if (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION)
```

### Lỗi 2: Empty methods
Đã thêm implementation cơ bản cho các methods trống.

## Safe Version Features

Phiên bản safe chỉ thực hiện:
- Remove emoji scripts
- Remove dashicons cho non-logged users
- Remove wp-embed
- Basic cleanup cho Cart/Checkout (elementor, revslider, instagram-feed, yith)

## Testing

1. Clear cache
2. Kiểm tra trang web load bình thường
3. View source, search "Vidieu V2 Safe Report"
4. Kiểm tra Cart/Checkout còn hoạt động

## Rollback nếu cần

```bash
# Restore original
cd /wp-content/plugins/vidieu-home-sections/
git checkout vidieu-home-sections.php
```