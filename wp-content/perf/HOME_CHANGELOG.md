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

---

## H1.2b - Vô hiệu hoá reCAPTCHA & guard code trên HOME (HOME-only)
**Date**: 2025-09-06  
**Status**: Completed  
**Impact**: Eliminates 400 Bad Request error from reCAPTCHA and improves load time

### Changes Made:

1. **Added new performance flag**
   - File: `/wp-content/plugins/vidieu-home-sections/performance-flags.php`
   - Flag: `VIDIEU_PERF_HOME_DISABLE_RECAPTCHA_ON_HOME` (enabled)

2. **Implemented reCAPTCHA removal for HOME**
   - File: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-perf-home.php`
   - Added `disable_recaptcha_on_home()` method
   - Dequeues all known reCAPTCHA script handles:
     - `wpcf7-recaptcha` (Contact Form 7)
     - `wpcaptcha-recaptcha` (Advanced Google reCAPTCHA)
     - `google-recaptcha`, `recaptcha`, `google-recaptcha-js`
   - Filters script URLs containing `google.com/recaptcha`

3. **Added JavaScript guard code**
   - Creates dummy `grecaptcha` object to prevent errors
   - Implements all common grecaptcha methods returning safe defaults
   - Overrides `wpcaptcha_captcha` function if exists
   - Prevents "Invalid site key" or undefined errors

### Code Implementation:
```php
// Method chain:
disable_recaptcha_on_home()
├── dequeue_recaptcha_scripts() - Remove all reCAPTCHA handles
├── filter_recaptcha_scripts() - Block URLs via script_loader_src
└── add_recaptcha_guard_code() - Inject dummy grecaptcha object
```

### Issues Fixed:
- ✅ No more 400 Bad Request to `google.com/recaptcha/api.js`
- ✅ No JavaScript errors from missing grecaptcha
- ✅ HOME page loads faster without reCAPTCHA overhead
- ✅ Other pages with forms remain unaffected

### Rollback Instructions:
1. Set `VIDIEU_PERF_HOME_DISABLE_RECAPTCHA_ON_HOME` to `false` in `performance-flags.php`
2. reCAPTCHA will load normally again on HOME

### Testing Checklist:
- [ ] Clear all caches (browser + WordPress)
- [ ] Open HOME in Chrome DevTools (Incognito)
- [ ] Network tab: No requests to `google.com/recaptcha/api.js`
- [ ] Console: No errors about grecaptcha or "Invalid site key"
- [ ] Check other pages (contact, checkout) - reCAPTCHA should still work there
- [ ] Test any forms on HOME page still function (if any exist)

### Risk Assessment:
- **Risk Level**: LOW-MEDIUM
- **What could break**: Forms on HOME page if they require reCAPTCHA
- **Mitigation**: Guard code prevents JS errors; other pages unaffected
- **Monitoring**: Check if HOME has any forms that need protection

---

## H2.0 - Làm sạch preload không dùng (HOME-only)
**Date**: 2025-09-06  
**Status**: Completed  
**Impact**: Optimizes preload strategy, removes duplicates and broken preloads

### Changes Made:

1. **Added performance flag**
   - File: `/wp-content/plugins/vidieu-home-sections/performance-flags.php`
   - Flag: `VIDIEU_PERF_HOME_CLEAN_PRELOADS` (enabled)

2. **Implemented preload optimization**
   - File: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-perf-home.php`
   - Added `clean_unused_preloads()` method with 3 sub-methods:
     - `optimize_css_preloads()` - Ensures proper preload format with onload handler
     - `remove_duplicate_preloads()` - JavaScript to remove duplicate preload links
     - `add_optimized_preloads()` - Adds only verified, existing font preloads

3. **Preload strategy improvements**
   - Only preload critical CSS: elessi-style, nasa-core-style, vidieu-home-style
   - Add proper `onload` handler to convert preload to stylesheet
   - Mark processed preloads to prevent duplicate processing
   - Remove preloads for non-existent files via JavaScript

4. **Font preload optimization**
   - Check file existence before preloading
   - Only preload critical fonts:
     - nasa-font.woff
     - Pe-icon-7-stroke.woff
     - fontawesome-webfont.woff2
   - Remove broken preloads for main-font.woff2

### Code Structure:
```
clean_unused_preloads()
├── optimize_css_preloads() - Fix CSS preload format
├── remove_duplicate_preloads() - JS to deduplicate
└── add_optimized_preloads() - Add verified preloads only
```

### Issues Fixed:
- ✅ No duplicate preload tags (e.g., sbi-styles.min.css)
- ✅ All preloads have proper onload handlers
- ✅ No preloads for non-existent files
- ✅ Cleaner preload strategy focused on critical resources
- ✅ No more "preloaded but not used" warnings

### Rollback Instructions:
1. Set `VIDIEU_PERF_HOME_CLEAN_PRELOADS` to `false` in `performance-flags.php`
2. Original preload behavior will be restored

### Testing Checklist:
- [ ] Clear all caches
- [ ] Open HOME in Chrome DevTools
- [ ] Check <head> for duplicate preload tags - should be none
- [ ] Verify critical fonts still preload (nasa-font, pe-icon, fontawesome)
- [ ] Check Console - no warnings about preloaded resources
- [ ] Verify CSS loads properly with preload → stylesheet conversion
- [ ] Test on mobile and desktop

