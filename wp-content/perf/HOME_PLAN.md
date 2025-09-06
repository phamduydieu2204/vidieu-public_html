# Kế Hoạch Tối Ưu Hiệu Năng Trang HOME - Vidieu.vn

**Phiên bản**: 1.0  
**Ngày tạo**: 2025-09-06  
**Phạm vi**: Chỉ trang HOME (/)  
**Phương pháp**: Từng bước nhỏ với flag bật/tắt và rollback plan

## Tổng Quan Chiến Lược

### Mục tiêu:
1. **Mobile PageSpeed**: 53 → 75+ 
2. **Desktop PageSpeed**: 77 → 90+
3. **LCP Mobile**: 10.2s → < 3s
4. **TTFB**: 1.2-2.5s → < 600ms

### Nguyên tắc:
- ✅ Mobile-first approach
- ✅ Không phá vỡ chức năng hiện tại
- ✅ Mỗi thay đổi có flag bật/tắt
- ✅ Rollback dễ dàng
- ✅ Test sau mỗi bước

## Danh Sách Các Bước Tối Ưu

### 🔴 H1: Fix Critical Issues (P0)
**Mục tiêu**: Sửa lỗi rõ ràng gây chặn render hoặc lỗi 404  
**Tác động dự kiến**: +5-10 điểm PageSpeed, -500ms TTFB  
**Rủi ro**: Thấp

#### H1.1: Enable Text Compression
- Bật gzip/brotli compression
- Tiết kiệm ~860KB bandwidth
- Thực hiện qua .htaccess hoặc nginx config

#### H1.2: Fix Missing Resources & 404s
- Kiểm tra và sửa broken links
- Remove references to missing files
- Clean up console errors

#### H1.3: Add Basic Security Headers
- X-Content-Type-Options
- X-Frame-Options
- Cải thiện security score

---

### 🔴 H2: JavaScript & CSS Optimization (P0)
**Mục tiêu**: Giảm render-blocking resources  
**Tác động dự kiến**: +15-20 điểm PageSpeed, -2s LCP mobile  
**Rủi ro**: Trung bình - cần test kỹ

#### H2.1: Defer Non-Critical JavaScript
- Defer jQuery (nếu an toàn)
- Async third-party scripts
- Prioritize critical JS only

#### H2.2: Extract & Inline Critical CSS
- Identify above-fold CSS (~15-20KB)
- Inline trong <head>
- Lazy load remaining CSS

#### H2.3: Remove/Defer Kaspersky Labs
- 100% unused CSS (44KB)
- Block render 956ms
- Verify security impact first

#### H2.4: Optimize Font Loading
- Preload 2-3 critical fonts
- font-display: swap
- Subset fonts if possible

---

### 🟡 H3: Image Optimization (P1)
**Mục tiêu**: Giảm kích thước và cải thiện loading  
**Tác động dự kiến**: +5-10 điểm PageSpeed, -1s load time  
**Rủi ro**: Thấp

#### H3.1: Implement Lazy Loading
- Native lazy loading cho below-fold images
- Placeholder/LQIP cho UX tốt hơn
- Skip hero/LCP images

#### H3.2: Responsive Images
- Thêm srcset cho breakpoints
- Serve đúng size cho mobile/desktop
- Picture element cho art direction

#### H3.3: Modern Image Formats
- Convert to WebP (fallback PNG/JPG)
- AVIF cho browsers hỗ trợ
- Optimize compression levels

---

### 🟡 H4: Bundle Size Reduction (P1)
**Mục tiêu**: Giảm CSS/JS không sử dụng  
**Tác động dự kiến**: +5-8 điểm PageSpeed  
**Rủi ro**: Trung bình

#### H4.1: Remove Unused CSS
- PurgeCSS cho theme styles
- Split CSS by page type
- Critical CSS per template

#### H4.2: Tree-shake JavaScript
- Remove dead code
- Split chunks properly
- Dynamic imports where possible

#### H4.3: Minification Enhancement
- Better minification settings
- Remove comments/whitespace
- Optimize delivery

---

### 🟡 H5: Database & Backend (P1)
**Mục tiêu**: Cải thiện TTFB và server response  
**Tác động dự kiến**: -500ms TTFB  
**Rủi ro**: Trung bình - cần backup

#### H5.1: Optimize Autoload Data
- Review 208KB autoload
- Remove ez-toc-post-content (68KB)
- Disable unnecessary options

#### H5.2: Database Cleanup
- Truncate bz_wpsmtp_logs (48k rows)
- Fix bz_users table (39MB/0 rows)
- Add missing indexes

#### H5.3: Implement Object Cache
- Redis/Memcached
- Transient API optimization
- Query result caching

---

### 🟢 H6: Caching Strategy (P2)
**Mục tiêu**: Full-page cache và CDN  
**Tác động dự kiến**: -800ms TTFB, +10 điểm  
**Rủi ro**: Cao - cần test với Woo

#### H6.1: Page Cache Implementation
- Exclude cart/checkout/account
- Smart cache invalidation
- Mobile/desktop variants

#### H6.2: Browser Caching Headers
- Proper Cache-Control
- Immutable for versioned assets
- Short TTL for HTML

#### H6.3: CDN Integration
- Cloudflare or similar
- Geographic distribution
- Smart routing

---

## Implementation Flags

Tất cả features sẽ được control qua constants trong `vidieu-home-sections` plugin:

```php
// wp-content/plugins/vidieu-home-sections/performance-flags.php
define('VIDIEU_PERF_HOME_ENABLE', true);
define('VIDIEU_PERF_HOME_COMPRESS', true);
define('VIDIEU_PERF_HOME_DEFER_JS', true);
define('VIDIEU_PERF_HOME_CRITICAL_CSS', true);
define('VIDIEU_PERF_HOME_LAZY_IMAGES', true);
define('VIDIEU_PERF_HOME_WEBP', true);
define('VIDIEU_PERF_HOME_CACHE', true);
```

## Rollback Procedures

Mỗi bước sẽ có:
1. **Backup files** trước khi sửa
2. **Feature flag** để tắt nhanh
3. **Revert instructions** chi tiết
4. **Test checklist** để verify

## Testing Protocol

Sau mỗi bước:
1. Clear all caches
2. Test trên Chrome DevTools (Mobile + Desktop)
3. Run PageSpeed Insights
4. Check console errors
5. Verify functionality:
   - Add to cart
   - Navigation
   - Search
   - Forms

## Ước Tính Timeline

- **H1 (Critical)**: 1-2 giờ
- **H2 (JS/CSS)**: 3-4 giờ + testing
- **H3 (Images)**: 2-3 giờ
- **H4 (Bundles)**: 2-3 giờ
- **H5 (Database)**: 1-2 giờ
- **H6 (Caching)**: 2-4 giờ + monitoring

**Tổng cộng**: ~15-20 giờ implementation + testing

## Metrics Tracking

File log để track progress:
`/wp-content/perf/HOME_METRICS.csv`

```csv
Date,Step,Mobile_Score,Desktop_Score,LCP,TTFB,Notes
2025-09-06,Baseline,53,77,10.2s,1214ms,Initial state
```

---

**LƯU Ý**: Đây là kế hoạch chi tiết. Việc thực hiện sẽ tiến hành TỪNG BƯỚC với sự xác nhận sau mỗi bước.