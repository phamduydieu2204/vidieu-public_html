# Font Cleanup Documentation

**Date**: 2025-09-07
**Version**: 1.5.0
**Purpose**: Fix font-related 404 errors and remove unused preloads

## Overview

This cleanup addresses three main issues:
1. **404 Error**: `/wp-content/uploads/elementor/google-fonts/css/jost.css`
2. **Unused Preloads**: Resources preloaded but never used
3. **Font Consistency**: Ensure consistent font display across the site

## Issues Fixed

### 1. Elementor Jost Font 404

**Problem**: Elementor tries to load `jost.css` which doesn't exist  
**Solution**: Block all attempts to load this file through multiple layers:
- `style_loader_src` filter to block the URL
- Late dequeue/deregister (priority 999)
- Remove from resource hints
- Disable Elementor Google fonts completely

### 2. Unused Preloads

**Problem**: These resources were preloaded but never actually used:
- `/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2`
- `/wp-content/themes/elessi-theme/style.min.css`

**Solution**: 
- Remove preload tags via output buffer
- Remove action hooks that add preloads
- Clean up both parent and child theme preloads

### 3. Font Fallback

**Problem**: Removing custom fonts could break UI
**Solution**: Implement safe system font stack that maintains layout

## Implementation

### Plugin Module: `class-vidieu-font-cleanup.php`

Located at: `/wp-content/plugins/vidieu-home-sections/inc/perf/class-vidieu-font-cleanup.php`

**Key Functions**:
1. `block_jost_css()` - Blocks jost.css via style_loader_src
2. `dequeue_jost_css()` - Removes from registered styles
3. `remove_jost_hints()` - Cleans resource hints
4. `clean_unused_preloads()` - Output buffer to remove preload tags
5. `enqueue_font_fallback()` - Adds system font CSS

### Child Theme Modifications

Added to `/wp-content/themes/elessi-theme-child/functions.php`:
- `elessi_child_remove_font_preloads()` - Removes parent theme preloads
- `elessi_child_remove_v3_preloads()` - Removes V3 performance preloads
- `elessi_child_ob_cleanup_preloads()` - Final cleanup via output buffer

### Font Fallback CSS

Created: `/wp-content/plugins/vidieu-home-sections/assets/css/font-fallback.css`

Uses system font stack:
```css
--vd-font-sans: system-ui, -apple-system, "Segoe UI", Roboto, 
                "Helvetica Neue", Arial, "Noto Sans", 
                "Liberation Sans", sans-serif;
```

## Hooks & Filters Used

### Blocking Hooks
- `style_loader_src` (priority 10) - Block style loading
- `wp_enqueue_scripts` (priority 999) - Late dequeue
- `wp_resource_hints` (priority 10) - Remove hints
- `elementor/frontend/print_google_fonts` - Disable Elementor fonts

### Cleanup Hooks
- `template_redirect` (priority 1) - Start output buffer
- `init` (priority 1-2) - Remove action hooks

## Testing

### Before Fix
- Network tab shows 404 for jost.css
- Console warnings about unused preloads
- Lighthouse flags preload issues

### After Fix
- No 404 errors for jost.css
- No preload warnings in console
- Clean network requests
- Consistent font display

## Elementor Configuration

1. Go to **Elementor → Settings → Advanced**
2. Enable "Disable Default Fonts" 
3. Go to **Elementor → Tools**
4. Click "Regenerate Files & Data"

## Cache Cleanup

After implementing:
1. Clear WordPress cache
2. Clear Cloudflare cache
3. Clear Autoptimize cache
4. Clear browser cache

## Performance Impact

- **Positive**: Fewer HTTP requests (no 404s)
- **Positive**: No unused preloads
- **Neutral**: System fonts load instantly (no web font downloads)

## Rollback

To revert changes:
1. Remove font cleanup module inclusion from `vidieu-home-sections.php`
2. Remove font cleanup functions from child theme `functions.php`
3. Delete `/assets/css/font-fallback.css`
4. Re-enable Elementor default fonts

## Known Limitations

- System fonts may look slightly different than custom fonts
- Font weights/sizes preserved but font face changes
- Some icons fonts (FontAwesome, Pe-icon) still preload if actually used

## Future Considerations

1. Consider implementing proper web font loading if custom fonts needed
2. Use `font-display: swap` for better performance
3. Subset fonts for Vietnamese characters only
4. Consider variable fonts for weight variations