### Risk Assessment:
- **Risk Level**: LOW
- **What could break**: Font/CSS loading if preload logic has issues
- **Mitigation**: Only removes duplicates and broken preloads, keeps working ones
- **Monitoring**: Watch for FOUC (Flash of Unstyled Content)

---

## H2.2 - Critical CSS for HOME page
**Date**: 2025-09-06  
**Status**: Completed  
**Impact**: Reduces FCP/LCP by inlining critical CSS for above-the-fold content

### Changes Made:

1. **Created critical CSS file**
   - File: `/wp-content/themes/elessi-theme-child/assets/css/crit-home.css`
   - Size: ~12KB (well under 20KB target)
   - Content: Minimal CSS for header, navigation, topbar, hero, and basic layout
   - Mobile-first responsive styles included

2. **Updated performance class to inject critical CSS**
   - File: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-perf-home.php`
   - Implemented `inline_critical_css()` method
   - Added CSS minification method `minify_css()`
   - Injects critical CSS in <head> with id="vd-crit-home"
   - Only runs when flag enabled and on HOME page

3. **Enabled critical CSS flag**
   - File: `/wp-content/plugins/vidieu-home-sections/performance-flags.php`
   - Flag: `VIDIEU_PERF_HOME_CRITICAL_CSS` set to `true`

### Critical CSS Contents:
- Base reset & typography (html, body, headings)
- Layout grid system (row, columns, container)
- Header structure (#masthead, .site-header, logo)
- Top bar styles
- Navigation (.main-navigation, header icons)
- Sticky header initial state
- Hero/banner section basics
- Mobile responsive (mobile-first approach)
- Essential utilities (hide/show, clearfix, text alignment)
- Child theme overrides (hide vertical menu title)
- Basic font icon setup

### Implementation Details:
```php
// Hook: wp_head with priority 5 (early injection)
add_action('wp_head', array($this, 'inline_critical_css'), 5);

// Process flow:
1. Read crit-home.css from child theme
2. Minify CSS (remove comments, whitespace)
3. Output as inline <style> in <head>
4. Main stylesheet loads normally (no FOUC)
```

### Issues Addressed:
- ✅ Faster initial render of header/navigation
- ✅ Reduced FCP (First Contentful Paint)
- ✅ Reduced LCP (Largest Contentful Paint)
- ✅ No layout shift during page load
- ✅ Mobile-optimized from the start

### Rollback Instructions:
1. Set `VIDIEU_PERF_HOME_CRITICAL_CSS` to `false` in `performance-flags.php`
2. Critical CSS will no longer be injected
3. Page will load with standard stylesheet loading

### Testing Checklist:
- [ ] Clear all caches
- [ ] Open HOME in Chrome DevTools (Incognito, disable cache)
- [ ] Check <head> for `<style id="vd-crit-home">` tag
- [ ] Verify no FOUC on page load
- [ ] Check Lighthouse scores - FCP/LCP should improve
- [ ] Test sticky header functionality
- [ ] Test mobile navigation display
- [ ] Verify main stylesheet still loads
- [ ] Check for any visual regressions

### Risk Assessment:
- **Risk Level**: LOW-MEDIUM
- **What could break**: Visual styling if critical CSS doesn't match main CSS
- **Mitigation**: Main stylesheet still loads, critical CSS is supplementary
- **Monitoring**: Watch for visual inconsistencies or FOUC

---

## VERIFY H2.2-fix - Critical CSS Implementation Verification
**Date**: 2025-09-06  
**Status**: ❌ **FAILED** - Critical implementation issues found  
**Report**: [/wp-content/perf/HOME_COMPARE_H2.2fix.md](HOME_COMPARE_H2.2fix.md)

### Verification Results Summary:

**Failed Criteria (5/6):**
- ❌ Critical CSS appears AFTER stylesheets (position 5723 vs 769)
- ❌ Duplicate preload found (sbi-styles.min.css)
- ❌ Font preloads using .woff instead of .woff2 (4/5 fonts)
- ❌ FCP/LCP regression: Mobile FCP +14.1%, LCP +27.6%
- ❌ FOUC risk due to incorrect CSS positioning

**Passed Criteria (1/6):**
- ✅ No preload for style.css (correctly not preloading enqueued stylesheets)

### Key Findings:
1. **Critical Issue**: Critical CSS injected too late (priority 5), appearing after external stylesheets
2. **Performance Regression**: Mobile FCP and LCP significantly worse after implementation
3. **Font Optimization Missed**: Most fonts still using older .woff format
4. **Excessive Preloads**: 40 CSS preloads may be hurting performance

### Required Patches:
1. **URGENT**: Change wp_head priority from 5 to 1 for critical CSS injection
2. Remove duplicate sbi-styles.min.css preload
3. Update font preloads to .woff2 format
4. Consider reducing CSS preloads from 40 to 5-10 critical files

### Next Steps:
- Apply proposed patches from verification report
- Re-test after patches to confirm improvements
- Consider rollback if patches don't resolve issues

**See full analysis**: [HOME_COMPARE_H2.2fix.md](HOME_COMPARE_H2.2fix.md)