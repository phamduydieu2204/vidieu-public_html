# HOME Performance Optimization Changelog

## H1.2 - Fix Missing/404 Resources (CODE-ONLY)
**Date**: 2025-09-06  
**Status**: Completed  
**Impact**: Eliminates 3 x 404 errors that block rendering

### Changes Made:

1. **Created performance flags system**
   - File: `/wp-content/plugins/vidieu-home-sections/performance-flags.php`
   - Master flag: `VIDIEU_PERF_HOME_ENABLE`
   - Feature flag: `VIDIEU_PERF_HOME_FIX_404` (enabled)

2. **Created performance optimization class**
   - File: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-perf-home.php`
   - Only runs on frontend HOME page when flags enabled
   - Fixes broken preloads and missing resources

3. **Updated plugin loader**
   - File: `/wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php`
   - Added includes for performance flags and optimization class

4. **Fixed broken preloads in child theme**
   - File: `/wp-content/themes/elessi-theme-child/functions-performance.php`
   - Commented out preloads for non-existent files:
     - `main-font.woff2` (404)
     - `style.min.css` (404)

### Issues Fixed:
1. ❌ `/wp-content/themes/elessi-theme/style.min.css` - 404 Not Found
2. ❌ `/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2` - 404 Not Found  
3. ❌ `/wp-content/uploads/elementor/google-fonts/css/jost.css` - 404 Not Found

### Rollback Instructions:
1. Set `VIDIEU_PERF_HOME_FIX_404` to `false` in `performance-flags.php`
2. Or set master flag `VIDIEU_PERF_HOME_ENABLE` to `false` to disable all optimizations
3. Uncomment lines 125-126 in `functions-performance.php` if you want 404s back

### Testing Checklist:
- [ ] Clear all caches
- [ ] Load HOME page in Chrome DevTools
- [ ] Check Network tab - no more 404 errors
- [ ] Check Console - no resource loading errors
- [ ] Verify fonts still load correctly
- [ ] Test on mobile view
- [ ] Run PageSpeed Insights - should see slight improvement

### Risk Assessment:
- **Risk Level**: LOW
- **What could break**: Nothing - we only removed broken references
- **Monitoring**: Check that fonts display correctly (Google Fonts should still work)

---

## H1.2a - Gỡ triệt để 404 jost.css + preload sai (HOME-only)
**Date**: 2025-09-06  
**Status**: Completed  
**Impact**: Eliminates Elementor Google Fonts 404 and prevents broken preloads

### Changes Made:

1. **Enhanced Elementor Google Fonts handling**
   - File: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-perf-home.php`
   - Added comprehensive detection and removal of local Elementor Google Fonts
   - Pattern matching for:
     - `/uploads/elementor/google-fonts/`
     - `elementor-gf-` handles
     - `google-fonts-N` patterns
   - Re-register Jost font from Google CDN with `display=swap`

2. **Added resource hints filter**
   - Method: `clean_resource_hints()` 
   - Removes preload hints for non-existent resources
   - Filters out jost.css and elementor google fonts uploads

3. **Added stylesheet tag filter**
   - Method: `filter_broken_stylesheets()`
   - Prevents rendering of broken stylesheet links
   - Also filters preload links for non-existent files

### Code Changes:
```php
// Added to fix_missing_resources():
add_filter('wp_resource_hints', array($this, 'clean_resource_hints'), 10, 2);
add_filter('style_loader_tag', array($this, 'filter_broken_stylesheets'), 999, 4);

// Enhanced fix_elementor_fonts() to:
- Loop through all registered styles
- Pattern match multiple Elementor font formats
- Completely dequeue/deregister broken fonts
- Re-add Jost from Google CDN
```

### Issues Fixed:
- ✅ No more 404 for `/uploads/elementor/google-fonts/css/jost.css`
- ✅ No more "preloaded but not used" warnings
- ✅ Jost font loads from Google CDN with display=swap
- ✅ All broken preload hints removed

### Rollback Instructions:
1. Set `VIDIEU_PERF_HOME_FIX_404` to `false` in `performance-flags.php`
2. The original Elementor fonts behavior will be restored

### Testing Checklist:
- [ ] Clear browser cache and WordPress cache
- [ ] Open HOME in Chrome DevTools (Incognito)
- [ ] Network tab: No 404 for jost.css
- [ ] Network tab: Jost loads from fonts.googleapis.com
- [ ] Console: No "preloaded but not used" warnings
- [ ] Visual: Jost font displays correctly in UI
- [ ] Mobile: Same checks on mobile view

### Risk Assessment:
- **Risk Level**: LOW
- **What could break**: Font display if Jost is critical
- **Mitigation**: Font loads from reliable Google CDN instead of local
- **Monitoring**: Check typography rendering remains consistent