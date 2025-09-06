# BÁO CÁO PHÂN TÍCH REQUEST TRÙNG LẶP - VIDIEU.VN
*Ngày phân tích: 2025-09-06*

## 1. TỔNG QUAN

### 1.1. Thống kê chung

| Route | Tổng requests | Request trùng | Lỗi 4xx/5xx | CSS files | JS files |
|-------|---------------|---------------|-------------|-----------|----------|
| Trang chủ | 144 | 2 nhóm | 4 | 41 | 59 |
| Sản phẩm | 140 | 2 nhóm | 4 | 41 | 59 |
| Bài viết | 122 | 2 nhóm | 4 | 39 | 51 |
| Contact | 116 | 1 nhóm | 4 | 39 | 51 |

### 1.2. Vấn đề nghiêm trọng cần xử lý ngay

1. **404 Errors (Ảnh hưởng toàn site)**
   - `/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2` - Font chính bị thiếu
   - `/wp-content/themes/elessi-theme/style.min.css` - CSS theme bị thiếu
   - `/wp-content/uploads/elementor/google-fonts/css/jost.css` - Font Elementor bị thiếu

2. **Google reCAPTCHA trùng lặp**
   - Load 3 lần mỗi trang với 2 API key khác nhau
   - 1 API key trả về lỗi 400 (invalid key)

## 2. PHÂN TÍCH CHI TIẾT THEO LOẠI

### 2.1. Request trùng lặp nghiêm trọng

#### A. Google reCAPTCHA (3x load)
```
URL: https://www.gstatic.com/recaptcha/releases/Lu6n5xwy2ghvnPNo3IxkhcCb/recaptcha__vi.js
Xuất hiện: 3 lần/trang
Sources:
1. Plugin WP Captcha: api.js?onload=wpcaptcha_captcha&render=6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ
2. Plugin khác: api.js?render=6LdAg_UqAAAAANiCMOwwgJ_I3We6DGQ3T5LohPX0 (lỗi 400)  
3. Inline script từ theme/plugin
```

#### B. Admin-ajax.php (2-4x load)
```
URL: https://vidieu.vn/wp-admin/admin-ajax.php
Trang chủ: 4 lần
Sản phẩm: 3 lần  
Bài viết: 2 lần
Contact: 1 lần (tốt)

Actions gọi:
- vidieu_filter_products_json (home)
- yith-woocompare-reload-compare
- get_refreshed_fragments (WooCommerce cart)
```

### 2.2. CSS/JS version conflicts

#### Theme child style.css
```
Load 2 lần với version khác nhau:
1. style.css?ver=1.0.0 (child theme version)
2. style.css?ver=6.8.2 (WordPress version)
```

### 2.3. Fonts issues

1. **main-font.woff2 (404)**
   - Path sai: `/themes/elessi-theme/assets/fonts/`
   - Có thể đúng path: `/themes/elessi-theme-child/assets/fonts/`

2. **Google Fonts Jost (404)**
   - Elementor cache path không tồn tại
   - Cần regenerate hoặc disable

## 3. ROUTE × HANDLE MATRIX

### 3.1. Scripts enqueued matrix

| Handle | Home | Products | Posts | Contact | Source |
|--------|------|----------|-------|---------|--------|
| jquery | ✓ | ✓ | ✓ | ✓ | WordPress Core |
| woocommerce | ✓ | ✓ | ✓ | ✓ | WooCommerce |
| elessi-theme-js | ✓ | ✓ | ✓ | ✓ | Theme |
| wp-captcha | ✓ | ✓ | ✓ | ✓ | WP Captcha Plugin |
| recaptcha-unknown | ✓ | ✓ | ✓ | ✓ | Unknown Plugin (lỗi) |
| yith-woocompare | ✓ | ✓ | ✗ | ✗ | YITH Compare |
| elementor-frontend | ✓ | ✓ | ✓ | ✓ | Elementor |

### 3.2. Styles enqueued matrix  

| Handle | Home | Products | Posts | Contact | Source |
|--------|------|----------|-------|---------|--------|
| elessi-style | ✓ | ✓ | ✓ | ✓ | Parent Theme |
| elessi-child-style | ✓✓ | ✓✓ | ✓✓ | ✓✓ | Child (2x!) |
| woocommerce-general | ✓ | ✓ | ✓ | ✓ | WooCommerce |
| elementor-frontend | ✓ | ✓ | ✓ | ✓ | Elementor |
| elementor-google-fonts | ✓ | ✓ | ✓ | ✓ | Elementor (404) |

## 4. PHÂN LOẠI ƯU TIÊN XỬ LÝ

### Priority 1 - Critical (Xử lý ngay)
1. **404 Errors** - 4 requests/trang fail
   - main-font.woff2
   - style.min.css  
   - jost.css
   - recaptcha API key lỗi

2. **reCAPTCHA trùng 3x** - Tốn 200KB+ mỗi trang

### Priority 2 - High
1. **admin-ajax.php duplicate calls** - 2-4 requests/trang
2. **Child theme CSS load 2x** - Version conflict

### Priority 3 - Medium  
1. **Excessive CSS files** - 39-41 files (nên < 10)
2. **Excessive JS files** - 51-59 files (nên < 20)

## 5. ƯỚC TÍNH TIẾT KIỆM

| Tối ưu | Requests giảm | Size giảm | Impact |
|--------|---------------|-----------|---------|
| Fix 404s | 4 req/trang | ~50KB | Loại bỏ retry |
| reCAPTCHA dedup | 2 req/trang | ~200KB | Giảm TBT |
| admin-ajax optimize | 1-3 req/trang | ~5KB | Giảm server load |
| CSS consolidation | 20+ req | ~100KB | Giảm render blocking |

**Tổng tiết kiệm dự kiến: 25-30 requests, 350KB+ mỗi trang**

## 6. DEPENDENCIES CẦN LƯU Ý

1. **jQuery** - Core dependency, KHÔNG được remove/defer
2. **WooCommerce scripts** - Cần giữ nguyên trên cart/checkout
3. **Elementor** - Có thể defer trên non-Elementor pages
4. **YITH plugins** - Chỉ cần trên product pages

## 7. KẾT LUẬN

Website đang có vấn đề nghiêm trọng về:
1. Multiple 404 errors gây retry requests
2. reCAPTCHA implementation conflict (2 plugins)  
3. Quá nhiều CSS/JS files riêng lẻ
4. Duplicate AJAX calls không cần thiết

Cần triển khai ngay Phase 1 để fix 404 và reCAPTCHA conflicts.