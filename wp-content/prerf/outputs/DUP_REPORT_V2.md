# BÁO CÁO PHÂN TÍCH REQUEST TRÙNG LẶP V2 - VIDIEU.VN
*Ngày phân tích: 2025-09-06*
*Cập nhật với data mới cho cả 6 routes*

## 1. TỔNG QUAN SO SÁNH

### 1.1. Kết quả optimization Phase 1

| Route | Trước | Sau thực tế | Dự kiến | Đạt được | Status |
|-------|-------|-------------|---------|----------|---------|
| Trang chủ | 144 | 131 | 115 | -9.03% | ⚠️ Partial |
| Sản phẩm | 140 | 131 | 112 | -6.43% | ⚠️ Partial |
| Bài viết | 122 | 117 | 97 | -4.10% | ⚠️ Partial |
| Contact | 116 | 109 | 92 | -6.03% | ⚠️ Partial |
| Cart | N/A | 251 | - | - | ❌ Critical |
| Checkout | N/A | 242 | - | - | ❌ Critical |

### 1.2. Issues chưa được fix

#### ❌ CRITICAL ISSUES VẪN TỒN TẠI:

1. **404 Errors (100% pages affected)**
   ```
   - /wp-content/themes/elessi-theme/assets/fonts/main-font.woff2
   - /wp-content/themes/elessi-theme/style.min.css
   ```

2. **reCAPTCHA Overload (Tệ hơn dự kiến)**
   - Expected: 1 load
   - Actual: 14 loads (most pages), 27 loads (checkout)
   - Main culprit: `recaptcha__vi.js` loaded 3-5x per page

3. **Cart/Checkout Pages - Disaster Performance**
   - Cart: 251 requests (2x normal pages)
   - Checkout: 242 requests 
   - JS files: 155-170 (3x normal)

## 2. PHÂN TÍCH CHI TIẾT THEO ROUTE

### 2.1. Home Page (/)
```
Total requests: 131 (-13 from before)
Duplicates: 4 groups
404 errors: 2
reCAPTCHA: 14 loads
Admin AJAX: 4 calls
CSS: 40 | JS: 56
```

**Main duplicates:**
- `recaptcha__vi.js` (3x)
- `admin-ajax.php` (4x)
- Child theme CSS still loading 2x

### 2.2. Product Archive (/san-pham/)
```
Total requests: 131 (-9 from before)
Duplicates: 3 groups
404 errors: 2
reCAPTCHA: 14 loads
Admin AJAX: 2 calls
CSS: 40 | JS: 56
```

### 2.3. Single Post (/bai-viet/)
```
Total requests: 117 (-5 from before)
Duplicates: 2 groups
404 errors: 2
reCAPTCHA: 14 loads
Admin AJAX: 1 call
CSS: 38 | JS: 48
```

### 2.4. Contact Page
```
Total requests: 109 (-7 from before)
Duplicates: 2 groups
404 errors: 2
reCAPTCHA: 14 loads
Admin AJAX: 1 call
CSS: 38 | JS: 46
```

### 2.5. Cart Page (NEW DATA)
```
Total requests: 251 (❌ VERY HIGH)
Duplicates: 49 groups
404 errors: 2
reCAPTCHA: 14 loads
Admin AJAX: 0 (good)
CSS: 43 | JS: 170 (❌)
```

**Critical issues:**
- 170 JS files (normal pages have ~50)
- Image duplicates: `Sortpin--247x296.webp` (4x), `placeholder.webp` (3x)
- WooCommerce scripts loading multiple versions

### 2.6. Checkout Page (NEW DATA)
```
Total requests: 242 (❌ VERY HIGH)
Duplicates: 58 groups (worst)
404 errors: 2
reCAPTCHA: 27 loads (❌❌)
Admin AJAX: 0
CSS: 45 | JS: 155
```

**Critical issues:**
- reCAPTCHA loading 27 times!
- Payment gateway scripts duplicated
- Multiple jQuery versions detected

## 3. ROOT CAUSE ANALYSIS

### 3.1. Why fixes didn't work

1. **404 Fixes Failed:**
   - Hook timing issue - resources already enqueued
   - Path detection logic not catching all variations
   - Need earlier intervention

2. **reCAPTCHA Still Duplicating:**
   - Multiple plugins enqueueing independently
   - Inline scripts bypassing dequeue
   - Dynamic loading via JavaScript

3. **Cart/Checkout Explosion:**
   - No optimization applied to these routes yet
   - WooCommerce loading all possible scripts
   - Payment gateways each loading own dependencies

### 3.2. Technical debt discovered

1. **Multiple reCAPTCHA sources:**
   - WP Captcha plugin
   - Unknown plugin with invalid key
   - Theme inline scripts
   - Contact Form 7 (possibly)

2. **Asset loading chaos:**
   - No dependency management
   - Scripts loaded in wrong order
   - Same libraries loaded by multiple plugins

## 4. IMPACT METRICS

### 4.1. Performance Impact

| Metric | Before | Current | Target | Gap |
|--------|--------|---------|--------|-----|
| Avg requests | 130.5 | 145.3 | 100 | -45.3 |
| 404 errors | 4 | 2 | 0 | -2 |
| reCAPTCHA | 3 | 14-27 | 1 | -13 to -26 |
| Wasted MB | ~1.2 | ~2.5 | 0.2 | -2.3 MB |

### 4.2. User Experience Impact

- **Page Load:** +1-2s due to duplicate resources
- **Interactivity:** High TBT from multiple reCAPTCHA
- **Cart/Checkout:** Likely timeout issues on slow connections

## 5. RECOMMENDATIONS

### 5.1. Immediate Actions Required

1. **Fix 404s properly:**
   - Use must-use plugin for earlier hook
   - Or fix at .htaccess level
   - Or physically create missing files

2. **Nuclear option for reCAPTCHA:**
   - Identify ALL sources
   - Disable all but one
   - Block via JavaScript if needed

3. **Cart/Checkout emergency:**
   - Implement aggressive dequeue
   - Lazy load non-critical scripts
   - Consider separate optimization plugin

### 5.2. Code fixes needed

1. **Earlier hook timing**
2. **More aggressive script removal**
3. **JavaScript-based blocking**
4. **Route-specific allow/block lists**

## 6. CONCLUSION

Current optimization achieved only 4-9% reduction instead of targeted 20-25%. Main blockers:
- 404 errors persist
- reCAPTCHA worse than before (14x instead of 1x)
- Cart/Checkout pages need urgent attention

**Recommendation:** Deploy V2 optimization class immediately with enhanced fixes.