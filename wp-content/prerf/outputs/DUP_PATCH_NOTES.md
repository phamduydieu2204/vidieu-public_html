# GHI CHÚ TRIỂN KHAI TỐI ƯU REQUEST TRÙNG LẶP
*Ngày triển khai: 2025-09-06*

## FILES ĐÃ TẠO/THAY ĐỔI

### 1. Tạo mới class xử lý
**File:** `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-dup-requests-guard.php`
- Main class xử lý tất cả optimizations
- Có kill switch: `define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);`
- Auto-initialize qua init hook

### 2. Cập nhật plugin loader
**File:** `/wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php`
- Thêm require_once cho class mới (line 87-89)
- Conditional loading với file_exists check

## CÁC VẤN ĐỀ ĐÃ XỬ LÝ

### Phase 1 - Critical Fixes

#### 1.1 Font 404 Fixes
- **Hook:** `style_loader_src` filter (priority 999)
- **Function:** `fix_font_paths()`
- **Xử lý:**
  - Redirect `main-font.woff2` từ parent theme sang child theme
  - Fallback to `.woff` nếu `.woff2` không tồn tại

#### 1.2 Style.min.css 404 Fix
- **Hook:** `wp_enqueue_scripts` (priority 999)
- **Function:** `fix_missing_styles()`
- **Xử lý:**
  - Check file existence cho minified CSS
  - Fallback to non-minified nếu không tìm thấy

#### 1.3 Elementor Google Fonts 404
- **Hook:** `elementor/files/file_generator/create_file` filter
- **Hook:** `wp_print_styles` (priority 999)
- **Function:** `fix_elementor_fonts()`
- **Xử lý:**
  - Prevent Elementor tạo Google Fonts cache files
  - Remove 404 font files từ queue

#### 1.4 reCAPTCHA Consolidation
- **Hook:** `wp_print_scripts` (priority 5)
- **Hook:** `script_loader_tag` filter
- **Functions:** `consolidate_recaptcha()`, `remove_duplicate_recaptcha_inline()`
- **Xử lý:**
  - Giữ lại chỉ WP Captcha instance (key: 6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ)
  - Remove invalid key script (400 error)
  - Prevent duplicate gstatic loads

### Phase 2 - High Priority

#### 2.1 AJAX Deduplication
- **Hook:** `wp_footer` (priority 1)
- **Function:** `optimize_ajax_calls()`
- **Xử lý:**
  - JavaScript-based deduplication
  - Chỉ áp dụng cho admin-ajax.php
  - Queue management với state checking

#### 2.2 Child Theme CSS Duplication
- **Hook:** `wp_enqueue_scripts` (priority 999)
- **Function:** `fix_child_style_duplication()`
- **Xử lý:**
  - Detect multiple enqueues của same file
  - Prefer version 1.0.0 (child theme version)
  - Remove duplicates

### Phase 3 - Route Optimization

#### 3.1 Contact Page
- Remove unnecessary WooCommerce scripts:
  - wc-add-to-cart
  - wc-cart-fragments
  - wc-single-product
  - yith-woocompare-main

#### 3.2 Non-Product Pages
- Remove YITH Compare scripts/styles
- Điều kiện: Not shop/product/cart/checkout

#### 3.3 Blog Posts
- Remove wc-add-to-cart-variation nếu không có products shortcode

## LOGGING & DEBUGGING

### View Optimization Log
- Chỉ visible cho admin users
- Xem trong HTML comments ở footer
- Format: `<!-- Vidieu Duplicate Requests Guard Log: -->`

### Emergency Disable
```php
// Trong wp-config.php hoặc theme functions.php
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
```

## TESTING CHECKLIST

- [ ] Trang chủ: Check reCAPTCHA chỉ load 1 lần
- [ ] All pages: Verify không còn 404 errors
- [ ] Contact form: Test submission vẫn hoạt động
- [ ] Product pages: YITH Compare vẫn work
- [ ] Cart/Checkout: Đầy đủ WooCommerce functionality
- [ ] Admin: Không bị ảnh hưởng

## ROLLBACK PROCEDURE

1. **Quick disable:** Add kill switch to wp-config.php
2. **Full removal:** 
   - Comment out require_once trong vidieu-home-sections.php
   - Hoặc rename/delete class file

## METRICS TO MONITOR

1. Network tab: Giảm requests từ 140+ xuống ~110-115
2. Không còn 404 errors
3. reCAPTCHA chỉ xuất hiện 1 lần
4. Admin-ajax calls giảm 50%+

## KNOWN ISSUES & FUTURE IMPROVEMENTS

1. Cart/Checkout pages chưa có data để test (thiếu HAR files)
2. CSS/JS consolidation chưa implement (Phase 4)
3. Cần monitor thêm edge cases với các plugins khác