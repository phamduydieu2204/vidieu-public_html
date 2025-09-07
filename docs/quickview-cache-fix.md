# QuickView Cache Fix Guide

## Vấn đề hiện tại
- Browser vẫn tải assets từ đường dẫn cũ: `/compat/assets/`
- Đường dẫn đúng phải là: `/assets/`
- Nguyên nhân: Cache ở nhiều cấp độ

## Cách khắc phục

### 1. Xóa cache browser
- Nhấn **Ctrl + F5** (Windows) hoặc **Cmd + Shift + R** (Mac)
- Hoặc mở Developer Tools (F12) → Network tab → ✓ Disable cache
- Test trong chế độ Incognito/Private browsing

### 2. Xóa cache WordPress
```bash
# SSH vào server
cd /path/to/vidieu.vn/public_html

# Xóa object cache
wp cache flush

# Xóa transients
wp transient delete --all

# Clear Autoptimize cache (nếu có)
wp autoptimize clear
```

### 3. Xóa cache plugin
- WP Rocket: Dashboard → WP Rocket → Clear Cache
- W3 Total Cache: Performance → Dashboard → Empty All Caches
- LiteSpeed Cache: LiteSpeed Cache → Toolbox → Purge All

### 4. Xóa cache CDN/CloudFlare
- CloudFlare: Caching → Configuration → Purge Everything
- Hoặc purge specific URLs:
  - `/wp-content/plugins/vidieu-home-sections/compat/assets/css/quickview-compat.css`
  - `/wp-content/plugins/vidieu-home-sections/compat/assets/js/quickview-compat.js`

### 5. Kích hoạt debug mode (tạm thời)
Thêm vào `wp-config.php`:
```php
define('VIDIEU_QV_DEBUG', true);
```
Điều này sẽ:
- Thêm timestamp vào URL để bypass cache
- Hiển thị debug info trong admin panel

### 6. Kiểm tra deployment
```bash
# Kiểm tra phiên bản plugin
grep "Version:" wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php

# Phải là Version: 1.6.1 hoặc mới hơn

# Kiểm tra file paths
grep "plugin_dir_url" wp-content/plugins/vidieu-home-sections/compat/compat-quickview.php

# Phải thấy dòng:
# $plugin_url = plugin_dir_url(dirname(__FILE__));
```

### 7. Giải pháp khẩn cấp
Plugin đã có sẵn inline fix tự động. Nếu vẫn không hoạt động:

1. Kiểm tra HTML source, tìm `vidieu-qv-inline-css` và `vidieu-qv-inline-js`
2. Nếu không thấy, có thể plugin chưa được cập nhật trên production

### 8. Test sau khi fix
1. Mở trang chủ vidieu.vn
2. Click icon QuickView (hình con mắt)
3. Chọn thuộc tính sản phẩm
4. Trang KHÔNG được scroll lên top

## Lưu ý
- Inline fix sẽ tự động hoạt động ngay cả khi assets 404
- Sau khi cache được xóa, assets sẽ load từ đúng đường dẫn
- Tắt debug mode sau khi test xong