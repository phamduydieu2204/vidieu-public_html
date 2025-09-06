# GHI CHÚ TRIỂN KHAI V2 - ENHANCED OPTIMIZATION
*Ngày cập nhật: 2025-09-06*

## TÌNH TRẠNG HIỆN TẠI

### Issues từ V1 chưa được fix:
1. ❌ **404 errors** vẫn còn trên 100% pages
2. ❌ **reCAPTCHA** tăng từ 3 lên 14-27 loads (tệ hơn!)
3. ❌ **Cart/Checkout** cực kỳ tệ (251/242 requests)

### Nguyên nhân V1 thất bại:
- Hook timing quá muộn
- Chỉ dequeue không đủ (scripts được re-add)
- Không handle inline scripts
- Thiếu optimization cho Cart/Checkout

## FILES ĐÃ TẠO/CẬP NHẬT CHO V2

### 1. Class V2 mới
**File:** `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-dup-requests-guard-v2.php`

**Improvements:**
- Hook vào `plugins_loaded` (sớm hơn)
- Multiple removal strategies cho reCAPTCHA
- JavaScript-based blocking
- Aggressive optimization cho Cart/Checkout
- Real-time monitoring

### 2. Update plugin loader
**File:** `/wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php`
```php
// Load V2 first, fallback to V1
if (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2.php')) {
    require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard-v2.php';
} elseif (file_exists(VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard.php')) {
    require_once VD_HOME_PLUGIN_DIR . 'inc/perf/class-vidieu-dup-requests-guard.php';
}
```

## CÁC FIX MỚI TRONG V2

### 1. 404 Fixes - Multiple approaches

#### A. Resource path filtering (enhanced)
- Hook cực sớm via `wp_loaded`
- Check multiple possible paths
- Return false để prevent load nếu không tìm thấy

#### B. CSS injection for fonts
```php
add_filter('theme_mod_custom_css', function($css) {
    $css .= '@font-face { 
        font-family: "main-font"; 
        src: url("' . get_stylesheet_directory_uri() . '/assets/fonts/main-font.woff"); 
    }';
    return $css;
});
```

#### C. Recommended: MU-Plugin approach
Tạo `/wp-content/mu-plugins/fix-404-resources.php` để redirect

### 2. reCAPTCHA Nuclear Options

#### A. Multi-phase removal
1. Early phase: `wp_enqueue_scripts` priority 5
2. Late phase: `wp_print_scripts` priority 999
3. Tag filtering: `script_loader_tag`
4. Inline blocking: Output buffering

#### B. JavaScript prevention
```javascript
// Override appendChild to block dynamic loads
Element.prototype.appendChild = function(child) {
    if (child.src && child.src.includes('recaptcha') && loaded) {
        return child; // Block it
    }
    // ... original logic
};
```

### 3. Cart/Checkout Optimization

#### A. Whitelist approach
- Only allow essential scripts/styles
- Block everything else by default

#### B. Duplicate removal
- Track loaded resources by filename
- Prevent same library loading multiple times

#### C. Image deduplication
- Return SVG placeholder for duplicate product images
- Lazy load duplicates

## TESTING CHECKLIST

### Critical Functions
- [ ] Contact form với reCAPTCHA
- [ ] Add to cart functionality
- [ ] Cart page calculations
- [ ] Checkout flow complete
- [ ] Payment gateway processing
- [ ] Admin area không affected

### Performance Metrics
- [ ] 404 errors = 0
- [ ] reCAPTCHA loads = 1
- [ ] Cart requests < 150
- [ ] Checkout requests < 180

## DEBUG MODE

Dành cho admin users, xem trong HTML footer:
```html
<!-- Vidieu Dup Requests Guard V2 Log:
Total fixes: XX
Removed scripts: XX
Removed styles: XX
... detailed log ...
-->
```

## ROLLBACK PROCEDURES

### 1. Quick disable (không cần xóa code)
```php
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
```

### 2. Revert to V1
- Rename hoặc delete `class-vidieu-dup-requests-guard-v2.php`
- V1 sẽ tự động được load

### 3. Complete removal
- Comment out require_once trong `vidieu-home-sections.php`

## KNOWN ISSUES & NEXT STEPS

### Still needs work:
1. Payment gateway scripts consolidation
2. Third-party plugin conflicts
3. CDN/Cache compatibility

### Recommended next phase:
1. Implement mu-plugin for 404s
2. Identify all reCAPTCHA sources
3. Create plugin-specific optimizations
4. Consider separate performance plugin

## MONITORING

Post-deployment, monitor:
1. Browser console errors
2. Network tab duplicates
3. User reports về functionality
4. Server error logs