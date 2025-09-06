# HAR Analysis - Optimization Summary

## Executive Summary

Analyzed 4 HAR files from vidieu.vn website:
- **trang-chu.har** (Home Page): 144 requests
- **trang-san-pham.har** (Product Page): 140 requests  
- **trang-bai-viet.har** (Article Page): 122 requests
- **trang-contact.har** (Contact Page): 116 requests

## Critical Issues Found

### 1. 404 Errors (All Pages)
These resources are missing and causing failed requests on every page:
- `/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2` - Main font file
- `/wp-content/themes/elessi-theme/style.min.css` - Theme styles
- `/wp-content/uploads/elementor/google-fonts/css/jost.css` - Google font

### 2. Google reCAPTCHA Issues
- **Duplicate Loading**: reCAPTCHA is loaded multiple times with different API keys:
  - Key 1: `6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ`
  - Key 2: `6LdAg_UqAAAAANiCMOwwgJ_I3We6DGQ3T5LohPX0` (returns 400 error)
- **Triple Loading**: `recaptcha__vi.js` is loaded 3 times on each page

### 3. Duplicate Requests

#### Home Page (trang-chu)
- **admin-ajax.php**: Called 4 times (potential for optimization)
- **Google reCAPTCHA**: Loaded 3 times
- **Child theme style.css**: Loaded with different version parameters

#### Product Page (trang-san-pham)  
- **admin-ajax.php**: Called 3 times
- **Google reCAPTCHA**: Loaded 3 times
- Similar duplicate patterns as home page

#### Article Page (trang-bai-viet)
- **admin-ajax.php**: Called 2 times
- **Google reCAPTCHA**: Loaded 3 times
- Fewer duplicates overall

#### Contact Page (trang-contact)
- **Google reCAPTCHA**: Loaded 3 times
- Least number of duplicates

### 4. Query String Variations
Multiple files are loaded with different query parameters, causing cache misses:
- **Child theme style.css**: Loaded with `ver=1.0.0` and `ver=6.8.2`
- **Kaspersky scripts**: Multiple versions with different attributes
- **Google reCAPTCHA**: Different render parameters

## Resource Breakdown by Type

### JavaScript (Highest Usage)
- Home: 59 requests
- Product: 59 requests
- Article: 51 requests
- Contact: 51 requests

### CSS
- All pages: ~39-41 requests (very high)

### Fonts
- 8-9 font requests per page

### Images
- Relatively low: 3-12 requests per page

## Optimization Recommendations

### Priority 1 - Fix 404 Errors
1. **Upload missing font file**: `main-font.woff2`
2. **Generate minified CSS**: Create `style.min.css` for elessi-theme
3. **Fix Elementor font path**: Update or regenerate Jost font CSS

### Priority 2 - Fix reCAPTCHA
1. **Use single reCAPTCHA instance**: Remove duplicate implementations
2. **Fix API key error**: Use only the working API key
3. **Implement lazy loading**: Load reCAPTCHA only when needed

### Priority 3 - Reduce Duplicates
1. **Consolidate AJAX calls**: Batch multiple admin-ajax.php requests
2. **Fix version parameters**: Use consistent versioning for CSS files
3. **Implement resource hints**: Use preconnect/dns-prefetch properly

### Priority 4 - General Optimizations
1. **Reduce CSS files**: 39-41 CSS files is excessive
2. **Combine JavaScript**: 51-59 JS files could be bundled
3. **Implement HTTP/2 Push**: For critical resources
4. **Add cache headers**: For static resources

## Estimated Impact

Fixing these issues could potentially:
- Reduce total requests by 20-30%
- Eliminate 4 failed requests per page
- Improve caching efficiency
- Reduce JavaScript execution time (fewer reCAPTCHA instances)
- Improve First Contentful Paint (fix missing fonts/CSS)

## Next Steps

1. Fix all 404 errors immediately
2. Audit and consolidate reCAPTCHA implementation  
3. Implement request batching for admin-ajax.php
4. Review and optimize CSS/JS loading strategy